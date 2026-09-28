import 'package:flutter_test/flutter_test.dart';
import 'package:khelsutra_mobile/core/models/athlete_models.dart';

void main() {
  group('Coach Attendance Workflow & Model Tests', () {
    test('TrainingSessionItem parses session with roster attendance and metadata', () {
      final json = {
        'id': 65,
        'training_reference': 'TRN-2026-8E74',
        'title': 'Badminton Morning Training',
        'training_date': '2026-09-28',
        'start_time': '08:00:00',
        'end_time': '09:00:00',
        'team_id': 1,
        'team_name': 'Team FB',
        'venue_id': 1,
        'venue_name': 'Cricket Ground',
        'coach_id': 1,
        'coach_name': 'Amit Kumar',
        'status': 'scheduled',
        'total_roster_count': 2,
        'present_count': '1',
        'roster_attendance': [
          {
            'athlete_id': 1,
            'athlete_code': 'ATH-E4ED5F',
            'first_name': 'Aarav',
            'last_name': 'Patel',
            'jersey_number': '10',
            'member_role': 'player',
            'attendance_status': 'present',
            'remarks': 'On time'
          },
          {
            'athlete_id': 348,
            'athlete_code': 'ATH-2026-BFB7',
            'first_name': 'Birbal',
            'last_name': 'Shah',
            'jersey_number': '11',
            'member_role': 'player',
            'attendance_status': 'not_marked',
            'remarks': null
          }
        ]
      };

      final session = TrainingSessionItem.fromJson(json);

      expect(session.id, 65);
      expect(session.title, 'Badminton Morning Training');
      expect(session.teamId, 1);
      expect(session.teamName, 'Team FB');
      expect(session.venue, 'Cricket Ground');
      expect(session.coachId, 1);
      expect(session.totalRosterCount, 2);
      expect(session.presentCount, 1);
      expect(session.rosterAttendance.length, 2);
    });

    test('SessionAttendanceItem defaults unrecorded status to not_marked', () {
      final unrecordedJson = {
        'athlete_id': 101,
        'first_name': 'Rahul',
        'last_name': 'Sharma',
        'athlete_code': 'ATH-101',
        'attendance_status': null,
      };

      final item = SessionAttendanceItem.fromJson(unrecordedJson);

      expect(item.athleteId, 101);
      expect(item.athleteName, 'Rahul Sharma');
      expect(item.status, 'not_marked');
    });

    test('SessionAttendanceItem correctly parses present and absent statuses', () {
      final presentJson = {
        'athlete_id': 1,
        'first_name': 'Aarav',
        'last_name': 'Patel',
        'athlete_code': 'ATH-001',
        'attendance_status': 'present',
      };
      final absentJson = {
        'athlete_id': 2,
        'first_name': 'Priya',
        'last_name': 'Singh',
        'athlete_code': 'ATH-002',
        'attendance_status': 'absent',
      };

      final item1 = SessionAttendanceItem.fromJson(presentJson);
      final item2 = SessionAttendanceItem.fromJson(absentJson);

      expect(item1.status, 'present');
      expect(item2.status, 'absent');
    });

    test('Attendance counter calculation correctly differentiates 3 states', () {
      final athletes = [
        SessionAttendanceItem(
          athleteId: 1,
          athleteName: 'Aarav Patel',
          athleteCode: 'ATH-1',
          status: 'present',
        ),
        SessionAttendanceItem(
          athleteId: 2,
          athleteName: 'Rahul Sharma',
          athleteCode: 'ATH-2',
          status: 'present',
        ),
        SessionAttendanceItem(
          athleteId: 3,
          athleteName: 'Priya Singh',
          athleteCode: 'ATH-3',
          status: 'absent',
        ),
        SessionAttendanceItem(
          athleteId: 4,
          athleteName: 'Vikram Verma',
          athleteCode: 'ATH-4',
          status: 'not_marked',
        ),
        SessionAttendanceItem(
          athleteId: 5,
          athleteName: 'Ananya Roy',
          athleteCode: 'ATH-5',
          status: 'not_marked',
        ),
      ];

      final presentCount = athletes.where((a) => a.status == 'present').length;
      final absentCount = athletes.where((a) => a.status == 'absent').length;
      final notMarkedCount = athletes.where((a) => a.status == 'not_marked').length;

      expect(presentCount, 2);
      expect(absentCount, 1);
      expect(notMarkedCount, 2);
    });

    test('Batch attendance payload excludes not_marked and only includes marked athletes', () {
      final athletes = [
        SessionAttendanceItem(athleteId: 1, athleteName: 'Aarav', athleteCode: 'A1', status: 'present'),
        SessionAttendanceItem(athleteId: 2, athleteName: 'Rahul', athleteCode: 'A2', status: 'absent'),
        SessionAttendanceItem(athleteId: 3, athleteName: 'Priya', athleteCode: 'A3', status: 'not_marked'),
      ];

      final payload = athletes
          .where((a) => a.status == 'present' || a.status == 'absent')
          .map((a) => {
                'athlete_id': a.athleteId,
                'status': a.status,
              })
          .toList();

      expect(payload.length, 2);
      expect(payload[0], {'athlete_id': 1, 'status': 'present'});
      expect(payload[1], {'athlete_id': 2, 'status': 'absent'});
    });
  });
}
