import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../models/dashboard_stats.dart';
import '../models/last_meeting_summary.dart';
import '../models/meeting_scheduled_date.dart';
import '../providers/auth_provider.dart';
import '../utils/group_picker.dart';
import '../services/dashboard_service.dart';
import '../services/meeting_service.dart';
import 'savings_screen.dart';
import 'charts_screen.dart';
import 'calendar_screen.dart';
import 'comparative_reports_screen.dart';
import 'open_meeting_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final _service = DashboardService();
  final _meetingService = MeetingService();

  DashboardStats? _stats;
  List<DashboardGroup> _groups = [];
  MeetingScheduledDate? _nextMeeting;
  LastMeetingSummary? _lastMeeting;
  bool _loading = true;
  String? _error;
  DateTime? _lastBack;

  Future<bool> _onWillPop() async {
    final now = DateTime.now();
    final isDoubleBack =
        _lastBack != null &&
        now.difference(_lastBack!) < const Duration(seconds: 2);
    if (isDoubleBack) {
      SystemNavigator.pop();
      return true;
    }
    _lastBack = now;
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Presioná atrás de nuevo para salir'),
          duration: Duration(seconds: 2),
        ),
      );
    }
    return false;
  }

  Future<bool?> _confirmLogout() {
    return showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Cerrar sesión'),
        content: const Text('¿Estás seguro que querés cerrar sesión?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: Colors.red.shade600),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Cerrar sesión'),
          ),
        ],
      ),
    );
  }

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final dashboard = await _service.fetch();
      if (!mounted) return;
      setState(() {
        _stats = dashboard.stats;
        _groups = dashboard.groups;
      });
      // Meetings and last meeting load independently — failure does not break the dashboard
      if (_groups.isNotEmpty) {
        final gid = _groups.first.id;
        try {
          final meetings = await _meetingService.fetchScheduledDates(gid);
          if (!mounted) return;
          meetings.sort((a, b) => a.scheduledDate.compareTo(b.scheduledDate));
          final next = meetings.where((m) =>
            m.status == MeetingStatus.today ||
            m.status == MeetingStatus.soon ||
            m.status == MeetingStatus.upcoming,
          ).firstOrNull;
          setState(() => _nextMeeting = next);
        } catch (_) {}
        try {
          final last = await _meetingService.fetchLastMeeting(gid);
          if (!mounted) return;
          setState(() => _lastMeeting = last);
        } catch (_) {}
      }
    } catch (e) {
      if (!mounted) return;
      setState(
        () => _error = 'No se pudo cargar el dashboard.\nRevisá tu conexión.',
      );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final name = auth.userName ?? 'usuario';

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) async {
        if (didPop) return;
        await _onWillPop();
      },
      child: Scaffold(
        backgroundColor: const Color(0xFFF2F4F8),
        drawer: _buildDrawer(context),
        body: _loading
            ? _buildLoading()
            : _error != null
            ? _buildError()
            : _buildContent(name),
      ),
    );
  }

  Widget _buildLoading() {
    return const Center(
      child: CircularProgressIndicator(color: Color(0xFF1B3A6B)),
    );
  }

  Widget _buildError() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                shape: BoxShape.circle,
              ),
              child: Icon(
                Icons.cloud_off_rounded,
                size: 48,
                color: Colors.grey.shade400,
              ),
            ),
            const SizedBox(height: 20),
            Text(
              _error!,
              textAlign: TextAlign.center,
              style: TextStyle(
                color: Colors.grey.shade600,
                fontSize: 15,
                height: 1.5,
              ),
            ),
            const SizedBox(height: 24),
            FilledButton.icon(
              onPressed: _load,
              style: FilledButton.styleFrom(
                backgroundColor: const Color(0xFF1B3A6B),
                padding: const EdgeInsets.symmetric(
                  horizontal: 24,
                  vertical: 12,
                ),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('Reintentar'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildContent(String name) {
    final s = _stats!;
    return RefreshIndicator(
      onRefresh: _load,
      color: const Color(0xFF1B3A6B),
      child: CustomScrollView(
        slivers: [
          _buildSliverHeader(name),
          // 1. Próxima reunión
          if (_nextMeeting != null) ...[
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(16, 20, 16, 8),
              sliver: SliverToBoxAdapter(
                child: _sectionHeader('Próxima reunión', Icons.event_rounded),
              ),
            ),
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
              sliver: SliverToBoxAdapter(child: _buildNextMeetingCard(_nextMeeting!)),
            ),
          ],
          // 2. Información de la última reunión (3 columnas)
          if (_lastMeeting != null) ...[
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
              sliver: SliverToBoxAdapter(
                child: _buildLastMeetingSectionHeader(_lastMeeting!),
              ),
            ),
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
              sliver: SliverToBoxAdapter(child: _buildLastMeetingRow(_lastMeeting!)),
            ),
          ],
          // 3. Resumen del grupo (chips)
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            sliver: SliverToBoxAdapter(
              child: _sectionHeader('Resumen del grupo', Icons.bar_chart_rounded),
            ),
          ),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
            sliver: SliverToBoxAdapter(child: _buildQuickStatsRow(s)),
          ),
          // 4. Fondos totales
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            sliver: SliverToBoxAdapter(
              child: _sectionHeader(
                'Fondos',
                Icons.account_balance_wallet_outlined,
              ),
            ),
          ),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
            sliver: SliverToBoxAdapter(child: _buildFundsCard(s)),
          ),
          // 5. Mis grupos
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            sliver: SliverToBoxAdapter(
              child: _sectionHeader('Mis grupos', Icons.groups_rounded),
            ),
          ),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
            sliver: SliverToBoxAdapter(child: _buildGroupsList()),
          ),
        ],
      ),
    );
  }

  Widget _buildSliverHeader(String name) {
    final now = DateTime.now();
    final months = [
      'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
      'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ];
    final dateStr = '${now.day} de ${months[now.month - 1]} de ${now.year}';
    final groupName = _groups.isNotEmpty ? _groups.first.name : null;

    return SliverAppBar(
      expandedHeight: 160,
      pinned: true,
      backgroundColor: const Color(0xFF0D2347),
      foregroundColor: Colors.white,
      leading: Builder(
        builder: (ctx) => IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => Scaffold.of(ctx).openDrawer(),
        ),
      ),
      actions: [
        IconButton(
          icon: const Icon(Icons.logout_rounded),
          tooltip: 'Cerrar sesión',
          onPressed: () async {
            final auth = context.read<AuthProvider>();
            final ok = await _confirmLogout();
            if (!mounted) return;
            if (ok == true) auth.logout();
          },
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
                  Text(
                    'Hola, $name 👋',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 22,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    groupName ?? dateStr,
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.6),
                      fontSize: 13,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
        titlePadding: EdgeInsets.zero,
      ),
    );
  }

  Widget _buildQuickStatsRow(DashboardStats s) {
    final items = [
      (
        s.totalGroups.toString(),
        'Grupos',
        Icons.groups_rounded,
        const Color(0xFF1B3A6B),
      ),
      (
        s.totalMembers.toString(),
        'Miembros',
        Icons.people_rounded,
        const Color(0xFF0D7C5F),
      ),
      (
        s.totalMeetings.toString(),
        'Reuniones',
        Icons.calendar_month_rounded,
        const Color(0xFF7B4F9E),
      ),
      (
        s.loansOverdue.toString(),
        'Vencidos',
        Icons.warning_rounded,
        const Color(0xFFD32F2F),
      ),
    ];

    return SizedBox(
      height: 96,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: items.length,
        separatorBuilder: (context, index) => const SizedBox(width: 10),
        itemBuilder: (_, i) {
          final (value, label, icon, color) = items[i];
          return Container(
            width: 100,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: color.withValues(alpha: 0.15)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(icon, color: color, size: 22),
                const Spacer(),
                Text(
                  value,
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.w800,
                    color: color,
                    height: 1,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 11,
                    color: color.withValues(alpha: 0.7),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _sectionHeader(String title, IconData icon) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        children: [
          Icon(icon, size: 16, color: const Color(0xFF1B3A6B)),
          const SizedBox(width: 6),
          Text(
            title,
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w700,
              color: Color(0xFF1B3A6B),
              letterSpacing: 0.2,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNextMeetingCard(MeetingScheduledDate meeting) {
    final d = meeting.scheduledDate;
    final months = [
      'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
      'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ];
    final days = ['lun', 'mar', 'mié', 'jue', 'vie', 'sáb', 'dom'];
    final dayName = days[d.weekday - 1];
    final dateStr = '$dayName ${d.day} de ${months[d.month - 1]} de ${d.year}';

    final (color, label, icon) = switch (meeting.status) {
      MeetingStatus.today => (const Color(0xFFD32F2F), 'Hoy', Icons.notifications_active_rounded),
      MeetingStatus.soon  => (const Color(0xFFE65100), 'Esta semana', Icons.schedule_rounded),
      _                   => (const Color(0xFF1B3A6B), 'Programada', Icons.event_rounded),
    };

    return GestureDetector(
      onTap: () => Navigator.push(
        context,
        MaterialPageRoute(builder: (_) => CalendarScreen(groupId: meeting.groupId)),
      ),
      child: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [color, color.withValues(alpha: 0.75)],
          ),
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: color.withValues(alpha: 0.3),
              blurRadius: 12,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              width: 54,
              height: 62,
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.2),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    '${d.day}',
                    style: const TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.w800,
                      color: Colors.white,
                      height: 1,
                    ),
                  ),
                  Text(
                    months[d.month - 1].substring(0, 3).toUpperCase(),
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w600,
                      color: Colors.white.withValues(alpha: 0.85),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(icon, size: 11, color: Colors.white),
                        const SizedBox(width: 4),
                        Text(
                          label,
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    dateStr,
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                  ),
                  if (meeting.notes != null && meeting.notes!.isNotEmpty) ...[
                    const SizedBox(height: 3),
                    Text(
                      meeting.notes!,
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.white.withValues(alpha: 0.8),
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ],
              ),
            ),
            Icon(Icons.chevron_right_rounded, color: Colors.white.withValues(alpha: 0.7)),
          ],
        ),
      ),
    );
  }

  Widget _buildLastMeetingSectionHeader(LastMeetingSummary last) {
    return Row(
      children: [
        Icon(Icons.history_rounded, size: 16, color: const Color(0xFF1B3A6B)),
        const SizedBox(width: 6),
        const Text(
          'Última reunión',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w700,
            color: Color(0xFF1B3A6B),
            letterSpacing: 0.2,
          ),
        ),
        const SizedBox(width: 8),
        GestureDetector(
          onTap: () => _showMeetingInfoSheet(last),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF1B3A6B), Color(0xFF1E4D8C)],
              ),
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFF1B3A6B).withValues(alpha: 0.3),
                  blurRadius: 6,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.info_outline_rounded, size: 13, color: Colors.white),
                const SizedBox(width: 4),
                const Text(
                  'Información de la última reunión',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  void _showMeetingInfoSheet(LastMeetingSummary last) {
    final months = [
      'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
      'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ];
    String dateStr = '—';
    if (last.meetingDate != null) {
      final d = DateTime.tryParse(last.meetingDate!);
      if (d != null) {
        dateStr = '${d.day} de ${months[d.month - 1]} de ${d.year}';
      }
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        padding: const EdgeInsets.fromLTRB(24, 12, 24, 32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Handle
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey.shade300,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 20),
            // Título con badge
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Color(0xFF1B3A6B), Color(0xFF1E4D8C)],
                    ),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    'Reunión N° ${last.meetingNumber ?? '—'}',
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w800,
                      color: Colors.white,
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Text(
                  dateStr,
                  style: TextStyle(fontSize: 13, color: Colors.grey.shade500),
                ),
              ],
            ),
            const SizedBox(height: 16),
            // Explicación
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: const Color(0xFF1B3A6B).withValues(alpha: 0.05),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: const Color(0xFF1B3A6B).withValues(alpha: 0.1),
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(Icons.lightbulb_rounded, size: 16, color: Colors.amber.shade700),
                      const SizedBox(width: 6),
                      const Text(
                        '¿Qué significa el número de reunión?',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF1A1A2E),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Cada vez que el grupo se reúne, la sesión se registra en orden: R1, R2, R3... '
                    'Los montos que ves corresponden a lo recaudado en esa reunión específica, '
                    'no al total acumulado.',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey.shade600,
                      height: 1.5,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            // Montos
            _sheetAmountRow(Icons.savings_rounded, 'Ahorros recaudados', last.savings, const Color(0xFF0D7C5F)),
            const SizedBox(height: 10),
            _sheetAmountRow(Icons.shield_rounded, 'Fondo de emergencia', last.emergency, const Color(0xFF1B3A6B)),
            const SizedBox(height: 10),
            _sheetAmountRow(Icons.gavel_rounded, 'Multas cobradas', last.fines, const Color(0xFFE65100)),
          ],
        ),
      ),
    );
  }

  Widget _sheetAmountRow(IconData icon, String label, double value, Color color) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Icon(icon, size: 18, color: color),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Text(
            label,
            style: const TextStyle(fontSize: 13, color: Color(0xFF444B60)),
          ),
        ),
        Text(
          'Bs. ${_fmt(value)}',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w800,
            color: color,
          ),
        ),
      ],
    );
  }

  Widget _buildLastMeetingRow(LastMeetingSummary last) {
    final label = last.meetingNumber != null ? 'R${last.meetingNumber}' : '—';
    final items = [
      (label, 'Ahorros', last.savings, const Color(0xFF0D7C5F), Icons.savings_rounded),
      ('R${last.meetingNumber ?? '—'}', 'Emergencia', last.emergency, const Color(0xFF1B3A6B), Icons.shield_rounded),
      ('R${last.meetingNumber ?? '—'}', 'Multas', last.fines, const Color(0xFFE65100), Icons.gavel_rounded),
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Leyenda
        Padding(
          padding: const EdgeInsets.only(bottom: 8),
          child: Wrap(
            spacing: 12,
            children: [
              _legend('R1, R2...', 'Número de reunión'),
            ],
          ),
        ),
        Row(
          children: List.generate(items.length, (i) {
            final (ref, sublabel, value, color, icon) = items[i];
            return Expanded(
              child: Container(
                margin: EdgeInsets.only(right: i < items.length - 1 ? 8 : 0),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.07),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: color.withValues(alpha: 0.2)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(icon, size: 14, color: color),
                        const SizedBox(width: 4),
                        Text(
                          ref,
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: color,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Bs. ${_fmt(value)}',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w800,
                        color: color,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      sublabel,
                      style: TextStyle(
                        fontSize: 10,
                        color: color.withValues(alpha: 0.7),
                      ),
                    ),
                  ],
                ),
              ),
            );
          }),
        ),
      ],
    );
  }

  Widget _legend(String code, String description) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
          decoration: BoxDecoration(
            color: const Color(0xFF1B3A6B).withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(6),
          ),
          child: Text(
            code,
            style: const TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w700,
              color: Color(0xFF1B3A6B),
            ),
          ),
        ),
        const SizedBox(width: 4),
        Text(
          description,
          style: TextStyle(fontSize: 10, color: Colors.grey.shade600),
        ),
      ],
    );
  }

  Widget _buildFundsCard(DashboardStats s) {
    final items = [
      (
        'Total Ahorrado',
        s.totalSavings,
        const Color(0xFF0D7C5F),
        Icons.savings_rounded,
      ),
      (
        'Fondo de Emergencia',
        s.totalEmergency,
        const Color(0xFF1B3A6B),
        Icons.shield_rounded,
      ),
      ('Multas', s.totalFines, const Color(0xFFE65100), Icons.gavel_rounded),
      (
        'Préstamos Pendientes',
        s.loansPending,
        const Color(0xFFD32F2F),
        Icons.pending_actions_rounded,
      ),
      (
        'Préstamos Pagados',
        s.loansPaid,
        const Color(0xFF0D7C5F),
        Icons.check_circle_rounded,
      ),
      (
        'Gastos Bancarios',
        s.bankExpenses,
        Colors.blueGrey,
        Icons.account_balance_rounded,
      ),
      (
        'Membresías',
        s.totalMembership,
        const Color(0xFF6A1B9A),
        Icons.card_membership_rounded,
      ),
    ];

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: List.generate(items.length, (i) {
          final (label, value, color, icon) = items[i];
          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 13,
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: color.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Icon(icon, color: color, size: 18),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        label,
                        style: const TextStyle(
                          fontSize: 14,
                          color: Color(0xFF444B60),
                        ),
                      ),
                    ),
                    Text(
                      'Bs. ${_fmt(value)}',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: color,
                      ),
                    ),
                  ],
                ),
              ),
              if (i < items.length - 1)
                Divider(
                  height: 1,
                  indent: 52,
                  endIndent: 16,
                  color: Colors.grey.shade100,
                ),
            ],
          );
        }),
      ),
    );
  }

  Widget _buildGroupsList() {
    if (_groups.isEmpty) {
      return Container(
        padding: const EdgeInsets.all(32),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          children: [
            Icon(Icons.groups_outlined, size: 48, color: Colors.grey.shade300),
            const SizedBox(height: 12),
            Text(
              'Sin grupos registrados',
              style: TextStyle(color: Colors.grey.shade500, fontSize: 14),
            ),
          ],
        ),
      );
    }

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: List.generate(_groups.length, (i) {
          final g = _groups[i];
          final isActive = g.status == 'active';
          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 12,
                ),
                child: Row(
                  children: [
                    Container(
                      width: 42,
                      height: 42,
                      decoration: BoxDecoration(
                        color: isActive
                            ? const Color(0xFF0D7C5F).withValues(alpha: 0.1)
                            : Colors.grey.shade100,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(
                        Icons.groups_rounded,
                        color: isActive
                            ? const Color(0xFF0D7C5F)
                            : Colors.grey.shade400,
                        size: 22,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            g.name,
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              color: Color(0xFF1A1A2E),
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            g.description,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey.shade500,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 3,
                          ),
                          decoration: BoxDecoration(
                            color: isActive
                                ? const Color(0xFF0D7C5F).withValues(alpha: 0.1)
                                : Colors.grey.shade100,
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Text(
                            isActive ? 'Activo' : 'Inactivo',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: isActive
                                  ? const Color(0xFF0D7C5F)
                                  : Colors.grey,
                            ),
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '${g.members} miembros · ${g.meetings} reuniones',
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey.shade400,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              if (i < _groups.length - 1)
                Divider(
                  height: 1,
                  indent: 70,
                  endIndent: 16,
                  color: Colors.grey.shade100,
                ),
            ],
          );
        }),
      ),
    );
  }

  Widget _buildDrawer(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    return Drawer(
      child: Column(
        children: [
          Container(
            width: double.infinity,
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [Color(0xFF0D2347), Color(0xFF1B3A6B)],
              ),
            ),
            padding: const EdgeInsets.fromLTRB(20, 56, 20, 24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                CircleAvatar(
                  radius: 30,
                  backgroundColor: Colors.white.withValues(alpha: 0.2),
                  child: Text(
                    (auth.userName ?? 'U')[0].toUpperCase(),
                    style: const TextStyle(
                      fontSize: 26,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  auth.userName ?? '',
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                    fontSize: 16,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  auth.userEmail ?? '',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.6),
                    fontSize: 13,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 8),
          _drawerItem(
            icon: Icons.dashboard_rounded,
            label: 'Dashboard',
            selected: true,
            onTap: () => Navigator.pop(context),
          ),
          _drawerItem(
            icon: Icons.savings_rounded,
            label: 'Mis ahorros',
            onTap: () {
              Navigator.pop(context);
              Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const SavingsScreen()),
              );
            },
          ),
          _drawerItem(
            icon: Icons.calendar_month_rounded,
            label: 'Calendario',
            onTap: () => _openGroupScreen(
              (groupId) => CalendarScreen(groupId: groupId),
            ),
          ),
          _drawerItem(
            icon: Icons.groups_rounded,
            label: 'Reunión',
            onTap: () => _openGroupScreen(
              (groupId) => OpenMeetingScreen(groupId: groupId),
            ),
          ),
          _drawerItem(
            icon: Icons.bar_chart_rounded,
            label: 'Estadísticas',
            onTap: () {
              Navigator.pop(context);
              Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const ChartsScreen()),
              );
            },
          ),
          if (auth.canViewReports)
            _drawerItem(
              icon: Icons.leaderboard_rounded,
              label: 'Comparativa de grupos',
              onTap: () {
                Navigator.pop(context);
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => const ComparativeReportsScreen()),
                );
              },
            ),
          const Spacer(),
          Divider(color: Colors.grey.shade200),
          _drawerItem(
            icon: Icons.logout_rounded,
            label: 'Cerrar sesión',
            color: Colors.red.shade600,
            onTap: () async {
              final auth = context.read<AuthProvider>();
              Navigator.pop(context);
              final ok = await _confirmLogout();
              if (!mounted) return;
              if (ok == true) auth.logout();
            },
          ),
          const SizedBox(height: 12),
        ],
      ),
    );
  }

  /// Closes the drawer, resolves the target group (asking the user when
  /// they belong to more than one), then opens the given screen for it.
  /// When the user comes back the dashboard reloads: screens reached from
  /// here (meetings, calendar) can change the numbers shown on this screen.
  Future<void> _openGroupScreen(Widget Function(int groupId) builder) async {
    Navigator.pop(context);
    final group = await pickGroup(context, _groups);
    if (group == null || !mounted) return;
    await Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => builder(group.id)),
    );
    if (mounted) _load();
  }

  Widget _drawerItem({
    required IconData icon,
    required String label,
    required VoidCallback onTap,
    bool selected = false,
    Color? color,
  }) {
    final effectiveColor =
        color ?? (selected ? const Color(0xFF1B3A6B) : Colors.grey.shade700);
    return ListTile(
      leading: Icon(icon, color: effectiveColor, size: 22),
      title: Text(
        label,
        style: TextStyle(
          color: effectiveColor,
          fontWeight: selected ? FontWeight.w600 : FontWeight.normal,
          fontSize: 15,
        ),
      ),
      selected: selected,
      selectedTileColor: const Color(0xFF1B3A6B).withValues(alpha: 0.07),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16),
      onTap: onTap,
    );
  }

  String _fmt(double v) {
    final parts = v.toStringAsFixed(2).split('.');
    final intPart = parts[0].replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (m) => '${m[1]},',
    );
    return '$intPart.${parts[1]}';
  }
}
