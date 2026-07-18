import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:grupoahorro_app/screens/open_meeting_screen.dart';
import 'package:grupoahorro_app/services/api_client.dart';

/// Minimal [HttpClientAdapter] stub — see
/// test/services/meeting_write_service_test.dart for the same pattern.
class _StubAdapter implements HttpClientAdapter {
  final int statusCode;
  final Map<String, dynamic> body;

  _StubAdapter({required this.statusCode, required this.body});

  @override
  void close({bool force = false}) {}

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<List<int>>? requestStream,
    Future<void>? cancelFuture,
  ) async {
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

  const secureStorageChannel = MethodChannel('plugins.it_nomads.com/flutter_secure_storage');
  TestWidgetsFlutterBinding.instance.defaultBinaryMessenger.setMockMethodCallHandler(
    secureStorageChannel,
    (call) async => null,
  );

  testWidgets('shows the create-meeting state when no meeting is open', (tester) async {
    ApiClient.instance.dio.httpClientAdapter = _StubAdapter(statusCode: 200, body: {
      'meeting': null,
      'is_partial': false,
      'contributions': [],
      'attendances': [],
      'totals': null,
    });

    await tester.pumpWidget(const MaterialApp(home: OpenMeetingScreen(groupId: 1)));
    await tester.pumpAndSettle();

    expect(find.text('No hay una reunión abierta'), findsOneWidget);
    expect(find.text('Abrir reunión'), findsOneWidget);
    expect(find.byType(TabBar), findsNothing);
  });

  testWidgets('shows the Asistencia/Aportes tabs and header when a meeting is open', (tester) async {
    ApiClient.instance.dio.httpClientAdapter = _StubAdapter(statusCode: 200, body: {
      'meeting': {
        'id': 1,
        'meeting_number': 3,
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
          'shares': 0,
          'savings': 0,
          'emergency_fund': 0,
          'fine': 0,
          'total': 0,
          'confirmed': false,
          'observations': null,
        },
      ],
      'attendances': [
        {
          'id': 1,
          'member_id': 5,
          'member': {'id': 5, 'full_name': 'Juana Pérez'},
          'status': 'absent',
          'observations': null,
        },
      ],
      'totals': null,
    });

    await tester.pumpWidget(const MaterialApp(home: OpenMeetingScreen(groupId: 1)));
    await tester.pumpAndSettle();

    expect(find.text('Reunión N° 3'), findsOneWidget);
    expect(find.text('Asistencia'), findsOneWidget);
    expect(find.text('Aportes'), findsOneWidget);
    expect(find.text('Juana Pérez'), findsOneWidget);
  });
}
