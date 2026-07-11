class LastMeetingSummary {
  final int? meetingNumber;
  final String? meetingDate;
  final double savings;
  final double emergency;
  final double fines;

  LastMeetingSummary({
    required this.meetingNumber,
    required this.meetingDate,
    required this.savings,
    required this.emergency,
    required this.fines,
  });

  factory LastMeetingSummary.fromJson(Map<String, dynamic> json) {
    return LastMeetingSummary(
      meetingNumber: json['meeting_number'] as int?,
      meetingDate: json['meeting_date'] as String?,
      savings: _toDouble(json['savings']),
      emergency: _toDouble(json['emergency']),
      fines: _toDouble(json['fines']),
    );
  }

  static double _toDouble(dynamic v) {
    if (v == null) return 0;
    if (v is double) return v;
    if (v is int) return v.toDouble();
    return double.tryParse(v.toString()) ?? 0;
  }
}
