class DashboardStats {
  final int totalGroups;
  final int totalMembers;
  final int totalMeetings;
  final double totalSavings;
  final double totalEmergency;
  final double totalFines;
  final double loansPending;
  final double loansPaid;
  final int loansOverdue;
  final double loansOverdueBalance;
  final double bankExpenses;
  final double totalMembership;

  const DashboardStats({
    required this.totalGroups,
    required this.totalMembers,
    required this.totalMeetings,
    required this.totalSavings,
    required this.totalEmergency,
    required this.totalFines,
    required this.loansPending,
    required this.loansPaid,
    required this.loansOverdue,
    required this.loansOverdueBalance,
    required this.bankExpenses,
    required this.totalMembership,
  });

  factory DashboardStats.fromJson(Map<String, dynamic> j) => DashboardStats(
        totalGroups:         _toInt(j['total_groups']),
        totalMembers:        _toInt(j['total_members']),
        totalMeetings:       _toInt(j['total_meetings']),
        totalSavings:        _toDouble(j['total_savings']),
        totalEmergency:      _toDouble(j['total_emergency']),
        totalFines:          _toDouble(j['total_fines']),
        loansPending:        _toDouble(j['loans_pending']),
        loansPaid:           _toDouble(j['loans_paid']),
        loansOverdue:        _toInt(j['loans_overdue']),
        loansOverdueBalance: _toDouble(j['loans_overdue_balance']),
        bankExpenses:        _toDouble(j['bank_expenses']),
        totalMembership:     _toDouble(j['total_membership']),
      );

  static int _toInt(dynamic v) => int.tryParse(v?.toString() ?? '') ?? 0;
  static double _toDouble(dynamic v) => double.tryParse(v?.toString() ?? '') ?? 0.0;
}

class DashboardGroup {
  final int id;
  final String name;
  final String description;
  final String status;
  final int members;
  final int meetings;

  const DashboardGroup({
    required this.id,
    required this.name,
    required this.description,
    required this.status,
    required this.members,
    required this.meetings,
  });

  factory DashboardGroup.fromJson(Map<String, dynamic> j) => DashboardGroup(
        id:          j['id'] as int,
        name:        j['name'] as String,
        description: j['description'] ?? '',
        status:      j['status'] ?? '',
        members:     j['members'] as int,
        meetings:    j['meetings'] as int,
      );
}
