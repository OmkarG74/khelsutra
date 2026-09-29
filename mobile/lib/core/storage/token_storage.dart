import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

class TokenStorage {
  static const _secureStorage = FlutterSecureStorage();
  static SharedPreferences? _prefs;

  static String? _token;
  static String? _organizationId;
  static String? _userRole;
  static int? _userId;
  static int? _athleteId;
  static int? _coachId;
  static int? _employeeId;
  static String? _userName;
  static String? _userEmail;
  static String? _orgName;
  static bool _initialized = false;

  /// Initialize persistent storage and restore cached session credentials
  static Future<void> init() async {
    if (_initialized) return;
    try {
      _prefs = await SharedPreferences.getInstance();
      _token = await _secureStorage.read(key: 'auth_token');
      if (_token == null && _prefs != null) {
        _token = _prefs!.getString('auth_token');
      }
      if (_prefs != null) {
        _organizationId = _prefs!.getString('org_id');
        _userRole = _prefs!.getString('user_role');
        _userId = _prefs!.getInt('user_id');
        _athleteId = _prefs!.getInt('athlete_id');
        _coachId = _prefs!.getInt('coach_id');
        _employeeId = _prefs!.getInt('employee_id');
        _userName = _prefs!.getString('user_name');
        _userEmail = _prefs!.getString('user_email');
        _orgName = _prefs!.getString('org_name');
      }
      _initialized = true;
    } catch (_) {
      _initialized = true;
    }
  }

  static Future<bool> hasValidSession() async {
    await init();
    return _token != null && _token!.isNotEmpty && _userRole != null && _userRole!.isNotEmpty;
  }

  static Future<void> saveToken(String token) async {
    _token = token;
    try {
      await _secureStorage.write(key: 'auth_token', value: token);
      final p = _prefs ?? await SharedPreferences.getInstance();
      await p.setString('auth_token', token);
    } catch (_) {}
  }

  static Future<String?> getToken() async {
    if (!_initialized) await init();
    return _token;
  }

  static Future<void> saveOrganizationId(String orgId) async {
    _organizationId = orgId;
    try {
      final p = _prefs ?? await SharedPreferences.getInstance();
      await p.setString('org_id', orgId);
    } catch (_) {}
  }

  static Future<String?> getOrganizationId() async {
    if (!_initialized) await init();
    return _organizationId;
  }

  static Future<void> saveRole(String role) async {
    _userRole = role;
    try {
      final p = _prefs ?? await SharedPreferences.getInstance();
      await p.setString('user_role', role);
    } catch (_) {}
  }

  static Future<String?> getRole() async {
    if (!_initialized) await init();
    return _userRole;
  }

  static Future<void> saveUserId(int id) async {
    _userId = id;
    try {
      final p = _prefs ?? await SharedPreferences.getInstance();
      await p.setInt('user_id', id);
    } catch (_) {}
  }

  static Future<int?> getUserId() async {
    if (!_initialized) await init();
    return _userId;
  }

  static Future<void> saveAthleteId(int? id) async {
    _athleteId = id;
    try {
      final p = _prefs ?? await SharedPreferences.getInstance();
      if (id != null) {
        await p.setInt('athlete_id', id);
      } else {
        await p.remove('athlete_id');
      }
    } catch (_) {}
  }

  static Future<int?> getAthleteId() async {
    if (!_initialized) await init();
    return _athleteId;
  }

  static Future<void> saveCoachId(int? id) async {
    _coachId = id;
    try {
      final p = _prefs ?? await SharedPreferences.getInstance();
      if (id != null) {
        await p.setInt('coach_id', id);
      } else {
        await p.remove('coach_id');
      }
    } catch (_) {}
  }

  static Future<int?> getCoachId() async {
    if (!_initialized) await init();
    return _coachId;
  }

  static Future<void> saveEmployeeId(int? id) async {
    _employeeId = id;
    try {
      final p = _prefs ?? await SharedPreferences.getInstance();
      if (id != null) {
        await p.setInt('employee_id', id);
      } else {
        await p.remove('employee_id');
      }
    } catch (_) {}
  }

  static Future<int?> getEmployeeId() async {
    if (!_initialized) await init();
    return _employeeId;
  }

  static Future<void> saveUserName(String name) async {
    _userName = name;
    try {
      final p = _prefs ?? await SharedPreferences.getInstance();
      await p.setString('user_name', name);
    } catch (_) {}
  }

  static Future<String?> getUserName() async {
    if (!_initialized) await init();
    return _userName;
  }

  static Future<void> saveUserEmail(String email) async {
    _userEmail = email;
    try {
      final p = _prefs ?? await SharedPreferences.getInstance();
      await p.setString('user_email', email);
    } catch (_) {}
  }

  static Future<String?> getUserEmail() async {
    if (!_initialized) await init();
    return _userEmail;
  }

  static Future<void> saveOrgName(String name) async {
    _orgName = name;
    try {
      final p = _prefs ?? await SharedPreferences.getInstance();
      await p.setString('org_name', name);
    } catch (_) {}
  }

  static Future<String?> getOrgName() async {
    if (!_initialized) await init();
    return _orgName;
  }

  static Future<void> clear() async {
    _token = null;
    _organizationId = null;
    _userRole = null;
    _userId = null;
    _athleteId = null;
    _coachId = null;
    _employeeId = null;
    _userName = null;
    _userEmail = null;
    _orgName = null;
    try {
      await _secureStorage.deleteAll();
      final p = _prefs ?? await SharedPreferences.getInstance();
      await p.clear();
    } catch (_) {}
  }
}
