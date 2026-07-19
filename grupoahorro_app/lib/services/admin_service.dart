import '../models/admin_group.dart';
import '../models/admin_meeting.dart';
import 'api_client.dart';

class AdminService {
  Future<List<AdminAttendanceRow>> fetchMeetingAttendance(int meetingId) async {
    final response = await ApiClient.instance.dio.get('/admin/meetings/$meetingId/attendance');
    final data = response.data;
    final List<dynamic> list = data is List ? data : (data['data'] ?? []);
    return list.map((e) => AdminAttendanceRow.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<List<AdminMeeting>> fetchMeetings(int groupId) async {
    final response = await ApiClient.instance.dio.get('/admin/groups/$groupId/meetings');
    final data = response.data;
    final List<dynamic> list = data is List ? data : (data['data'] ?? []);
    return list.map((e) => AdminMeeting.fromJson(e as Map<String, dynamic>)).toList();
  }

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
