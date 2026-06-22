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
        $groups = Group::where('status', 'active')
            ->when($request->q, fn($q, $search) => $q->where('name', 'like', "%$search%"))
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'description']);

        return response()->json($groups);
    }

    public function select(Request $request)
    {
        $request->validate(['group_id' => 'required|exists:groups,id']);

        $group = Group::where('id', $request->group_id)
            ->where('status', 'active')
            ->firstOrFail();

        session(['active_group_id' => $group->id, 'active_group_name' => $group->name]);

        return redirect()->route('dashboard');
    }

    public function clear()
    {
        session()->forget(['active_group_id', 'active_group_name']);
        return redirect()->route('group.selector');
    }
}
