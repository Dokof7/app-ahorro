class AdminGroup {
  final int id;
  final String name;
  final String description;
  final String status;
  final double shareValue;
  final String? startDate;
  final String registrationMode;
  final int members;
  final int meetings;

  AdminGroup({
    required this.id,
    required this.name,
    required this.description,
    required this.status,
    required this.shareValue,
    required this.startDate,
    required this.registrationMode,
    required this.members,
    required this.meetings,
  });

  bool get isPartial => registrationMode == 'partial';

  factory AdminGroup.fromJson(Map<String, dynamic> json) {
    return AdminGroup(
      id: _toInt(json['id']),
      name: json['name'] as String? ?? '',
      description: json['description'] as String? ?? '',
      status: json['status'] as String? ?? '',
      shareValue: _toDouble(json['share_value']),
      startDate: json['start_date'] as String?,
      registrationMode: json['registration_mode'] as String? ?? 'full',
      members: _toInt(json['members']),
      meetings: _toInt(json['meetings']),
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

class AdminMember {
  final int id;
  final String fullName;
  final String? documentNumber;
  final String? phone;
  final String status;
  final bool membershipPaid;
  final String? joinDate;
  final double totalSavings;
  final double totalEmergency;
  final double totalFines;
  final int totalShares;
  final int attended;
  final int absences;
  final int excusedAbsences;

  AdminMember({
    required this.id,
    required this.fullName,
    required this.documentNumber,
    required this.phone,
    required this.status,
    required this.membershipPaid,
    required this.joinDate,
    required this.totalSavings,
    required this.totalEmergency,
    required this.totalFines,
    required this.totalShares,
    required this.attended,
    required this.absences,
    required this.excusedAbsences,
  });

  int get totalMeetings => attended + absences + excusedAbsences;

  factory AdminMember.fromJson(Map<String, dynamic> json) {
    return AdminMember(
      id: _toInt(json['id']),
      fullName: json['full_name'] as String? ?? '',
      documentNumber: json['document_number'] as String?,
      phone: json['phone'] as String?,
      status: json['status'] as String? ?? '',
      membershipPaid: json['membership_paid'] == true,
      joinDate: json['join_date'] as String?,
      totalSavings: _toDouble(json['total_savings']),
      totalEmergency: _toDouble(json['total_emergency']),
      totalFines: _toDouble(json['total_fines']),
      totalShares: _toInt(json['total_shares']),
      attended: _toInt(json['attended']),
      absences: _toInt(json['absences']),
      excusedAbsences: _toInt(json['excused_absences']),
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
