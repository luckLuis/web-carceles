<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ActiveUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */


    // Creación de un middleware para verificar que el usuario este activo
    public function handle(Request $request, Closure $next)
    {
        $user = $request->route('user');

        if (!$user->state)
        {
            return abort(403, 'This action is unauthorized.');
        }
        return $next($request);
    }
}