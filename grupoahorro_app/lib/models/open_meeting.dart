/// Lightweight member reference embedded in contribution/attendance rows.
class MemberRef {
  final int id;
  final String fullName;

  const MemberRef({required this.id, required this.fullName});

  factory MemberRef.fromJson(Map<String, dynamic> j) => MemberRef(
        id: _toInt(j['id']),
        fullName: j['full_name'] as String? ?? '',
      );

  static int _toInt(dynamic v) => int.tryParse(v?.toString() ?? '') ?? 0;
}

/// A single meeting_contributions row (non-partial groups: one row per member).
class ContributionRow {
  final int id;
  final int memberId;
  final MemberRef? member;
  final int shares;
  final double savings;
  final double emergencyFund;
  final double fine;
  final double total;
  final bool confirmed;
  final String? observations;

  const ContributionRow({
    required this.id,
    required this.memberId,
    required this.member,
    required this.shares,
    required this.savings,
    required this.emergencyFund,
    required this.fine,
    required this.total,
    required this.confirmed,
    required this.observations,
  });

  factory ContributionRow.fromJson(Map<String, dynamic> j) => ContributionRow(
        id:            _toInt(j['id']),
        memberId:      _toInt(j['member_id']),
        member: j['member'] is Map<String, dynamic>
            ? MemberRef.fromJson(j['member'] as Map<String, dynamic>)
            : null,
        shares:        _toInt(j['shares']),
        savings:       _toDouble(j['savings']),
        emergencyFund: _toDouble(j['emergency_fund']),
        fine:          _toDouble(j['fine']),
        total:         _toDouble(j['total']),
        confirmed:     j['confirmed'] as bool? ?? false,
        observations:  j['observations'] as String?,
      );

  static int _toInt(dynamic v) => int.tryParse(v?.toString() ?? '') ?? 0;
  static double _toDouble(dynamic v) => double.tryParse(v?.toString() ?? '') ?? 0.0;
}

/// A single attendances row.
class AttendanceRow {
  final int id;
  final int memberId;
  final MemberRef? member;
  final String status; // present | late | absent | excused
  final String? observations;

  const AttendanceRow({
    required this.id,
    required this.memberId,
    required this.member,
    required this.status,
    required this.observations,
  });

  factory AttendanceRow.fromJson(Map<String, dynamic> j) => AttendanceRow(
        id:       _toInt(j['id']),
        memberId: _toInt(j['member_id']),
        member: j['member'] is Map<String, dynamic>
            ? MemberRef.fromJson(j['member'] as Map<String, dynamic>)
            : null,
        status:       j['status'] as String? ?? 'absent',
        observations: j['observations'] as String?,
      );

  static int _toInt(dynamic v) => int.tryParse(v?.toString() ?? '') ?? 0;
}

/// The single meeting_totals row used by partial groups.
class MeetingTotals {
  final int shares;
  final double savings;
  final double emergencyFund;
  final double fine;
  final String? observations;

  const MeetingTotals({
    required this.shares,
    required this.savings,
    required this.emergencyFund,
    required this.fine,
    required this.observations,
  });

  factory MeetingTotals.fromJson(Map<String, dynamic> j) => MeetingTotals(
        shares:        _toInt(j['shares']),
        savings:       _toDouble(j['savings']),
        emergencyFund: _toDouble(j['emergency_fund']),
        fine:          _toDouble(j['fine']),
        observations:  j['observations'] as String?,
      );

  static int _toInt(dynamic v) => int.tryParse(v?.toString() ?? '') ?? 0;
  static double _toDouble(dynamic v) => double.tryParse(v?.toString() ?? '') ?? 0.0;
}

/// Response shape shared by `GET /meetings/open` and `POST
/// /groups/{group}/meetings` (design ADR-5): a meeting plus its pre-seeded
/// contribution/attendance rows, ready for the mobile write forms.
class OpenMeeting {
  final int id;
  final int meetingNumber;
  final String meetingDate;
  final String month;
  final String status;
  final bool isPartial;
  final List<ContributionRow> contributions;
  final List<AttendanceRow> attendances;
  final MeetingTotals? totals;

  const OpenMeeting({
    required this.id,
    required this.meetingNumber,
    required this.meetingDate,
    required this.month,
    required this.status,
    required this.isPartial,
    required this.contributions,
    required this.attendances,
    required this.totals,
  });

  /// Parses the full envelope: `{meeting, is_partial, contributions,
  /// attendances, totals}`. Returns null when `meeting` is null (no open
  /// meeting for the group).
  static OpenMeeting? fromEnvelope(Map<String, dynamic> data) {
    final meetingJson = data['meeting'];
    if (meetingJson == null || meetingJson is! Map<String, dynamic>) {
      return null;
    }
    final isPartial = data['is_partial'] as bool? ?? false;
    final contributionsJson = data['contributions'];
    final attendancesJson = data['attendances'];
    final totalsJson = data['totals'];

    return OpenMeeting(
      id:            _toInt(meetingJson['id']),
      meetingNumber: _toInt(meetingJson['meeting_number']),
      meetingDate:   _dateOnly(meetingJson['meeting_date']),
      month:         meetingJson['month'] as String? ?? '',
      status:        meetingJson['status'] as String? ?? 'open',
      isPartial:     isPartial,
      contributions: contributionsJson is List
          ? contributionsJson
              .whereType<Map<String, dynamic>>()
              .map(ContributionRow.fromJson)
              .toList()
          : const [],
      attendances: attendancesJson is List
          ? attendancesJson
              .whereType<Map<String, dynamic>>()
              .map(AttendanceRow.fromJson)
              .toList()
          : const [],
      totals: totalsJson is Map<String, dynamic>
          ? MeetingTotals.fromJson(totalsJson)
          : null,
    );
  }

  static int _toInt(dynamic v) => int.tryParse(v?.toString() ?? '') ?? 0;

  /// `meeting_date` may come back as `2026-07-16` or `2026-07-16 00:00:00`
  /// (Eloquent date cast toString). Keep only the date portion.
  static String _dateOnly(dynamic v) {
    final s = v?.toString() ?? '';
    return s.length >= 10 ? s.substring(0, 10) : s;
  }
}
