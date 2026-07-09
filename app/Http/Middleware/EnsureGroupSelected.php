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

        if ($user && $user->isAdminGrupo()) {
            $myGroups = $user->groups()->get(['groups.id', 'groups.name']);

            $activeGroupId = session('active_group_id');
            if ($activeGroupId && !$myGroups->contains('id', $activeGroupId)) {
                // Stale session: the group was unassigned from this user (or never was theirs).
                session()->forget(['active_group_id', 'active_group_name']);
                $activeGroupId = null;
            }

            if (!$activeGroupId) {
                if ($myGroups->count() === 1) {
                    $onlyGroup = $myGroups->first();
                    session(['active_group_id' => $onlyGroup->id, 'active_group_name' => $onlyGroup->name]);
                } elseif ($myGroups->count() > 1) {
                    return redirect()->route('group.selector');
                }
                // 0 groups: pass through unchanged.
            }
        }

        return $next($request);
    }
}
