class TokenStorage {
  static String? _token;
  static String? _organizationId;
  static String? _userRole;

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

  static Future<void> clear() async {
    _token = null;
    _organizationId = null;
    _userRole = null;
  }
}
