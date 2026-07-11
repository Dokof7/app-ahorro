import 'api_client.dart';
import '../models/savings.dart';

class SavingsService {
  final _client = ApiClient.instance;

  Future<({SavingsTotals totals, MembershipInfo membership, List<SavingsContribution> contributions, String? memberName, String? groupName})> fetch() async {
    final res  = await _client.dio.get('/savings');
    final data = res.data as Map<String, dynamic>;

    final totals       = SavingsTotals.fromJson(data['total'] as Map<String, dynamic>);
    final membership   = MembershipInfo.fromJson(data['membership'] as Map<String, dynamic>);
    final contributions = (data['contributions'] as List)
        .map((c) => SavingsContribution.fromJson(c as Map<String, dynamic>))
        .toList();
    final member     = data['member'] as Map<String, dynamic>?;
    final memberName = member?['full_name'] as String?;
    final rawGroup   = member?['group'];
    final groupName  = rawGroup is Map ? rawGroup['name'] as String? : rawGroup as String?;

    return (totals: totals, membership: membership, contributions: contributions, memberName: memberName, groupName: groupName);
  }
}
