import 'package:flutter/material.dart';
import '../models/savings.dart';
import '../services/savings_service.dart';

class SavingsScreen extends StatefulWidget {
  const SavingsScreen({super.key});

  @override
  State<SavingsScreen> createState() => _SavingsScreenState();
}

class _SavingsScreenState extends State<SavingsScreen> {
  final _service = SavingsService();

  SavingsTotals? _totals;
  MembershipInfo? _membership;
  List<SavingsContribution> _contributions = [];
  String? _memberName;
  String? _groupName;
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
      final result = await _service.fetch();
      if (!mounted) return;
      setState(() {
        _totals = result.totals;
        _membership = result.membership;
        _contributions = result.contributions;
        _memberName = result.memberName;
        _groupName = result.groupName;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = 'No se pudo cargar la información.\nRevisá tu conexión.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF2F4F8),
      body: _loading
          ? _buildLoading()
          : _error != null
              ? _buildError()
              : _buildContent(),
    );
  }

  Widget _buildLoading() {
    return const Center(
      child: CircularProgressIndicator(color: Color(0xFF0D7C5F)),
    );
  }

  Widget _buildError() {
    return CustomScrollView(
      slivers: [
        _buildAppBar(),
        SliverFillRemaining(
          child: Center(
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
                    child: Icon(Icons.cloud_off_rounded,
                        size: 48, color: Colors.grey.shade400),
                  ),
                  const SizedBox(height: 20),
                  Text(
                    _error!,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                        color: Colors.grey.shade600, fontSize: 15, height: 1.5),
                  ),
                  const SizedBox(height: 24),
                  FilledButton.icon(
                    onPressed: _load,
                    style: FilledButton.styleFrom(
                      backgroundColor: const Color(0xFF0D7C5F),
                      padding: const EdgeInsets.symmetric(
                          horizontal: 24, vertical: 12),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                    ),
                    icon: const Icon(Icons.refresh_rounded),
                    label: const Text('Reintentar'),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildContent() {
    final hasData = _totals != null && _contributions.isNotEmpty;

    return RefreshIndicator(
      onRefresh: _load,
      color: const Color(0xFF0D7C5F),
      child: CustomScrollView(
        slivers: [
          _buildAppBar(),
          if (hasData) ...[
            SliverToBoxAdapter(child: _buildHeroCard()),
            if (_membership != null)
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                sliver: SliverToBoxAdapter(child: _buildMembershipBanner()),
              ),
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(16, 20, 16, 8),
              sliver: SliverToBoxAdapter(child: _buildContributionsHeader()),
            ),
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 32),
              sliver: SliverToBoxAdapter(child: _buildTimeline()),
            ),
          ] else
            SliverFillRemaining(child: _buildEmpty()),
        ],
      ),
    );
  }

