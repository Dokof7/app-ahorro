import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:grupoahorro_app/models/admin_group.dart';
import 'package:grupoahorro_app/screens/admin_members_screen.dart';
import 'package:grupoahorro_app/services/api_client.dart';

/// Path-routing stub — same pattern as home_screen_test.dart.
class _RoutingStubAdapter implements HttpClientAdapter {
  final Map<String, Map<String, dynamic>> routes;

  _RoutingStubAdapter(this.routes);

  @override
  void close({bool force = false}) {}

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<List<int>>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    final match = routes.entries.where((e) => options.path == e.key).toList();
    return ResponseBody.fromBytes(
      utf8.encode(jsonEncode(match.isEmpty ? {} : match.first.value)),
      match.isEmpty ? 404 : 200,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }
}

AdminGroup _group({required String mode}) => AdminGroup.fromJson({
      'id': 7,
      'name': 'pruebaa',
      'description': '',
      'status': 'active',
      'share_value': 10,
      'start_date': null,
      'registration_mode': mode,
      'members': 2,
      'meetings': 1,
    });

const _memberJson = {
  'id': 1,
  'full_name': 'Carlitos',
  'document_number': '123',
  'phone': null,
  'status': 'active',
  'membership_paid': true,
  'join_date': null,
  'total_savings': 0,
  'total_emergency': 0,
  'total_fines': 0,
  'total_shares': 0,
  'attended': 1,
  'absences': 0,
  'excused_absences': 0,
};

const _meetingJson = {
  'id': 27,
  'meeting_number': 5,
  'meeting_date': '2026-07-18',
  'month': 'Julio',
  'status': 'closed',
  'savings': 130.0,
  'emergency': 34.0,
  'fines': 0.0,
  'attended': 1,
  'total_attendance': 2,
};

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  const secureStorageChannel = MethodChannel('plugins.it_nomads.com/flutter_secure_storage');
  TestWidgetsFlutterBinding.instance.defaultBinaryMessenger.setMockMethodCallHandler(
    secureStorageChannel,
    (call) async => null,
  );

  void stubEndpoints() {
    ApiClient.instance.dio.httpClientAdapter = _RoutingStubAdapter({
      '/admin/groups/7/members': {
        'data': [_memberJson],
      },
      '/admin/groups/7/meetings': {
        'data': [_meetingJson],
      },
    });
  }

  testWidgets('partial group: totals come from meetings and the per-meeting section shows', (tester) async {
    stubEndpoints();

    await tester.pumpWidget(
      MaterialApp(home: AdminMembersScreen(group: _group(mode: 'partial'))),
    );
    await tester.pumpAndSettle();

    // Group totals from the meetings endpoint, not the (zeroed) member sums.
    expect(find.text('Bs. 130.00'), findsOneWidget);
    expect(find.text('Bs. 34.00'), findsOneWidget);

    // Per-meeting section with amounts and attendance.
    expect(find.text('Reuniones'), findsOneWidget);
    expect(find.text('Reunión N° 5 · 2026-07-18'), findsOneWidget);
    expect(find.text('Asistencia: 1/2'), findsOneWidget);
  });

  testWidgets('full group: no per-meeting section', (tester) async {
    stubEndpoints();

    await tester.pumpWidget(
      MaterialApp(home: AdminMembersScreen(group: _group(mode: 'full'))),
    );
    await tester.pumpAndSettle();

    expect(find.text('Reuniones'), findsNothing);
    expect(find.text('Reunión N° 5 · 2026-07-18'), findsNothing);
  });
}
