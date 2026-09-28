class TokenStorage {
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

  static Future<void> saveToken(String token) async {
    _token = token;
  }

  static Future<String?> getToken() async {
    return _token;
  }

  static Future<void> saveOrganizationId(String orgId) async {
    _organizationId = orgId;
  }

  static Future<String?> getOrganizationId() async {
    return _organizationId;
  }

  static Future<void> saveRole(String role) async {
    _userRole = role;
  }

  static Future<String?> getRole() async {
    return _userRole;
  }

  static Future<void> saveUserId(int id) async {
    _userId = id;
  }

  static Future<int?> getUserId() async {
    return _userId;
  }

  static Future<void> saveAthleteId(int? id) async {
    _athleteId = id;
  }

  static Future<int?> getAthleteId() async {
    return _athleteId;
  }

  static Future<void> saveCoachId(int? id) async {
    _coachId = id;
  }

  static Future<int?> getCoachId() async {
    return _coachId;
  }

  static Future<void> saveEmployeeId(int? id) async {
    _employeeId = id;
  }

  static Future<int?> getEmployeeId() async {
    return _employeeId;
  }

  static Future<void> saveUserName(String name) async {
    _userName = name;
  }

  static Future<String?> getUserName() async {
    return _userName;
  }

  static Future<void> saveUserEmail(String email) async {
    _userEmail = email;
  }

  static Future<String?> getUserEmail() async {
    return _userEmail;
  }

  static Future<void> saveOrgName(String name) async {
    _orgName = name;
  }

  static Future<String?> getOrgName() async {
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
  }
}
