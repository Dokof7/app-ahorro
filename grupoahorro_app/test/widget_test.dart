// Smoke test: the app boots into the login screen when no session exists.
//
// NOTE: this file previously referenced a `MyApp` counter-demo widget left
// over from `flutter create` scaffolding, which no longer exists — the real
// root widget is `GrupoAhorroApp`. Updated to match, no behavior change.

import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';

import 'package:grupoahorro_app/main.dart';
import 'package:grupoahorro_app/providers/auth_provider.dart';

void main() {
  testWidgets('App boots and shows the login screen without a session', (WidgetTester tester) async {
    const secureStorageChannel = MethodChannel('plugins.it_nomads.com/flutter_secure_storage');
    TestWidgetsFlutterBinding.instance.defaultBinaryMessenger.setMockMethodCallHandler(
      secureStorageChannel,
      (call) async => null,
    );

    await tester.pumpWidget(
      ChangeNotifierProvider(
        create: (_) => AuthProvider(),
        child: const GrupoAhorroApp(),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Iniciar sesión'), findsOneWidget);
  });
}
