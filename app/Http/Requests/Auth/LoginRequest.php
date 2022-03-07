<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'login_field' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

         // Obtener el rol prisionero
        // $prisioner_role = Role::where('name', 'prisoner')->first();


         // Obtener todos los emails de los  usuarios que sean prisioneros
         //$prisioners = $prisioner_role->users()->pluck('email');

         // Obtener todos los nicks de los  usuarios que sean prisioneros
         //$prisioners_username = $prisioner_role->users()->pluck('username');

         

         //dd($prisioners->all());
         //dd($this->input('login_field'));
         //dd(in_array($this->input('login_field'), $prisioners->all()));


        $email_exist = Auth::attempt(['email' => $this->input('login_field'), 'password' => $this->input('password')], $this->boolean('remember'));


        $username_exist = Auth::attempt(['username' => $this->input('login_field'), 'password' => $this->input('password')], $this->boolean('remember'));

        //dd(!$email_exist || !$username_exist || in_array($this->input('login_field'), $prisioners->all()));


        if (!$email_exist  &&  !$username_exist)
        {
            
           
            //if(in_array($this->input('login_field'), $prisioners->all()) || in_array($this->input('login_field'), $prisioners_username->all())){
              //  return abort(403, 'This action is unauthorized.');
            //}
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);

        }
        if (auth()->user()->role_id == "4") {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => __('auth.not_allowed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited()
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     *
     * @return string
     */
    public function throttleKey()
    {
        return Str::lower($this->input('email')).'|'.$this->ip();
    }
}
