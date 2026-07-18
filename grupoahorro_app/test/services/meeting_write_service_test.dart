import 'dart:async';
import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:grupoahorro_app/services/api_client.dart';
import 'package:grupoahorro_app/services/meeting_write_service.dart';

/// Minimal [HttpClientAdapter] stub so [MeetingWriteService] can be tested
/// against canned responses without a real network call or a mocking
/// package (none is installed in this project).
class _StubAdapter implements HttpClientAdapter {
  final int statusCode;
  final Map<String, dynamic> body;
  RequestOptions? lastRequest;
  dynamic lastRequestBody;

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
    lastRequestBody = options.data;
    final bytes = utf8.encode(jsonEncode(body));
    return ResponseBody.fromBytes(
      bytes,
      statusCode,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  // ApiClient's request interceptor reads the auth token from
  // flutter_secure_storage on every request. Stub its method channel so
  // that read returns null instead of throwing (no platform available in
  // the unit test host).
  const secureStorageChannel = MethodChannel('plugins.it_nomads.com/flutter_secure_storage');
  TestWidgetsFlutterBinding.instance.defaultBinaryMessenger.setMockMethodCallHandler(
    secureStorageChannel,
    (call) async => call.method == 'read' ? null : null,
  );

  group('MeetingWriteService.fetchOpenMeeting', () {
    test('returns an OpenMeeting when the group has one open', () async {
      final adapter = _StubAdapter(statusCode: 200, body: {
        'meeting': {
          'id': 1,
          'meeting_number': 1,
          'meeting_date': '2026-07-16',
          'month': 'Julio',
          'status': 'open',
        },
        'is_partial': false,
        'contributions': [],
        'attendances': [],
        'totals': null,
      });
      ApiClient.instance.dio.httpClientAdapter = adapter;

      final service = MeetingWriteService();
      final meeting = await service.fetchOpenMeeting(3);

      expect(meeting, isNotNull);
      expect(meeting!.id, 1);
      expect(adapter.lastRequest?.path, '/meetings/open');
      expect(adapter.lastRequest?.queryParameters['group_id'], 3);
    });

    test('returns null when no meeting is open', () async {
      final adapter = _StubAdapter(statusCode: 200, body: {
        'meeting': null,
        'is_partial': false,
        'contributions': [],
        'attendances': [],
        'totals': null,
      });
      ApiClient.instance.dio.httpClientAdapter = adapter;

      final service = MeetingWriteService();
      final meeting = await service.fetchOpenMeeting(3);

      expect(meeting, isNull);
    });
  });

  group('MeetingWriteService.openMeeting', () {
    test('POSTs meeting_date and parses the 201 envelope', () async {
      final adapter = _StubAdapter(statusCode: 201, body: {
        'meeting': {
          'id': 2,
          'meeting_number': 5,
          'meeting_date': '2026-07-20',
          'month': 'Julio',
          'status': 'open',
        },
        'is_partial': true,
        'contributions': [],
        'attendances': [],
        'totals': {'shares': 0, 'savings': 0, 'emergency_fund': 0, 'fine': 0, 'observations': null},
      });
      ApiClient.instance.dio.httpClientAdapter = adapter;

      final service = MeetingWriteService();
      final meeting = await service.openMeeting(3, '2026-07-20');

      expect(meeting.id, 2);
      expect(meeting.isPartial, true);
      expect(adapter.lastRequest?.path, '/groups/3/meetings');
      expect(adapter.lastRequestBody, {'meeting_date': '2026-07-20'});
    });

    test('propagates a 409 DioException for the caller to handle', () async {
      final adapter = _StubAdapter(statusCode: 409, body: {
        'error': 'Ya hay una reunión abierta para este grupo.',
        'reason': 'meeting_already_open',
        'meeting': {'id': 9, 'meeting_number': 2, 'meeting_date': '2026-07-01', 'month': 'Julio', 'is_partial': false},
      });
      ApiClient.instance.dio.httpClientAdapter = adapter;

      final service = MeetingWriteService();

      expect(
        () => service.openMeeting(3, '2026-07-20'),
        throwsA(isA<DioException>()),
      );
    });
  });

  group('MeetingWriteService.closeMeeting', () {
    test('posts to /meetings/{id}/close', () async {
      final adapter = _StubAdapter(statusCode: 200, body: {
        'meeting': {'id': 9, 'meeting_number': 2, 'status': 'closed'},
      });
      ApiClient.instance.dio.httpClientAdapter = adapter;

      final service = MeetingWriteService();
      await service.closeMeeting(9);

      expect(adapter.lastRequest?.path, '/meetings/9/close');
      expect(adapter.lastRequest?.method, 'POST');
    });

    test('propagates a 403 DioException when the meeting is already closed', () async {
      final adapter = _StubAdapter(statusCode: 403, body: {
        'error': 'Reunión cerrada, no se puede editar.',
        'reason': 'closed',
      });
      ApiClient.instance.dio.httpClientAdapter = adapter;

      final service = MeetingWriteService();

      expect(
        () => service.closeMeeting(9),
        throwsA(isA<DioException>()),
      );
    });
  });
}
