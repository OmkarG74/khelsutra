import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:khelsutra_mobile/app/routes/role_router.dart';
import 'package:khelsutra_mobile/core/models/athlete_models.dart';
import 'package:khelsutra_mobile/core/models/coach_models.dart';
import 'package:khelsutra_mobile/features/athlete/screens/athlete_dashboard_screen.dart';
import 'package:khelsutra_mobile/features/auth/screens/login_screen.dart';
import 'package:khelsutra_mobile/features/coach/screens/coach_dashboard_screen.dart';

class _MockHttpClient extends Fake implements HttpClient {
  @override
  bool autoUncompress = true;
  @override
  Duration? connectionTimeout;
  @override
  Duration idleTimeout = const Duration(seconds: 15);
  @override
  int? maxConnectionsPerHost;
  @override
  String? userAgent;

  @override
  Future<HttpClientRequest> getUrl(Uri url) async => _MockHttpRequest();
  @override
  Future<HttpClientRequest> postUrl(Uri url) async => _MockHttpRequest();
  @override
  Future<HttpClientRequest> openUrl(String method, Uri url) async => _MockHttpRequest();
}

class _MockHttpRequest extends Fake implements HttpClientRequest {
  @override
  final HttpHeaders headers = _MockHttpHeaders();

  @override
  bool followRedirects = true;

  @override
  int maxRedirects = 5;

  @override
  int contentLength = -1;

  @override
  bool persistentConnection = true;

  @override
  bool bufferOutput = true;

  @override
  void add(List<int> data) {}

  @override
  void write(Object? obj) {}

  @override
  Future<HttpClientResponse> close() async => _MockHttpResponse();
}

class _MockHttpHeaders extends Fake implements HttpHeaders {
  @override
  void add(String name, Object value, {bool preserveHeaderCase = false}) {}
  @override
  void set(String name, Object value, {bool preserveHeaderCase = false}) {}
  @override
  void removeAll(String name) {}
  @override
  List<String>? operator [](String name) => null;
  @override
  String? value(String name) => null;
  @override
  ContentType? contentType;
}

class _MockHttpResponse extends Fake implements HttpClientResponse {
  @override
  int get statusCode => 200;

  @override
  int get contentLength => -1;

  @override
  String get reasonPhrase => 'OK';

  @override
  final HttpHeaders headers = _MockHttpHeaders();

  @override
  HttpClientResponseCompressionState get compressionState =>
      HttpClientResponseCompressionState.notCompressed;

  @override
  StreamSubscription<List<int>> listen(
    void Function(List<int> event)? onData, {
    Function? onError,
    void Function()? onDone,
    bool? cancelOnError,
  }) {
    final responseBody = jsonEncode({
      'success': true,
      'message': 'OK',
      'data': {
        'id': 1,
        'athlete_code': 'ATH-2026-9E4C',
        'coach_code': 'CCH-2026-E07E',
        'first_name': 'Test',
        'last_name': 'User',
        'sport_name': 'Athletics',
        'category_name': 'Senior',
        'team_name': 'First Squad',
        'status': 'active',
        'teams_count': 1,
        'athletes_count': 1,
        'today_sessions_count': 0,
        'attendance_rate': 100.0,
        'total_attendance_records': 0,
        'present_count': 0,
        'today_sessions': [],
      }
    });
    return Stream.value(utf8.encode(responseBody)).listen(
      onData,
      onError: onError,
      onDone: onDone,
      cancelOnError: cancelOnError,
    );
  }
}

class TestHttpOverrides extends HttpOverrides {
  @override
  HttpClient createHttpClient(SecurityContext? context) => _MockHttpClient();
}

