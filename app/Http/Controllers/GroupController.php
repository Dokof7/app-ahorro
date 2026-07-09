<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = auth()->user()->isAdmin()
                ? Group::with('user')
                : auth()->user()->groups()->with('user');

            return DataTables::of($query)
                ->addColumn('members_count', fn($g) => $g->members()->count())
                ->addColumn('meetings_count', fn($g) => $g->meetings()->count())
                ->addColumn('status_badge', fn($g) => $g->status === 'active'
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-danger">Inactivo</span>')
                ->addColumn('actions', fn($g) => view('groups._actions', ['group' => $g])->render())
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }
        return view('groups.index');
    }

    public function create() { return view('groups.create'); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string',
            'start_date'        => 'required|date',
            'status'            => 'required|in:active,inactive',
            'share_value'       => 'required|numeric|min:0.01',
            'default_shares'    => 'nullable|integer|min:1|max:25',
            'default_emergency' => 'nullable|numeric|min:0',
            'membership_fee'    => 'nullable|numeric|min:0',
            'registration_mode' => 'required|in:full,partial',
        ]);

        $data['default_shares'] = $data['default_shares'] ?? null;
        $data['membership_fee'] = $data['membership_fee'] ?? 0;
        $data['user_id'] = auth()->id();
        $group = Group::create($data);

        return redirect()->route('groups.show', $group)
            ->with('success', 'Grupo creado exitosamente.');
    }

    public function show(Group $group)
    {
        $this->authorize('view', $group);
        $group->load(['members', 'meetings.summary']);
        $stats = [
            'total_members'      => $group->members()->count(),
            'total_meetings'     => $group->meetings()->count(),
            'total_savings'      => $group->meetings()->with(['contributions', 'group', 'totals'])->get()->sum('total_savings'),
            'pending_loans'      => $group->loans()->where('status', 'pending')->count(),
            'membership_paid'    => $group->members()->where('membership_paid', true)->count(),
        ];
        return view('groups.show', compact('group', 'stats'));
    }

    public function edit(Group $group)
    {
        $this->authorize('update', $group);
        $hasMeetings = $group->meetings()->exists();
        return view('groups.edit', compact('group', 'hasMeetings'));
    }

    public function update(Request $request, Group $group)
    {
        $this->authorize('update', $group);
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string',
            'start_date'        => 'required|date',
            'status'            => 'required|in:active,inactive',
            'share_value'       => 'required|numeric|min:0.01',
            'default_shares'    => 'nullable|integer|min:1|max:25',
            'default_emergency' => 'nullable|numeric|min:0',
            'membership_fee'    => 'nullable|numeric|min:0',
            'registration_mode' => 'sometimes|in:full,partial',
        ]);
        $data['default_shares'] = $data['default_shares'] ?? null;
        $data['membership_fee'] = $data['membership_fee'] ?? 0;
        try {
            $group->update($data);
            return redirect()->route('groups.show', $group)->with('success', 'Grupo actualizado.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Group $group)
    {
        $this->authorize('delete', $group);
        try {
            $group->delete();
            return redirect()->route('groups.index')->with('success', 'Grupo eliminado.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
