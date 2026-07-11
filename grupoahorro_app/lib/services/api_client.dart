import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../config/app_config.dart';

class ApiClient {
  ApiClient._();
  static final ApiClient instance = ApiClient._();

  final _storage = const FlutterSecureStorage();

  late final Dio dio = Dio(BaseOptions(
    baseUrl: AppConfig.baseUrl,
    connectTimeout: const Duration(seconds: 10),
    receiveTimeout: const Duration(seconds: 10),
    headers: {'Accept': 'application/json'},
  ))
    ..interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _storage.read(key: 'auth_token');
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
    ));

  Future<void> saveToken(String token) =>
      _storage.write(key: 'auth_token', value: token);

  Future<void> deleteToken() => _storage.delete(key: 'auth_token');

  Future<String?> getToken() => _storage.read(key: 'auth_token');
}
