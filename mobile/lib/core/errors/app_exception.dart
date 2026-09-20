class AppException implements Exception {
  final String message;
  final int? statusCode;

  AppException(this.message, [this.statusCode]);

  @override
  String toString() => message;
}

class NetworkException extends AppException {
  NetworkException([String message = 'Network connection failure. Please verify internet connectivity.'])
      : super(message);
}

class UnauthorizedException extends AppException {
  UnauthorizedException([String message = 'Unauthorized or session expired.'])
      : super(message, 401);
}

class ForbiddenException extends AppException {
  ForbiddenException([String message = 'Permission denied for this action.'])
      : super(message, 403);
}

class NotFoundException extends AppException {
  NotFoundException([String message = 'Requested resource not found.'])
      : super(message, 404);
}

class ValidationException extends AppException {
  final Map<String, dynamic> errors;
  ValidationException(this.errors, [String message = 'Validation failed.'])
      : super(message, 422);
}
