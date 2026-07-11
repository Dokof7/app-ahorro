import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import '../models/dashboard_stats.dart';
import '../models/savings.dart';
import '../services/dashboard_service.dart';
import '../services/savings_service.dart';

class ChartsScreen extends StatefulWidget {
  const ChartsScreen({super.key});

  @override
  State<ChartsScreen> createState() => _ChartsScreenState();
}

class _ChartsScreenState extends State<ChartsScreen>
    with SingleTickerProviderStateMixin {
  final _dashService = DashboardService();
  final _savingsService = SavingsService();

  DashboardStats? _stats;
  List<SavingsContribution> _contributions = [];
  bool _loading = true;
  String? _error;

  late final AnimationController _animCtrl = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 900),
  );
  late final Animation<double> _anim = CurvedAnimation(
    parent: _animCtrl,
    curve: Curves.easeOutCubic,
  );

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _animCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final results = await Future.wait([
        _dashService.fetch(),
        _savingsService.fetch(),
      ]);
      if (!mounted) return;
      final dash = results[0] as ({DashboardStats stats, List<DashboardGroup> groups});
      final savings = results[1] as ({
        SavingsTotals totals,
        MembershipInfo membership,
        List<SavingsContribution> contributions,
        String? memberName,
        String? groupName,
      });
      setState(() {
        _stats = dash.stats;
        _contributions = savings.contributions;
      });
      _animCtrl.forward(from: 0);
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
      appBar: AppBar(
        backgroundColor: const Color(0xFF0D2347),
        foregroundColor: Colors.white,
        title: const Text(
          'Estadísticas',
          style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700),
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF1B3A6B)))
          : _error != null
              ? _buildError()
              : _buildContent(),
    );
  }

  Widget _buildError() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.cloud_off_rounded, size: 52, color: Colors.grey.shade400),
            const SizedBox(height: 16),
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

  Widget _buildContent() {
    return RefreshIndicator(
      onRefresh: _load,
      color: const Color(0xFF1B3A6B),
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (_contributions.isNotEmpty) ...[
            _buildSectionHeader('Evolución de ahorros', Icons.trending_up_rounded),
            const SizedBox(height: 12),
            _buildSavingsLineChart(),
            const SizedBox(height: 24),
          ],
          if (_stats != null) ...[
            _buildSectionHeader('Distribución de fondos', Icons.pie_chart_rounded),
            const SizedBox(height: 12),
            _buildFundsBarChart(),
            const SizedBox(height: 24),
            _buildSectionHeader('Préstamos', Icons.handshake_rounded),
            const SizedBox(height: 12),
            _buildLoansChart(),
          ],
        ],
      ),
    );
  }

  Widget _buildSectionHeader(String title, IconData icon) {
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
          ),
        ),
      ],
    );
  }

  Widget _buildSavingsLineChart() {
    final sorted = [..._contributions]
      ..sort((a, b) => (a.meetingNumber ?? 0).compareTo(b.meetingNumber ?? 0));

    double cumulative = 0;
    final spots = sorted.map((c) {
      cumulative += c.savings;
      return FlSpot((c.meetingNumber ?? 0).toDouble(), cumulative);
    }).toList();

    final maxY = cumulative * 1.15;

    return _chartCard(
      child: AnimatedBuilder(
        animation: _anim,
        builder: (context, child) => LineChart(
          LineChartData(
            minY: 0,
            maxY: maxY,
            gridData: FlGridData(
              show: true,
              drawVerticalLine: false,
              horizontalInterval: maxY / 4,
              getDrawingHorizontalLine: (_) => FlLine(
                color: Colors.grey.shade100,
                strokeWidth: 1,
              ),
            ),
            borderData: FlBorderData(show: false),
            titlesData: FlTitlesData(
              leftTitles: AxisTitles(
                sideTitles: SideTitles(
                  showTitles: true,
                  reservedSize: 56,
                  interval: maxY / 4,
                  getTitlesWidget: (v, _) => Text(
                    'Bs.${_fmtShort(v)}',
                    style: TextStyle(fontSize: 10, color: Colors.grey.shade500),
                  ),
                ),
              ),
              bottomTitles: AxisTitles(
                sideTitles: SideTitles(
                  showTitles: true,
                  reservedSize: 28,
                  getTitlesWidget: (v, _) => Text(
                    'R${v.toInt()}',
                    style: TextStyle(fontSize: 10, color: Colors.grey.shade500),
                  ),
                ),
              ),
              rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
              topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
            ),
            lineBarsData: [
              LineChartBarData(
                spots: spots.map((s) => FlSpot(s.x, s.y * _anim.value)).toList(),
                isCurved: true,
                curveSmoothness: 0.35,
                color: const Color(0xFF0D7C5F),
                barWidth: 3,
                dotData: FlDotData(
                  show: true,
                  getDotPainter: (spot, percent, bar, index) => FlDotCirclePainter(
                    radius: 4,
                    color: const Color(0xFF0D7C5F),
                    strokeWidth: 2,
                    strokeColor: Colors.white,
                  ),
                ),
                belowBarData: BarAreaData(
                  show: true,
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      const Color(0xFF0D7C5F).withValues(alpha: 0.2),
                      const Color(0xFF0D7C5F).withValues(alpha: 0.0),
                    ],
                  ),
                ),
              ),
            ],
            lineTouchData: LineTouchData(
              touchTooltipData: LineTouchTooltipData(
                getTooltipColor: (_) => const Color(0xFF0D2347),
                getTooltipItems: (spots) => spots.map((s) {
                  return LineTooltipItem(
                    'Bs. ${_fmt(s.y / _anim.value.clamp(0.01, 1.0))}',
                    const TextStyle(
                      color: Colors.white,
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                    ),
                  );
                }).toList(),
              ),
            ),
          ),
        ),
      ),
      legend: _legend(const Color(0xFF0D7C5F), 'Ahorro acumulado por reunión'),
    );
  }

  Widget _buildFundsBarChart() {
    final s = _stats!;
    final items = [
      ('Ahorrado', s.totalSavings, const Color(0xFF0D7C5F)),
      ('Emergencia', s.totalEmergency, const Color(0xFF1B3A6B)),
      ('Multas', s.totalFines, const Color(0xFFE65100)),
      ('Membresías', s.totalMembership, const Color(0xFF6A1B9A)),
    ];

    final maxY = items.map((e) => e.$2).reduce((a, b) => a > b ? a : b) * 1.2;

    return _chartCard(
      child: AnimatedBuilder(
        animation: _anim,
        builder: (context, child) => BarChart(
          BarChartData(
            maxY: maxY,
            barTouchData: BarTouchData(
              touchTooltipData: BarTouchTooltipData(
                getTooltipColor: (_) => const Color(0xFF0D2347),
                getTooltipItem: (group, groupIndex, rod, rodIndex) {
                  final label = items[groupIndex].$1;
                  final value = items[groupIndex].$2;
                  return BarTooltipItem(
                    '$label\nBs. ${_fmt(value)}',
                    const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600),
                  );
                },
              ),
            ),
            titlesData: FlTitlesData(
              bottomTitles: AxisTitles(
                sideTitles: SideTitles(
                  showTitles: true,
                  reservedSize: 28,
                  getTitlesWidget: (v, _) {
                    final i = v.toInt();
                    if (i >= 0 && i < items.length) {
                      return Padding(
                        padding: const EdgeInsets.only(top: 6),
                        child: Text(
                          items[i].$1,
                          style: TextStyle(fontSize: 10, color: Colors.grey.shade600),
                        ),
                      );
                    }
                    return const SizedBox.shrink();
                  },
                ),
              ),
              leftTitles: AxisTitles(
                sideTitles: SideTitles(
                  showTitles: true,
                  reservedSize: 56,
                  getTitlesWidget: (v, _) => Text(
                    'Bs.${_fmtShort(v)}',
                    style: TextStyle(fontSize: 10, color: Colors.grey.shade500),
                  ),
                ),
              ),
              rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
              topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
            ),
            gridData: FlGridData(
              show: true,
              drawVerticalLine: false,
              getDrawingHorizontalLine: (_) => FlLine(
                color: Colors.grey.shade100,
                strokeWidth: 1,
              ),
            ),
            borderData: FlBorderData(show: false),
            barGroups: List.generate(items.length, (i) {
              final (_, value, color) = items[i];
              return BarChartGroupData(
                x: i,
                barRods: [
                  BarChartRodData(
                    toY: value * _anim.value,
                    color: color,
                    width: 32,
                    borderRadius: const BorderRadius.vertical(top: Radius.circular(8)),
                    backDrawRodData: BackgroundBarChartRodData(
                      show: true,
                      toY: maxY,
                      color: Colors.grey.shade100,
                    ),
                  ),
                ],
              );
            }),
          ),
        ),
      ),
    );
  }

  Widget _buildLoansChart() {
    final s = _stats!;
    final pending = s.loansPending;
    final paid = s.loansPaid;
    final total = pending + paid;

    if (total == 0) {
      return Container(
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
        ),
        child: Center(
          child: Text(
            'Sin préstamos registrados',
            style: TextStyle(color: Colors.grey.shade500, fontSize: 14),
          ),
        ),
      );
    }

    return _chartCard(
      child: AnimatedBuilder(
        animation: _anim,
        builder: (context, child) => PieChart(
          PieChartData(
            sectionsSpace: 3,
            centerSpaceRadius: 48,
            sections: [
              PieChartSectionData(
                value: paid * _anim.value,
                color: const Color(0xFF0D7C5F),
                radius: 52,
                title: '${(paid / total * 100).toStringAsFixed(0)}%',
                titleStyle: const TextStyle(
                  color: Colors.white,
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                ),
              ),
              PieChartSectionData(
                value: pending * _anim.value,
                color: const Color(0xFFD32F2F),
                radius: 52,
                title: '${(pending / total * 100).toStringAsFixed(0)}%',
                titleStyle: const TextStyle(
                  color: Colors.white,
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
        ),
      ),
      legend: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          _legend(const Color(0xFF0D7C5F), 'Pagados  Bs. ${_fmt(paid)}'),
          const SizedBox(width: 20),
          _legend(const Color(0xFFD32F2F), 'Pendientes  Bs. ${_fmt(pending)}'),
        ],
      ),
    );
  }

  Widget _chartCard({required Widget child, Widget? legend}) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 20, 16, 16),
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
        children: [
          SizedBox(height: 200, child: child),
          if (legend != null) ...[
            const SizedBox(height: 12),
            legend,
          ],
        ],
      ),
    );
  }

  Widget _legend(Color color, String label) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 10,
          height: 10,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 6),
        Text(label, style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
      ],
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

  String _fmtShort(double v) {
    if (v >= 1000) return '${(v / 1000).toStringAsFixed(1)}k';
    return v.toStringAsFixed(0);
  }
}
