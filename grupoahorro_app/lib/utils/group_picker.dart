import 'package:flutter/material.dart';

import '../models/dashboard_stats.dart';

/// Resolves which group a group-scoped screen should target.
///
/// - No groups: shows a snackbar and returns null.
/// - One group: returns it directly, no extra UI.
/// - Multiple groups: shows a bottom sheet so the user picks one;
///   returns null if the sheet is dismissed.
Future<DashboardGroup?> pickGroup(
  BuildContext context,
  List<DashboardGroup> groups,
) async {
  if (groups.isEmpty) {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('No tienes grupos asignados todavía.')),
    );
    return null;
  }
  if (groups.length == 1) return groups.first;

  return showModalBottomSheet<DashboardGroup>(
    context: context,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
    ),
    builder: (sheetContext) => SafeArea(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Padding(
            padding: EdgeInsets.fromLTRB(20, 20, 20, 8),
            child: Text(
              'Selecciona un grupo',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
            ),
          ),
          ...groups.map(
            (g) => ListTile(
              leading: const Icon(Icons.groups_rounded, color: Color(0xFF1B3A6B)),
              title: Text(g.name),
              onTap: () => Navigator.pop(sheetContext, g),
            ),
          ),
          const SizedBox(height: 8),
        ],
      ),
    ),
  );
}
