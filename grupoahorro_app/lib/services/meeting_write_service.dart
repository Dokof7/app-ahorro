import 'api_client.dart';
import '../models/open_meeting.dart';

/// Mobile write API for meeting data entry: loading/creating the group's
/// open meeting and submitting attendance/contributions against it.
class MeetingWriteService {
  final _client = ApiClient.instance;

  /// `GET /meetings/open?group_id=` — loads the group's currently open
  /// meeting with its pre-seeded rows, or null if none is open.
  Future<OpenMeeting?> fetchOpenMeeting(int groupId) async {
    final res = await _client.dio.get(
      '/meetings/open',
      queryParameters: {'group_id': groupId},
    );
    return OpenMeeting.fromEnvelope(res.data as Map<String, dynamic>);
  }

  /// `POST /groups/{group}/meetings` — opens (creates) a new meeting for the
  /// group. On 201 the payload is shape-identical to [fetchOpenMeeting], so
  /// it hydrates the same [OpenMeeting] model. On 409 (another user already
  /// opened one) the caller should catch the [DioException] and build an
  /// [ApiError]/[MeetingConflict] from it (design ADR-8).
  Future<OpenMeeting> openMeeting(int groupId, String meetingDate) async {
    final res = await _client.dio.post(
      '/groups/$groupId/meetings',
      data: {'meeting_date': meetingDate},
    );
    final meeting = OpenMeeting.fromEnvelope(res.data as Map<String, dynamic>);
    if (meeting == null) {
      throw StateError('El servidor no devolvió la reunión creada.');
    }
    return meeting;
  }

  /// `POST /meetings/{meeting}/contributions/bulk` — non-partial groups:
  /// submits one row per member.
  Future<void> submitContributions(
    int meetingId,
    List<Map<String, dynamic>> contributions,
  ) async {
    await _client.dio.post(
      '/meetings/$meetingId/contributions/bulk',
      data: {'contributions': contributions},
    );
  }

  /// `POST /meetings/{meeting}/contributions/bulk` — partial groups: submits
  /// a single group-level total.
  Future<void> submitGroupTotals(
    int meetingId, {
    required int shares,
    required double emergencyFund,
    required double fine,
    String? observations,
  }) async {
    await _client.dio.post(
      '/meetings/$meetingId/contributions/bulk',
      data: {
        'shares': shares,
        'emergency_fund': emergencyFund,
        'fine': fine,
        'observations': observations,
      },
    );
  }

  /// `PUT /meetings/{meeting}/attendance/bulk`.
  Future<void> submitAttendance(
    int meetingId,
    List<Map<String, dynamic>> attendances,
  ) async {
    await _client.dio.put(
      '/meetings/$meetingId/attendance/bulk',
      data: {'attendances': attendances},
    );
  }
}
