import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/admin_group.dart';
import '../providers/auth_provider.dart';
import '../services/admin_service.dart';
import 'admin_members_screen.dart';
import 'comparative_reports_screen.dart';

class AdminGroupsScreen extends StatefulWidget {
  const AdminGroupsScreen({super.key});

  @override
  State<AdminGroupsScreen> createState() => _AdminGroupsScreenState();
}

class _AdminGroupsScreenState extends State<AdminGroupsScreen> {
  final _service = AdminService();
  List<AdminGroup> _groups = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final groups = await _service.fetchGroups();
      if (!mounted) return;
      setState(() => _groups = groups);
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = 'No se pudieron cargar los grupos.\nRevisá tu conexión.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _confirmLogout() async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Cerrar sesión'),
        content: const Text('¿Estás seguro que querés cerrar sesión?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancelar')),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: Colors.red.shade600),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Cerrar sesión'),
          ),
        ],
      ),
    );
    if (ok == true && mounted) context.read<AuthProvider>().logout();
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    return Scaffold(
      backgroundColor: const Color(0xFFF2F4F8),
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 160,
            pinned: true,
            backgroundColor: const Color(0xFF0D2347),
            foregroundColor: Colors.white,
            automaticallyImplyLeading: false,
            actions: [
              if (auth.canViewReports)
                IconButton(
                  icon: const Icon(Icons.leaderboard_rounded),
                  tooltip: 'Comparativa de grupos',
                  onPressed: () => Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => const ComparativeReportsScreen()),
                  ),
                ),
              IconButton(
                icon: const Icon(Icons.logout_rounded),
                tooltip: 'Cerrar sesión',
                onPressed: _confirmLogout,
              ),
            ],
            flexibleSpace: FlexibleSpaceBar(
              collapseMode: CollapseMode.parallax,
              background: Container(
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [Color(0xFF0D2347), Color(0xFF1B3A6B), Color(0xFF1E4D8C)],
                  ),
                ),
                child: SafeArea(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 56, 20, 20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: Colors.amber.withValues(alpha: 0.2),
                                borderRadius: BorderRadius.circular(20),
                                border: Border.all(color: Colors.amber.withValues(alpha: 0.5)),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(Icons.admin_panel_settings_rounded, size: 13, color: Colors.amber.shade300),
                                  const SizedBox(width: 4),
                                  Text('Administrador', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Colors.amber.shade300)),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Hola, ${auth.userName ?? 'Admin'} 👋',
                          style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Seleccioná un grupo para ver su información',
                          style: TextStyle(color: Colors.white.withValues(alpha: 0.6), fontSize: 13),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              titlePadding: EdgeInsets.zero,
            ),
          ),
          if (_loading)
            const SliverFillRemaining(
              child: Center(child: CircularProgressIndicator(color: Color(0xFF1B3A6B))),
            )
          else if (_error != null)
            SliverFillRemaining(child: _buildError())
          else
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(16, 20, 16, 32),
              sliver: SliverList(
                delegate: SliverChildBuilderDelegate(
                  (_, i) => _GroupCard(group: _groups[i], onTap: () => _openGroup(_groups[i])),
                  childCount: _groups.length,
                ),
              ),
            ),
        ],
      ),
    );
  }

  void _openGroup(AdminGroup group) {
    Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => AdminMembersScreen(group: group)),
    );
  }

  Widget _buildError() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.cloud_off_rounded, size: 48, color: Colors.grey.shade400),
            const SizedBox(height: 20),
            Text(_error!, textAlign: TextAlign.center, style: TextStyle(color: Colors.grey.shade600, fontSize: 15, height: 1.5)),
            const SizedBox(height: 24),
            FilledButton.icon(
              onPressed: _load,
              style: FilledButton.styleFrom(
                backgroundColor: const Color(0xFF1B3A6B),
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('Reintentar'),
            ),
          ],
        ),
      ),
    );
  }
}

class _GroupCard extends StatelessWidget {
  final AdminGroup group;
  final VoidCallback onTap;

  const _GroupCard({required this.group, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final isActive = group.status == 'active';
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12, offset: const Offset(0, 4)),
          ],
        ),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 50,
                height: 50,
                decoration: BoxDecoration(
                  color: isActive ? const Color(0xFF1B3A6B).withValues(alpha: 0.1) : Colors.grey.shade100,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Icon(
                  Icons.groups_rounded,
                  color: isActive ? const Color(0xFF1B3A6B) : Colors.grey.shade400,
                  size: 26,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(group.name, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: Color(0xFF1A1A2E))),
                    const SizedBox(height: 3),
                    Text(group.description, maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        _chip(Icons.people_rounded, '${group.members} miembros', const Color(0xFF0D7C5F)),
                        const SizedBox(width: 8),
                        _chip(Icons.calendar_month_rounded, '${group.meetings} reuniones', const Color(0xFF7B4F9E)),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Icon(Icons.chevron_right_rounded, color: Colors.grey.shade400),
            ],
          ),
        ),
      ),
    );
  }

  Widget _chip(IconData icon, String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 11, color: color),
          const SizedBox(width: 3),
          Text(label, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: color)),
        ],
      ),
    );
  }
}
