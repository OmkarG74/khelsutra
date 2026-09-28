import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import '../errors/app_exception.dart';
import '../storage/token_storage.dart';
import 'api_config.dart';
import 'api_response.dart';

class ApiClient {
  final http.Client _client;
  final String _baseUrl;

  ApiClient({http.Client? client, String? baseUrl})
      : _client = client ?? http.Client(),
        _baseUrl = baseUrl ?? ApiConfig.activeBaseUrl;

  Future<Map<String, String>> _getHeaders() async {
    final token = await TokenStorage.getToken();
    final orgId = await TokenStorage.getOrganizationId();
    final headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    if (orgId != null && orgId.isNotEmpty) {
      headers['X-Organization-ID'] = orgId;
    }
    return headers;
  }

  Future<ApiResponse<T>> get<T>(String endpoint, {T Function(dynamic)? fromJson}) async {
    final uri = Uri.parse('$_baseUrl$endpoint');
    debugPrint('ApiClient [GET] -> $uri');
    try {
      final headers = await _getHeaders();
      final response = await _client.get(
        uri,
        headers: headers,
      ).timeout(ApiConfig.timeoutDuration);
      debugPrint('ApiClient [GET] <- ${response.statusCode} from $uri');
      return _processResponse<T>(response, fromJson);
    } catch (e) {
      debugPrint('ApiClient [GET] ERROR on $uri: $e');
      throw _handleError(e);
    }
  }

  Future<ApiResponse<T>> post<T>(String endpoint, {dynamic body, T Function(dynamic)? fromJson}) async {
    final uri = Uri.parse('$_baseUrl$endpoint');
    debugPrint('ApiClient [POST] -> $uri');
    try {
      final headers = await _getHeaders();
      final response = await _client.post(
        uri,
        headers: headers,
        body: body != null ? jsonEncode(body) : null,
      ).timeout(ApiConfig.timeoutDuration);
      debugPrint('ApiClient [POST] <- ${response.statusCode} from $uri');
      return _processResponse<T>(response, fromJson);
    } catch (e) {
      debugPrint('ApiClient [POST] ERROR on $uri: $e');
      throw _handleError(e);
    }
  }

  Future<ApiResponse<T>> put<T>(String endpoint, {dynamic body, T Function(dynamic)? fromJson}) async {
    final uri = Uri.parse('$_baseUrl$endpoint');
    debugPrint('ApiClient [PUT] -> $uri');
    try {
      final headers = await _getHeaders();
      final response = await _client.put(
        uri,
        headers: headers,
        body: body != null ? jsonEncode(body) : null,
      ).timeout(ApiConfig.timeoutDuration);
      debugPrint('ApiClient [PUT] <- ${response.statusCode} from $uri');
      return _processResponse<T>(response, fromJson);
    } catch (e) {
      debugPrint('ApiClient [PUT] ERROR on $uri: $e');
      throw _handleError(e);
    }
  }

  Future<ApiResponse<T>> patch<T>(String endpoint, {dynamic body, T Function(dynamic)? fromJson}) async {
    final uri = Uri.parse('$_baseUrl$endpoint');
    debugPrint('ApiClient [PATCH] -> $uri');
    try {
      final headers = await _getHeaders();
      final response = await _client.patch(
        uri,
        headers: headers,
        body: body != null ? jsonEncode(body) : null,
      ).timeout(ApiConfig.timeoutDuration);
      debugPrint('ApiClient [PATCH] <- ${response.statusCode} from $uri');
      return _processResponse<T>(response, fromJson);
    } catch (e) {
      debugPrint('ApiClient [PATCH] ERROR on $uri: $e');
      throw _handleError(e);
    }
  }

  Future<ApiResponse<T>> delete<T>(String endpoint, {T Function(dynamic)? fromJson}) async {
    final uri = Uri.parse('$_baseUrl$endpoint');
    debugPrint('ApiClient [DELETE] -> $uri');
    try {
      final headers = await _getHeaders();
      final response = await _client.delete(
        uri,
        headers: headers,
      ).timeout(ApiConfig.timeoutDuration);
      debugPrint('ApiClient [DELETE] <- ${response.statusCode} from $uri');
      return _processResponse<T>(response, fromJson);
    } catch (e) {
      debugPrint('ApiClient [DELETE] ERROR on $uri: $e');
      throw _handleError(e);
    }
  }

  ApiResponse<T> _processResponse<T>(http.Response response, T Function(dynamic)? fromJson) {
    final body = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return ApiResponse.fromJson(body, fromJson);
    } else if (response.statusCode == 401) {
      throw UnauthorizedException(body['message'] ?? 'Unauthorized');
    } else if (response.statusCode == 403) {
      throw ForbiddenException(body['message'] ?? 'Forbidden');
    } else if (response.statusCode == 404) {
      throw NotFoundException(body['message'] ?? 'Not Found');
    } else if (response.statusCode == 422) {
      throw ValidationException(body['errors'] ?? {}, body['message'] ?? 'Validation Failed');
    } else {
      throw AppException(body['message'] ?? 'Server error', response.statusCode);
    }
  }

  Exception _handleError(dynamic error) {
    if (error is AppException) return error;
    return NetworkException(error.toString());
  }
}
