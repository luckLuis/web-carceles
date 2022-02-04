<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PrisonerController extends Controller
{

    public function __construct()
    {
        $this->middleware('can:manage-prisoners');
        $this->middleware('active.user')->only('edit', 'update');

        $this->middleware('verify.user.role:prisoner')->except('index', 'create', 'store');

    }


      // Función para mostrar la vista principal de todo los prisioneros
      public function index()
      {
          // Obtener el rol prisionero
          $prisioner_role = Role::where('name', 'prisoner')->first();


          // Obtener todos los usuarios que sean prisioneros
          $prisioners = $prisioner_role->users();



          if (request('search'))
          {
              // https://laravel.com/docs/8.x/queries#basic-where-clauses
              $prisioners = $prisioners->where('username', 'like', '%' . request('search') . '%');
          }

          $prisoners = $prisioners->orderBy('first_name', 'asc')
          ->orderBy('last_name', 'asc')
          ->paginate(5);

          // Mandar a la vista los usuarios que sean prisioneros
          return view('prisoner.index', compact('prisoners'));
      }



      // Función para mostrar la vista del formulario
      public function create()
      {
          return view('prisoner.create');
      }




      // Función para tomar los datos del formulario y guardar en la BDD
      public function store(Request $request)
      {
          // Obtener el id del rol prisionero
          $prisoner_role = Role::where('name', 'prisoner')->first()->id;

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
              'address' => ['required', 'string', 'min:5', 'max:50'],
          ]);

          // Guardar en la BDD los datos por medio de ELOQUENT
          $prisoner = User::create([

              'role_id' => $prisoner_role,
              'first_name' => $request['first_name'],
              'last_name' => $request['last_name'],
              'username' => $request['username'],
              'email' => $request['email'],
              'birthdate' => $this->changeDateFormat($request['birthdate']),
              'personal_phone' => $request['personal_phone'],
              'home_phone' => $request['home_phone'],
              'address' => $request['address'],
              'password' => Hash::make('secret123'),
          ]);

          // Se crear el avatar y se almacena en la BDD por medio de ELOQUENT y su relación
          $prisoner->image()->create(['path' => $prisoner->generateAvatarUrl()]);

          // Se imprime el mensaje de exito
          return redirect()->route('prisoner.index')->with('status', 'Prisoner created successfully');

      }




      // Función para mostrar la vista y los datos de un solo prisionero
      public function show(User $user)
      {
          return view('prisoner.show', ['prisoner' => $user]);
      }




      // Función para mostrar la vista y los datos de un solo prisionero a través de un formulario
      public function edit(User $user)
      {
          return view('prisoner.update', ['prisoner' => $user]);
      }

      // Función para tomar los datos del formulario y actualizar en la BDD
      public function update( Request $request, User $user)
      {

       // Obtener el model del usuario
       $userRequest = $request->user;

      // Validación de datos respectivos
       $request->validate([
           'first_name' => ['required', 'string', 'min:3', 'max:35'],
           'last_name' => ['required', 'string', 'min:3', 'max:35'],
           'username' => ['required', 'string', 'min:5', 'max:20',
              // Ignorar el username del usuario
               Rule::unique('users')->ignore($userRequest),
           ],
           'email' => ['required', 'string', 'email', 'max:255',
              // Ignorar el email del usuario
               Rule::unique('users')->ignore($userRequest),
           ],
           'birthdate' => ['nullable', 'string', 'date_format:d/m/Y',
               'after_or_equal:' . date('Y-m-d', strtotime('-70 years')),
               'before_or_equal:' . date('Y-m-d', strtotime('-18 years')),
           ],
           'personal_phone' => ['required', 'numeric', 'digits:10'],
           'home_phone' => ['required', 'numeric', 'digits:9'],
           'address' => ['required', 'string', 'min:5', 'max:50'],
       ]);

      // Se obtiene el modelo del usuario
       $prisoner = $user;
      // Se procede con la actualización de los datos por medio de Eloquent
       $prisoner->update([
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
       $prisoner->updateUIAvatar($prisoner->generateAvatarUrl());


       // Se imprime el mensaje de exito
       return back()->with('status', 'Prisoner updated successfully');
      }


      // Función para dar de baja a un prisionero en la BDD
      public function destroy(User $user)
      {
          // Tomar el modelo del usuario
          $prisoner = $user;
          // Tomar el estado del director
          $state = $prisoner->state;
          // Almacenar un mensaje para el estado
          $message = $state ? 'inactivated' : 'activated';
          // Cambiar el estado del usuario
          $prisoner->state = !$state;
          // Guardar los cambios
          $prisoner->save();
          // Se imprime el mensaje de exito
          return back()->with('status', "Prisoner $message successfully");
      }


      // Cambiar el formato para almacenar en la BDD
      public function changeDateFormat(string $date, string $date_format='d/m/Y', string $expected_format = 'Y-m-d')
      {
          return Carbon::createFromFormat($date_format, $date)->format($expected_format);
      }


}