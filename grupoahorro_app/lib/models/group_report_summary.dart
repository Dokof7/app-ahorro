class GroupReportGroup {
  final int id;
  final String name;
  final String registrationMode;

  bool get isPartial => registrationMode == 'partial';

  const GroupReportGroup({
    required this.id,
    required this.name,
    required this.registrationMode,
  });

  factory GroupReportGroup.fromJson(Map<String, dynamic> j) => GroupReportGroup(
        id: _toInt(j['id']),
        name: j['name'] as String? ?? '',
        registrationMode: j['registration_mode'] as String? ?? 'full',
      );

  static int _toInt(dynamic v) {
    if (v == null) return 0;
    if (v is int) return v;
    if (v is double) return v.toInt();
    return int.tryParse(v.toString()) ?? 0;
  }
}

class GroupMonthlyPoint {
  final String period;
  final String label;
  final double savings;
  final double fines;
  final double loansOut;
  final double loanPayments;
  final double attendanceRate;
  final double? savingsDelta;

  const GroupMonthlyPoint({
    required this.period,
    required this.label,
    required this.savings,
    required this.fines,
    required this.loansOut,
    required this.loanPayments,
    required this.attendanceRate,
    required this.savingsDelta,
  });

  factory GroupMonthlyPoint.fromJson(Map<String, dynamic> j) => GroupMonthlyPoint(
        period: j['period'] as String? ?? '',
        label: j['label'] as String? ?? '',
        savings: _toDouble(j['savings']),
        fines: _toDouble(j['fines']),
        loansOut: _toDouble(j['loans_out']),
        loanPayments: _toDouble(j['loan_payments']),
        attendanceRate: _toDouble(j['attendance_rate']),
        savingsDelta: j['savings_delta'] == null ? null : _toDouble(j['savings_delta']),
      );

  static double _toDouble(dynamic v) {
    if (v == null) return 0.0;
    if (v is double) return v;
    if (v is int) return v.toDouble();
    return double.tryParse(v.toString()) ?? 0.0;
  }
}

class GroupTopSaver {
  final int memberId;
  final String name;
  final double totalSaved;
  final int contributions;

  const GroupTopSaver({
    required this.memberId,
    required this.name,
    required this.totalSaved,
    required this.contributions,
  });

  factory GroupTopSaver.fromJson(Map<String, dynamic> j) => GroupTopSaver(
        memberId: _toInt(j['member_id']),
        name: j['name'] as String? ?? '',
        totalSaved: _toDouble(j['total_saved']),
        contributions: _toInt(j['contributions']),
      );

  static int _toInt(dynamic v) {
    if (v == null) return 0;
    if (v is int) return v;
    if (v is double) return v.toInt();
    return int.tryParse(v.toString()) ?? 0;
  }

  static double _toDouble(dynamic v) {
    if (v == null) return 0.0;
    if (v is double) return v;
    if (v is int) return v.toDouble();
    return double.tryParse(v.toString()) ?? 0.0;
  }
}

class GroupTopAttendance {
  final int memberId;
  final String name;
  final int attended;
  final double attendanceRate;

  const GroupTopAttendance({
    required this.memberId,
    required this.name,
    required this.attended,
    required this.attendanceRate,
  });

  factory GroupTopAttendance.fromJson(Map<String, dynamic> j) => GroupTopAttendance(
        memberId: _toInt(j['member_id']),
        name: j['name'] as String? ?? '',
        attended: _toInt(j['attended']),
        attendanceRate: _toDouble(j['attendance_rate']),
      );

  static int _toInt(dynamic v) {
    if (v == null) return 0;
    if (v is int) return v;
    if (v is double) return v.toInt();
    return int.tryParse(v.toString()) ?? 0;
  }

  static double _toDouble(dynamic v) {
    if (v == null) return 0.0;
    if (v is double) return v;
    if (v is int) return v.toDouble();
    return double.tryParse(v.toString()) ?? 0.0;
  }
}

class GroupSessionRow {
  final int meetingId;
  final int number;
  final String? date;
  final String status;
  final int attended;
  final int totalMembers;
  final double attendanceRate;
  final double savings;
  final double emergency;
  final double fines;

  bool get isClosed => status == 'closed';

  const GroupSessionRow({
    required this.meetingId,
    required this.number,
    required this.date,
    required this.status,
    required this.attended,
    required this.totalMembers,
    required this.attendanceRate,
    required this.savings,
    required this.emergency,
    required this.fines,
  });

  factory GroupSessionRow.fromJson(Map<String, dynamic> j) => GroupSessionRow(
        meetingId: _toInt(j['meeting_id']),
        number: _toInt(j['number']),
        date: j['date'] as String?,
        status: j['status'] as String? ?? 'closed',
        attended: _toInt(j['attended']),
        totalMembers: _toInt(j['total_members']),
        attendanceRate: _toDouble(j['attendance_rate']),
        savings: _toDouble(j['savings']),
        emergency: _toDouble(j['emergency']),
        fines: _toDouble(j['fines']),
      );

  static int _toInt(dynamic v) {
    if (v == null) return 0;
    if (v is int) return v;
    if (v is double) return v.toInt();
    return int.tryParse(v.toString()) ?? 0;
  }

  static double _toDouble(dynamic v) {
    if (v == null) return 0.0;
    if (v is double) return v;
    if (v is int) return v.toDouble();
    return double.tryParse(v.toString()) ?? 0.0;
  }
}

class GroupReportSummary {
  final GroupReportGroup group;
  final List<GroupMonthlyPoint> monthly;
  final List<GroupTopSaver> topSavers;
  final List<GroupTopAttendance> topAttendance;
  final List<GroupSessionRow> sessions;

  const GroupReportSummary({
    required this.group,
    required this.monthly,
    required this.topSavers,
    required this.topAttendance,
    required this.sessions,
  });

  factory GroupReportSummary.fromJson(Map<String, dynamic> j) => GroupReportSummary(
        group: GroupReportGroup.fromJson(
          (j['group'] as Map?)?.cast<String, dynamic>() ?? const {},
        ),
        monthly: (j['monthly'] as List? ?? const [])
            .map((e) => GroupMonthlyPoint.fromJson((e as Map).cast<String, dynamic>()))
            .toList(),
        topSavers: (j['top_savers'] as List? ?? const [])
            .map((e) => GroupTopSaver.fromJson((e as Map).cast<String, dynamic>()))
            .toList(),
        topAttendance: (j['top_attendance'] as List? ?? const [])
            .map((e) => GroupTopAttendance.fromJson((e as Map).cast<String, dynamic>()))
            .toList(),
        // Missing on stale backends — tolerate absence with an empty list.
        sessions: (j['sessions'] as List? ?? const [])
            .map((e) => GroupSessionRow.fromJson((e as Map).cast<String, dynamic>()))
            .toList(),
      );
}
