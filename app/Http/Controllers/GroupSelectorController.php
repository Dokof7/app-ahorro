<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

class GroupSelectorController extends Controller
{
    public function index()
    {
        return view('group-selector.index');
    }

    public function search(Request $request)
    {
        $user = auth()->user();

        $query = Group::where('status', 'active')
            ->when($request->q, fn($q, $search) => $q->where('name', 'like', "%$search%"))
            ->orderBy('name')
            ->limit(15);

        if (!$user->isAdmin()) {
            $query->whereIn('id', $user->groups()->pluck('groups.id'));
        }

        return response()->json($query->get(['id', 'name', 'description']));
    }

    public function select(Request $request)
    {
        $request->validate(['group_id' => 'required|exists:groups,id']);

        $user = auth()->user();

        $group = Group::where('id', $request->group_id)
            ->where('status', 'active')
            ->firstOrFail();

        if (!$user->isAdmin() && !$user->groups()->where('groups.id', $group->id)->exists()) {
            abort(403, 'No tenés acceso a ese grupo.');
        }

        session(['active_group_id' => $group->id, 'active_group_name' => $group->name]);

        return redirect()->route('dashboard');
    }

    public function clear()
    {
        session()->forget(['active_group_id', 'active_group_name']);
        return redirect()->route('group.selector');
    }
}
