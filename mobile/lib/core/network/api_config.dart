class ApiConfig {
  static const String baseUrl = 'http://10.0.2.2:8000/api/v1'; // Android Emulator default
  static const String localUrl = 'http://127.0.0.1:8000/api/v1'; // Web / iOS Simulator
  static const Duration timeoutDuration = Duration(seconds: 15);
}
