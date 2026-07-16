<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesMeetingWrite;
use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Meeting;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

class LoanPaymentApiController extends Controller
{
    use AuthorizesMeetingWrite;

    public function store(Request $request, Loan $loan)
    {
        $this->denyUnlessRole($request->user()->can('createPayment', $loan));

        $data = $request->validate([
            'meeting_id' => 'nullable|exists:meetings,id',
            'payment_date' => 'required|date',
            'amount_paid' => 'required|numeric|min:0',
            'interest_paid' => 'required|numeric|min:0',
            'observations' => 'nullable|string',
        ]);

        if (!empty($data['meeting_id'])) {
            $meeting = Meeting::findOrFail($data['meeting_id']);
            if (!$meeting->isOpen()) {
                throw new HttpResponseException($this->closedMeetingResponse());
            }
        }

        $data['loan_id'] = $loan->id;
        $data['remaining_balance'] = max(0, $loan->balance - $data['amount_paid'] - $data['interest_paid']);

        $payment = LoanPayment::create($data);

        return response()->json([
            'payment' => $payment,
            'loan' => $loan->fresh(),
        ], 201);
    }
}
