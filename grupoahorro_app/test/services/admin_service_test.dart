import 'dart:async';
import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:grupoahorro_app/models/admin_group.dart';
import 'package:grupoahorro_app/services/api_client.dart';
import 'package:grupoahorro_app/services/admin_service.dart';

/// Same stub pattern as meeting_write_service_test.dart.
class _StubAdapter implements HttpClientAdapter {
  final int statusCode;
  final Map<String, dynamic> body;
  RequestOptions? lastRequest;

  _StubAdapter({required this.statusCode, required this.body});

  @override
  void close({bool force = false}) {}

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    lastRequest = options;
    return ResponseBody.fromBytes(
      utf8.encode(jsonEncode(body)),
      statusCode,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  const secureStorageChannel = MethodChannel('plugins.it_nomads.com/flutter_secure_storage');
  TestWidgetsFlutterBinding.instance.defaultBinaryMessenger.setMockMethodCallHandler(
    secureStorageChannel,
    (call) async => null,
  );

  test('fetchMeetings hits the admin meetings endpoint and parses rows', () async {
    final adapter = _StubAdapter(statusCode: 200, body: {
      'data': [
        {
          'id': 27,
          'meeting_number': 5,
          'meeting_date': '2026-07-18',
          'month': 'Julio',
          'status': 'closed',
          'savings': 130.0,
          'emergency': 34.0,
          'fines': 2.5,
          'attended': 1,
          'total_attendance': 2,
        },
      ],
    });
    ApiClient.instance.dio.httpClientAdapter = adapter;

    final meetings = await AdminService().fetchMeetings(7);

    expect(adapter.lastRequest?.path, '/admin/groups/7/meetings');
    expect(meetings, hasLength(1));
    final m = meetings.first;
    expect(m.meetingNumber, 5);
    expect(m.status, 'closed');
    expect(m.savings, 130.0);
    expect(m.emergency, 34.0);
    expect(m.fines, 2.5);
    expect(m.attended, 1);
    expect(m.totalAttendance, 2);
  });

  test('AdminGroup parses registration_mode and defaults to full', () {
    Map<String, dynamic> json(String? mode) => {
          'id': 1,
          'name': 'G',
          'description': '',
          'status': 'active',
          'share_value': 25,
          'start_date': null,
          'members': 0,
          'meetings': 0,
          'registration_mode': ?mode,
        };

    expect(AdminGroup.fromJson(json('partial')).isPartial, isTrue);
    expect(AdminGroup.fromJson(json(null)).isPartial, isFalse);
  });
}
