import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import '../models/group_comparison.dart';
import '../services/reports_service.dart';
import 'group_report_detail_screen.dart';

class ComparativeReportsScreen extends StatefulWidget {
  const ComparativeReportsScreen({super.key});

  @override
  State<ComparativeReportsScreen> createState() => _ComparativeReportsScreenState();
}

class _ComparativeReportsScreenState extends State<ComparativeReportsScreen>
    with SingleTickerProviderStateMixin {
  final _service = ReportsService();

  List<GroupComparison> _groups = [];
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
      final groups = await _service.fetchGroupsComparison(year: _selectedYear);
      if (!mounted) return;
      setState(() => _groups = groups);
      _animCtrl.forward(from: 0);
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = 'No se pudo cargar la comparación de grupos.\nRevisá tu conexión.');
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
          'Comparativa de grupos',
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
    if (_groups.isEmpty) {
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
                  Icon(Icons.groups_outlined, size: 48, color: Colors.grey.shade300),
                  const SizedBox(height: 12),
                  Text(
                    'Sin datos de grupos para este período',
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
          _buildSectionHeader('Ahorro total por grupo', Icons.savings_rounded),
          const SizedBox(height: 12),
          _buildSavingsBarChart(),
          const SizedBox(height: 24),
          _buildSectionHeader('Asistencia por grupo', Icons.event_available_rounded),
          const SizedBox(height: 12),
          _buildAttendanceBarChart(),
          const SizedBox(height: 24),
          _buildSectionHeader('Ranking de grupos', Icons.leaderboard_rounded),
          const SizedBox(height: 12),
          _buildGroupsList(),
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

  Widget _buildSavingsBarChart() {
    final sorted = [..._groups]..sort((a, b) => b.totalSavings.compareTo(a.totalSavings));
    final maxX = sorted.isEmpty
        ? 1.0
        : sorted.map((g) => g.totalSavings).reduce((a, b) => a > b ? a : b) * 1.2;

    return _chartCard(
      height: (sorted.length * 40.0).clamp(120.0, 320.0),
      child: AnimatedBuilder(
        animation: _anim,
        builder: (context, child) => BarChart(
          BarChartData(
            alignment: BarChartAlignment.spaceAround,
            maxY: maxX == 0 ? 1 : maxX,
            barTouchData: BarTouchData(
              touchTooltipData: BarTouchTooltipData(
                getTooltipColor: (_) => const Color(0xFF0D2347),
                getTooltipItem: (group, groupIndex, rod, rodIndex) {
                  final g = sorted[groupIndex];
                  return BarTooltipItem(
                    '${g.groupName}\nBs. ${_fmt(g.totalSavings)}',
                    const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600),
                  );
                },
              ),
            ),
            titlesData: FlTitlesData(
              bottomTitles: AxisTitles(
                sideTitles: SideTitles(
                  showTitles: true,
                  reservedSize: 60,
                  getTitlesWidget: (v, _) {
                    final i = v.toInt();
                    if (i >= 0 && i < sorted.length) {
                      final name = sorted[i].groupName;
                      final short = name.length > 8 ? '${name.substring(0, 7)}…' : name;
                      return Padding(
                        padding: const EdgeInsets.only(top: 6),
                        child: Text(
                          short,
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
                  reservedSize: 52,
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
            barGroups: List.generate(sorted.length, (i) {
              final g = sorted[i];
              return BarChartGroupData(
                x: i,
                barRods: [
                  BarChartRodData(
                    toY: g.totalSavings * _anim.value,
                    color: const Color(0xFF0D7C5F),
                    width: 22,
                    borderRadius: const BorderRadius.vertical(top: Radius.circular(6)),
                    backDrawRodData: BackgroundBarChartRodData(
                      show: true,
                      toY: maxX == 0 ? 1 : maxX,
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

  Widget _buildAttendanceBarChart() {
    final sorted = [..._groups]..sort((a, b) => b.attendanceRate.compareTo(a.attendanceRate));

    return _chartCard(
      height: (sorted.length * 40.0).clamp(120.0, 320.0),
      child: AnimatedBuilder(
        animation: _anim,
        builder: (context, child) => BarChart(
          BarChartData(
            alignment: BarChartAlignment.spaceAround,
            maxY: 100,
            barTouchData: BarTouchData(
              touchTooltipData: BarTouchTooltipData(
                getTooltipColor: (_) => const Color(0xFF0D2347),
                getTooltipItem: (group, groupIndex, rod, rodIndex) {
                  final g = sorted[groupIndex];
                  return BarTooltipItem(
                    '${g.groupName}\n${g.attendanceRate.toStringAsFixed(1)}%',
                    const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600),
                  );
                },
              ),
            ),
            titlesData: FlTitlesData(
              bottomTitles: AxisTitles(
                sideTitles: SideTitles(
                  showTitles: true,
                  reservedSize: 60,
                  getTitlesWidget: (v, _) {
                    final i = v.toInt();
                    if (i >= 0 && i < sorted.length) {
                      final name = sorted[i].groupName;
                      final short = name.length > 8 ? '${name.substring(0, 7)}…' : name;
                      return Padding(
                        padding: const EdgeInsets.only(top: 6),
                        child: Text(
                          short,
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
            barGroups: List.generate(sorted.length, (i) {
              final g = sorted[i];
              return BarChartGroupData(
                x: i,
                barRods: [
                  BarChartRodData(
                    toY: g.attendanceRate.clamp(0, 100) * _anim.value,
                    color: const Color(0xFF1B3A6B),
                    width: 22,
                    borderRadius: const BorderRadius.vertical(top: Radius.circular(6)),
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

  Widget _buildGroupsList() {
    final sorted = [..._groups]..sort((a, b) => b.totalSavings.compareTo(a.totalSavings));

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12, offset: const Offset(0, 4)),
        ],
      ),
      child: Column(
        children: List.generate(sorted.length, (i) {
          final g = sorted[i];
          return Column(
            children: [
              _buildGroupRow(g, i + 1),
              if (i < sorted.length - 1)
                Divider(height: 1, indent: 70, endIndent: 16, color: Colors.grey.shade100),
            ],
          );
        }),
      ),
    );
  }

  Widget _buildGroupRow(GroupComparison g, int rank) {
    return InkWell(
      onTap: () => Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => GroupReportDetailScreen(
            groupId: g.groupId,
            groupName: g.groupName,
          ),
        ),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        child: Row(
          children: [
            Container(
              width: 34,
              height: 34,
              decoration: BoxDecoration(
                color: const Color(0xFF1B3A6B).withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: Center(
                child: Text(
                  '$rank',
                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF1B3A6B)),
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Flexible(
                        child: Text(
                          g.groupName,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: Color(0xFF1A1A2E)),
                        ),
                      ),
                      if (g.isPartial) ...[
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: const Color(0xFF7B4F9E).withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: const Text(
                            'Registro grupal',
                            style: TextStyle(
                              fontSize: 9,
                              fontWeight: FontWeight.w700,
                              color: Color(0xFF7B4F9E),
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                  const SizedBox(height: 3),
                  Text(
                    '${g.activeMembers} miembros · ${g.attendanceRate.toStringAsFixed(0)}% asistencia',
                    style: TextStyle(fontSize: 11, color: Colors.grey.shade500),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  'Bs. ${_fmt(g.totalSavings)}',
                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF0D7C5F)),
                ),
                const SizedBox(height: 2),
                Text(
                  'Ahorrado',
                  style: TextStyle(fontSize: 10, color: Colors.grey.shade400),
                ),
              ],
            ),
            const SizedBox(width: 4),
            Icon(Icons.chevron_right_rounded, color: Colors.grey.shade400),
          ],
        ),
      ),
    );
  }

  Widget _chartCard({required Widget child, required double height}) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 20, 16, 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12, offset: const Offset(0, 4)),
        ],
      ),
      child: SizedBox(height: height, child: child),
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
