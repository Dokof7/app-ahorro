import 'api_client.dart';
import '../models/dashboard_stats.dart';

class DashboardService {
  final _client = ApiClient.instance;

  Future<({DashboardStats stats, List<DashboardGroup> groups})> fetch() async {
    final res = await _client.dio.get('/dashboard');
    final data = res.data as Map<String, dynamic>;

    final stats = DashboardStats.fromJson(data['stats'] as Map<String, dynamic>);
    final groups = (data['groups'] as List)
        .map((g) => DashboardGroup.fromJson(g as Map<String, dynamic>))
        .toList();

    return (stats: stats, groups: groups);
  }
}
