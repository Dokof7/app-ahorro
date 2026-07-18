/// Lightweight summary of an already-open meeting, returned by
/// `POST /groups/{group}/meetings` on a 409 (design ADR-7). It only carries
/// enough to inform the user and let the screen switch into "load the
/// existing open meeting" mode via `fetchOpenMeeting`.
class MeetingConflict {
  final int id;
  final int meetingNumber;
  final String meetingDate;
  final String month;
  final bool isPartial;

  const MeetingConflict({
    required this.id,
    required this.meetingNumber,
    required this.meetingDate,
    required this.month,
    required this.isPartial,
  });

  factory MeetingConflict.fromJson(Map<String, dynamic> j) => MeetingConflict(
        id:            _toInt(j['id']),
        meetingNumber: _toInt(j['meeting_number']),
        meetingDate:   _dateOnly(j['meeting_date']),
        month:         j['month'] as String? ?? '',
        isPartial:     j['is_partial'] as bool? ?? false,
      );

  static int _toInt(dynamic v) => int.tryParse(v?.toString() ?? '') ?? 0;

  static String _dateOnly(dynamic v) {
    final s = v?.toString() ?? '';
    return s.length >= 10 ? s.substring(0, 10) : s;
  }
}
