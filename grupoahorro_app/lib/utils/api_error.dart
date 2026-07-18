import 'package:dio/dio.dart';

/// Maps a [DioException] from the mobile write API into a user-facing
/// Spanish message plus the fields a caller needs to react (status code,
/// server-provided `reason`, and field-level validation errors on 422).
class ApiError {
  final String message;
  final int? statusCode;
  final String? reason;
  final Map<String, List<String>>? fieldErrors;

  const ApiError(
    this.message, {
    this.statusCode,
    this.reason,
    this.fieldErrors,
  });

  factory ApiError.fromDioException(DioException e) {
    final status = e.response?.statusCode;
    final data = e.response?.data;

    if (status == 403) {
      final reason = data is Map ? data['reason'] as String? : null;
      final msg = data is Map ? data['error'] as String? : null;
      if (reason == 'closed') {
        return ApiError(
          msg ?? 'Reunión cerrada, no se puede editar.',
          statusCode: status,
          reason: reason,
        );
      }
      return ApiError(
        msg ?? 'No tenés permisos para realizar esta acción.',
        statusCode: status,
        reason: reason ?? 'role',
      );
    }

    if (status == 409) {
      final reason = data is Map ? data['reason'] as String? : null;
      if (reason == 'meeting_already_open') {
        return ApiError(
          'Ya hay una reunión abierta para este grupo. Abriendo para cargar…',
          statusCode: status,
          reason: reason,
        );
      }
      final msg = data is Map ? data['error'] as String? : null;
      return ApiError(
        msg ?? 'Conflicto al abrir la reunión.',
        statusCode: status,
        reason: reason,
      );
    }

    if (status == 422) {
      final errors = data is Map ? data['errors'] : null;
      Map<String, List<String>>? fieldErrors;
      if (errors is Map) {
        fieldErrors = errors.map(
          (key, value) => MapEntry(
            key.toString(),
            value is List ? value.map((v) => v.toString()).toList() : <String>[],
          ),
        );
      }
      final msg = data is Map ? data['message'] as String? : null;
      return ApiError(
        msg ?? 'Los datos ingresados no son válidos.',
        statusCode: status,
        reason: 'validation',
        fieldErrors: fieldErrors,
      );
    }

    return const ApiError('Ocurrió un error. Revisá tu conexión e intentá de nuevo.');
  }
}
