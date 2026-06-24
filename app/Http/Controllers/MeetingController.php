<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Group;
use App\Models\Member;
use App\Models\MeetingContribution;
use App\Models\Attendance;
use App\Models\MeetingScheduledDate;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class MeetingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $groupIds = auth()->user()->isAdmin()
                ? Group::pluck('id')
                : auth()->user()->groups()->pluck('groups.id');

            $query = Meeting::with('group')->whereIn('group_id', $groupIds);

            if ($request->group_id) $query->where('group_id', $request->group_id);
            if ($request->month)    $query->where('month', $request->month);
            if ($request->status)   $query->where('status', $request->status);

            return DataTables::of($query)
                ->addColumn('meeting_date', fn($m) => $m->meeting_date->format('d/m/Y'))
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
            : auth()->user()->groups()->get();

        return view('meetings.index', compact('groups'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();

        $groups = $user->isAdmin()
            ? Group::where('status', 'active')->get()
            : $user->groups()->where('status', 'active')->get();

        if ($request->group_id) {
            $selectedGroup = Group::find($request->group_id);
        } elseif ($user->isAdmin() && session('active_group_id')) {
            $selectedGroup = Group::find(session('active_group_id'));
        } else {
            $selectedGroup = $groups->first();
        }

        return view('meetings.create', compact('groups', 'selectedGroup'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group_id'     => 'required|exists:groups,id',
            'meeting_date' => 'required|date',
            'month'        => 'required|string|max:20',
            'observations' => 'nullable|string',
            'status'       => 'required|in:open,closed',
        ]);

        $data['meeting_number'] = Meeting::where('group_id', $data['group_id'])->max('meeting_number') + 1;

        $meeting = Meeting::create($data);

        // Mark the scheduled date as used if it matches
        MeetingScheduledDate::where('group_id', $data['group_id'])
            ->where('scheduled_date', $data['meeting_date'])
            ->update(['used' => true]);

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

        // Sincronizar miembros activos que no tienen registro en esta reunión
        $existingMemberIds = $meeting->attendances()->pluck('member_id');
        $missingMembers = Member::where('group_id', $meeting->group_id)
            ->where('status', 'active')
            ->whereNotIn('id', $existingMemberIds)
            ->get();

        foreach ($missingMembers as $member) {
            Attendance::create(['meeting_id' => $meeting->id, 'member_id' => $member->id]);
            MeetingContribution::firstOrCreate(
                ['meeting_id' => $meeting->id, 'member_id' => $member->id],
                ['shares' => 0, 'emergency_fund' => 0, 'fine' => 0, 'confirmed' => false]
            );
        }

        $meeting->load(['group', 'contributions.member', 'attendances.member', 'loans.member', 'bankExpenses', 'summary']);
        return view('meetings.show', compact('meeting'));
    }

    public function edit(Meeting $meeting)
    {
        $this->authorize('update', $meeting);
        if ($meeting->isClosed()) {
            return back()->with('error', 'No se puede editar una reunión cerrada.');
        }
        $groups = auth()->user()->isAdmin() ? Group::all() : auth()->user()->groups()->get();
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
