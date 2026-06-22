<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGroupSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->isAdmin() && !session('active_group_id')) {
            return redirect()->route('group.selector');
        }

        return $next($request);
    }
}
