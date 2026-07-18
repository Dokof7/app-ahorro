import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:grupoahorro_app/models/dashboard_stats.dart';
import 'package:grupoahorro_app/utils/group_picker.dart';

DashboardGroup _group(int id, String name) => DashboardGroup(
      id: id,
      name: name,
      description: '',
      status: 'activo',
      members: 5,
      meetings: 2,
    );

/// Pumps a host screen with a button that invokes [pickGroup] and records
/// the resolved group, so tests can assert on both UI and return value.
Future<void> _pumpHost(
  WidgetTester tester,
  List<DashboardGroup> groups,
  void Function(DashboardGroup?) onResult,
) async {
  await tester.pumpWidget(
    MaterialApp(
      home: Scaffold(
        body: Builder(
          builder: (context) => Center(
            child: ElevatedButton(
              onPressed: () async {
                final result = await pickGroup(context, groups);
                onResult(result);
              },
              child: const Text('open'),
            ),
          ),
        ),
      ),
    ),
  );
}

void main() {
  testWidgets('returns null and shows a snackbar when there are no groups',
      (tester) async {
    DashboardGroup? result = _group(99, 'sentinel');
    await _pumpHost(tester, [], (r) => result = r);

    await tester.tap(find.text('open'));
    await tester.pumpAndSettle();

    expect(result, isNull);
    expect(find.text('No tienes grupos asignados todavía.'), findsOneWidget);
  });

  testWidgets('returns the single group directly without showing a sheet',
      (tester) async {
    DashboardGroup? result;
    final only = _group(7, 'Grupo Esperanza');
    await _pumpHost(tester, [only], (r) => result = r);

    await tester.tap(find.text('open'));
    await tester.pumpAndSettle();

    expect(result, same(only));
    expect(find.text('Selecciona un grupo'), findsNothing);
  });

  testWidgets('shows a sheet with all group names and returns the tapped one',
      (tester) async {
    DashboardGroup? result;
    final groups = [_group(1, 'Grupo Norte'), _group(2, 'Grupo Sur')];
    await _pumpHost(tester, groups, (r) => result = r);

    await tester.tap(find.text('open'));
    await tester.pumpAndSettle();

    expect(find.text('Selecciona un grupo'), findsOneWidget);
    expect(find.text('Grupo Norte'), findsOneWidget);
    expect(find.text('Grupo Sur'), findsOneWidget);

    await tester.tap(find.text('Grupo Sur'));
    await tester.pumpAndSettle();

    expect(result, same(groups[1]));
  });

  testWidgets('returns null when the sheet is dismissed without choosing',
      (tester) async {
    DashboardGroup? result = _group(99, 'sentinel');
    final groups = [_group(1, 'Grupo Norte'), _group(2, 'Grupo Sur')];
    await _pumpHost(tester, groups, (r) => result = r);

    await tester.tap(find.text('open'));
    await tester.pumpAndSettle();

    // Tap outside the sheet to dismiss it.
    await tester.tapAt(const Offset(10, 10));
    await tester.pumpAndSettle();

    expect(result, isNull);
  });
}
