<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Member;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AdminAttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        $groups = Group::where('status', 'active')->orderBy('name')->get();
        $selectedGroup = null;
        $members = collect();
        $meetings = collect();

        if ($request->group_id) {
            $selectedGroup = Group::with(['meetings' => fn($q) => $q->orderBy('meeting_number')])->findOrFail($request->group_id);
            $meetings = $selectedGroup->meetings;

            $memberIds = Member::where('group_id', $selectedGroup->id)
                ->where('status', 'active')
                ->orderBy('first_name')
                ->pluck('id');

            $attendances = Attendance::whereIn('member_id', $memberIds)
                ->whereHas('meeting', fn($q) => $q->where('group_id', $selectedGroup->id))
                ->get()
                ->groupBy('member_id');

            $members = Member::whereIn('id', $memberIds)
                ->orderBy('first_name')
                ->get()
                ->map(function ($member) use ($attendances, $meetings) {
                    $atts = $attendances->get($member->id, collect());
                    $totalMeetings  = $meetings->count();
                    $attended       = $atts->where('attended', true)->count();
                    $excused        = $atts->where('excused_absence', true)->count();
                    $absent         = $totalMeetings - $attended - $excused;
                    $attendancePct  = $totalMeetings > 0 ? round($attended / $totalMeetings * 100) : 0;

                    $member->stats = (object) [
                        'total'          => $totalMeetings,
                        'attended'       => $attended,
                        'excused'        => $excused,
                        'absent'         => max(0, $absent),
                        'attendance_pct' => $attendancePct,
                        'by_meeting'     => $atts->keyBy('meeting_id'),
                    ];

                    return $member;
                });
        }

        return view('admin.attendance', compact('groups', 'selectedGroup', 'members', 'meetings'));
    }
}
