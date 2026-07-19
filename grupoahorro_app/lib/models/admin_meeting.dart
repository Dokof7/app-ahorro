/// One meeting row from GET /admin/groups/{group}/meetings: amounts already
/// resolved server-side for both registration modes, plus attendance counts.
class AdminMeeting {
  final int id;
  final int meetingNumber;
  final String meetingDate;
  final String month;
  final String status;
  final double savings;
  final double emergency;
  final double fines;
  final int attended;
  final int totalAttendance;

  AdminMeeting({
    required this.id,
    required this.meetingNumber,
    required this.meetingDate,
    required this.month,
    required this.status,
    required this.savings,
    required this.emergency,
    required this.fines,
    required this.attended,
    required this.totalAttendance,
  });

  bool get isOpen => status == 'open';

  factory AdminMeeting.fromJson(Map<String, dynamic> json) {
    return AdminMeeting(
      id: _toInt(json['id']),
      meetingNumber: _toInt(json['meeting_number']),
      meetingDate: json['meeting_date'] as String? ?? '',
      month: json['month'] as String? ?? '',
      status: json['status'] as String? ?? '',
      savings: _toDouble(json['savings']),
      emergency: _toDouble(json['emergency']),
      fines: _toDouble(json['fines']),
      attended: _toInt(json['attended']),
      totalAttendance: _toInt(json['total_attendance']),
    );
  }

  static int _toInt(dynamic v) {
    if (v == null) return 0;
    if (v is int) return v;
    return int.tryParse(v.toString()) ?? 0;
  }

  static double _toDouble(dynamic v) {
    if (v == null) return 0;
    if (v is double) return v;
    if (v is int) return v.toDouble();
    return double.tryParse(v.toString()) ?? 0;
  }
}
