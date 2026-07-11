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

  /// [sections] limits the response to the given section tokens
  /// (monthly, sessions, top_savers, top_attendance).
  ///
  /// - null: the param is omitted and the backend returns every section.
  /// - empty list: base-only call. The service sends the literal
  ///   `sections=none`; 'none' is not a known token, so the backend
  ///   whitelist-filters it to an empty selection and returns just the
  ///   "group" object.
  /// - non-empty list: sends `sections=a,b` and only those keys come back.
  Future<GroupReportSummary> fetchGroupSummary(
    int groupId, {
    int? year,
    List<String>? sections,
  }) async {
    final res = await _client.dio.get(
      '/reports/groups/$groupId/summary',
      queryParameters: {
        'year': year,
        if (sections != null)
          'sections': sections.isEmpty ? 'none' : sections.join(','),
      },
    );
    final data = res.data as Map<String, dynamic>;
    return GroupReportSummary.fromJson((data['data'] as Map).cast<String, dynamic>());
  }
}
