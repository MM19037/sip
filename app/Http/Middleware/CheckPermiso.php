<?php

namespace App\Http\Middleware;

use App\Models\PermisoRol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermiso
{
    public function handle(Request $request, Closure $next, string $seccion): Response
    {
        $user = $request->user();

        if ($user->esAdministrador()) {
            return $next($request);
        }

        if (! PermisoRol::tiene($user->rol, $seccion)) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
