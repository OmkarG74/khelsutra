import 'package:flutter_test/flutter_test.dart';
import 'package:khelsutra_mobile/core/models/athlete_models.dart';
import 'package:khelsutra_mobile/core/models/coach_models.dart';
import 'package:khelsutra_mobile/core/models/domain_models.dart';

void main() {
  group('Coach Performance Dropdown & Model Tests', () {
    test('PerformanceMetricItem equality is based on unique ID', () {
      final metric1 = PerformanceMetricItem(
        id: 1,
        name: 'Smash Velocity',
        code: 'SMASH_VEL',
        metricType: 'decimal',
        unit: 'km/h',
      );

      final metric2 = PerformanceMetricItem(
        id: 1,
        name: 'Smash Velocity Updated',
        code: 'SMASH_VEL',
        metricType: 'decimal',
        unit: 'km/h',
      );

      final metric3 = PerformanceMetricItem(
        id: 2,
        name: 'Agility T-Test',
        code: 'AGILITY_T',
        metricType: 'time',
        unit: 'seconds',
      );

      expect(metric1 == metric2, isTrue);
      expect(metric1 == metric3, isFalse);
      expect(metric1.hashCode, metric2.hashCode);
    });

    test('CoachRosterAthleteItem equality is based on unique ID', () {
      final a1 = CoachRosterAthleteItem(
        id: 1,
        athleteCode: 'ATH-E4ED5F',
        firstName: 'Aarav',
        lastName: 'Patel',
        status: 'active',
        sportName: 'Badminton',
        teamId: 1,
        teamName: 'Team FB',
        memberRole: 'player',
      );

      final a2 = CoachRosterAthleteItem(
        id: 1,
        athleteCode: 'ATH-E4ED5F',
        firstName: 'Aarav',
        lastName: 'Patel',
        status: 'active',
        sportName: 'Badminton',
        teamId: 21,
        teamName: 'teen titans',
        memberRole: 'player',
      );

      expect(a1 == a2, isTrue);
      expect(a1.hashCode, a2.hashCode);
    });

    test('Athlete and Metric list deduplication filters multi-team assignments correctly', () {
      // Simulating raw backend response where athlete 1 is in 2 teams and athlete 65 is listed twice
      final rawAthletes = [
        CoachRosterAthleteItem(
          id: 1,
          athleteCode: 'ATH-E4ED5F',
          firstName: 'Aarav',
          lastName: 'Patel',
          status: 'active',
          sportName: 'Badminton',
          teamId: 1,
          teamName: 'Team FB',
          memberRole: 'player',
        ),
        CoachRosterAthleteItem(
          id: 1,
          athleteCode: 'ATH-E4ED5F',
          firstName: 'Aarav',
          lastName: 'Patel',
          status: 'active',
          sportName: 'Badminton',
          teamId: 21,
          teamName: 'teen titans',
          memberRole: 'player',
        ),
        CoachRosterAthleteItem(
          id: 65,
          athleteCode: 'ATH-2026-34D7',
          firstName: 'jeet',
          lastName: 'singh',
          status: 'active',
          sportName: 'Badminton',
          teamId: 21,
          teamName: 'teen titans',
          memberRole: 'player',
        ),
        CoachRosterAthleteItem(
          id: 65,
          athleteCode: 'ATH-2026-34D7',
          firstName: 'jeet',
          lastName: 'singh',
          status: 'active',
          sportName: 'Badminton',
          teamId: 21,
          teamName: 'teen titans',
          memberRole: 'captain',
        ),
        CoachRosterAthleteItem(
          id: 348,
          athleteCode: 'ATH-2026-BFB7',
          firstName: 'Birbal',
          lastName: 'Shah',
          status: 'active',
          sportName: 'Cricket',
          teamId: 1,
          teamName: 'Team FB',
          memberRole: 'player',
        ),
      ];

      final athleteMap = <int, CoachRosterAthleteItem>{};
      for (final a in rawAthletes) {
        if (a.id > 0 && !athleteMap.containsKey(a.id)) {
          athleteMap[a.id] = a;
        }
      }
      final cleanAthletes = athleteMap.values.toList();

      expect(cleanAthletes.length, 3);
      expect(cleanAthletes.map((a) => a.id).toList(), [1, 65, 348]);

      // Ensure every ID in cleanAthletes is strictly unique
      final idSet = cleanAthletes.map((a) => a.id).toSet();
      expect(idSet.length, cleanAthletes.length);
    });

    test('Performance metric deduplication ignores duplicate IDs and empty names', () {
      final rawMetrics = [
        PerformanceMetricItem(id: 1, name: 'Smash Velocity', code: 'SMASH_VEL', metricType: 'decimal'),
        PerformanceMetricItem(id: 1, name: 'Smash Velocity Duplicate', code: 'SMASH_VEL', metricType: 'decimal'),
        PerformanceMetricItem(id: 2, name: 'Agility T-Test', code: 'AGILITY_T', metricType: 'time'),
        PerformanceMetricItem(id: 0, name: 'Invalid Metric', code: 'INVALID', metricType: 'number'),
        PerformanceMetricItem(id: 3, name: '   ', code: 'BLANK', metricType: 'number'),
      ];

      final metricMap = <int, PerformanceMetricItem>{};
      for (final m in rawMetrics) {
        if (m.id > 0 && m.name.trim().isNotEmpty && !metricMap.containsKey(m.id)) {
          metricMap[m.id] = m;
        }
      }
      final cleanMetrics = metricMap.values.toList();

      expect(cleanMetrics.length, 2);
      expect(cleanMetrics[0].id, 1);
      expect(cleanMetrics[0].name, 'Smash Velocity');
      expect(cleanMetrics[1].id, 2);
    });

    test('Initial athlete ID resolution safely selects matching athlete or first available', () {
      final athletes = [
        CoachRosterAthleteItem(
          id: 1,
          athleteCode: 'ATH-1',
          firstName: 'Aarav',
          lastName: 'Patel',
          status: 'active',
          sportName: 'Badminton',
          teamName: 'Team A',
          memberRole: 'player',
        ),
        CoachRosterAthleteItem(
          id: 2,
          athleteCode: 'ATH-2',
          firstName: 'Rohan',
          lastName: 'Verma',
          status: 'active',
          sportName: 'Tennis',
          teamName: 'Team B',
          memberRole: 'player',
        ),
      ];

      // Test matching athlete
      final parsedMatch = int.tryParse('2');
      final selectedMatch = (parsedMatch != null && athletes.any((a) => a.id == parsedMatch))
          ? parsedMatch
          : (athletes.isNotEmpty ? athletes.first.id : null);
      expect(selectedMatch, 2);

      // Test non-matching athlete (e.g. initialAthleteId is "999") -> falls back to first
      final parsedNonMatch = int.tryParse('999');
      final selectedFallback = (parsedNonMatch != null && athletes.any((a) => a.id == parsedNonMatch))
          ? parsedNonMatch
          : (athletes.isNotEmpty ? athletes.first.id : null);
      expect(selectedFallback, 1);

      // Test null initialAthleteId -> falls back to first
      final int? nullParsed = int.tryParse('');
      final selectedNull = (nullParsed != null && athletes.any((a) => a.id == nullParsed))
          ? nullParsed
          : (athletes.isNotEmpty ? athletes.first.id : null);
      expect(selectedNull, 1);
    });

    test('Sport-aware metric filtering differentiates Cricket, Badminton and Common tests', () {
      final allMetrics = [
        PerformanceMetricItem(id: 1, sportId: 1, name: 'Smash Velocity', code: 'SMASH_VEL', metricType: 'decimal', unit: 'km/h'),
        PerformanceMetricItem(id: 2, sportId: null, name: 'Agility T-Test', code: 'AGILITY_T', metricType: 'time', unit: 'seconds'),
        PerformanceMetricItem(id: 3, sportId: null, name: 'Vertical Jump', code: 'VERT_JUMP', metricType: 'decimal', unit: 'cm'),
        PerformanceMetricItem(id: 4, sportId: null, name: 'Shuttle Endurance Run', code: 'SHUTTLE_RUN', metricType: 'distance', unit: 'meters'),
        PerformanceMetricItem(id: 5, sportId: 2, name: 'Bowling Speed', code: 'BOWL_SPEED', metricType: 'decimal', unit: 'km/h'),
        PerformanceMetricItem(id: 6, sportId: 2, name: 'Batting Exit Velocity', code: 'BAT_EXIT_VEL', metricType: 'decimal', unit: 'km/h'),
      ];

      // Filter for Badminton (sport_id = 1) -> Badminton-specific + common
      final badmintonMetrics = allMetrics.where((m) => m.sportId == null || m.sportId == 1).toList();
      expect(badmintonMetrics.map((m) => m.name), containsAll(['Smash Velocity', 'Agility T-Test', 'Vertical Jump', 'Shuttle Endurance Run']));
      expect(badmintonMetrics.any((m) => m.name == 'Bowling Speed'), isFalse);
      expect(badmintonMetrics.any((m) => m.name == 'Batting Exit Velocity'), isFalse);

      // Filter for Cricket (sport_id = 2) -> Cricket-specific + common
      final cricketMetrics = allMetrics.where((m) => m.sportId == null || m.sportId == 2).toList();
      expect(cricketMetrics.map((m) => m.name), containsAll(['Bowling Speed', 'Batting Exit Velocity', 'Agility T-Test', 'Vertical Jump', 'Shuttle Endurance Run']));
      expect(cricketMetrics.any((m) => m.name == 'Smash Velocity'), isFalse);
    });

    test('Training Score validation strictly requires 0.0 to 10.0', () {
      bool isValidScore(String input) {
        final val = double.tryParse(input.trim());
        if (val == null) return false;
        return val >= 0.0 && val <= 10.0;
      }

      expect(isValidScore('8.0'), isTrue);
      expect(isValidScore('0'), isTrue);
      expect(isValidScore('10'), isTrue);
      expect(isValidScore('9.5'), isTrue);
      expect(isValidScore('-1'), isFalse);
      expect(isValidScore('10.5'), isFalse);
      expect(isValidScore('abc'), isFalse);
      expect(isValidScore(''), isFalse);
    });

    test('AssessmentRecord correctly preserves both Result Value and Training Score', () {
      final record = PerformanceRecordItem(
        id: 10,
        evaluationDate: '2026-09-29',
        overallRating: 8.5,
        coachRemarks: 'Excellent explosive release and follow-through',
        sportName: 'Cricket',
        values: [
          PerformanceValueItem(
            metricId: 5,
            numericValue: 135.2,
            metricName: 'Bowling Speed',
            metricCode: 'BOWL_SPEED',
            unit: 'km/h',
            metricType: 'decimal',
          )
        ],
      );

      final assessment = AssessmentRecord.fromItem(record, record.values.first);
      expect(assessment.testName, 'Bowling Speed');
      expect(assessment.score, '135.2'); // Result Value
      expect(assessment.unit, 'km/h');     // Metric Unit
      expect(assessment.trainingScore, 8.5); // Training Score (0 - 10)
      expect(assessment.percentage, 85.0);
    });
  });
}
