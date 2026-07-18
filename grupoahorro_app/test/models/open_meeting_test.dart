import 'package:flutter_test/flutter_test.dart';
import 'package:grupoahorro_app/models/open_meeting.dart';

void main() {
  group('OpenMeeting.fromEnvelope', () {
    test('returns null when meeting is null (no open meeting)', () {
      final result = OpenMeeting.fromEnvelope({
        'meeting': null,
        'is_partial': false,
        'contributions': [],
        'attendances': [],
        'totals': null,
      });

      expect(result, isNull);
    });

    test('parses a full-group meeting with contributions and attendances', () {
      final result = OpenMeeting.fromEnvelope({
        'meeting': {
          'id': 10,
          'meeting_number': 4,
          'meeting_date': '2026-07-16',
          'month': 'Julio',
          'status': 'open',
        },
        'is_partial': false,
        'contributions': [
          {
            'id': 1,
            'member_id': 5,
            'member': {'id': 5, 'full_name': 'Juana Pérez'},
            'shares': 2,
            'savings': 50.0,
            'emergency_fund': 5.0,
            'fine': 0,
            'total': 55.0,
            'confirmed': false,
            'observations': null,
          },
        ],
        'attendances': [
          {
            'id': 1,
            'member_id': 5,
            'member': {'id': 5, 'full_name': 'Juana Pérez'},
            'status': 'present',
            'observations': null,
          },
        ],
        'totals': null,
      });

      expect(result, isNotNull);
      expect(result!.id, 10);
      expect(result.meetingNumber, 4);
      expect(result.meetingDate, '2026-07-16');
      expect(result.month, 'Julio');
      expect(result.isPartial, false);
      expect(result.contributions, hasLength(1));
      expect(result.contributions.first.member?.fullName, 'Juana Pérez');
      expect(result.contributions.first.shares, 2);
      expect(result.attendances, hasLength(1));
      expect(result.attendances.first.status, 'present');
      expect(result.totals, isNull);
    });

    test('parses a partial-group meeting with a single totals row', () {
      final result = OpenMeeting.fromEnvelope({
        'meeting': {
          'id': 11,
          'meeting_number': 1,
          'meeting_date': '2026-07-16 00:00:00',
          'month': 'Julio',
          'status': 'open',
        },
        'is_partial': true,
        'contributions': [],
        'attendances': [],
        'totals': {
          'shares': 10,
          'savings': 250.0,
          'emergency_fund': 20.0,
          'fine': 0,
          'observations': null,
        },
      });

      expect(result, isNotNull);
      expect(result!.meetingDate, '2026-07-16');
      expect(result.isPartial, true);
      expect(result.contributions, isEmpty);
      expect(result.totals, isNotNull);
      expect(result.totals!.shares, 10);
      expect(result.totals!.emergencyFund, 20.0);
    });
  });
}
