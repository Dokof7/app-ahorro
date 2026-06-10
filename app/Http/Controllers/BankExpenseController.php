<?php

namespace App\Http\Controllers;

use App\Models\BankExpense;
use App\Models\Group;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class BankExpenseController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $groupIds = auth()->user()->isAdmin()
                ? Group::pluck('id')
                : auth()->user()->groups()->pluck('groups.id');

            $query = BankExpense::with(['group', 'meeting'])->whereIn('group_id', $groupIds);

            return DataTables::of($query)
                ->addColumn('actions', fn($e) => view('bank_expenses._actions', ['expense' => $e])->render())
                ->rawColumns(['actions'])
                ->make(true);
        }

        $groups = auth()->user()->isAdmin() ? Group::all() : auth()->user()->groups()->get();
        return view('bank_expenses.index', compact('groups'));
    }

    public function create()
    {
        $groups = auth()->user()->isAdmin() ? Group::all() : auth()->user()->groups()->get();
        $selectedMeeting = request()->meeting_id ? Meeting::find(request()->meeting_id) : null;
        return view('bank_expenses.create', compact('groups', 'selectedMeeting'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group_id'     => 'required|exists:groups,id',
            'meeting_id'   => 'required|exists:meetings,id',
            'expense_date' => 'required|date',
            'concept'      => 'required|string',
            'amount'       => 'required|numeric|min:0.01',
            'observations' => 'nullable|string',
        ]);

        BankExpense::create($data);
        return redirect()->route('bank-expenses.index')->with('success', 'Gasto bancario registrado.');
    }

    public function edit(BankExpense $bankExpense)
    {
        return view('bank_expenses.edit', compact('bankExpense'));
    }

    public function update(Request $request, BankExpense $bankExpense)
    {
        $data = $request->validate([
            'concept'      => 'required|string',
            'amount'       => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'observations' => 'nullable|string',
        ]);
        $bankExpense->update($data);
        return redirect()->route('bank-expenses.index')->with('success', 'Gasto actualizado.');
    }

    public function destroy(BankExpense $bankExpense)
    {
        $bankExpense->delete();
        return redirect()->route('bank-expenses.index')->with('success', 'Gasto eliminado.');
    }

    public function getMeetingsByGroup($groupId)
    {
        return Meeting::where('group_id', $groupId)->where('status', 'open')->get();
    }
}
