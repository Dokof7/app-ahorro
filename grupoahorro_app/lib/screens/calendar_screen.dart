import 'package:flutter/material.dart';
import '../models/meeting_scheduled_date.dart';
import '../services/meeting_service.dart';

class CalendarScreen extends StatefulWidget {
  final int groupId;

  const CalendarScreen({super.key, required this.groupId});

  @override
  State<CalendarScreen> createState() => _CalendarScreenState();
}

class _CalendarScreenState extends State<CalendarScreen> {
  final _service = MeetingService();
  List<MeetingScheduledDate> _dates = [];
  bool _loading = true;
  String? _error;

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
      final result = await _service.fetchScheduledDates(widget.groupId);
      if (!mounted) return;
      result.sort((a, b) => a.scheduledDate.compareTo(b.scheduledDate));
      setState(() => _dates = result);
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = 'No se pudieron cargar las fechas.\nRevisá tu conexión.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF2F4F8),
      appBar: AppBar(
        backgroundColor: const Color(0xFF0D2347),
        foregroundColor: Colors.white,
        title: const Text(
          'Calendario de reuniones',
          style: TextStyle(fontSize: 17, fontWeight: FontWeight.w600),
        ),
        centerTitle: false,
        elevation: 0,
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF1B3A6B)))
          : _error != null
              ? _buildError()
              : RefreshIndicator(
                  onRefresh: _load,
                  color: const Color(0xFF1B3A6B),
                  child: _dates.isEmpty ? _buildEmpty() : _buildList(),
                ),
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
              child: Icon(Icons.cloud_off_rounded, size: 48, color: Colors.grey.shade400),
            ),
            const SizedBox(height: 20),
            Text(
              _error!,
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey.shade600, fontSize: 15, height: 1.5),
            ),
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

  Widget _buildEmpty() {
    return ListView(
      children: [
        const SizedBox(height: 80),
        Center(
          child: Column(
            children: [
              Icon(Icons.event_busy_rounded, size: 64, color: Colors.grey.shade300),
              const SizedBox(height: 16),
              Text(
                'No hay fechas programadas',
                style: TextStyle(color: Colors.grey.shade500, fontSize: 15),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildList() {
    final upcoming = _dates.where((d) =>
        d.status == MeetingStatus.today ||
        d.status == MeetingStatus.soon ||
        d.status == MeetingStatus.upcoming).toList();
    final past = _dates.where((d) =>
        d.status == MeetingStatus.done ||
        d.status == MeetingStatus.missed).toList();

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 20, 16, 32),
      children: [
        if (upcoming.isNotEmpty) ...[
          _sectionHeader('Próximas reuniones', Icons.upcoming_rounded),
          const SizedBox(height: 10),
          ...upcoming.map((d) => _MeetingCard(date: d)),
          const SizedBox(height: 24),
        ],
        if (past.isNotEmpty) ...[
          _sectionHeader('Historial', Icons.history_rounded),
          const SizedBox(height: 10),
          ...past.map((d) => _MeetingCard(date: d)),
        ],
      ],
    );
  }

  Widget _sectionHeader(String title, IconData icon) {
    return Row(
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
    );
  }
}

class _MeetingCard extends StatelessWidget {
  final MeetingScheduledDate date;

  const _MeetingCard({required this.date});

  @override
  Widget build(BuildContext context) {
    final cfg = _statusConfig(date.status);
    final d = date.scheduledDate;
    final months = [
      'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
      'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ];
    final days = ['lun', 'mar', 'mié', 'jue', 'vie', 'sáb', 'dom'];
    final dayName = days[d.weekday - 1];
    final dateStr = '$dayName ${d.day} de ${months[d.month - 1]} de ${d.year}';

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: cfg.color.withValues(alpha: 0.25)),
        boxShadow: [
          BoxShadow(
            color: cfg.color.withValues(alpha: 0.08),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(
          children: [
            // Date bubble
            Container(
              width: 52,
              height: 60,
              decoration: BoxDecoration(
                color: cfg.color.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: cfg.color.withValues(alpha: 0.3)),
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    '${d.day}',
                    style: TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.w800,
                      color: cfg.color,
                      height: 1,
                    ),
                  ),
                  Text(
                    months[d.month - 1].substring(0, 3).toUpperCase(),
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w600,
                      color: cfg.color.withValues(alpha: 0.8),
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
                  Text(
                    dateStr,
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: Color(0xFF1A1A2E),
                    ),
                  ),
                  if (date.notes != null && date.notes!.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(
                      date.notes!,
                      style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(width: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
              decoration: BoxDecoration(
                color: cfg.color.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(cfg.icon, size: 12, color: cfg.color),
                  const SizedBox(width: 4),
                  Text(
                    cfg.label,
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: cfg.color,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  _StatusConfig _statusConfig(MeetingStatus status) {
    return switch (status) {
      MeetingStatus.today => _StatusConfig(
          color: const Color(0xFFD32F2F),
          label: 'Hoy',
          icon: Icons.notifications_active_rounded,
        ),
      MeetingStatus.soon => _StatusConfig(
          color: const Color(0xFFE65100),
          label: 'Pronto',
          icon: Icons.schedule_rounded,
        ),
      MeetingStatus.upcoming => _StatusConfig(
          color: const Color(0xFF1B3A6B),
          label: 'Programada',
          icon: Icons.event_rounded,
        ),
      MeetingStatus.done => _StatusConfig(
          color: const Color(0xFF0D7C5F),
          label: 'Realizada',
          icon: Icons.check_circle_rounded,
        ),
      MeetingStatus.missed => _StatusConfig(
          color: Colors.grey,
          label: 'No realizada',
          icon: Icons.cancel_rounded,
        ),
    };
  }
}

class _StatusConfig {
  final Color color;
  final String label;
  final IconData icon;
  const _StatusConfig({required this.color, required this.label, required this.icon});
}
