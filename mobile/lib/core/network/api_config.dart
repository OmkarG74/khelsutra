import 'package:flutter/foundation.dart';

class ApiConfig {
  static const String androidEmulatorUrl = 'http://10.0.2.2:8000/api/v1';
  static const String localhostUrl = 'http://127.0.0.1:8000/api/v1';

  /// Legacy static aliases for backwards compatibility
  static const String baseUrl = androidEmulatorUrl;
  static const String localUrl = localhostUrl;

  /// Single source of truth for the API base URL.
  /// Resolves to:
  /// - Android: http://10.0.2.2:8000/api/v1 (Android Emulator loopback)
  /// - Web / Windows / Desktop / iOS: http://127.0.0.1:8000/api/v1
  static String get activeBaseUrl {
    if (kIsWeb) {
      return localhostUrl;
    }
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return androidEmulatorUrl;
      case TargetPlatform.iOS:
      case TargetPlatform.windows:
      case TargetPlatform.macOS:
      case TargetPlatform.linux:
      case TargetPlatform.fuchsia:
        return localhostUrl;
    }
  }

  static const Duration timeoutDuration = Duration(seconds: 15);
}

