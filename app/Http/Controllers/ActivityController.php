<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Group;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $groupIds = auth()->user()->activeGroupIds();

            $query = Activity::with(['group'])->whereIn('group_id', $groupIds);

            return DataTables::of($query)
                ->addColumn('status_badge', fn($a) => $a->amount_raised !== null
                    ? '<span class="badge bg-success">Bs. ' . number_format($a->amount_raised, 2) . '</span>'
                    : '<span class="badge bg-warning">Pendiente</span>')
                ->addColumn('actions', fn($a) => view('activities._actions', ['activity' => $a])->render())
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        $groups = Group::whereIn('id', auth()->user()->activeGroupIds())->get();
        return view('activities.index', compact('groups'));
    }

    public function create()
    {
        $groups = Group::whereIn('id', auth()->user()->activeGroupIds())->get();
        return view('activities.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group_id'      => 'required|exists:groups,id',
            'name'          => 'required|string|max:255',
            'activity_date' => 'required|date',
            'location'      => 'nullable|string|max:255',
            'notes'         => 'nullable|string',
            'amount_raised' => 'nullable|numeric|min:0',
        ]);

        Activity::create($data);
        return redirect()->route('activities.index')->with('success', 'Actividad registrada.');
    }

    public function edit(Activity $activity)
    {
        return view('activities.edit', compact('activity'));
    }

    public function update(Request $request, Activity $activity)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'activity_date' => 'required|date',
            'location'      => 'nullable|string|max:255',
            'notes'         => 'nullable|string',
            'amount_raised' => 'nullable|numeric|min:0',
        ]);

        $activity->update($data);
        return redirect()->route('activities.index')->with('success', 'Actividad actualizada.');
    }

    public function destroy(Activity $activity)
    {
        $activity->delete();
        return redirect()->route('activities.index')->with('success', 'Actividad eliminada.');
    }
}
