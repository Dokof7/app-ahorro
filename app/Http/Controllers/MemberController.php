<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Group;
use App\Models\Meeting;
use App\Models\Attendance;
use App\Models\MeetingContribution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\DataTables;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $groupIds = auth()->user()->isAdmin()
                ? Group::pluck('id')
                : auth()->user()->groups()->pluck('groups.id');

            $query = Member::with('group')->whereIn('group_id', $groupIds);
            if ($request->group_id) $query->where('group_id', $request->group_id);
            if ($request->cycle) $query->where('cycle', $request->cycle);

            return DataTables::of($query)
                ->addColumn('group_name', fn($m) => $m->group->name)
                ->addColumn('cycle_badge', fn($m) => '<span class="badge bg-info">Ciclo ' . $m->cycle . '</span>')
                ->addColumn('status_badge', fn($m) => $m->status === 'active'
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-danger">Inactivo</span>')
                ->addColumn('pending_fines', fn($m) => $m->pendingFines()->count())
                ->addColumn('actions', fn($m) => view('members._actions', ['member' => $m])->render())
                ->rawColumns(['cycle_badge', 'status_badge', 'actions'])
                ->make(true);
        }

        $groups = auth()->user()->isAdmin() ? Group::all() : auth()->user()->groups()->get();
        return view('members.index', compact('groups'));
    }

    public function create()
    {
        $groups = auth()->user()->isAdmin()
            ? Group::where('status', 'active')->get()
            : auth()->user()->groups()->where('status', 'active')->get();
        return view('members.create', compact('groups'));
    }

    public function createForGroup(Group $group)
    {
        $groups = collect([$group]);
        return view('members.create', ['groups' => $groups, 'selectedGroup' => $group]);
    }

    public function searchUsers(Request $request)
    {
        $q = $request->get('q', '');
        $users = User::whereNull('deleted_at')
            ->whereDoesntHave('member')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
            })
            ->select('id', 'name', 'email')
            ->limit(10)
            ->get()
            ->map(fn($u) => ['id' => $u->id, 'text' => "{$u->name} ({$u->email})"]);

        return response()->json(['results' => $users]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group_id'        => 'required|exists:groups,id',
            'full_name'       => 'required|string|max:255',
            'document_number' => 'nullable|string|max:20',
            'phone'           => 'nullable|string|max:20',
            'address'         => 'nullable|string',
            'join_date'       => 'required|date',
            'cycle'           => 'required|integer|min:1|max:99',
            'status'          => 'required|in:active,inactive',
            'user_id'         => 'nullable|exists:users,id',
        ]);

        if (!empty($data['user_id'])) {
            $user = User::findOrFail($data['user_id']);
            if ($user->member()->exists()) {
                return back()->withErrors(['user_id' => 'Ese usuario ya está vinculado a otro miembro.'])->withInput();
            }
        }

        $member = Member::create($data);

        // Agregar al miembro a todas las reuniones abiertas del grupo
        Meeting::where('group_id', $data['group_id'])
            ->where('status', 'open')
            ->each(function ($meeting) use ($member) {
                Attendance::firstOrCreate([
                    'meeting_id' => $meeting->id,
                    'member_id'  => $member->id,
                ]);
                MeetingContribution::firstOrCreate(
                    ['meeting_id' => $meeting->id, 'member_id' => $member->id],
                    ['shares' => 0, 'emergency_fund' => 0, 'fine' => 0, 'confirmed' => false]
                );
            });

        return redirect()->route('members.show', $member)
            ->with('success', 'Miembro registrado exitosamente.');
    }

    public function show(Member $member)
    {
        $this->authorize('view', $member);
        $member->load(['group', 'contributions.meeting', 'loans', 'fines', 'user']);

        $stats = [
            'total_savings'   => $member->total_savings,
            'total_emergency' => $member->total_emergency,
            'total_fines'     => $member->fines()->sum('amount'),
            'pending_loans'   => $member->loans()->where('status', '!=', 'paid')->count(),
        ];

        return view('members.show', compact('member', 'stats'));
    }

    public function edit(Member $member)
    {
        $this->authorize('update', $member);
        $groups = auth()->user()->isAdmin() ? Group::all() : auth()->user()->groups()->get();
        return view('members.edit', compact('member', 'groups'));
    }

    public function update(Request $request, Member $member)
    {
        $this->authorize('update', $member);
        $data = $request->validate([
            'full_name'       => 'required|string|max:255',
            'document_number' => 'nullable|string|max:20',
            'phone'           => 'nullable|string|max:20',
            'address'         => 'nullable|string',
            'join_date'       => 'required|date',
            'cycle'           => 'required|integer|min:1|max:99',
            'status'          => 'required|in:active,inactive',
        ]);
        try {
            $member->update($data);
            return redirect()->route('members.show', $member)->with('success', 'Miembro actualizado.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function markMembershipPaid(Member $member)
    {
        $this->authorize('update', $member);

        if ($member->membership_paid) {
            return back()->with('error', 'Este miembro ya tiene su membresía registrada como pagada.');
        }

        $member->update([
            'membership_paid'    => true,
            'membership_paid_at' => now(),
        ]);

        return back()->with('success', 'Membresía de ' . $member->full_name . ' marcada como pagada.');
    }

    public function linkUser(Request $request, Member $member)
    {
        $this->authorize('update', $member);

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->member()->exists()) {
            return back()->with('error', 'Ese usuario ya está vinculado a otro miembro.');
        }

        $member->update(['user_id' => $user->id]);

        if (!$user->hasRole('miembro')) {
            $user->assignRole('miembro');
        }

        return back()->with('success', 'Usuario vinculado correctamente.');
    }

    public function createUser(Request $request, Member $member)
    {
        $this->authorize('update', $member);

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'is_active' => true,
        ]);

        $user->assignRole('miembro');
        $member->update(['user_id' => $user->id]);

        return back()->with('success', 'Cuenta creada y vinculada correctamente.');
    }

    public function unlinkUser(Member $member)
    {
        $this->authorize('update', $member);

        if (!$member->user_id) {
            return back()->with('error', 'Este miembro no tiene usuario vinculado.');
        }

        $member->update(['user_id' => null]);

        return back()->with('success', 'Usuario desvinculado.');
    }

    public function destroy(Member $member)
    {
        $this->authorize('delete', $member);
        try {
            $member->delete();
            return redirect()->route('members.index')->with('success', 'Miembro eliminado.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
