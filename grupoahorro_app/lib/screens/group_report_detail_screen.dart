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

  GroupReportGroup? _group;
  bool _loading = true;
  String? _error;
  int? _selectedYear;
  bool _showAllSessions = false;

  static const int _visibleSessionCount = 6;

  // Section keys — they double as the backend's `sections` query tokens.
  static const _kMonthly = 'monthly';
  static const _kSessions = 'sessions';
  static const _kTopSavers = 'top_savers';
  static const _kTopAttendance = 'top_attendance';

  // null = not fetched yet; non-null (possibly empty) = loaded.
  List<GroupMonthlyPoint>? _monthly;
  List<GroupSessionRow>? _sessions;
  List<GroupTopSaver>? _topSavers;
  List<GroupTopAttendance>? _topAttendance;

  final Set<String> _expanded = {};
  final Set<String> _sectionLoading = {};
  final Map<String, String> _sectionError = {};

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
    _loadBase();
  }

  @override
  void dispose() {
    _animCtrl.dispose();
    super.dispose();
  }

  /// Fetches only the group base info (name + registration_mode).
  /// Sections load lazily on first expand.
  Future<void> _loadBase() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final summary = await _service.fetchGroupSummary(
        widget.groupId,
        year: _selectedYear,
        sections: const [],
      );
      if (!mounted) return;
      setState(() => _group = summary.group);
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = 'No se pudo cargar el resumen del grupo.\nRevisá tu conexión.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  bool _isSectionLoaded(String key) => switch (key) {
        _kMonthly => _monthly != null,
        _kSessions => _sessions != null,
        _kTopSavers => _topSavers != null,
        _kTopAttendance => _topAttendance != null,
        _ => false,
      };

  /// Both "top" sections share one fetch: expanding either loads both, so the
  /// second expand is instant. Partial groups only ever need top_attendance.
  List<String> _sectionTokensFor(String key) {
    if (key == _kTopSavers || key == _kTopAttendance) {
      final isPartial = _group?.isPartial ?? false;
      return isPartial ? const [_kTopAttendance] : const [_kTopSavers, _kTopAttendance];
    }
    return [key];
  }

  Future<void> _loadSection(String key) async {
    if (_isSectionLoaded(key) || _sectionLoading.contains(key)) return;
    final tokens = _sectionTokensFor(key);
    setState(() {
      for (final t in tokens) {
        _sectionLoading.add(t);
        _sectionError.remove(t);
      }
    });
    try {
      final summary = await _service.fetchGroupSummary(
        widget.groupId,
        year: _selectedYear,
        sections: tokens,
      );
      if (!mounted) return;
      setState(() {
        for (final t in tokens) {
          switch (t) {
            case _kMonthly:
              _monthly = summary.monthly;
            case _kSessions:
              _sessions = summary.sessions;
              _showAllSessions = false;
            case _kTopSavers:
              _topSavers = summary.topSavers;
            case _kTopAttendance:
              _topAttendance = summary.topAttendance;
          }
        }
      });
      _animCtrl.forward(from: 0);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        for (final t in tokens) {
          _sectionError[t] = 'No se pudo cargar esta sección.';
        }
      });
    } finally {
      if (mounted) {
        setState(() {
          for (final t in tokens) {
            _sectionLoading.remove(t);
          }
        });
      }
    }
  }

  void _toggleSection(String key) {
    final willExpand = !_expanded.contains(key);
    setState(() {
      if (willExpand) {
        _expanded.add(key);
      } else {
        _expanded.remove(key);
      }
    });
    if (willExpand) _loadSection(key);
  }

  /// Year change invalidates every cached section, then re-fetches only what
  /// is currently expanded. Collapsed sections re-fetch on their next expand.
  void _onYearChanged(int year) {
    setState(() {
      _selectedYear = year;
      _monthly = null;
      _sessions = null;
      _topSavers = null;
      _topAttendance = null;
      _sectionError.clear();
      _showAllSessions = false;
    });
    for (final key in _expanded) {
      _loadSection(key);
    }
  }

  Future<void> _refresh() async {
    setState(() {
      _monthly = null;
      _sessions = null;
      _topSavers = null;
      _topAttendance = null;
      _sectionError.clear();
      _showAllSessions = false;
    });
    await Future.wait(_expanded.map(_loadSection).toList());
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
              onPressed: _loadBase,
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
    final isPartial = _group?.isPartial ?? false;

    return RefreshIndicator(
      onRefresh: _refresh,
      color: const Color(0xFF1B3A6B),
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _buildYearFilter(),
          const SizedBox(height: 16),
          _buildCollapsibleSection(
            sectionKey: _kMonthly,
            title: 'Evolución mensual',
            icon: Icons.trending_up_rounded,
            contentBuilder: _buildMonthlyContent,
          ),
          const SizedBox(height: 12),
          _buildCollapsibleSection(
            sectionKey: _kSessions,
            title: 'Detalle por sesión',
            icon: Icons.receipt_long_rounded,
            contentBuilder: _buildSessionsContent,
          ),
          const SizedBox(height: 12),
          if (isPartial)
            _buildPartialModeNote()
          else
            _buildCollapsibleSection(
              sectionKey: _kTopSavers,
              title: 'Top aportadores',
              icon: Icons.emoji_events_rounded,
              contentBuilder: _buildTopSaversContent,
            ),
          const SizedBox(height: 12),
          _buildCollapsibleSection(
            sectionKey: _kTopAttendance,
            title: 'Mejor asistencia',
            icon: Icons.verified_rounded,
            contentBuilder: _buildTopAttendanceContent,
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _buildCollapsibleSection({
    required String sectionKey,
    required String title,
    required IconData icon,
    required Widget Function() contentBuilder,
  }) {
    final expanded = _expanded.contains(sectionKey);
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12, offset: const Offset(0, 4)),
        ],
      ),
      child: Column(
        children: [
          InkWell(
            borderRadius: BorderRadius.circular(16),
            onTap: () => _toggleSection(sectionKey),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              child: Row(
                children: [
                  Icon(icon, size: 16, color: const Color(0xFF1B3A6B)),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      title,
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF1B3A6B),
                      ),
                    ),
                  ),
                  AnimatedRotation(
                    turns: expanded ? 0.5 : 0,
                    duration: const Duration(milliseconds: 200),
                    child: Icon(Icons.keyboard_arrow_down_rounded, color: Colors.grey.shade500),
                  ),
                ],
              ),
            ),
          ),
          if (expanded)
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
              child: _buildSectionBody(sectionKey, contentBuilder),
            ),
        ],
      ),
    );
  }

  Widget _buildSectionBody(String key, Widget Function() contentBuilder) {
    if (_sectionLoading.contains(key)) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 24),
        child: Center(
          child: SizedBox(
            width: 24,
            height: 24,
            child: CircularProgressIndicator(strokeWidth: 2.5, color: Color(0xFF1B3A6B)),
          ),
        ),
      );
    }
    final error = _sectionError[key];
    if (error != null) {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 12),
        child: Column(
          children: [
            Text(
              error,
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
            ),
            const SizedBox(height: 8),
            TextButton.icon(
              onPressed: () => _loadSection(key),
              style: TextButton.styleFrom(foregroundColor: const Color(0xFF1B3A6B)),
              icon: const Icon(Icons.refresh_rounded, size: 18),
              label: const Text(
                'Reintentar',
                style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
              ),
            ),
          ],
        ),
      );
    }
    if (!_isSectionLoaded(key)) return const SizedBox.shrink();
    return contentBuilder();
  }

  Widget _buildMonthlyContent() {
    final monthly = _monthly!;
    if (monthly.isEmpty) return _quietEmpty('Sin datos para este período');
    return Column(
      children: [
        _buildSavingsLineChart(monthly),
        const SizedBox(height: 16),
        _buildAttendanceBarChart(monthly),
      ],
    );
  }

  Widget _buildSessionsContent() => _buildSessionsSection(_sessions!);

  Widget _buildTopSaversContent() {
    final list = _topSavers!;
    if (list.isEmpty) return _quietEmpty('Sin datos de aportes');
    return _buildTopSaversCard(list);
  }

  Widget _buildTopAttendanceContent() {
    final list = _topAttendance!;
    if (list.isEmpty) return _quietEmpty('Sin datos de asistencia');
    return _buildTopAttendanceCard(list);
  }

  Widget _quietEmpty(String message) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 16),
      child: Center(
        child: Text(
          message,
          style: TextStyle(color: Colors.grey.shade500, fontSize: 13),
        ),
      ),
    );
  }

  Widget _buildSessionsSection(List<GroupSessionRow> sessions) {
    if (sessions.isEmpty) {
      return Container(
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
        ),
        child: Center(
          child: Text(
            'Sin sesiones registradas',
            style: TextStyle(color: Colors.grey.shade500, fontSize: 14),
          ),
        ),
      );
    }

    // Backend sends sessions ordered by number ascending; render newest first.
    final newestFirst = sessions.reversed.toList();
    final visible = _showAllSessions
        ? newestFirst
        : newestFirst.take(_visibleSessionCount).toList();
    final hasMore = newestFirst.length > _visibleSessionCount;

    return Column(
      children: [
        for (final session in visible) ...[
          _buildSessionCard(session),
          const SizedBox(height: 12),
        ],
        if (hasMore)
          TextButton.icon(
            onPressed: () => setState(() => _showAllSessions = !_showAllSessions),
            style: TextButton.styleFrom(foregroundColor: const Color(0xFF1B3A6B)),
            icon: Icon(
              _showAllSessions
                  ? Icons.keyboard_arrow_up_rounded
                  : Icons.keyboard_arrow_down_rounded,
            ),
            label: Text(
              _showAllSessions
                  ? 'Ver menos'
                  : 'Ver todas las sesiones (${newestFirst.length})',
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
            ),
          ),
      ],
    );
  }

  Widget _buildSessionCard(GroupSessionRow session) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12, offset: const Offset(0, 4)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  'Sesión #${session.number} · ${_fmtSessionDate(session.date)}',
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF1A1A2E),
                  ),
                ),
              ),
              if (!session.isClosed)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: const Color(0xFFE65100).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: const Text(
                    'Abierta',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFFE65100),
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            'Asistencia: ${session.attended}/${session.totalMembers} '
            '(${session.attendanceRate.toStringAsFixed(0)}%)',
            style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
          ),
          const SizedBox(height: 10),
          Divider(height: 1, color: Colors.grey.shade100),
          const SizedBox(height: 10),
          _sessionAmountRow(Icons.savings_rounded, 'Ahorro', session.savings, const Color(0xFF0D7C5F)),
          const SizedBox(height: 8),
          _sessionAmountRow(Icons.shield_rounded, 'Emergencia', session.emergency, const Color(0xFF1B3A6B)),
          const SizedBox(height: 8),
          _sessionAmountRow(Icons.gavel_rounded, 'Multas', session.fines, const Color(0xFFE65100)),
        ],
      ),
    );
  }

  Widget _sessionAmountRow(IconData icon, String label, double value, Color color) {
    return Row(
      children: [
        Icon(icon, size: 15, color: color),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            label,
            style: const TextStyle(fontSize: 13, color: Color(0xFF444B60)),
          ),
        ),
        Text(
          'Bs. ${_fmt(value)}',
          style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: color),
        ),
      ],
    );
  }

  String _fmtSessionDate(String? raw) {
    if (raw == null || raw.isEmpty) return '—';
    final d = DateTime.tryParse(raw);
    if (d == null) return raw;
    const months = [
      'ene', 'feb', 'mar', 'abr', 'may', 'jun',
      'jul', 'ago', 'sep', 'oct', 'nov', 'dic',
    ];
    return '${d.day} ${months[d.month - 1]} ${d.year}';
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
                _onYearChanged(y);
              },
            ),
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
      legend: _legend(const Color(0xFF1B3A6B), 'Asistencia mensual'),
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
