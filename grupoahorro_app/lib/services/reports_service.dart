import 'api_client.dart';
import '../models/group_comparison.dart';
import '../models/group_report_summary.dart';

class ReportsService {
  final _client = ApiClient.instance;

  Future<List<GroupComparison>> fetchGroupsComparison({
    int? year,
    String? dateFrom,
    String? dateTo,
  }) async {
    final res = await _client.dio.get('/reports/groups-comparison', queryParameters: {
      'year': year,
      'date_from': dateFrom,
      'date_to': dateTo,
    });
    final data = res.data as Map<String, dynamic>;
    return (data['data'] as List? ?? const [])
        .map((e) => GroupComparison.fromJson((e as Map).cast<String, dynamic>()))
        .toList();
  }

  Future<GroupReportSummary> fetchGroupSummary(int groupId, {int? year}) async {
    final res = await _client.dio.get(
      '/reports/groups/$groupId/summary',
      queryParameters: {'year': year},
    );
    final data = res.data as Map<String, dynamic>;
    return GroupReportSummary.fromJson((data['data'] as Map).cast<String, dynamic>());
  }
}
