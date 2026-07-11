import 'package:flutter/material.dart';
import '../models/admin_group.dart';
import '../services/admin_service.dart';

class AdminMembersScreen extends StatefulWidget {
  final AdminGroup group;

  const AdminMembersScreen({super.key, required this.group});

  @override
  State<AdminMembersScreen> createState() => _AdminMembersScreenState();
}

class _AdminMembersScreenState extends State<AdminMembersScreen> {
  final _service = AdminService();
  List<AdminMember> _members = [];
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
      final members = await _service.fetchMembers(widget.group.id);
      if (!mounted) return;
      setState(() => _members = members);
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = 'No se pudieron cargar los miembros.\nRevisá tu conexión.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String _fmt(double v) {
    final parts = v.toStringAsFixed(2).split('.');
    final intPart = parts[0].replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (m) => '${m[1]},',
    );
    return '$intPart.${parts[1]}';
  }

  @override
  Widget build(BuildContext context) {
    final totalSavings = _members.fold(0.0, (s, m) => s + m.totalSavings);
    final totalEmergency = _members.fold(0.0, (s, m) => s + m.totalEmergency);
    final totalFines = _members.fold(0.0, (s, m) => s + m.totalFines);

    return Scaffold(
      backgroundColor: const Color(0xFFF2F4F8),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF1B3A6B)))
          : _error != null
              ? _buildError()
              : RefreshIndicator(
                  onRefresh: _load,
                  color: const Color(0xFF1B3A6B),
                  child: CustomScrollView(
                    slivers: [
                      _buildHeader(),
                      // Totales del grupo
                      SliverPadding(
                        padding: const EdgeInsets.fromLTRB(16, 20, 16, 8),
                        sliver: SliverToBoxAdapter(child: _buildGroupTotals(totalSavings, totalEmergency, totalFines)),
                      ),
                      // Título lista
                      SliverPadding(
                        padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
                        sliver: SliverToBoxAdapter(child: _sectionHeader('Miembros', Icons.people_rounded)),
                      ),
                      // Lista
                      SliverPadding(
                        padding: const EdgeInsets.fromLTRB(16, 0, 16, 32),
                        sliver: SliverList(
                          delegate: SliverChildBuilderDelegate(
                            (_, i) => _MemberCard(member: _members[i], fmt: _fmt),
                            childCount: _members.length,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
    );
  }

  Widget _buildHeader() {
    return SliverAppBar(
      expandedHeight: 140,
      pinned: true,
      backgroundColor: const Color(0xFF0D2347),
      foregroundColor: Colors.white,
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
                  Text(widget.group.name,
                      style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 4),
                  Text('${widget.group.members} miembros · ${widget.group.meetings} reuniones',
                      style: TextStyle(color: Colors.white.withValues(alpha: 0.6), fontSize: 13)),
                ],
              ),
            ),
          ),
        ),
        titlePadding: EdgeInsets.zero,
      ),
    );
  }

  Widget _buildGroupTotals(double savings, double emergency, double fines) {
    final items = [
      ('Ahorros', savings, const Color(0xFF0D7C5F), Icons.savings_rounded),
      ('Emergencia', emergency, const Color(0xFF1B3A6B), Icons.shield_rounded),
      ('Multas', fines, const Color(0xFFE65100), Icons.gavel_rounded),
    ];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _sectionHeader('Totales del grupo', Icons.account_balance_wallet_outlined),
        const SizedBox(height: 10),
        Row(
          children: List.generate(items.length, (i) {
            final (label, value, color, icon) = items[i];
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
                    Icon(icon, size: 16, color: color),
                    const SizedBox(height: 6),
                    Text('Bs. ${_fmt(value)}',
                        style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: color)),
                    const SizedBox(height: 2),
                    Text(label, style: TextStyle(fontSize: 10, color: color.withValues(alpha: 0.7))),
                  ],
                ),
              ),
            );
          }),
        ),
      ],
    );
  }

  Widget _sectionHeader(String title, IconData icon) {
    return Row(
      children: [
        Icon(icon, size: 16, color: const Color(0xFF1B3A6B)),
        const SizedBox(width: 6),
        Text(title,
            style: const TextStyle(
                fontSize: 14, fontWeight: FontWeight.w700, color: Color(0xFF1B3A6B), letterSpacing: 0.2)),
      ],
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
            Text(_error!, textAlign: TextAlign.center,
                style: TextStyle(color: Colors.grey.shade600, fontSize: 15, height: 1.5)),
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

class _MemberCard extends StatefulWidget {
  final AdminMember member;
  final String Function(double) fmt;

  const _MemberCard({required this.member, required this.fmt});

  @override
  State<_MemberCard> createState() => _MemberCardState();
}

class _MemberCardState extends State<_MemberCard> with SingleTickerProviderStateMixin {
  bool _expanded = false;
  late final AnimationController _ctrl;
  late final Animation<double> _rotate;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(vsync: this, duration: const Duration(milliseconds: 200));
    _rotate = Tween<double>(begin: 0, end: 0.5).animate(CurvedAnimation(parent: _ctrl, curve: Curves.easeInOut));
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  void _toggle() {
    setState(() => _expanded = !_expanded);
    _expanded ? _ctrl.forward() : _ctrl.reverse();
  }

  @override
  Widget build(BuildContext context) {
    final m = widget.member;
    final isActive = m.status == 'active';
    final initial = m.fullName.isNotEmpty ? m.fullName[0].toUpperCase() : '?';

    return GestureDetector(
      onTap: _toggle,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        margin: const EdgeInsets.only(bottom: 10),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: _expanded ? const Color(0xFF1B3A6B).withValues(alpha: 0.2) : Colors.transparent,
          ),
          boxShadow: [
            BoxShadow(
              color: _expanded
                  ? const Color(0xFF1B3A6B).withValues(alpha: 0.08)
                  : Colors.black.withValues(alpha: 0.04),
              blurRadius: _expanded ? 16 : 8,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Column(
          children: [
            // Header siempre visible
            Padding(
              padding: const EdgeInsets.all(14),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 22,
                    backgroundColor: isActive
                        ? const Color(0xFF1B3A6B).withValues(alpha: 0.1)
                        : Colors.grey.shade100,
                    child: Text(initial,
                        style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                            color: isActive ? const Color(0xFF1B3A6B) : Colors.grey.shade400)),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(m.fullName,
                            style: const TextStyle(
                                fontSize: 14, fontWeight: FontWeight.w700, color: Color(0xFF1A1A2E))),
                        if (m.documentNumber != null)
                          Text('CI: ${m.documentNumber}',
                              style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
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
                          color: isActive ? const Color(0xFF0D7C5F) : Colors.grey),
                    ),
                  ),
                  const SizedBox(width: 8),
                  RotationTransition(
                    turns: _rotate,
                    child: Icon(Icons.keyboard_arrow_down_rounded,
                        color: Colors.grey.shade400, size: 22),
                  ),
                ],
              ),
            ),
            // Detalle expandible
            if (_expanded) ...[
              Divider(height: 1, indent: 14, endIndent: 14, color: Colors.grey.shade100),
              Padding(
                padding: const EdgeInsets.fromLTRB(14, 12, 14, 14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Fondos
                    _sectionLabel('Fondos acumulados'),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        _amountChip('Ahorros', m.totalSavings, const Color(0xFF0D7C5F), Icons.savings_rounded),
                        const SizedBox(width: 8),
                        _amountChip('Emergencia', m.totalEmergency, const Color(0xFF1B3A6B), Icons.shield_rounded),
                        const SizedBox(width: 8),
                        _amountChip('Multas', m.totalFines, const Color(0xFFE65100), Icons.gavel_rounded),
                      ],
                    ),
                    const SizedBox(height: 16),
                    // Asistencia
                    _sectionLabel('Asistencia a reuniones'),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        _attendChip('Asistencias', m.attended, const Color(0xFF0D7C5F), Icons.check_circle_rounded),
                        const SizedBox(width: 8),
                        _attendChip('F. con permiso', m.excusedAbsences, const Color(0xFF7B4F9E), Icons.event_busy_rounded),
                        const SizedBox(width: 8),
                        _attendChip('Faltas', m.absences, const Color(0xFFD32F2F), Icons.cancel_rounded),
                      ],
                    ),
                    if (m.totalMeetings > 0) ...[
                      const SizedBox(height: 10),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(6),
                        child: Row(
                          children: [
                            if (m.attended > 0)
                              Flexible(
                                flex: m.attended,
                                child: Container(height: 6, color: const Color(0xFF0D7C5F)),
                              ),
                            if (m.excusedAbsences > 0)
                              Flexible(
                                flex: m.excusedAbsences,
                                child: Container(height: 6, color: const Color(0xFF7B4F9E)),
                              ),
                            if (m.absences > 0)
                              Flexible(
                                flex: m.absences,
                                child: Container(height: 6, color: const Color(0xFFD32F2F)),
                              ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${m.totalMeetings} reuniones en total',
                        style: TextStyle(fontSize: 11, color: Colors.grey.shade500),
                      ),
                    ],
                    const SizedBox(height: 16),
                    // Info adicional
                    _sectionLabel('Información adicional'),
                    const SizedBox(height: 8),
                    _infoRow(Icons.layers_rounded, '${m.totalShares} acciones acumuladas', Colors.grey.shade600),
                    if (m.phone != null) ...[
                      const SizedBox(height: 6),
                      _infoRow(Icons.phone_rounded, m.phone!, Colors.grey.shade600),
                    ],
                    if (m.joinDate != null) ...[
                      const SizedBox(height: 6),
                      _infoRow(Icons.calendar_today_rounded, 'Ingresó: ${m.joinDate}', Colors.grey.shade600),
                    ],
                    const SizedBox(height: 6),
                    _infoRow(
                      m.membershipPaid ? Icons.check_circle_rounded : Icons.pending_rounded,
                      m.membershipPaid ? 'Membresía pagada' : 'Membresía pendiente',
                      m.membershipPaid ? const Color(0xFF0D7C5F) : const Color(0xFFE65100),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _sectionLabel(String label) {
    return Text(label,
        style: const TextStyle(
            fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFF1B3A6B), letterSpacing: 0.3));
  }

  Widget _amountChip(String label, double value, Color color, IconData icon) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.07),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, size: 14, color: color),
            const SizedBox(height: 4),
            Text('Bs. ${widget.fmt(value)}',
                style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: color)),
            Text(label, style: TextStyle(fontSize: 10, color: color.withValues(alpha: 0.7))),
          ],
        ),
      ),
    );
  }

  Widget _attendChip(String label, int count, Color color, IconData icon) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.07),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, size: 14, color: color),
            const SizedBox(height: 4),
            Text('$count', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: color, height: 1)),
            Text(label, style: TextStyle(fontSize: 10, color: color.withValues(alpha: 0.8))),
          ],
        ),
      ),
    );
  }

  Widget _infoRow(IconData icon, String text, Color color) {
    return Row(
      children: [
        Icon(icon, size: 14, color: color),
        const SizedBox(width: 6),
        Text(text, style: TextStyle(fontSize: 12, color: color)),
      ],
    );
  }
}
