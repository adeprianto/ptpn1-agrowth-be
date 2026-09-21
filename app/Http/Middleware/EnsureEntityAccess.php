<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEntityAccess
{
    /**
     * @param string $routeParam nama parameter route yang bind ke model Entities, misal 'entity', 'regional', atau 'unit'
     */
    public function handle(Request $request, Closure $next, string $routeParam = 'entity'): Response
    {
        $entity = $request->route($routeParam);

        if (! $entity) {
            return $next($request);
        }

        $accessibleIds = $request->user()->accessibleEntityIds();

        if (! in_array($entity->id, $accessibleIds, true)) {
            abort(403, 'Anda tidak memiliki akses ke data ini.');
        }

        return $next($request);
    }
}