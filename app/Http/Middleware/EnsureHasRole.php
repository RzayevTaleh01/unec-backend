<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('role:reviewer') or 'role:editor_in_chief,section_editor' (any of).
 * Administrators are not implicitly allowed: a role-specific area needs that role.
 */
class EnsureHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && array_intersect($roles, $user->roles ?? []), 403, __('site.rbac.forbidden'));

        return $next($request);
    }
}
