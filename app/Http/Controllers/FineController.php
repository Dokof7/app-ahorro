<?php

namespace App\Http\Controllers;

use App\Models\Fine;
use App\Models\Group;
use App\Models\Member;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class FineController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $groupIds = auth()->user()->isAdmin()
                ? Group::pluck('id')
                : auth()->user()->groups()->pluck('id');

            $query = Fine::with(['member.group', 'meeting'])
                ->whereHas('member', fn($q) => $q->whereIn('group_id', $groupIds));

            return DataTables::of($query)
                ->addColumn('member_name', fn($f) => $f->member->full_name)
                ->addColumn('group_name', fn($f) => $f->member->group->name)
                ->addColumn('status_badge', fn($f) => $f->status === 'pending'
                    ? '<span class="badge bg-warning">Pendiente</span>'
                    : '<span class="badge bg-success">Pagada</span>')
                ->addColumn('actions', fn($f) => view('fines._actions', ['fine' => $f])->render())
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        $groups = auth()->user()->isAdmin() ? Group::all() : auth()->user()->groups;
        return view('fines.index', compact('groups'));
    }

    public function create()
    {
        $groups = auth()->user()->isAdmin() ? Group::all() : auth()->user()->groups;
        return view('fines.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'member_id'    => 'required|exists:members,id',
            'meeting_id'   => 'required|exists:meetings,id',
            'amount'       => 'required|numeric|min:0.01',
            'reason'       => 'required|string',
            'observations' => 'nullable|string',
        ]);

        Fine::create($data);
        return redirect()->route('fines.index')->with('success', 'Multa registrada.');
    }

    public function markPaid(Fine $fine)
    {
        $fine->update(['status' => 'paid', 'paid_date' => now()]);
        return back()->with('success', 'Multa marcada como pagada.');
    }

    public function destroy(Fine $fine)
    {
        $fine->delete();
        return redirect()->route('fines.index')->with('success', 'Multa eliminada.');
    }

    public function getMembersByGroup($groupId)
    {
        return Member::where('group_id', $groupId)->where('status', 'active')->get();
    }

    public function getMeetingsByGroup($groupId)
    {
        return Meeting::where('group_id', $groupId)->where('status', 'open')->get();
    }
}
