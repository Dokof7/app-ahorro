<?php

namespace App\Http\Controllers;

use App\Models\LoanPayment;
use App\Models\Loan;
use Illuminate\Http\Request;

class LoanPaymentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'loan_id'       => 'required|exists:loans,id',
            'meeting_id'    => 'nullable|exists:meetings,id',
            'payment_date'  => 'required|date',
            'amount_paid'   => 'required|numeric|min:0.01',
            'interest_paid' => 'required|numeric|min:0',
            'observations'  => 'nullable|string',
        ]);

        $loan = Loan::findOrFail($data['loan_id']);
        $data['remaining_balance'] = max(0, $loan->balance - $data['amount_paid'] - $data['interest_paid']);

        LoanPayment::create($data);

        return back()->with('success', 'Pago registrado exitosamente.');
    }

    public function destroy(LoanPayment $loanPayment)
    {
        $loanPayment->delete();
        return back()->with('success', 'Pago eliminado.');
    }
}
