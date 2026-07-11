class GroupComparison {
  final int groupId;
  final String groupName;
  final int activeMembers;
  final double totalSavings;
  final double totalEmergency;
  final double totalFines;
  final double loansOut;
  final double loansRecovered;
  final double loansBalance;
  final double totalInterest;
  final double attendanceRate;
  final String registrationMode;

  bool get isPartial => registrationMode == 'partial';

  const GroupComparison({
    required this.groupId,
    required this.groupName,
    required this.activeMembers,
    required this.totalSavings,
    required this.totalEmergency,
    required this.totalFines,
    required this.loansOut,
    required this.loansRecovered,
    required this.loansBalance,
    required this.totalInterest,
    required this.attendanceRate,
    required this.registrationMode,
  });

  factory GroupComparison.fromJson(Map<String, dynamic> j) => GroupComparison(
        groupId: _toInt(j['group_id']),
        groupName: j['group_name'] as String? ?? '',
        activeMembers: _toInt(j['active_members']),
        totalSavings: _toDouble(j['total_savings']),
        totalEmergency: _toDouble(j['total_emergency']),
        totalFines: _toDouble(j['total_fines']),
        loansOut: _toDouble(j['loans_out']),
        loansRecovered: _toDouble(j['loans_recovered']),
        loansBalance: _toDouble(j['loans_balance']),
        totalInterest: _toDouble(j['total_interest']),
        attendanceRate: _toDouble(j['attendance_rate']),
        registrationMode: j['registration_mode'] as String? ?? 'full',
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
