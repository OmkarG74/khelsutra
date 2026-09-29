import '../../../core/network/api_client.dart';
import '../../../core/network/api_response.dart';
import '../../../core/network/json_parser.dart';
import '../../../core/storage/token_storage.dart';

class AuthService {
  final ApiClient _client;

  AuthService({ApiClient? client}) : _client = client ?? ApiClient();

  Future<ApiResponse<Map<String, dynamic>>> login({
    required String email,
    required String password,
    String? organizationCode,
  }) async {
    final response = await _client.post<Map<String, dynamic>>(
      '/auth/login',
      body: {
        'email': email,
        'password': password,
        'organization_code': organizationCode,
      },
      fromJson: (json) => Map<String, dynamic>.from(json),
    );

    if (response.success && response.data != null) {
      final data = response.data!;
      if (data['token'] != null) {
        await TokenStorage.saveToken(data['token'].toString());
      }
      if (data['organization'] != null && data['organization']['id'] != null) {
        await TokenStorage.saveOrganizationId(data['organization']['id'].toString());
      }
      if (data['organization'] != null && data['organization']['name'] != null) {
        await TokenStorage.saveOrgName(data['organization']['name'].toString());
      }
      if (data['role'] != null && data['role']['name'] != null) {
        await TokenStorage.saveRole(data['role']['name'].toString());
      }
      if (data['user'] != null) {
        final u = data['user'];
        if (u['id'] != null) await TokenStorage.saveUserId(parseInt(u['id']));
        if (u['athlete_id'] != null) await TokenStorage.saveAthleteId(parseInt(u['athlete_id']));
        if (u['coach_id'] != null) await TokenStorage.saveCoachId(parseInt(u['coach_id']));
        if (u['employee_id'] != null) await TokenStorage.saveEmployeeId(parseInt(u['employee_id']));
        final fullName = '${u['first_name'] ?? ''} ${u['last_name'] ?? ''}'.trim();
        await TokenStorage.saveUserName(fullName.isNotEmpty ? fullName : (u['email'] ?? 'User'));
        if (u['email'] != null) await TokenStorage.saveUserEmail(u['email'].toString());
      }
    }

    return response;
  }

  Future<ApiResponse<Map<String, dynamic>>> getCurrentUser() async {
    final response = await _client.get<Map<String, dynamic>>(
      '/auth/me',
      fromJson: (json) => Map<String, dynamic>.from(json),
    );

    if (response.success && response.data != null) {
      final u = response.data!;
      if (u['id'] != null) await TokenStorage.saveUserId(parseInt(u['id']));
      if (u['athlete_id'] != null) await TokenStorage.saveAthleteId(parseInt(u['athlete_id']));
      if (u['coach_id'] != null) await TokenStorage.saveCoachId(parseInt(u['coach_id']));
      if (u['employee_id'] != null) await TokenStorage.saveEmployeeId(parseInt(u['employee_id']));
      if (u['role'] != null && u['role']['name'] != null) {
        await TokenStorage.saveRole(u['role']['name'].toString());
      }
      final fullName = '${u['first_name'] ?? ''} ${u['last_name'] ?? ''}'.trim();
      if (fullName.isNotEmpty) await TokenStorage.saveUserName(fullName);
      if (u['email'] != null) await TokenStorage.saveUserEmail(u['email'].toString());
    }

    return response;
  }

  Future<void> logout() async {
    try {
      await _client.post('/auth/logout');
    } catch (_) {}
    await TokenStorage.clear();
  }
}
