import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:grupoahorro_app/utils/api_error.dart';

DioException _exceptionWithResponse(int statusCode, dynamic data) {
  final requestOptions = RequestOptions(path: '/test');
  return DioException(
    requestOptions: requestOptions,
    response: Response(
      requestOptions: requestOptions,
      statusCode: statusCode,
      data: data,
    ),
    type: DioExceptionType.badResponse,
  );
}

void main() {
  group('ApiError.fromDioException', () {
    test('maps 409 meeting_already_open to a friendly redirect message', () {
      final error = ApiError.fromDioException(_exceptionWithResponse(409, {
        'error': 'Ya hay una reunión abierta para este grupo.',
        'reason': 'meeting_already_open',
        'meeting': {'id': 1, 'meeting_number': 1, 'meeting_date': '2026-07-16', 'month': 'Julio', 'is_partial': false},
      }));

      expect(error.statusCode, 409);
      expect(error.reason, 'meeting_already_open');
      expect(error.message, contains('Ya hay una reunión abierta'));
    });

    test('maps 403 role to the role-denied message', () {
      final error = ApiError.fromDioException(_exceptionWithResponse(403, {
        'error': 'No tenés permisos para realizar esta acción.',
        'reason': 'role',
      }));

      expect(error.statusCode, 403);
      expect(error.reason, 'role');
      expect(error.message, contains('permisos'));
    });

    test('maps 403 closed to the closed-meeting message', () {
      final error = ApiError.fromDioException(_exceptionWithResponse(403, {
        'error': 'Reunión cerrada, no se puede editar.',
        'reason': 'closed',
      }));

      expect(error.reason, 'closed');
      expect(error.message, contains('cerrada'));
    });

    test('maps 422 to field-level validation errors', () {
      final error = ApiError.fromDioException(_exceptionWithResponse(422, {
        'message': 'The meeting date field is required.',
        'errors': {
          'meeting_date': ['El campo fecha de reunión es obligatorio.'],
        },
      }));

      expect(error.statusCode, 422);
      expect(error.reason, 'validation');
      expect(error.fieldErrors?['meeting_date']?.first, contains('obligatorio'));
    });

    test('falls back to a generic message for unknown statuses', () {
      final error = ApiError.fromDioException(_exceptionWithResponse(500, null));

      expect(error.message, isNotEmpty);
      expect(error.reason, isNull);
    });
  });
}
