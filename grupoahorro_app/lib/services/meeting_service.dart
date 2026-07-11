import '../models/last_meeting_summary.dart';
import '../models/meeting_scheduled_date.dart';
import 'api_client.dart';

class MeetingService {
  Future<List<MeetingScheduledDate>> fetchScheduledDates(int groupId) async {
    final response = await ApiClient.instance.dio.get(
      '/meeting-scheduled-dates',
      queryParameters: {'group_id': groupId},
    );
    final data = response.data;
    final List<dynamic> list = data is List
        ? data
        : (data['data'] ?? data['dates'] ?? []);
    return list
        .map((e) => MeetingScheduledDate.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<LastMeetingSummary> fetchLastMeeting(int groupId) async {
    final response = await ApiClient.instance.dio.get(
      '/dashboard/last-meeting',
      queryParameters: {'group_id': groupId},
    );
    return LastMeetingSummary.fromJson(response.data as Map<String, dynamic>);
  }
}
