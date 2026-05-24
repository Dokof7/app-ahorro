<?php

namespace App\Http\Controllers;

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
                ->addColumn('role_badge', fn($u) => $u->isAdmin()
                    ? '<span class="badge bg-danger">Admin</span>'
                    : '<span class="badge bg-primary">Usuario</span>')
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

    public function create() { return view('users.create'); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role'     => 'required|in:admin,user',
            'phone'    => 'nullable|string',
        ]);
        $data['password'] = Hash::make($data['password']);
        User::create($data);
        return redirect()->route('users.index')->with('success', 'Usuario creado.');
    }

    public function edit(User $user) { return view('users.edit', compact('user')); }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'role'      => 'required|in:admin,user',
            'is_active' => 'boolean',
            'phone'     => 'nullable|string',
        ]);
        if ($request->filled('password')) {
            $request->validate(['password' => 'min:8|confirmed']);
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);
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
