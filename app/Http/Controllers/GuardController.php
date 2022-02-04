<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Notifications\RegisteredUserNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class GuardController extends Controller
{
    public function __construct()
    {
        // https://styde.net/laravel-6-doc-autorizacion/#authorizing-actions-using-policies
        // https://laravel.com/docs/8.x/authorization#via-middleware
        $this->middleware('can:manage-guards');
        
        $this->middleware('active.user')->only('edit', 'update');
        
        $this->middleware('verify.user.role:guard')->except('index', 'create', 'store');
    }
    // Función para mostrar la vista principal de todo los guardias
    public function index()
    {
        // Traer el rol guardia
        $guard_role = Role::where('name', 'guard')->first();
        // Obtener todos los usuarios que sean guardias
        $guards = $guard_role->users();

        if (request('search'))
        {
            // https://laravel.com/docs/8.x/queries#basic-where-clauses
            $guards = $guards->where('username', 'like', '%' . request('search') . '%');
        }

        $guards = $guards->orderBy('first_name', 'asc')
            ->orderBy('last_name', 'asc')
            ->paginate();

        // Mandar a la vista los usuarios que sean directores
        return view('guard.index', compact('guards'));
    }

    // Función para mostrar la vista del formulario
    public function create()
    {
        return view('guard.create');
    }




    // Función para tomar los datos del formulario y guardar en la BDD
    public function store(Request $request)
    {
        // Validación de datos respectivos
        $request->validate([
            'first_name' => ['required', 'string', 'min:3', 'max:35'],
            'last_name' => ['required', 'string', 'min:3', 'max:35'],
            'username' => ['required', 'string', 'min:5', 'max:20', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'birthdate' => [
                'required', 'string', 'date_format:d/m/Y',
                'after_or_equal:' . date('Y-m-d', strtotime('-70 years')),
                'before_or_equal:' . date('Y-m-d', strtotime('-18 years')),
            ],
            'personal_phone' => ['required', 'numeric', 'digits:10'],
            'home_phone' => ['required', 'numeric', 'digits:9'],
            'address' => ['required', 'string', 'min:5', 'max:50']
        ]);

        // Invocar a la función para generar una contraseña
        $password_generated = $this->generatePassword();
        // Traer el rol guardia
        $guard_role = Role::where('name', 'guard')->first();
        // Guardar en la BDD los datos por medio de ELOQUENT y su relación
        $guard = $guard_role->users()->create([
            'first_name' => $request['first_name'],
            'last_name' => $request['last_name'],
            'username' => $request['username'],
            'email' => $request['email'],
            'birthdate' => $this->changeDateFormat($request['birthdate']),
            'personal_phone' => $request['personal_phone'],
            'home_phone' => $request['home_phone'],
            'address' => $request['address'],
            'password' => Hash::make($password_generated),
        ]);

        // Se crear el avatar y se almacena en la BDD por medio de ELOQUENT y su relación
        $guard->image()->create(['path' => $guard->generateAvatarUrl()]);

        // Se procede a enviar una notificación al correo
        $guard->notify(
            new RegisteredUserNotification(
                $guard->getFullName(),
                $guard_role->name,
                $password_generated
            )
        );

        // Se imprime el mensaje de exito
        return back()->with('status', 'Guard created successfully');
    }

    // Función para mostrar la vista y los datos de un solo guardia
    public function show(User $user)
    {
        return view('guard.show', ['guard' => $user]);
    }



    // Función para mostrar la vista y los datos de un solo guardia a través de un formulario
    public function edit(User $user)
    {
        return view('guard.update', ['guard' => $user]);
    }



    // Función para tomar los datos del formulario y actualizar en la BDD
    public function update(Request $request, User $user)
    {
        // Obtener el model del usuario
        $userRequest = $request->user;

        // Validación de datos respectivos
        $request->validate([
            'first_name' => ['required', 'string', 'min:3', 'max:35'],
            'last_name' => ['required', 'string', 'min:3', 'max:35'],
            'username' => [
                'required', 'string', 'min:5', 'max:20',
                // Ignorar el username del usuario
                Rule::unique('users')->ignore($userRequest),
            ],
            'email' => [
                'required', 'string', 'email', 'max:255',
                // Ignorar el email del usuario
                Rule::unique('users')->ignore($userRequest),
            ],
            'birthdate' => [
                'nullable', 'string', 'date_format:d/m/Y',
                'after_or_equal:' . date('Y-m-d', strtotime('-70 years')),
                'before_or_equal:' . date('Y-m-d', strtotime('-18 years')),
            ],
            'personal_phone' => ['required', 'numeric', 'digits:10'],
            'home_phone' => ['required', 'numeric', 'digits:9'],
            'address' => ['required', 'string', 'min:5', 'max:50'],
        ]);

        // Se obtiene el email antiguo del usuario
        $old_email = $user->email;

        // Se obtiene el modelo del usuario
        $guard = $user;

        // Se procede con la actualización de los datos por medio de Eloquent
        $guard->update([
            'first_name' => $request['first_name'],
            'last_name' => $request['last_name'],
            'username' => $request['username'],
            'email' => $request['email'],
            'birthdate' => $this->changeDateFormat($request['birthdate']),
            'personal_phone' => $request['personal_phone'],
            'home_phone' => $request['home_phone'],
            'address' => $request['address'],
        ]);

        // Se procede con la actualización del avatar del usuario
        $guard->updateUIAvatar($guard->generateAvatarUrl());

        // Función para verificar si el usuario cambio el email
        $this->verifyEmailChange($guard, $old_email);

        // Se imprime el mensaje de exito
        return back()->with('status', 'Guard updated successfully');
    }

    // Función para dar de baja a un director en la BDD
    public function destroy(User $user)
    {
        // Tomar el modelo del usuario
        $guard = $user;
        // Tomar el estado del guardia
        $state = $guard->state;
        // Almacenar un mensaje para el estado
        $message = $state ? 'inactivated' : 'activated';
        // Cambiar el estado del usuario
        $guard->state = !$state;
        // Guardar los cambios
        $guard->save();
        // Se imprime el mensaje de exito
        return back()->with('status', "Guard $message successfully");
    }


    // Función para generar una contraseña
    public function generatePassword(): string
    {
        $characters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*?";
        $length = 8;
        $count = mb_strlen($characters);
        for ($i = 0, $result = ''; $i < $length; $i++) {
            $index = rand(0, $count - 1);
            $result .= mb_substr($characters, $index, 1);
        }
        return $result;
    }

    // Cambiar el formato para almacenar en la BDD
    public function changeDateFormat(string $date, string $date_format = 'd/m/Y', string $expected_format = 'Y-m-d')
    {
        return Carbon::createFromFormat($date_format, $date)->format($expected_format);
    }

    // Función para verificar si el usuario actualizo el email
    private function verifyEmailChange(User $guard, string $old_email)
    {
        if ($guard->email !== $old_email)
        {
            // Se invoca a la función respectiva para crear un nuevo password
            $password_generated = $this->generatePassword();
            // Se realiza el proceso de encriptar el password
            $guard->password = Hash::make($password_generated);
            //  Se guarda en la BDD
            $guard->save();
            // Se procede a notificar al usuario con su nuevo password
            $guard->notify(
                new RegisteredUserNotification(
                    $guard->getFullName(),
                    $guard->role->name,
                    $password_generated
                )
            );
        }
    }


}