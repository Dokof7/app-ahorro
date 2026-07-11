import 'package:flutter/material.dart';
import '../services/auth_service.dart';

enum AuthStatus { unknown, authenticated, unauthenticated }

class AuthProvider extends ChangeNotifier {
  final _service = AuthService();

  AuthStatus _status = AuthStatus.unknown;
  String? _userName;
  String? _userEmail;
  String? _userRole;
  String? _error;
  bool _loading = false;

  AuthStatus get status => _status;
  String? get userName => _userName;
  String? get userEmail => _userEmail;
  String? get userRole => _userRole;
  bool get isAdmin => _userRole == 'admin';
  bool get canViewReports => _userRole == 'admin' || _userRole == 'admin_grupo';
  String? get error => _error;
  bool get loading => _loading;

  Future<void> checkSession() async {
    final has = await _service.hasSession();
    if (!has) {
      _status = AuthStatus.unauthenticated;
      notifyListeners();
      return;
    }
    try {
      final data = await _service.fetchUser();
      _userName  = data['name'] as String?;
      _userEmail = data['email'] as String?;
      _userRole  = data['role'] as String?;
      _status = AuthStatus.authenticated;
    } catch (_) {
      _status = AuthStatus.unauthenticated;
    }
    notifyListeners();
  }

  Future<bool> login(String email, String password) async {
    _loading = true;
    _error = null;
    notifyListeners();

    try {
      final data = await _service.login(email, password);
      _userName  = data['user']?['name'] as String?;
      _userEmail = data['user']?['email'] as String?;
      _userRole  = data['user']?['role'] as String?;
      _status = AuthStatus.authenticated;
      return true;
    } catch (e) {
      _error = _parseError(e);
      _status = AuthStatus.unauthenticated;
      return false;
    } finally {
      _loading = false;
      notifyListeners();
    }
  }

  Future<void> logout() async {
    await _service.logout();
    _status    = AuthStatus.unauthenticated;
    _userName  = null;
    _userEmail = null;
    _userRole  = null;
    notifyListeners();
  }

  String _parseError(Object e) {
    if (e.toString().contains('401') || e.toString().contains('422')) {
      return 'Email o contraseña incorrectos.';
    }
    if (e.toString().contains('SocketException') ||
        e.toString().contains('connection')) {
      return 'Sin conexión. Revisá tu red.';
    }
    return 'Error inesperado. Intentá de nuevo.';
  }
}