  SliverAppBar _buildAppBar() {
    return SliverAppBar(
      pinned: true,
      backgroundColor: const Color(0xFF074A37),
      foregroundColor: Colors.white,
      title: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Mis ahorros',
              style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700)),
          if (_groupName != null)
            Text(_groupName!,
                style: TextStyle(
                    fontSize: 12,
                    color: Colors.white.withValues(alpha: 0.65),
                    fontWeight: FontWeight.normal)),
        ],
      ),
    );
  }

  Widget _buildHeroCard() {
    final t = _totals!;
    return Container(
      width: double.infinity,
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF074A37), Color(0xFF0D7C5F), Color(0xFF10956F)],
        ),
      ),
      padding: const EdgeInsets.fromLTRB(24, 28, 24, 32),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 20,
                backgroundColor: Colors.white.withValues(alpha: 0.15),
                child: Text(
                  (_memberName ?? 'M')[0].toUpperCase(),
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Text(
                _memberName ?? '',
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.85),
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
          Text(
            'Total ahorrado',
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.65),
              fontSize: 13,
              letterSpacing: 0.3,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Bs. ${_fmt(t.savings)}',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 38,
              fontWeight: FontWeight.w800,
              letterSpacing: -0.5,
              height: 1.1,
            ),
          ),
          const SizedBox(height: 24),
          Row(
            children: [
              _heroChip('Emergencia', t.emergency, Icons.shield_rounded,
                  Colors.white.withValues(alpha: 0.25)),
              const SizedBox(width: 8),
              _heroChip('Multas', t.fines, Icons.gavel_rounded,
                  Colors.white.withValues(alpha: 0.25)),
              const SizedBox(width: 8),
              _heroChip('Prestado', t.loans, Icons.handshake_rounded,
                  Colors.white.withValues(alpha: 0.25)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _heroChip(String label, double value, IconData icon, Color bg) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: Colors.white, size: 16),
            const SizedBox(height: 6),
            Text(
              'Bs. ${_fmt(value)}',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
            Text(
              label,
              style: TextStyle(
                color: Colors.white.withValues(alpha: 0.7),
                fontSize: 10,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMembershipBanner() {
    final m = _membership!;
    final paid = m.paid;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: paid
            ? const Color(0xFF0D7C5F).withValues(alpha: 0.08)
            : const Color(0xFFE65100).withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: paid
              ? const Color(0xFF0D7C5F).withValues(alpha: 0.3)
              : const Color(0xFFE65100).withValues(alpha: 0.3),
        ),
      ),
      child: Row(
        children: [
          Icon(
            paid ? Icons.verified_rounded : Icons.pending_rounded,
            color: paid ? const Color(0xFF0D7C5F) : const Color(0xFFE65100),
            size: 20,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Membresía ${paid ? 'al día' : 'pendiente'}',
                  style: TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                    color: paid ? const Color(0xFF0D7C5F) : const Color(0xFFE65100),
                  ),
                ),
                if (paid && m.paidAt != null)
                  Text(
                    'Pagada el ${m.paidAt}',
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                  )
                else if (!paid)
                  Text(
                    'Regularizá tu membresía',
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildContributionsHeader() {
    return Row(
      children: [
        const Icon(Icons.receipt_long_rounded,
            size: 16, color: Color(0xFF1B3A6B)),
        const SizedBox(width: 6),
        const Text(
          'Aportes por reunión',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w700,
            color: Color(0xFF1B3A6B),
          ),
        ),
        const Spacer(),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
          decoration: BoxDecoration(
            color: const Color(0xFF1B3A6B).withValues(alpha: 0.08),
            borderRadius: BorderRadius.circular(20),
          ),
          child: Text(
            '${_contributions.length} reuniones',
            style: const TextStyle(
                fontSize: 11,
                color: Color(0xFF1B3A6B),
                fontWeight: FontWeight.w600),
          ),
        ),
      ],
    );
  }

  Widget _buildTimeline() {
    return Column(
      children: List.generate(_contributions.length, (i) {
        final c = _contributions[i];
        final isLast = i == _contributions.length - 1;
        return IntrinsicHeight(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              SizedBox(
                width: 32,
                child: Column(
                  children: [
                    Container(
                      width: 28,
                      height: 28,
                      decoration: const BoxDecoration(
                        color: Color(0xFF0D7C5F),
                        shape: BoxShape.circle,
                      ),
                      child: Center(
                        child: Text(
                          '${c.meetingNumber ?? '?'}',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 10,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ),
                    if (!isLast)
                      Expanded(
                        child: Container(
                          width: 2,
                          color: const Color(0xFF0D7C5F).withValues(alpha: 0.2),
                        ),
                      ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Padding(
                  padding: EdgeInsets.only(bottom: isLast ? 0 : 10),
                  child: _buildContributionCard(c),
                ),
              ),
            ],
          ),
        );
      }),
    );
  }

  Widget _buildContributionCard(SavingsContribution c) {
    return Container(
      margin: const EdgeInsets.only(bottom: 2),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Theme(
        data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
        child: ExpansionTile(
          tilePadding: const EdgeInsets.symmetric(horizontal: 16),
          childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
          minTileHeight: 64,
          expandedCrossAxisAlignment: CrossAxisAlignment.stretch,
          title: Text(
            'Reunión ${c.meetingNumber ?? '?'}${c.month != null ? ' · ${c.month}' : ''}',
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: Color(0xFF1A1A2E),
            ),
          ),
          subtitle: c.meetingDate != null
              ? Text(
                  c.meetingDate!,
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade500),
                )
              : null,
          trailing: Text(
            'Bs. ${_fmt(c.savings)}',
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w700,
              color: Color(0xFF0D7C5F),
            ),
          ),
          children: [
            Divider(color: Colors.grey.shade100, height: 16),
            _detailRow('Acciones', '${c.shares}', Colors.grey.shade600),
            const SizedBox(height: 6),
            _detailRow(
                'Fondo emergencia', 'Bs. ${_fmt(c.emergency)}', const Color(0xFF1B3A6B)),
            const SizedBox(height: 6),
            _detailRow('Multas', 'Bs. ${_fmt(c.fines)}', const Color(0xFFE65100)),
            Divider(color: Colors.grey.shade100, height: 16),
            _detailRow(
              'Total aportado',
              'Bs. ${_fmt(c.total)}',
              const Color(0xFF0D7C5F),
              bold: true,
            ),
          ],
        ),
      ),
    );
  }

  Widget _detailRow(String label, String value, Color color,
      {bool bold = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label,
            style: TextStyle(fontSize: 13, color: Colors.grey.shade500)),
        Text(
          value,
          style: TextStyle(
            fontSize: 13,
            color: color,
            fontWeight: bold ? FontWeight.w700 : FontWeight.w500,
          ),
        ),
      ],
    );
  }

  Widget _buildEmpty() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: const Color(0xFF0D7C5F).withValues(alpha: 0.06),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.savings_outlined,
              size: 52,
              color: Color(0xFF0D7C5F),
            ),
          ),
          const SizedBox(height: 20),
          const Text(
            'Sin aportes registrados',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: Color(0xFF1A1A2E),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'Tus aportes aparecerán aquí\ncuando se registren en el sistema.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 13, color: Colors.grey.shade500, height: 1.5),
          ),
        ],
      ),
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
