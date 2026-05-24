<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Group;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $groupIds = auth()->user()->isAdmin()
                ? Group::pluck('id')
                : auth()->user()->groups()->pluck('id');

            $query = Member::with('group')->whereIn('group_id', $groupIds);
            if ($request->group_id) $query->where('group_id', $request->group_id);

            return DataTables::of($query)
                ->addColumn('group_name', fn($m) => $m->group->name)
                ->addColumn('status_badge', fn($m) => $m->status === 'active'
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-danger">Inactivo</span>')
                ->addColumn('pending_fines', fn($m) => $m->pendingFines()->count())
                ->addColumn('actions', fn($m) => view('members._actions', ['member' => $m])->render())
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        $groups = auth()->user()->isAdmin() ? Group::all() : auth()->user()->groups;
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'group_id'        => 'required|exists:groups,id',
            'full_name'       => 'required|string|max:255',
            'document_number' => 'nullable|string|max:20',
            'phone'           => 'nullable|string|max:20',
            'address'         => 'nullable|string',
            'join_date'       => 'required|date',
            'status'          => 'required|in:active,inactive',
        ]);

        $member = Member::create($data);
        return redirect()->route('members.show', $member)
            ->with('success', 'Miembro registrado exitosamente.');
    }

    public function show(Member $member)
    {
        $this->authorize('view', $member);
        $member->load(['group', 'contributions.meeting', 'loans', 'fines']);

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
        $groups = auth()->user()->isAdmin() ? Group::all() : auth()->user()->groups;
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
            'status'          => 'required|in:active,inactive',
        ]);
        try {
            $member->update($data);
            return redirect()->route('members.show', $member)->with('success', 'Miembro actualizado.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
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
