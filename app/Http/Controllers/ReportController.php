<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Member;
use App\Models\Meeting;
use App\Models\Loan;
use App\Models\MeetingContribution;
use App\Exports\GroupReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $groups = $user->isAdmin() ? Group::all() : $user->groups()->get();
        return view('reports.index', compact('groups'));
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'report_type' => 'required|in:group,member,meeting,loans_pending,loans_paid,monthly',
            'group_id'    => 'nullable|exists:groups,id',
            'member_id'   => 'nullable|exists:members,id',
            'month'       => 'nullable|string',
            'date_from'   => 'nullable|date',
            'date_to'     => 'nullable|date',
            'format'      => 'required|in:pdf,excel',
        ]);

        $reportData = $this->getReportData($data);

        if ($data['format'] === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf.' . $data['report_type'], $reportData)
                ->setPaper('a4', 'landscape');
            return $pdf->download('reporte_' . $data['report_type'] . '_' . now()->format('Ymd') . '.pdf');
        }

        return Excel::download(
            new GroupReportExport($reportData, $data['report_type']),
            'reporte_' . $data['report_type'] . '_' . now()->format('Ymd') . '.xlsx'
        );
    }

    public function membersByGroup(Group $group)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->groups()->where('groups.id', $group->id)->exists()) {
            abort(403);
        }
        return response()->json(
            $group->members()->where('status', 'active')->orderBy('full_name')->get(['id', 'full_name'])
        );
    }

    private function getReportData(array $filters): array
    {
        $user = auth()->user();
        $groupIds = $user->isAdmin()
            ? Group::pluck('id')
            : $user->groups()->pluck('groups.id');

        return match($filters['report_type']) {
            'group'        => $this->groupReport($filters, $groupIds),
            'member'       => $this->memberReport($filters, $groupIds),
            'meeting'      => $this->meetingReport($filters, $groupIds),
            'loans_pending'=> $this->loanReport($filters, $groupIds, 'pending'),
            'loans_paid'   => $this->loanReport($filters, $groupIds, 'paid'),
            'monthly'      => $this->monthlyReport($filters, $groupIds),
            default        => []
        };
    }

    private function groupReport(array $filters, $groupIds): array
    {
        $query = Group::whereIn('id', $groupIds)->with(['members', 'meetings']);
        if (!empty($filters['group_id'])) $query->where('id', $filters['group_id']);
        return ['groups' => $query->get(), 'filters' => $filters];
    }

    private function meetingReport(array $filters, $groupIds): array
    {
        $query = Meeting::whereIn('group_id', $groupIds)
            ->with(['group', 'contributions'])
            ->orderBy('meeting_date', 'desc');
        if (!empty($filters['group_id'])) $query->where('group_id', $filters['group_id']);
        if (!empty($filters['month']))    $query->where('month', $filters['month']);
        if (!empty($filters['date_from']))$query->where('meeting_date', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))  $query->where('meeting_date', '<=', $filters['date_to']);
        return ['meetings' => $query->get(), 'filters' => $filters];
    }

    private function loanReport(array $filters, $groupIds, string $status): array
    {
        $query = Loan::whereIn('group_id', $groupIds)->where('status', $status)
            ->with(['member', 'group', 'meeting', 'payments']);
        if (!empty($filters['group_id'])) $query->where('group_id', $filters['group_id']);
        return ['loans' => $query->get(), 'filters' => $filters, 'status' => $status];
    }

    private function memberReport(array $filters, $groupIds): array
    {
        $query = Member::whereIn('group_id', $groupIds)->with(['group', 'contributions', 'loans', 'fines']);
        if (!empty($filters['group_id'])) $query->whereIn('group_id', [$filters['group_id']]);
        if (!empty($filters['member_id'])) $query->where('id', $filters['member_id']);
        return ['members' => $query->get(), 'filters' => $filters];
    }

    private function monthlyReport(array $filters, $groupIds): array
    {
        $query = MeetingContribution::whereHas('meeting', fn($q) => $q->whereIn('group_id', $groupIds))
            ->with(['meeting.group', 'member']);
        if (!empty($filters['month'])) {
            $query->whereHas('meeting', fn($q) => $q->where('month', $filters['month']));
        }
        if (!empty($filters['group_id'])) {
            $query->whereHas('meeting', fn($q) => $q->where('group_id', $filters['group_id']));
        }
        return ['contributions' => $query->get(), 'filters' => $filters];
    }
}
