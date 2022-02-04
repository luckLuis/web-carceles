<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */


    // Creación de un middleware para verificar que el usuario tenga el rol respectivo
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->route('user');

        if (!$user->hasRole($role))
        {
            return abort(403, 'This action is unauthorized.');
        }
        return $next($request);
    }




}