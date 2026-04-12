<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
            ], 401);
        }

        if (! in_array($user->role, $roles, true)) {
            $institutionAliasMatch = in_array('institution', $roles, true)
                && in_array($user->role, ['manager', 'employee'], true);

            if ($institutionAliasMatch) {
                return $next($request);
            }

            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
