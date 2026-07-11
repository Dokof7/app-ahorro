import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import '../models/group_report_summary.dart';
import '../services/reports_service.dart';

class GroupReportDetailScreen extends StatefulWidget {
  final int groupId;
  final String groupName;

  const GroupReportDetailScreen({
    super.key,
    required this.groupId,
    required this.groupName,
  });

  @override
  State<GroupReportDetailScreen> createState() => _GroupReportDetailScreenState();
}

class _GroupReportDetailScreenState extends State<GroupReportDetailScreen>
    with SingleTickerProviderStateMixin {
  final _service = ReportsService();

  GroupReportSummary? _summary;
  bool _loading = true;
  String? _error;
  int? _selectedYear;

  late final List<int> _years = List.generate(5, (i) => DateTime.now().year - i);

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
    _selectedYear = DateTime.now().year;
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
      final summary = await _service.fetchGroupSummary(widget.groupId, year: _selectedYear);
      if (!mounted) return;
      setState(() => _summary = summary);
      _animCtrl.forward(from: 0);
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = 'No se pudo cargar el resumen del grupo.\nRevisá tu conexión.');
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
        title: Text(
          widget.groupName,
          style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w700),
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
    final s = _summary!;
    final isPartial = s.group.isPartial;
    final hasMonthly = s.monthly.isNotEmpty;
    final hasTopSavers = !isPartial && s.topSavers.isNotEmpty;
    final hasTopAttendance = s.topAttendance.isNotEmpty;

    if (!hasMonthly && !hasTopSavers && !hasTopAttendance) {
      return RefreshIndicator(
        onRefresh: _load,
        color: const Color(0xFF1B3A6B),
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            _buildYearFilter(),
            const SizedBox(height: 80),
            Center(
              child: Column(
                children: [
                  Icon(Icons.bar_chart_rounded, size: 48, color: Colors.grey.shade300),
                  const SizedBox(height: 12),
                  Text(
                    'Sin datos para este período',
                    style: TextStyle(color: Colors.grey.shade500, fontSize: 14),
                  ),
                ],
              ),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _load,
      color: const Color(0xFF1B3A6B),
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildYearFilter(),
          const SizedBox(height: 16),
          if (hasMonthly) ...[
            _buildSectionHeader('Evolución de ahorros', Icons.trending_up_rounded),
            const SizedBox(height: 12),
            _buildSavingsLineChart(s.monthly),
            const SizedBox(height: 24),
            _buildSectionHeader('Asistencia mensual', Icons.event_available_rounded),
            const SizedBox(height: 12),
            _buildAttendanceBarChart(s.monthly),
            const SizedBox(height: 24),
          ],
          if (isPartial) ...[
            _buildPartialModeNote(),
            const SizedBox(height: 24),
          ] else if (hasTopSavers) ...[
            _buildSectionHeader('Top aportadores', Icons.emoji_events_rounded),
            const SizedBox(height: 12),
            _buildTopSaversCard(s.topSavers),
            const SizedBox(height: 24),
          ],
          if (hasTopAttendance) ...[
            _buildSectionHeader('Mejor asistencia', Icons.verified_rounded),
            const SizedBox(height: 12),
            _buildTopAttendanceCard(s.topAttendance),
          ],
        ],
      ),
    );
  }

  Widget _buildYearFilter() {
    return Row(
      children: [
        Icon(Icons.calendar_month_rounded, size: 16, color: Colors.grey.shade600),
        const SizedBox(width: 8),
        Text('Año:', style: TextStyle(fontSize: 13, color: Colors.grey.shade600)),
        const SizedBox(width: 10),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: Colors.grey.shade200),
          ),
          child: DropdownButtonHideUnderline(
            child: DropdownButton<int>(
              value: _selectedYear,
              isDense: true,
              items: _years
                  .map((y) => DropdownMenuItem(value: y, child: Text('$y')))
                  .toList(),
              onChanged: (y) {
                if (y == null) return;
                setState(() => _selectedYear = y);
                _load();
              },
            ),
          ),
        ),
      ],
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

  Widget _buildSavingsLineChart(List<GroupMonthlyPoint> monthly) {
    final spots = <FlSpot>[];
    for (var i = 0; i < monthly.length; i++) {
      spots.add(FlSpot(i.toDouble(), monthly[i].savings));
    }
    final maxVal = monthly.map((m) => m.savings).fold(0.0, (a, b) => a > b ? a : b);
    final maxY = maxVal == 0 ? 1.0 : maxVal * 1.2;

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
                  getTitlesWidget: (v, _) {
                    final i = v.toInt();
                    if (i >= 0 && i < monthly.length) {
                      return Text(
                        monthly[i].label,
                        style: TextStyle(fontSize: 9, color: Colors.grey.shade500),
                      );
                    }
                    return const SizedBox.shrink();
                  },
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
                  final i = s.x.toInt();
                  final actualY = s.y / _anim.value.clamp(0.01, 1.0);
                  final delta = (i >= 0 && i < monthly.length) ? monthly[i].savingsDelta : null;
                  final deltaStr = delta == null
                      ? ''
                      : '\n${delta >= 0 ? '+' : ''}${delta.toStringAsFixed(1)}%';
                  return LineTooltipItem(
                    'Bs. ${_fmt(actualY)}$deltaStr',
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
      legend: _legend(const Color(0xFF0D7C5F), 'Ahorro mensual'),
    );
  }

  Widget _buildAttendanceBarChart(List<GroupMonthlyPoint> monthly) {
    return _chartCard(
      child: AnimatedBuilder(
        animation: _anim,
        builder: (context, child) => BarChart(
          BarChartData(
            maxY: 100,
            barTouchData: BarTouchData(
              touchTooltipData: BarTouchTooltipData(
                getTooltipColor: (_) => const Color(0xFF0D2347),
                getTooltipItem: (group, groupIndex, rod, rodIndex) {
                  final m = monthly[groupIndex];
                  return BarTooltipItem(
                    '${m.label}\n${m.attendanceRate.toStringAsFixed(1)}%',
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
                    if (i >= 0 && i < monthly.length) {
                      return Padding(
                        padding: const EdgeInsets.only(top: 6),
                        child: Text(
                          monthly[i].label,
                          style: TextStyle(fontSize: 9, color: Colors.grey.shade600),
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
                  reservedSize: 40,
                  interval: 25,
                  getTitlesWidget: (v, _) => Text(
                    '${v.toInt()}%',
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
              horizontalInterval: 25,
              getDrawingHorizontalLine: (_) => FlLine(
                color: Colors.grey.shade100,
                strokeWidth: 1,
              ),
            ),
            borderData: FlBorderData(show: false),
            barGroups: List.generate(monthly.length, (i) {
              final m = monthly[i];
              return BarChartGroupData(
                x: i,
                barRods: [
                  BarChartRodData(
                    toY: m.attendanceRate.clamp(0, 100) * _anim.value,
                    color: const Color(0xFF1B3A6B),
                    width: 14,
                    borderRadius: const BorderRadius.vertical(top: Radius.circular(4)),
                    backDrawRodData: BackgroundBarChartRodData(
                      show: true,
                      toY: 100,
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

  Widget _buildPartialModeNote() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF1B3A6B).withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFF1B3A6B).withValues(alpha: 0.1)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.info_outline_rounded, size: 18, color: const Color(0xFF1B3A6B)),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Registro grupal',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF1A1A2E),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Este grupo registra los aportes como total del grupo, no por miembro. '
                  'Por eso no se muestra el ranking de aportadores individuales.',
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600, height: 1.5),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTopSaversCard(List<GroupTopSaver> topSavers) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12, offset: const Offset(0, 4)),
        ],
      ),
      child: Column(
        children: List.generate(topSavers.length, (i) {
          final m = topSavers[i];
          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                child: Row(
                  children: [
                    _rankBadge(i + 1),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            m.name,
                            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: Color(0xFF1A1A2E)),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            '${m.contributions} aportes',
                            style: TextStyle(fontSize: 11, color: Colors.grey.shade500),
                          ),
                        ],
                      ),
                    ),
                    Text(
                      'Bs. ${_fmt(m.totalSaved)}',
                      style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF0D7C5F)),
                    ),
                  ],
                ),
              ),
              if (i < topSavers.length - 1)
                Divider(height: 1, indent: 62, endIndent: 16, color: Colors.grey.shade100),
            ],
          );
        }),
      ),
    );
  }

  Widget _buildTopAttendanceCard(List<GroupTopAttendance> topAttendance) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12, offset: const Offset(0, 4)),
        ],
      ),
      child: Column(
        children: List.generate(topAttendance.length, (i) {
          final m = topAttendance[i];
          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                child: Row(
                  children: [
                    _rankBadge(i + 1),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            m.name,
                            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: Color(0xFF1A1A2E)),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            '${m.attended} reuniones asistidas',
                            style: TextStyle(fontSize: 11, color: Colors.grey.shade500),
                          ),
                        ],
                      ),
                    ),
                    Text(
                      '${m.attendanceRate.toStringAsFixed(0)}%',
                      style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF1B3A6B)),
                    ),
                  ],
                ),
              ),
              if (i < topAttendance.length - 1)
                Divider(height: 1, indent: 62, endIndent: 16, color: Colors.grey.shade100),
            ],
          );
        }),
      ),
    );
  }

  Widget _rankBadge(int rank) {
    final color = switch (rank) {
      1 => const Color(0xFFE6A100),
      2 => const Color(0xFF9AA0A6),
      3 => const Color(0xFFB5651D),
      _ => const Color(0xFF1B3A6B),
    };
    return Container(
      width: 34,
      height: 34,
      decoration: BoxDecoration(color: color.withValues(alpha: 0.12), shape: BoxShape.circle),
      child: Center(
        child: Text(
          '$rank',
          style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: color),
        ),
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
          BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12, offset: const Offset(0, 4)),
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
