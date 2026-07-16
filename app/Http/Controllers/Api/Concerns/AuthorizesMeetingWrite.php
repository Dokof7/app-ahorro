<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Meeting;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Cross-cutting guard shared by every mobile write endpoint touching a
 * meeting: role/policy check (via the meeting's `update` policy, which
 * already mirrors the web `canEdit()` role matrix) + open-meeting gate.
 *
 * This trait does NOT own domain math — controllers mutate through the
 * existing Eloquent models (MeetingContribution, MeetingTotal, Attendance,
 * LoanPayment, Activity), whose saving/saved events already compute
 * shares*share_value, loan balances, and recalculateSummary().
 */
trait AuthorizesMeetingWrite
{
    /**
     * Assert the current user may write to this meeting: policy `update`
     * (role matrix) AND the meeting must be open. Aborts the request with
     * a JSON 403 response otherwise.
     */
    protected function assertCanWriteMeeting(Meeting $meeting): void
    {
        if (!$this->authorizeSilently('update', $meeting)) {
            throw new HttpResponseException($this->roleDeniedResponse());
        }

        if (!$meeting->isOpen()) {
            throw new HttpResponseException($this->closedMeetingResponse());
        }
    }

    private function authorizeSilently(string $ability, $target): bool
    {
        try {
            $this->authorize($ability, $target);
            return true;
        } catch (AuthorizationException $e) {
            return false;
        }
    }

    protected function roleDeniedResponse(): JsonResponse
    {
        return response()->json([
            'error' => 'No tenés permisos para realizar esta acción.',
            'reason' => 'role',
        ], 403);
    }

    protected function closedMeetingResponse(): JsonResponse
    {
        return response()->json([
            'error' => 'Reunión cerrada, no se puede editar.',
            'reason' => 'closed',
        ], 403);
    }
}
