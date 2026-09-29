import 'package:flutter_test/flutter_test.dart';
import 'package:khelsutra_mobile/core/network/json_parser.dart';
import 'package:khelsutra_mobile/core/models/coach_models.dart';
import 'package:khelsutra_mobile/core/models/athlete_models.dart';

void main() {
  group('JSON Parser Resilience Tests', () {
    test('parseInt parses int, double, num, string, and null gracefully', () {
      expect(parseInt(10), 10);
      expect(parseInt(10.7), 10);
      expect(parseInt('10'), 10);
      expect(parseInt(' 42 '), 42);
      expect(parseInt('42.8'), 42);
      expect(parseInt(null, 5), 5);
      expect(parseInt('invalid', 0), 0);
      expect(parseInt('', 9), 9);
    });

    test('parseNullableInt parses int, num, string, and null gracefully', () {
      expect(parseNullableInt(10), 10);
      expect(parseNullableInt('10'), 10);
      expect(parseNullableInt('10.5'), 10);
      expect(parseNullableInt(null), isNull);
      expect(parseNullableInt(''), isNull);
      expect(parseNullableInt('abc'), isNull);
    });

    test('parseDouble parses int, double, num, string, and null gracefully', () {
      expect(parseDouble(71.4), 71.4);
      expect(parseDouble('71.4'), 71.4);
      expect(parseDouble(' 8.8 '), 8.8);
      expect(parseDouble(10), 10.0);
      expect(parseDouble(null, 0.0), 0.0);
      expect(parseDouble('invalid', 1.5), 1.5);
    });

    test('parseNullableDouble parses int, double, num, string, and null gracefully', () {
      expect(parseNullableDouble(71.4), 71.4);
      expect(parseNullableDouble('71.4'), 71.4);
      expect(parseNullableDouble(10), 10.0);
      expect(parseNullableDouble(null), isNull);
      expect(parseNullableDouble(''), isNull);
      expect(parseNullableDouble('abc'), isNull);
    });

    test('CoachRosterAthleteItem.fromJson handles MySQL string IDs and COALESCE values', () {
      final json = {
        'id': '1',
        'athlete_code': 'ATH-001',
        'first_name': 'Aarav',
        'last_name': 'Patel',
        'status': 'active',
        'sport_id': '1',
        'sport_name': 'Badminton',
        'team_id': '21',
        'team_name': 'teen titans',
        'jersey_number': '10',
        'member_role': 'player'
      };

      final athlete = CoachRosterAthleteItem.fromJson(json);
      expect(athlete.id, 1);
      expect(athlete.sportId, 1);
      expect(athlete.teamId, 21);
      expect(athlete.fullName, 'Aarav Patel');
    });

    test('CoachDashboardData.fromJson handles string and numeric counts & rates', () {
      final json = {
        'teams_count': '2',
        'athletes_count': '5',
        'today_sessions_count': '4',
        'today_sessions': [],
        'attendance_rate': '71.4',
        'total_attendance_records': '14',
        'present_count': '10',
      };

      final dashboard = CoachDashboardData.fromJson(json);
      expect(dashboard.teamsCount, 2);
      expect(dashboard.athletesCount, 5);
      expect(dashboard.todaySessionsCount, 4);
      expect(dashboard.attendanceRate, 71.4);
      expect(dashboard.totalAttendanceRecords, 14);
      expect(dashboard.presentCount, 10);
    });

    test('TrainingSessionItem.fromJson handles string counts and IDs', () {
      final json = {
        'id': '68',
        'training_reference': 'TRN-2026-8667',
        'title': 'Match Preparation',
        'training_date': '2026-09-28',
        'start_time': '17:00:00',
        'end_time': '18:30:00',
        'status': 'scheduled',
        'team_id': '1',
        'venue_id': '1',
        'coach_id': '1',
        'total_roster_count': '2',
        'present_count': '1',
      };

      final session = TrainingSessionItem.fromJson(json);
      expect(session.id, 68);
      expect(session.teamId, 1);
      expect(session.venueId, 1);
      expect(session.coachId, 1);
      expect(session.totalRosterCount, 2);
      expect(session.presentCount, 1);
    });

    test('PerformanceMetricItem.fromJson handles string min/max and IDs', () {
      final json = {
        'id': '5',
        'sport_id': '2',
        'name': 'Bowling Speed',
        'code': 'BOWL_SPEED',
        'metric_type': 'number',
        'unit': 'km/h',
        'min_value': '80.0',
        'max_value': '160.0',
      };

      final metric = PerformanceMetricItem.fromJson(json);
      expect(metric.id, 5);
      expect(metric.sportId, 2);
      expect(metric.minValue, 80.0);
      expect(metric.maxValue, 160.0);
    });
  });
}
