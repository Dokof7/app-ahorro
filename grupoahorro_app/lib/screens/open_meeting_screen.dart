import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../models/meeting_conflict.dart';
import '../models/open_meeting.dart';
import '../services/meeting_write_service.dart';
import '../utils/api_error.dart';

const _kPrimary = Color(0xFF1B3A6B);

/// Unified screen to open a meeting (if none is open) and load
/// attendance/contributions for the group's currently open meeting, via two
/// tabs sharing one header (design ADR-9/ADR-10).
class OpenMeetingScreen extends StatefulWidget {
  final int groupId;

  const OpenMeetingScreen({super.key, required this.groupId});

  @override
  State<OpenMeetingScreen> createState() => _OpenMeetingScreenState();
}

class _OpenMeetingScreenState extends State<OpenMeetingScreen>
    with SingleTickerProviderStateMixin {
  final _service = MeetingWriteService();
  late final TabController _tabController;

  OpenMeeting? _meeting;
  bool _loading = true;
  bool _saving = false;
  String? _error;

  // ---- Attendance tab state ----
  final Map<int, String> _attendanceStatus = {};
  final Map<int, TextEditingController> _attendanceObsCtrls = {};
  static const _statusOptions = [
    ('present', 'Asistió', Icons.check_circle_rounded, Color(0xFF0D7C5F)),
    ('late', 'Atraso', Icons.watch_later_rounded, Color(0xFFE65100)),
    ('absent', 'Falta', Icons.cancel_rounded, Color(0xFFD32F2F)),
    ('excused', 'Falta c/Permiso', Icons.info_rounded, Color(0xFF1565C0)),
  ];

  // ---- Contributions tab state (non-partial: keyed by memberId) ----
  final Map<int, TextEditingController> _sharesCtrls = {};
  final Map<int, TextEditingController> _emergencyCtrls = {};
  final Map<int, TextEditingController> _fineCtrls = {};

  // Semantic accents per contribution field, matching the attendance palette.
  static const _sharesColor = Color(0xFF0D7C5F);
  static const _emergencyColor = Color(0xFFE65100);
  static const _fineColor = Color(0xFFD32F2F);

  // ---- Contributions tab state (partial: single group total) ----
  final _totalSharesCtrl = TextEditingController();
  final _totalEmergencyCtrl = TextEditingController();
  final _totalFineCtrl = TextEditingController();
  final _totalObsCtrl = TextEditingController();

  // ---- Create-meeting state ----
  DateTime _createDate = DateTime.now();
  bool _creating = false;
  String? _createFieldError;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _load();
  }

  @override
  void dispose() {
    _tabController.dispose();
    for (final c in _attendanceObsCtrls.values) {
      c.dispose();
    }
    for (final c in _sharesCtrls.values) {
      c.dispose();
    }
    for (final c in _emergencyCtrls.values) {
      c.dispose();
    }
    for (final c in _fineCtrls.values) {
      c.dispose();
    }
    _totalSharesCtrl.dispose();
    _totalEmergencyCtrl.dispose();
    _totalFineCtrl.dispose();
    _totalObsCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final meeting = await _service.fetchOpenMeeting(widget.groupId);
      if (!mounted) return;
      setState(() {
        _meeting = meeting;
        if (meeting != null) _seedControllers(meeting);
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = 'No se pudo cargar la reunión.\nRevisá tu conexión.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _seedControllers(OpenMeeting meeting) {
    // Attendance — persistent controllers keyed by memberId to avoid cursor
    // loss on rebuild.
    _attendanceStatus.clear();
    for (final c in _attendanceObsCtrls.values) {
      c.dispose();
    }
    _attendanceObsCtrls.clear();
    for (final row in meeting.attendances) {
      _attendanceStatus[row.memberId] = row.status;
      _attendanceObsCtrls[row.memberId] =
          TextEditingController(text: row.observations ?? '');
    }

    // Contributions — full group: persistent controllers keyed by memberId.
    for (final c in _sharesCtrls.values) {
      c.dispose();
    }
    for (final c in _emergencyCtrls.values) {
      c.dispose();
    }
    for (final c in _fineCtrls.values) {
      c.dispose();
    }
    _sharesCtrls.clear();
    _emergencyCtrls.clear();
    _fineCtrls.clear();
    for (final row in meeting.contributions) {
      _sharesCtrls[row.memberId] = TextEditingController(text: row.shares.toString());
      _emergencyCtrls[row.memberId] =
          TextEditingController(text: _fmtInput(row.emergencyFund));
      _fineCtrls[row.memberId] = TextEditingController(text: _fmtInput(row.fine));
    }

    // Contributions — partial group: single group total.
    final totals = meeting.totals;
    _totalSharesCtrl.text = totals != null ? totals.shares.toString() : '0';
    _totalEmergencyCtrl.text = totals != null ? _fmtInput(totals.emergencyFund) : '0';
    _totalFineCtrl.text = totals != null ? _fmtInput(totals.fine) : '0';
    _totalObsCtrl.text = totals?.observations ?? '';
  }

  String _fmtInput(double v) => v == v.roundToDouble() ? v.toStringAsFixed(0) : v.toString();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF2F4F8),
      appBar: AppBar(
        backgroundColor: _kPrimary,
        foregroundColor: Colors.white,
        title: const Text('Reunión'),
        actions: [
          if (_meeting != null && !_loading && _error == null)
            IconButton(
              tooltip: 'Cerrar reunión',
              icon: const Icon(Icons.lock_rounded),
              onPressed: _saving ? null : _confirmCloseMeeting,
            ),
        ],
        bottom: _meeting != null
            ? TabBar(
                controller: _tabController,
                indicatorColor: Colors.white,
                labelColor: Colors.white,
                unselectedLabelColor: Colors.white70,
                tabs: const [
                  Tab(text: 'Asistencia'),
                  Tab(text: 'Aportes'),
                ],
              )
            : null,
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: _kPrimary))
          : _error != null
              ? _buildError()
              : _meeting == null
                  ? _buildCreateState()
                  : _buildLoadedState(),
      floatingActionButton: _meeting == null || _loading || _error != null
          ? null
          : AnimatedBuilder(
              animation: _tabController,
              builder: (_, _) => FloatingActionButton.extended(
                backgroundColor: _kPrimary,
                foregroundColor: Colors.white,
                elevation: 4,
                onPressed: _saving
                    ? null
                    : (_tabController.index == 0 ? _submitAttendance : _submitContributions),
                icon: _saving
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : const Icon(Icons.save_rounded),
                label: Text(
                  _saving ? 'Guardando…' : 'Guardar',
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 15,
                    letterSpacing: 0.3,
                  ),
                ),
              ),
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
            Icon(Icons.cloud_off_rounded, size: 48, color: Colors.grey.shade400),
            const SizedBox(height: 16),
            Text(
              _error!,
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey.shade600, fontSize: 15, height: 1.5),
            ),
            const SizedBox(height: 20),
            FilledButton.icon(
              onPressed: _load,
              style: FilledButton.styleFrom(backgroundColor: _kPrimary),
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('Reintentar'),
            ),
          ],
        ),
      ),
    );
  }

  // ------------------------------------------------------------------
  // CREATE state (design ADR-10)
  // ------------------------------------------------------------------

  Widget _buildCreateState() {
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(20),
            boxShadow: [
              BoxShadow(color: Colors.black.withValues(alpha: 0.06), blurRadius: 16, offset: const Offset(0, 6)),
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Icon(Icons.event_available_rounded, color: _kPrimary, size: 40),
              const SizedBox(height: 16),
              const Text(
                'No hay una reunión abierta',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700, color: Color(0xFF1A1A2E)),
              ),
              const SizedBox(height: 6),
              Text(
                'Elegí la fecha para abrir una nueva reunión del grupo.',
                style: TextStyle(fontSize: 13, color: Colors.grey.shade600, height: 1.4),
              ),
              const SizedBox(height: 20),
              InkWell(
                borderRadius: BorderRadius.circular(12),
                onTap: _creating ? null : _pickDate,
                child: InputDecorator(
                  decoration: InputDecoration(
                    labelText: 'Fecha de la reunión',
                    errorText: _createFieldError,
                    prefixIcon: const Icon(Icons.calendar_today_rounded, color: _kPrimary),
                    filled: true,
                    fillColor: const Color(0xFFF7F8FC),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                  ),
                  child: Text(_fmtDate(_createDate), style: const TextStyle(fontSize: 15)),
                ),
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: _creating ? null : _submitCreate,
                  style: FilledButton.styleFrom(
                    backgroundColor: _kPrimary,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  icon: _creating
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                        )
                      : const Icon(Icons.add_circle_outline_rounded),
                  label: Text(_creating ? 'Abriendo…' : 'Abrir reunión'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _fmtDate(DateTime d) {
    final months = [
      'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
      'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ];
    return '${d.day} de ${months[d.month - 1]} de ${d.year}';
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _createDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 7)),
      builder: (context, child) => Theme(
        data: Theme.of(context).copyWith(
          colorScheme: Theme.of(context).colorScheme.copyWith(primary: _kPrimary),
        ),
        child: child!,
      ),
    );
    if (picked != null) setState(() => _createDate = picked);
  }

  Future<void> _submitCreate() async {
    setState(() {
      _creating = true;
      _createFieldError = null;
    });
    final iso = '${_createDate.year.toString().padLeft(4, '0')}-'
        '${_createDate.month.toString().padLeft(2, '0')}-'
        '${_createDate.day.toString().padLeft(2, '0')}';
    try {
      final meeting = await _service.openMeeting(widget.groupId, iso);
      if (!mounted) return;
      setState(() {
        _meeting = meeting;
        _seedControllers(meeting);
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Reunión abierta.'), backgroundColor: Color(0xFF0D7C5F)),
      );
    } on DioException catch (e) {
      final apiError = ApiError.fromDioException(e);
      if (!mounted) return;
      if (apiError.statusCode == 409 && apiError.reason == 'meeting_already_open') {
        final data = e.response?.data;
        if (data is Map<String, dynamic> && data['meeting'] is Map<String, dynamic>) {
          MeetingConflict.fromJson(data['meeting'] as Map<String, dynamic>);
        }
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(apiError.message)));
        await _load();
        return;
      }
      if (apiError.statusCode == 422) {
        setState(() => _createFieldError = apiError.fieldErrors?['meeting_date']?.first ?? apiError.message);
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(apiError.message), backgroundColor: Colors.red.shade600),
      );
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('No se pudo abrir la reunión.'), backgroundColor: Colors.red.shade600),
      );
    } finally {
      if (mounted) setState(() => _creating = false);
    }
  }

  /// Confirms and closes the open meeting. On success the screen reloads,
  /// which lands on the create state so a new meeting can be opened.
  Future<void> _confirmCloseMeeting() async {
    final meeting = _meeting;
    if (meeting == null) return;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('¿Cerrar la reunión?'),
        content: const Text(
          'Se calculará el resumen final y no se podrán registrar más datos en esta reunión.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: _fineColor),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Cerrar'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    setState(() => _saving = true);
    try {
      await _service.closeMeeting(meeting.id);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Reunión cerrada.'), backgroundColor: Color(0xFF0D7C5F)),
      );
      await _load();
    } on DioException catch (e) {
      final apiError = ApiError.fromDioException(e);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(apiError.message), backgroundColor: Colors.red.shade600),
      );
      // Someone else may have closed it already — refresh to the real state.
      if (apiError.reason == 'closed') await _load();
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('No se pudo cerrar la reunión.'), backgroundColor: Colors.red.shade600),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  // ------------------------------------------------------------------
  // LOADED state: header + tabs
  // ------------------------------------------------------------------

  Widget _buildLoadedState() {
    return RefreshIndicator(
      onRefresh: _load,
      color: _kPrimary,
      child: Column(
        children: [
          _buildMeetingHeader(_meeting!),
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                _attendanceTab(),
                _contributionsTab(),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMeetingHeader(OpenMeeting meeting) {
    return Container(
      width: double.infinity,
      color: _kPrimary,
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 16),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              'Reunión N° ${meeting.meetingNumber}',
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              '${meeting.meetingDate} · ${meeting.month}',
              style: TextStyle(color: Colors.white.withValues(alpha: 0.85), fontSize: 12),
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }

  // ------------------------------------------------------------------
  // Attendance tab
  // ------------------------------------------------------------------

  Widget _attendanceTab() {
    final meeting = _meeting!;
    if (meeting.attendances.isEmpty) {
      return _emptyState('Sin miembros para registrar asistencia.');
    }
    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 96),
      itemCount: meeting.attendances.length,
      separatorBuilder: (_, _) => const SizedBox(height: 10),
      itemBuilder: (_, i) => _attendanceCard(meeting.attendances[i]),
    );
  }

  Widget _attendanceCard(AttendanceRow row) {
    final name = row.member?.fullName ?? 'Miembro #${row.memberId}';
    final obsCtrl = _attendanceObsCtrls[row.memberId] ??= TextEditingController(text: row.observations ?? '');
    final currentStatus = _attendanceStatus[row.memberId] ?? row.status;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(name, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _statusOptions.map((opt) {
              final (value, label, icon, color) = opt;
              final selected = currentStatus == value;
              return ChoiceChip(
                selected: selected,
                onSelected: (_) => setState(() => _attendanceStatus[row.memberId] = value),
                avatar: Icon(icon, size: 16, color: selected ? Colors.white : color),
                label: Text(label, style: const TextStyle(fontSize: 13)),
                selectedColor: color,
                labelStyle: TextStyle(color: selected ? Colors.white : color, fontWeight: FontWeight.w600),
                backgroundColor: color.withValues(alpha: 0.08),
                side: BorderSide(color: color.withValues(alpha: 0.3)),
              );
            }).toList(),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: obsCtrl,
            style: const TextStyle(fontSize: 14),
            decoration: InputDecoration(
              labelText: 'Observaciones',
              isDense: true,
              filled: true,
              fillColor: const Color(0xFFF7F8FC),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _submitAttendance() async {
    final meeting = _meeting;
    if (meeting == null) return;
    setState(() => _saving = true);
    try {
      final payload = meeting.attendances
          .map((row) => {
                'id': row.id,
                'status': _attendanceStatus[row.memberId] ?? row.status,
                'observations': _attendanceObsCtrls[row.memberId]?.text.trim().isEmpty == true
                    ? null
                    : _attendanceObsCtrls[row.memberId]?.text.trim(),
              })
          .toList();
      await _service.submitAttendance(meeting.id, payload);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Asistencia guardada.'), backgroundColor: Color(0xFF0D7C5F)),
      );
    } on DioException catch (e) {
      if (!mounted) return;
      final apiError = ApiError.fromDioException(e);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(apiError.message), backgroundColor: Colors.red.shade600),
      );
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('No se pudo guardar la asistencia.'), backgroundColor: Colors.red.shade600),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  // ------------------------------------------------------------------
  // Contributions tab — forks on is_partial (design ADR-9)
  // ------------------------------------------------------------------

  Widget _contributionsTab() {
    final meeting = _meeting!;
    return meeting.isPartial ? _partialContributionsForm() : _fullContributionsList(meeting);
  }

  Widget _fullContributionsList(OpenMeeting meeting) {
    if (meeting.contributions.isEmpty) {
      return _emptyState('Sin miembros para registrar aportes.');
    }
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
          child: _liveTotalsBar(),
        ),
        Expanded(
          child: ListView.separated(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 96),
            itemCount: meeting.contributions.length,
            separatorBuilder: (_, _) => const SizedBox(height: 10),
            itemBuilder: (_, i) => _contributionCard(meeting.contributions[i]),
          ),
        ),
      ],
    );
  }

  /// Sticky summary above the list: sums every member's fields as the user
  /// types, so the treasurer can reconcile cash without adding by hand.
  Widget _liveTotalsBar() {
    final listenable = Listenable.merge([
      ..._sharesCtrls.values,
      ..._emergencyCtrls.values,
      ..._fineCtrls.values,
    ]);
    return ListenableBuilder(
      listenable: listenable,
      builder: (_, _) {
        final shares = _sharesCtrls.values
            .fold<int>(0, (sum, c) => sum + (int.tryParse(c.text) ?? 0));
        final emergency = _emergencyCtrls.values
            .fold<double>(0, (sum, c) => sum + (double.tryParse(c.text) ?? 0));
        final fine = _fineCtrls.values
            .fold<double>(0, (sum, c) => sum + (double.tryParse(c.text) ?? 0));

        return Container(
          padding: const EdgeInsets.symmetric(vertical: 14),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(14),
            boxShadow: [
              BoxShadow(
                  color: Colors.black.withValues(alpha: 0.06),
                  blurRadius: 12,
                  offset: const Offset(0, 4)),
            ],
          ),
          child: IntrinsicHeight(
            child: Row(
              children: [
                _totalTile('totals-shares', Icons.savings_rounded, _sharesColor,
                    'Acciones', shares.toString()),
                VerticalDivider(width: 1, color: Colors.grey.shade200),
                _totalTile('totals-emergency', Icons.health_and_safety_rounded,
                    _emergencyColor, 'Emergencia', _fmtInput(emergency)),
                VerticalDivider(width: 1, color: Colors.grey.shade200),
                _totalTile('totals-fine', Icons.gavel_rounded, _fineColor,
                    'Multas', _fmtInput(fine)),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _totalTile(
      String key, IconData icon, Color color, String label, String value) {
    return Expanded(
      child: Column(
        children: [
          Icon(icon, size: 18, color: color),
          const SizedBox(height: 4),
          Text(
            value,
            key: Key(key),
            style: TextStyle(
                fontSize: 17, fontWeight: FontWeight.w800, color: color),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: TextStyle(
                fontSize: 11,
                color: Colors.grey.shade600,
                fontWeight: FontWeight.w600),
          ),
        ],
      ),
    );
  }

  Widget _contributionCard(ContributionRow row) {
    final name = row.member?.fullName ?? 'Miembro #${row.memberId}';
    final sharesCtrl = _sharesCtrls[row.memberId] ??= TextEditingController(text: row.shares.toString());
    final emergencyCtrl = _emergencyCtrls[row.memberId] ??= TextEditingController(text: _fmtInput(row.emergencyFund));
    final fineCtrl = _fineCtrls[row.memberId] ??= TextEditingController(text: _fmtInput(row.fine));

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 16,
                backgroundColor: _kPrimary.withValues(alpha: 0.12),
                child: Text(
                  name.isNotEmpty ? name[0].toUpperCase() : '?',
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: _kPrimary,
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  name,
                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _numberField(sharesCtrl, 'Acciones',
                    digitsOnly: true, icon: Icons.savings_rounded, accent: _sharesColor),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _numberField(emergencyCtrl, 'Emergencia',
                    icon: Icons.health_and_safety_rounded, accent: _emergencyColor),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _numberField(fineCtrl, 'Multa',
                    icon: Icons.gavel_rounded, accent: _fineColor),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _partialContributionsForm() {
    return SingleChildScrollView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 96),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          boxShadow: [
            BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2)),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Aporte grupal',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 4),
            Text(
              'Este grupo registra un único total por reunión.',
              style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
            ),
            const SizedBox(height: 14),
            Row(
              children: [
                Expanded(
                  child: _numberField(_totalSharesCtrl, 'Acciones',
                      digitsOnly: true, icon: Icons.savings_rounded, accent: _sharesColor),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _numberField(_totalEmergencyCtrl, 'Emergencia',
                      icon: Icons.health_and_safety_rounded, accent: _emergencyColor),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _numberField(_totalFineCtrl, 'Multa',
                      icon: Icons.gavel_rounded, accent: _fineColor),
                ),
              ],
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _totalObsCtrl,
              style: const TextStyle(fontSize: 14),
              decoration: InputDecoration(
                labelText: 'Observaciones',
                isDense: true,
                filled: true,
                fillColor: const Color(0xFFF7F8FC),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _numberField(
    TextEditingController ctrl,
    String label, {
    bool digitsOnly = false,
    IconData? icon,
    Color? accent,
  }) {
    return TextField(
      controller: ctrl,
      keyboardType: digitsOnly
          ? TextInputType.number
          : const TextInputType.numberWithOptions(decimal: true),
      inputFormatters: digitsOnly
          ? [FilteringTextInputFormatter.digitsOnly]
          : [FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d*'))],
      style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: TextStyle(
          color: accent ?? Colors.grey.shade600,
          fontWeight: FontWeight.w600,
          fontSize: 13,
        ),
        prefixIcon: icon == null ? null : Icon(icon, size: 18, color: accent),
        prefixIconConstraints: const BoxConstraints(minWidth: 34),
        isDense: true,
        filled: true,
        fillColor: accent?.withValues(alpha: 0.06) ?? const Color(0xFFF7F8FC),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: accent ?? _kPrimary, width: 1.5),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
      ),
    );
  }

  Future<void> _submitContributions() async {
    final meeting = _meeting;
    if (meeting == null) return;
    setState(() => _saving = true);
    try {
      if (meeting.isPartial) {
        await _service.submitGroupTotals(
          meeting.id,
          shares: int.tryParse(_totalSharesCtrl.text) ?? 0,
          emergencyFund: double.tryParse(_totalEmergencyCtrl.text) ?? 0,
          fine: double.tryParse(_totalFineCtrl.text) ?? 0,
          observations: _totalObsCtrl.text.trim().isEmpty ? null : _totalObsCtrl.text.trim(),
        );
      } else {
        final payload = meeting.contributions
            .map((row) => {
                  'id': row.id,
                  'shares': int.tryParse(_sharesCtrls[row.memberId]?.text ?? '') ?? 0,
                  'emergency_fund': double.tryParse(_emergencyCtrls[row.memberId]?.text ?? '') ?? 0,
                  'fine': double.tryParse(_fineCtrls[row.memberId]?.text ?? '') ?? 0,
                  'confirmed': row.confirmed,
                })
            .toList();
        await _service.submitContributions(meeting.id, payload);
      }
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Aportes guardados.'), backgroundColor: Color(0xFF0D7C5F)),
      );
    } on DioException catch (e) {
      if (!mounted) return;
      final apiError = ApiError.fromDioException(e);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(apiError.message), backgroundColor: Colors.red.shade600),
      );
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('No se pudieron guardar los aportes.'), backgroundColor: Colors.red.shade600),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Widget _emptyState(String message) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.inbox_rounded, size: 48, color: Colors.grey.shade300),
            const SizedBox(height: 12),
            Text(message, style: TextStyle(color: Colors.grey.shade500, fontSize: 14)),
          ],
        ),
      ),
    );
  }
}
