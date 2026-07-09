<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Group;
use App\Models\Member;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $groupIds = auth()->user()->activeGroupIds();

            $query = Loan::with(['member', 'group', 'meeting'])->whereIn('loans.group_id', $groupIds);

            return DataTables::of($query)
                ->addColumn('status_badge', fn($l) => match($l->status) {
                    'pending' => '<span class="badge bg-warning">Pendiente</span>',
                    'paid'    => '<span class="badge bg-success">Pagado</span>',
                    'overdue' => '<span class="badge bg-danger">Vencido</span>',
                    default   => ''
                })
                ->addColumn('actions', fn($l) => view('loans._actions', ['loan' => $l])->render())
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        $groups = Group::whereIn('id', auth()->user()->activeGroupIds())->get();
        return view('loans.index', compact('groups'));
    }

    public function create()
    {
        $groups = Group::whereIn('id', auth()->user()->activeGroupIds())->get();
        return view('loans.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'member_id'     => 'required|exists:members,id',
            'group_id'      => 'required|exists:groups,id',
            'meeting_id'    => 'required|exists:meetings,id',
            'amount'        => 'required|numeric|min:0.01',
            'interest_rate' => 'required|numeric|min:0',
            'delivery_date' => 'required|date',
            'due_date'      => 'required|date|after:delivery_date',
            'observations'  => 'nullable|string',
        ]);

        $loan = Loan::create($data);

        return redirect()->route('loans.show', $loan)
            ->with('success', 'Préstamo registrado exitosamente.');
    }

    public function show(Loan $loan)
    {
        $this->authorize('view', $loan);
        $loan->load(['member', 'group.meetings', 'meeting', 'payments']);
        return view('loans.show', compact('loan'));
    }

    public function destroy(Loan $loan)
    {
        $this->authorize('delete', $loan);
        $loan->delete();
        return redirect()->route('loans.index')->with('success', 'Préstamo eliminado.');
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
