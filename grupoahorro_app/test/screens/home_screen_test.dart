import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';

import 'package:grupoahorro_app/providers/auth_provider.dart';
import 'package:grupoahorro_app/screens/home_screen.dart';
import 'package:grupoahorro_app/services/api_client.dart';

/// Path-routing stub: serves a canned body per path prefix and records every
/// request, so tests can assert on refetch behavior.
class _RoutingStubAdapter implements HttpClientAdapter {
  final Map<String, Map<String, dynamic>> routes;
  final List<RequestOptions> requests = [];

  _RoutingStubAdapter(this.routes);

  @override
  void close({bool force = false}) {}

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<List<int>>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    requests.add(options);
    final match = routes.entries
        .where((e) => options.path.startsWith(e.key))
        .toList();
    final body = match.isEmpty ? <String, dynamic>{} : match.first.value;
    final status = match.isEmpty ? 404 : 200;
    return ResponseBody.fromBytes(
      utf8.encode(jsonEncode(body)),
      status,
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

  testWidgets('dashboard reloads after returning from the meeting screen', (tester) async {
    final adapter = _RoutingStubAdapter({
      '/dashboard': {
        'stats': {'total_groups': 1, 'total_members': 2, 'total_meetings': 5},
        'groups': [
          {
            'id': 1,
            'name': 'Grupo Uno',
            'description': '',
            'status': 'active',
            'members': 2,
            'meetings': 5,
          },
        ],
      },
      '/meetings/open': {
        'meeting': null,
        'is_partial': true,
        'contributions': [],
        'attendances': [],
        'totals': null,
      },
    });
    ApiClient.instance.dio.httpClientAdapter = adapter;

    await tester.pumpWidget(
      ChangeNotifierProvider(
        create: (_) => AuthProvider(),
        child: const MaterialApp(home: HomeScreen()),
      ),
    );
    await tester.pumpAndSettle();

    dashboardRequests() =>
        adapter.requests.where((r) => r.path == '/dashboard').length;
    expect(dashboardRequests(), 1);

    // Drawer → Reunión (single group: picker navigates directly).
    tester.state<ScaffoldState>(find.byType(Scaffold).first).openDrawer();
    await tester.pumpAndSettle();
    await tester.tap(find.text('Reunión'));
    await tester.pumpAndSettle();
    expect(find.text('Abrir reunión'), findsOneWidget);

    // Coming back must refetch the dashboard — data may have changed.
    await tester.pageBack();
    await tester.pumpAndSettle();
    expect(dashboardRequests(), 2);
  });
}
