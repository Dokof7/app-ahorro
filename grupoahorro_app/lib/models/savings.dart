class SavingsContribution {
  final int? meetingNumber;
  final String? meetingDate;
  final String? month;
  final int shares;
  final double savings;
  final double emergency;
  final double fines;
  final double total;

  const SavingsContribution({
    required this.meetingNumber,
    required this.meetingDate,
    required this.month,
    required this.shares,
    required this.savings,
    required this.emergency,
    required this.fines,
    required this.total,
  });

  factory SavingsContribution.fromJson(Map<String, dynamic> j) =>
      SavingsContribution(
        meetingNumber: j['meeting_number'] as int?,
        meetingDate:   j['meeting_date'] as String?,
        month:         j['month'] as String?,
        shares:        _toInt(j['shares']),
        savings:       _toDouble(j['savings']),
        emergency:     _toDouble(j['emergency']),
        fines:         _toDouble(j['fines']),
        total:         _toDouble(j['total']),
      );

  static int _toInt(dynamic v) => int.tryParse(v?.toString() ?? '') ?? 0;
  static double _toDouble(dynamic v) => double.tryParse(v?.toString() ?? '') ?? 0.0;
}

class SavingsTotals {
  final double savings;
  final double emergency;
  final double fines;
  final double loans;

  const SavingsTotals({
    required this.savings,
    required this.emergency,
    required this.fines,
    required this.loans,
  });

  factory SavingsTotals.fromJson(Map<String, dynamic> j) => SavingsTotals(
        savings:   _toDouble(j['savings']),
        emergency: _toDouble(j['emergency']),
        fines:     _toDouble(j['fines']),
        loans:     _toDouble(j['loans']),
      );

  static double _toDouble(dynamic v) => double.tryParse(v?.toString() ?? '') ?? 0.0;
}

class MembershipInfo {
  final bool paid;
  final String? paidAt;

  const MembershipInfo({required this.paid, this.paidAt});

  factory MembershipInfo.fromJson(Map<String, dynamic> j) => MembershipInfo(
        paid:   j['paid'] as bool? ?? false,
        paidAt: j['paid_at'] as String?,
      );
}
