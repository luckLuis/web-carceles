<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    //Funcion para realizar login
    public function login(LoginRequest $request)
    {
        //Ejecuta el metodo de la clase LoginRequest
        $request->authenticate();
        //Toma el usuario del request
        $user = $request->user();
        //Se crea un token
        $token = $user->createToken('token-name')->plainTextToken;
        //Se procede a realizar la respuesta
        return response([
            'user' => new UserResource($user),
            'token' => $token
        ], 201);
    }

    public function logout(Request $request): array
    {
        // https://laravel.com/docs/8.x/queries#delete-statements
        $request->user()->tokens()->delete();

        return [
            'message' => 'Logged out'
        ];
    }
}