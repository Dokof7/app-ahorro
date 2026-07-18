import 'package:flutter_test/flutter_test.dart';
import 'package:grupoahorro_app/models/meeting_conflict.dart';

void main() {
  group('MeetingConflict.fromJson', () {
    test('parses the 409 meeting summary shape', () {
      final conflict = MeetingConflict.fromJson({
        'id': 7,
        'meeting_number': 3,
        'meeting_date': '2026-07-01',
        'month': 'Julio',
        'is_partial': false,
      });

      expect(conflict.id, 7);
      expect(conflict.meetingNumber, 3);
      expect(conflict.meetingDate, '2026-07-01');
      expect(conflict.month, 'Julio');
      expect(conflict.isPartial, false);
    });

    test('truncates a datetime-formatted meeting_date to date-only', () {
      final conflict = MeetingConflict.fromJson({
        'id': 1,
        'meeting_number': 1,
        'meeting_date': '2026-07-01 00:00:00',
        'month': 'Julio',
        'is_partial': true,
      });

      expect(conflict.meetingDate, '2026-07-01');
      expect(conflict.isPartial, true);
    });

    test('defaults missing/invalid fields safely', () {
      final conflict = MeetingConflict.fromJson({});

      expect(conflict.id, 0);
      expect(conflict.meetingNumber, 0);
      expect(conflict.meetingDate, '');
      expect(conflict.month, '');
      expect(conflict.isPartial, false);
    });
  });
}