void main() {
  setUpAll(() {
    HttpOverrides.global = TestHttpOverrides();
  });

  group('Model Serialization & Deserialization Tests', () {
    test('AthleteProfile parses real API JSON format properly', () {
      final json = {
        'id': 1,
        'athlete_code': 'ATH-2026-9E4C',
        'first_name': 'Aarav',
        'last_name': 'Patel',
        'email': 'athlete@khelsutra.local',
        'phone': '9876543210',
        'gender': 'Male',
        'date_of_birth': '2005-04-12',
        'blood_group': 'B+',
        'nationality': 'Indian',
        'sport_name': 'Athletics',
        'category_name': 'Senior Sprint',
        'team_name': 'KhelSutra Elite Squad',
        'status': 'active',
      };

      final profile = AthleteProfile.fromJson(json);
      expect(profile.id, 1);
      expect(profile.fullName, 'Aarav Patel');
      expect(profile.athleteCode, 'ATH-2026-9E4C');
      expect(profile.sportName, 'Athletics');
      expect(profile.bloodGroup, 'B+');
      expect(profile['sport'], 'Athletics');
    });

    test('CoachProfile parses real API JSON format properly', () {
      final json = {
        'id': 1,
        'coach_code': 'CCH-2026-E07E',
        'employee_id': 4,
        'employee_code': 'EMP-2026-6655',
        'first_name': 'Amit',
        'last_name': 'Kumar',
        'specialization': 'Track and Field Sprint',
        'designation': 'Head Coach',
        'email': 'coach@khelsutra.local',
        'status': 'active',
        'teams': [
          {
            'team_id': 1,
            'team_name': 'KhelSutra Elite Squad',
            'sport_name': 'Athletics',
            'coach_role': 'head_coach',
            'is_primary': 1,
          }
        ],
      };

      final profile = CoachProfile.fromJson(json);
      expect(profile.id, 1);
      expect(profile.fullName, 'Amit Kumar');
      expect(profile.coachCode, 'CCH-2026-E07E');
      expect(profile.assignedTeams.length, 1);
      expect(profile.assignedTeams.first.teamName, 'KhelSutra Elite Squad');
      expect(profile.assignedTeams.first.roleInTeam, 'head_coach');
    });

    test('AthleteAttendanceSummary calculates percentage correctly from real records', () {
      final records = [
        AttendanceRecordItem(
          id: 1,
          trainingSessionId: 10,
          sessionTitle: 'Sprint Drill',
          date: '2026-09-28',
          time: '06:00 - 08:00',
          status: 'present',
        ),
        AttendanceRecordItem(
          id: 2,
          trainingSessionId: 11,
          sessionTitle: 'Tactical Conditioning',
          date: '2026-09-27',
          time: '06:00 - 08:00',
          status: 'absent',
        ),
        AttendanceRecordItem(
          id: 3,
          trainingSessionId: 12,
          sessionTitle: 'Endurance Interval',
          date: '2026-09-26',
          time: '06:00 - 08:00',
          status: 'present',
        ),
      ];

      final summary = AthleteAttendanceSummary.fromRecords(records);
      expect(summary.totalSessions, 3);
      expect(summary.presentSessions, 2);
      expect(summary.absentSessions, 1);
      expect(summary.percentage, 66.7);
    });

    test('CoachDashboardData parses KPI counts correctly', () {
      final json = {
        'teams_count': 1,
        'athletes_count': 1,
        'today_sessions_count': 1,
        'attendance_rate': 100.0,
        'total_attendance_records': 1,
        'present_count': 1,
        'today_sessions': [],
      };

      final data = CoachDashboardData.fromJson(json);
      expect(data.athletesCount, 1);
      expect(data.presentToday, 1);
      expect(data.absentToday, 0);
      expect(data.attendanceRate, 100.0);
    });

    test('PerformanceRecordItem & PerformanceValueItem parse properly', () {
      final json = {
        'id': 1,
        'athlete_id': 1,
        'evaluation_date': '2026-09-28',
        'overall_rating': 8.5,
        'coach_remarks': 'Excellent posture and acceleration.',
        'sport_name': 'Athletics',
        'team_name': 'KhelSutra Elite Squad',
        'values': [
          {
            'metric_id': 1,
            'metric_name': '100m Sprint',
            'metric_code': 'SMASH_VEL',
            'metric_type': 'Speed',
            'numeric_value': 11.8,
            'unit': 'seconds',
          }
        ],
      };

      final record = PerformanceRecordItem.fromJson(json);
      expect(record.id, 1);
      expect(record.overallRating, 8.5);
      expect(record.values.length, 1);
      expect(record.values.first.metricName, '100m Sprint');
      expect(record.values.first.numericValue, 11.8);
      expect(record.values.first.unit, 'seconds');
    });
  });

  group('Role Router Verification', () {
    testWidgets('RoleRouter routes to CoachDashboardScreen for coach role', (tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: RoleRouter(role: 'Coach'),
        ),
      );
      expect(find.byType(CoachDashboardScreen), findsOneWidget);
    });

    testWidgets('RoleRouter routes to AthleteDashboardScreen for athlete role', (tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: RoleRouter(role: 'Athlete'),
        ),
      );
      expect(find.byType(AthleteDashboardScreen), findsOneWidget);
    });

    testWidgets('LoginScreen renders fields and login buttons without mock bypass', (tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: LoginScreen(),
        ),
      );
      expect(find.text('KhelSutra'), findsOneWidget);
      expect(find.text('Sign In'), findsOneWidget);
      expect(find.text('Login as Coach'), findsOneWidget);
      expect(find.text('Login as Athlete'), findsOneWidget);
    });
  });
}
