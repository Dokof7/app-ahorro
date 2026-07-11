import 'package:dio/dio.dart';
import 'api_client.dart';

class AuthService {
  final _client = ApiClient.instance;

  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await _client.dio.post('/login', data: {
      'email': email,
      'password': password,
    });
    final token = response.data['token'] as String;
    await _client.saveToken(token);
    return response.data as Map<String, dynamic>;
  }

  Future<void> logout() async {
    try {
      await _client.dio.post('/logout');
    } on DioException {
      // best-effort
    } finally {
      await _client.deleteToken();
    }
  }

  Future<bool> hasSession() async {
    final token = await _client.getToken();
    return token != null;
  }

  Future<Map<String, dynamic>> fetchUser() async {
    final response = await _client.dio.get('/user');
    return response.data as Map<String, dynamic>;
  }
}
