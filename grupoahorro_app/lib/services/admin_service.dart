import '../models/admin_group.dart';
import 'api_client.dart';

class AdminService {
  Future<List<AdminGroup>> fetchGroups() async {
    final response = await ApiClient.instance.dio.get('/admin/groups');
    final data = response.data;
    final List<dynamic> list = data is List ? data : (data['data'] ?? []);
    return list.map((e) => AdminGroup.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<List<AdminMember>> fetchMembers(int groupId) async {
    final response = await ApiClient.instance.dio.get('/admin/groups/$groupId/members');
    final data = response.data;
    final List<dynamic> list = data is List ? data : (data['data'] ?? []);
    return list.map((e) => AdminMember.fromJson(e as Map<String, dynamic>)).toList();
  }
}
