<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Group;
use App\Models\Member;
use App\Models\MeetingContribution;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class MeetingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $groupIds = auth()->user()->isAdmin()
                ? Group::pluck('id')
                : auth()->user()->groups()->pluck('id');

            $query = Meeting::with('group')->whereIn('group_id', $groupIds);

            if ($request->group_id) $query->where('group_id', $request->group_id);
            if ($request->month)    $query->where('month', $request->month);
            if ($request->status)   $query->where('status', $request->status);

            return DataTables::of($query)
                ->addColumn('status_badge', fn($m) => $m->status === 'open'
                    ? '<span class="badge bg-success">Abierta</span>'
                    : '<span class="badge bg-secondary">Cerrada</span>')
                ->addColumn('total_contributions', fn($m) => number_format($m->contributions()->sum('total'), 2))
                ->addColumn('actions', fn($m) => view('meetings._actions', ['meeting' => $m])->render())
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        $groups = auth()->user()->isAdmin()
            ? Group::all()
            : auth()->user()->groups;

        return view('meetings.index', compact('groups'));
    }

    public function create(Request $request)
    {
        $groups = auth()->user()->isAdmin()
            ? Group::where('status', 'active')->get()
            : auth()->user()->groups()->where('status', 'active')->get();

        $selectedGroup = $request->group_id ? Group::find($request->group_id) : null;
        return view('meetings.create', compact('groups', 'selectedGroup'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group_id'       => 'required|exists:groups,id',
            'meeting_number' => 'required|integer|min:1',
            'meeting_date'   => 'required|date',
            'month'          => 'required|string|max:20',
            'observations'   => 'nullable|string',
            'status'         => 'required|in:open,closed',
        ]);

        $exists = Meeting::where('group_id', $data['group_id'])
            ->where('meeting_number', $data['meeting_number'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['meeting_number' => 'Ya existe una reunión con ese número en este grupo.'])->withInput();
        }

        $meeting = Meeting::create($data);

        $members = Member::where('group_id', $data['group_id'])->where('status', 'active')->get();

        foreach ($members as $member) {
            Attendance::create(['meeting_id' => $meeting->id, 'member_id' => $member->id]);
            MeetingContribution::create([
                'meeting_id'     => $meeting->id,
                'member_id'      => $member->id,
                'shares'         => 0,
                'emergency_fund' => 0,
                'fine'           => 0,
                'confirmed'      => false,
            ]);
        }

        $meeting->recalculateSummary();

        return redirect()->route('meetings.show', $meeting)
            ->with('success', 'Reunión creada exitosamente.');
    }

    public function show(Meeting $meeting)
    {
        $this->authorize('view', $meeting);
        $meeting->load(['group', 'contributions.member', 'attendances.member', 'loans.member', 'bankExpenses', 'summary']);
        return view('meetings.show', compact('meeting'));
    }

    public function edit(Meeting $meeting)
    {
        $this->authorize('update', $meeting);
        if ($meeting->isClosed()) {
            return back()->with('error', 'No se puede editar una reunión cerrada.');
        }
        $groups = auth()->user()->isAdmin() ? Group::all() : auth()->user()->groups;
        return view('meetings.edit', compact('meeting', 'groups'));
    }

    public function update(Request $request, Meeting $meeting)
    {
        $this->authorize('update', $meeting);
        if ($meeting->isClosed()) {
            return back()->with('error', 'No se puede editar una reunión cerrada.');
        }

        $data = $request->validate([
            'meeting_date' => 'required|date',
            'month'        => 'required|string|max:20',
            'observations' => 'nullable|string',
            'status'       => 'required|in:open,closed',
        ]);

        $meeting->update($data);
        return redirect()->route('meetings.show', $meeting)->with('success', 'Reunión actualizada.');
    }

    public function destroy(Meeting $meeting)
    {
        $this->authorize('delete', $meeting);
        try {
            $meeting->delete();
            return redirect()->route('meetings.index')->with('success', 'Reunión eliminada.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function close(Meeting $meeting)
    {
        $this->authorize('update', $meeting);
        $meeting->update(['status' => 'closed']);
        $meeting->recalculateSummary();
        return back()->with('success', 'Reunión cerrada exitosamente.');
    }

    public function reopen(Meeting $meeting)
    {
        $this->authorize('update', $meeting);
        $meeting->update(['status' => 'open']);
        return back()->with('success', 'Reunión reabierta.');
    }
}
