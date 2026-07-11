class MeetingScheduledDate {
  final int id;
  final int groupId;
  final DateTime scheduledDate;
  final String? notes;
  final bool used;

  MeetingScheduledDate({
    required this.id,
    required this.groupId,
    required this.scheduledDate,
    required this.notes,
    required this.used,
  });

  factory MeetingScheduledDate.fromJson(Map<String, dynamic> json) {
    return MeetingScheduledDate(
      id: _toInt(json['id']),
      groupId: _toInt(json['group_id']),
      scheduledDate: DateTime.tryParse(json['scheduled_date'] ?? '') ?? DateTime.now(),
      notes: json['notes'] as String?,
      used: _toInt(json['used']) == 1,
    );
  }

  MeetingStatus get status {
    final today = DateTime.now();
    final date = DateTime(scheduledDate.year, scheduledDate.month, scheduledDate.day);
    final todayOnly = DateTime(today.year, today.month, today.day);

    if (used) return MeetingStatus.done;
    if (date.isBefore(todayOnly)) return MeetingStatus.missed;
    if (date == todayOnly) return MeetingStatus.today;
    if (date.difference(todayOnly).inDays <= 7) return MeetingStatus.soon;
    return MeetingStatus.upcoming;
  }

  static int _toInt(dynamic v) {
    if (v == null) return 0;
    if (v is int) return v;
    return int.tryParse(v.toString()) ?? 0;
  }
}

enum MeetingStatus { today, soon, upcoming, missed, done }
