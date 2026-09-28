import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:khelsutra_mobile/core/network/api_client.dart';
import 'package:khelsutra_mobile/core/network/api_config.dart';

void main() {
  group('ApiConfig and ApiClient tests', () {
    test('ApiConfig constants', () {
      expect(ApiConfig.androidEmulatorUrl, equals('http://10.0.2.2:8000/api/v1'));
      expect(ApiConfig.localhostUrl, equals('http://127.0.0.1:8000/api/v1'));
    });

    test('ApiConfig.activeBaseUrl resolves based on target platform', () {
      debugDefaultTargetPlatformOverride = TargetPlatform.android;
      expect(ApiConfig.activeBaseUrl, equals('http://10.0.2.2:8000/api/v1'));

      debugDefaultTargetPlatformOverride = TargetPlatform.windows;
      expect(ApiConfig.activeBaseUrl, equals('http://127.0.0.1:8000/api/v1'));

      debugDefaultTargetPlatformOverride = TargetPlatform.iOS;
      expect(ApiConfig.activeBaseUrl, equals('http://127.0.0.1:8000/api/v1'));

      debugDefaultTargetPlatformOverride = null;
    });

    test('ApiClient uses ApiConfig.activeBaseUrl by default', () {
      debugDefaultTargetPlatformOverride = TargetPlatform.android;
      final client = ApiClient();
      expect(client, isNotNull);
      expect(ApiConfig.activeBaseUrl, equals('http://10.0.2.2:8000/api/v1'));

      debugDefaultTargetPlatformOverride = null;
    });
  });
}
