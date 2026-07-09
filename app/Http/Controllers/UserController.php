<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of(User::query())
                ->addColumn('role_badge', function ($u) {
                    $color = $u->role_color;
                    return '<span class="badge bg-' . $color . '">' . $u->role_label . '</span>';
                })
                ->addColumn('status_badge', fn($u) => $u->is_active
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-secondary">Inactivo</span>')
                ->addColumn('groups_count', fn($u) => $u->groups()->count())
                ->addColumn('actions', fn($u) => view('users._actions', ['user' => $u])->render())
                ->rawColumns(['role_badge', 'status_badge', 'actions'])
                ->make(true);
        }
        return view('users.index');
    }

    public function create()
    {
        $groups = Group::orderBy('name')->get();
        return view('users.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role'     => 'required|in:' . implode(',', array_keys(User::ROLES)),
            'phone'    => 'nullable|string',
            'groups'   => 'nullable|array',
            'groups.*' => 'exists:groups,id',
        ]);

        $role = $data['role'];
        $groupIds = $data['groups'] ?? [];
        unset($data['role'], $data['groups']);
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        $user->assignRole($role);
        $user->groups()->sync($groupIds);

        return redirect()->route('users.index')->with('success', 'Usuario creado.');
    }

    public function edit(User $user)
    {
        $groups = Group::orderBy('name')->get();
        return view('users.edit', compact('user', 'groups'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'role'      => 'required|in:' . implode(',', array_keys(User::ROLES)),
            'is_active' => 'boolean',
            'phone'     => 'nullable|string',
            'groups'    => 'nullable|array',
            'groups.*'  => 'exists:groups,id',
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:8|confirmed']);
            $data['password'] = Hash::make($request->password);
        }

        $role = $data['role'];
        $groupIds = $data['groups'] ?? [];
        unset($data['role'], $data['groups']);

        $user->update($data);
        $user->syncRoles([$role]);
        $user->groups()->sync($groupIds);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Usuario eliminado.');
    }
}
