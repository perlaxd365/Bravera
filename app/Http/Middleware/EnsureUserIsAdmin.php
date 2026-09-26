<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Permite el acceso al panel únicamente a usuarios
     * con roles administrativos (excluye Cliente).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->hasAnyRole([
            'Super Admin',
            'Administrador',
            'Operador',
            'Marketing',
            'Atención al Cliente',
        ])) {
            abort(403, 'No tienes permisos para acceder al panel administrativo.');
        }

        return $next($request);
    }
}
