import 'package:flutter/material.dart';
import '../models/athlete_models.dart';
import '../models/coach_models.dart';

/// Models for mock data in offline UI phase and bridge to real API models
class TrainingSession {
  final String id;
  final String title;
  final String date;
  final String startTime;
  final String endTime;
  final String venue;
  final String coachName;
  final String teamName;
  final String instructions;
  final String status; // 'Scheduled', 'In Progress', 'Completed', 'Cancelled'
  final String attendanceStatus; // 'Present', 'Absent', 'Pending'
  final bool isToday;
  final bool isUpcoming;
  final bool isCompleted;

  TrainingSession({
    required this.id,
    required this.title,
    required this.date,
    required this.startTime,
    required this.endTime,
    required this.venue,
    required this.coachName,
    this.teamName = 'KhelSutra Elite',
    required this.instructions,
    required this.status,
    required this.attendanceStatus,
    this.isToday = false,
    this.isUpcoming = false,
    this.isCompleted = false,
  });

  factory TrainingSession.fromItem(TrainingSessionItem item) {
    return TrainingSession(
      id: item.id.toString(),
      title: item.title,
      date: item.date,
      startTime: item.startTime,
      endTime: item.endTime,
      venue: item.venue,
      coachName: item.coachName,
      teamName: item.teamName,
      instructions: item.objectives ?? item.notes ?? 'Standard team tactical training session drill',
      status: item.status.isNotEmpty ? (item.status[0].toUpperCase() + item.status.substring(1)) : 'Scheduled',
      attendanceStatus: 'Present',
      isToday: item.isToday,
      isUpcoming: item.isUpcoming,
      isCompleted: item.isCompleted,
    );
  }

  TrainingSession copyWith({
    String? status,
    String? attendanceStatus,
  }) {
    return TrainingSession(
      id: id,
      title: title,
      date: date,
      startTime: startTime,
      endTime: endTime,
      venue: venue,
      coachName: coachName,
      teamName: teamName,
      instructions: instructions,
      status: status ?? this.status,
      attendanceStatus: attendanceStatus ?? this.attendanceStatus,
      isToday: isToday,
      isUpcoming: isUpcoming,
      isCompleted: isCompleted,
    );
  }
}

class AttendanceRecord {
  final String date;
  final String day;
  final bool isPresent;
  final String sessionTitle;
  final String time;

  AttendanceRecord({
    required this.date,
    required this.day,
    required this.isPresent,
    required this.sessionTitle,
    required this.time,
  });

  factory AttendanceRecord.fromItem(AttendanceRecordItem item) {
    String day = 'Session';
    try {
      final dt = DateTime.parse(item.date);
      final weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
      day = weekdays[dt.weekday - 1];
    } catch (_) {}

    return AttendanceRecord(
      date: item.date,
      day: day,
      isPresent: item.isPresent,
      sessionTitle: item.sessionTitle,
      time: item.time,
    );
  }
}

class AssessmentRecord {
  final String id;
  final String athleteName;
  final String testName;
  final String date;
  final String score;
  final String unit;
  final double percentage;
  final String category; // 'Speed', 'Strength', 'Endurance', 'Agility'
  final String notes;

  AssessmentRecord({
    required this.id,
    required this.athleteName,
    required this.testName,
    required this.date,
    required this.score,
    required this.unit,
    required this.percentage,
    required this.category,
    this.notes = '',
  });

  factory AssessmentRecord.fromItem(PerformanceRecordItem record, PerformanceValueItem val) {
    return AssessmentRecord(
      id: '${record.id}_${val.metricId}',
      athleteName: record.sportName,
      testName: val.metricName,
      date: record.evaluationDate,
      score: val.numericValue != null
          ? (val.numericValue! % 1 == 0 ? val.numericValue!.toInt().toString() : val.numericValue!.toStringAsFixed(1))
          : (val.textValue ?? '-'),
      unit: val.unit ?? '',
      percentage: (record.overallRating ?? 8.0) * 10,
      category: val.metricType,
      notes: record.coachRemarks ?? '',
    );
  }

  factory AssessmentRecord.fromRecordAndValue(PerformanceRecordItem record, PerformanceValueItem val) =>
      AssessmentRecord.fromItem(record, val);
}

class AchievementItem {
  final String id;
  final String title;
  final String competition;
  final String date;
  final String medal; // 'Gold', 'Silver', 'Bronze', 'Trophy', 'Certificate'
  final String category;
  final String description;

  AchievementItem({
    required this.id,
    required this.title,
    required this.competition,
    required this.date,
    required this.medal,
    required this.category,
    required this.description,
  });

  factory AchievementItem.fromItem(AchievementItemModel item) {
    return AchievementItem(
      id: item.id.toString(),
      title: item.title,
      competition: item.tournamentName ?? item.type,
      date: item.date,
      medal: item.positionOrMedal ?? 'Gold',
      category: item.type,
      description: item.description,
    );
  }
}

class AppNotificationItem {
  final String id;
  final String title;
  final String message;
  final String timeAgo;
  final String category; // 'Training', 'Tournament', 'Attendance', 'General'
  final IconData icon;
  final Color iconColor;
  bool isRead;

  AppNotificationItem({
    required this.id,
    required this.title,
    required this.message,
    required this.timeAgo,
    required this.category,
    required this.icon,
    required this.iconColor,
    this.isRead = false,
  });

  factory AppNotificationItem.fromItem(NotificationItemModel item) {
    IconData icon = Icons.notifications_outlined;
    Color color = const Color(0xFF1E3A8A);
    if (item.type.contains('train')) {
      icon = Icons.fitness_center;
      color = const Color(0xFF1E3A8A);
    } else if (item.type.contains('stock') || item.type.contains('alert')) {
      icon = Icons.warning_amber_rounded;
      color = Colors.orange;
    } else if (item.type.contains('attend')) {
      icon = Icons.how_to_reg;
      color = Colors.green;
    }

    return AppNotificationItem(
      id: item.id.toString(),
      title: item.title,
      message: item.message,
      timeAgo: item.createdAt.length >= 10 ? item.createdAt.substring(0, 10) : item.createdAt,
      category: item.type,
      icon: icon,
      iconColor: color,
      isRead: item.isRead,
    );
  }
}

class CoachAthleteItem {
  final String id;
  final String name;
  final String sport;
  final String event;
  final String team;
  final int attendancePct;
  final int performancePct;
  final String jerseyNo;
  final String status; // 'Active', 'Injured', 'On Leave'
  bool isPresentToday;

  CoachAthleteItem({
    required this.id,
    required this.name,
    required this.sport,
    required this.event,
    required this.team,
    required this.attendancePct,
    required this.performancePct,
    required this.jerseyNo,
    this.status = 'Active',
    this.isPresentToday = true,
  });

  factory CoachAthleteItem.fromItem(CoachRosterAthleteItem item) {
    return CoachAthleteItem(
      id: item.id.toString(),
      name: item.fullName,
      sport: item.sportName,
      event: item.teamName,
      team: item.teamName,
      attendancePct: 95,
      performancePct: 88,
      jerseyNo: item.jerseyNumber ?? '#${item.id}',
      status: item.status.isNotEmpty ? (item.status[0].toUpperCase() + item.status.substring(1)) : 'Active',
      isPresentToday: true,
    );
  }
}

/// Central Mock Data Store with state management for UI demonstrations
class MockDataStore {
  // Singleton instance
  static final MockDataStore instance = MockDataStore._internal();

  MockDataStore._internal() {
    _initializeData();
  }

  // --- Athlete Profile ---
  final Map<String, dynamic> athleteProfile = {
    'name': 'Mayur Ghadi',
    'id': 'ATH-2026-084',
    'sport': 'Athletics',
    'discipline': '100m & 200m Sprint',
    'team': 'KhelSutra Elite',
    'coach': 'Rahul Sharma',
    'attendancePct': 92,
    'performancePct': 82,
    'totalSessions': 26,
    'presentSessions': 24,
    'absentSessions': 2,
    'speed': 82,
    'strength': 74,
    'endurance': 80,
    'agility': 78,
    'dob': '14 Aug 2004',
    'bloodGroup': 'O+ Positive',
    'phone': '+91 98765 43210',
    'email': 'mayur.ghadi@khelsutra.local',
    'emergencyContact': 'Sunil Ghadi (Father) • +91 98765 11223',
    'height': '178 cm',
    'weight': '71 kg',
  };

  // --- Coach Profile ---
  final Map<String, dynamic> coachProfile = {
    'name': 'Rahul Sharma',
    'id': 'CCH-2026-012',
    'sport': 'Athletics',
    'designation': 'Senior Performance Coach',
    'team': 'KhelSutra Elite',
    'athletesCount': 24,
    'presentToday': 22,
    'absentToday': 2,
    'experience': '9 Years',
    'phone': '+91 99887 76655',
    'email': 'coach@khelsutra.local',
    'certifications': 'AFI Level 2 Certified Coach, World Athletics CECS II',
  };

  // --- Athlete Training Sessions ---
  late List<TrainingSession> athleteTrainings;

  // --- Coach Training Sessions ---
  late List<TrainingSession> coachTrainings;

  // --- Attendance Records ---
  late List<AttendanceRecord> attendanceRecords;

  // --- Performance Assessments ---
  late List<AssessmentRecord> assessments;

  // --- Achievements ---
  late List<AchievementItem> achievements;

  // --- Notifications ---
  late List<AppNotificationItem> notifications;

  // --- Coach's Athletes List ---
  late List<CoachAthleteItem> coachAthletes;

  void _initializeData() {
    athleteTrainings = [
      TrainingSession(
        id: 'TRN-101',
        title: 'Speed & Sprint Mechanics',
        date: 'Today, 28 Sep',
        startTime: '06:00 AM',
        endTime: '08:00 AM',
        venue: 'Athletics Track - Lane 3 & 4',
        coachName: 'Rahul Sharma',
        instructions:
            'Warm-up 20 min, block start drills x 6 sets, 60m accelerations with resistance bands. Stay hydrated.',
        status: 'Scheduled',
        attendanceStatus: 'Present',
        isToday: true,
      ),
      TrainingSession(
        id: 'TRN-102',
        title: 'Explosive Strength & Plyometrics',
        date: 'Today, 28 Sep',
        startTime: '04:30 PM',
        endTime: '06:00 PM',
        venue: 'High Performance Gym 2',
        coachName: 'Rahul Sharma',
        instructions:
            'Focus on box jumps, weighted squats, and hamstring curls. Foam rolling session post workout.',
        status: 'Scheduled',
        attendanceStatus: 'Pending',
        isToday: true,
      ),
      TrainingSession(
        id: 'TRN-103',
        title: 'Endurance & Active Recovery',
        date: 'Tomorrow, 29 Sep',
        startTime: '06:30 AM',
        endTime: '08:00 AM',
        venue: 'Cross Country Trail & Turf',
        coachName: 'Rahul Sharma',
        instructions:
            '4km tempo run followed by dynamic mobility and pool recovery session.',
        status: 'Scheduled',
        attendanceStatus: 'Pending',
        isUpcoming: true,
      ),
      TrainingSession(
        id: 'TRN-104',
        title: 'Sprint Curve Analysis & Starts',
        date: '30 Sep 2026',
        startTime: '06:00 AM',
        endTime: '07:45 AM',
        venue: 'Synthetic Track Ground A',
        coachName: 'Rahul Sharma',
        instructions:
            'Video analysis on block reaction time and curve transition mechanics.',
        status: 'Scheduled',
        attendanceStatus: 'Pending',
        isUpcoming: true,
      ),
      TrainingSession(
        id: 'TRN-100',
        title: 'Maximal Velocity Sprint Test',
        date: '27 Sep 2026',
        startTime: '06:00 AM',
        endTime: '08:00 AM',
        venue: 'Athletics Track - 100m Straight',
        coachName: 'Rahul Sharma',
        instructions: 'Timing gate measurements for 30m fly and 100m timed run.',
        status: 'Completed',
        attendanceStatus: 'Present',
        isCompleted: true,
      ),
      TrainingSession(
        id: 'TRN-099',
        title: 'Hypertrophy & Core Stability',
        date: '25 Sep 2026',
        startTime: '05:00 PM',
        endTime: '06:30 PM',
        venue: 'Strength Training Facility',
        coachName: 'Rahul Sharma',
        instructions: 'Core rotation, barbell hip thrusts, sled pulls.',
        status: 'Completed',
        attendanceStatus: 'Present',
        isCompleted: true,
      ),
      TrainingSession(
        id: 'TRN-098',
        title: 'Lactate Threshold Intervals',
        date: '23 Sep 2026',
        startTime: '06:00 AM',
        endTime: '07:30 AM',
        venue: 'Track Ground A',
        coachName: 'Rahul Sharma',
        instructions: '300m x 4 reps with 3 min rest.',
        status: 'Completed',
        attendanceStatus: 'Absent',
        isCompleted: true,
      ),
    ];

    coachTrainings = [
      TrainingSession(
        id: 'TRN-C1',
        title: 'Strength & Conditioning',
        date: 'Today, 28 Sep',
        startTime: '05:00 PM',
        endTime: '06:30 PM',
        venue: 'Ground 2 & Gym B',
        coachName: 'Rahul Sharma',
        teamName: 'KhelSutra Elite',
        instructions:
            'Focus on compound lifts, core circuits, and injury prevention routine for 24 athletes.',
        status: 'Scheduled',
        attendanceStatus: 'Pending',
        isToday: true,
      ),
      TrainingSession(
        id: 'TRN-C2',
        title: 'Sprint Acceleration & Agility',
        date: 'Tomorrow, 29 Sep',
        startTime: '06:00 AM',
        endTime: '08:00 AM',
        venue: 'Main Synthetic Track',
        coachName: 'Rahul Sharma',
        teamName: 'KhelSutra Elite',
        instructions: 'Laser timing drills, ladder agility, relay baton handoffs.',
        status: 'Scheduled',
        attendanceStatus: 'Pending',
        isUpcoming: true,
      ),
      TrainingSession(
        id: 'TRN-C3',
        title: 'Aerobic Capacity & Recovery',
        date: '01 Oct 2026',
        startTime: '06:30 AM',
        endTime: '08:00 AM',
        venue: 'Perimeter Trail',
        coachName: 'Rahul Sharma',
        teamName: 'KhelSutra Junior squad',
        instructions: 'Low intensity recovery intervals and mobility assessment.',
        status: 'Scheduled',
        attendanceStatus: 'Pending',
        isUpcoming: true,
      ),
      TrainingSession(
        id: 'TRN-C0',
        title: 'Sprint Starts & Reaction Drill',
        date: '26 Sep 2026',
        startTime: '06:00 AM',
        endTime: '08:00 AM',
        venue: 'Track Ground A',
        coachName: 'Rahul Sharma',
        teamName: 'KhelSutra Elite',
        instructions: 'Complete group timing and reaction testing.',
        status: 'Completed',
        attendanceStatus: 'Completed',
        isCompleted: true,
      ),
    ];

    attendanceRecords = [
      AttendanceRecord(
        date: '28 Sep',
        day: 'Monday',
        isPresent: true,
        sessionTitle: 'Speed & Sprint Mechanics',
        time: '06:00 AM',
      ),
      AttendanceRecord(
        date: '27 Sep',
        day: 'Sunday',
        isPresent: true,
        sessionTitle: 'Maximal Velocity Sprint Test',
        time: '06:00 AM',
      ),
      AttendanceRecord(
        date: '25 Sep',
        day: 'Friday',
        isPresent: true,
        sessionTitle: 'Hypertrophy & Core Stability',
        time: '05:00 PM',
      ),
      AttendanceRecord(
        date: '24 Sep',
        day: 'Thursday',
        isPresent: true,
        sessionTitle: 'Baton Exchange & Relay Practice',
        time: '06:00 AM',
      ),
      AttendanceRecord(
        date: '23 Sep',
        day: 'Wednesday',
        isPresent: false,
        sessionTitle: 'Lactate Threshold Intervals',
        time: '06:00 AM',
      ),
      AttendanceRecord(
        date: '22 Sep',
        day: 'Tuesday',
        isPresent: true,
        sessionTitle: 'Strength Circuit & Mobility',
        time: '05:30 PM',
      ),
      AttendanceRecord(
        date: '21 Sep',
        day: 'Monday',
        isPresent: true,
        sessionTitle: 'Hill Sprints & Power Jumps',
        time: '06:00 AM',
      ),
      AttendanceRecord(
        date: '19 Sep',
        day: 'Saturday',
        isPresent: true,
        sessionTitle: 'Weekly Assessment Trial',
        time: '07:00 AM',
      ),
      AttendanceRecord(
        date: '18 Sep',
        day: 'Friday',
        isPresent: false,
        sessionTitle: 'Active Recovery & Swim',
        time: '06:30 AM',
      ),
      AttendanceRecord(
        date: '17 Sep',
        day: 'Thursday',
        isPresent: true,
        sessionTitle: 'Speed Endurance Intervals',
        time: '06:00 AM',
      ),
    ];

    assessments = [
      AssessmentRecord(
        id: 'ASM-1',
        athleteName: 'Mayur Ghadi',
        testName: '100m Sprint',
        date: '27 Sep 2026',
        score: '11.8',
        unit: 'seconds',
        percentage: 86,
        category: 'Speed',
        notes: 'Personal best recorded with laser gates. Strong drive phase.',
      ),
      AssessmentRecord(
        id: 'ASM-2',
        athleteName: 'Mayur Ghadi',
        testName: 'Vertical Jump',
        date: '25 Sep 2026',
        score: '58',
        unit: 'cm',
        percentage: 78,
        category: 'Strength',
        notes: 'Good explosive power. Improved by 3 cm from last month.',
      ),
      AssessmentRecord(
        id: 'ASM-3',
        athleteName: 'Mayur Ghadi',
        testName: 'Beep Test (VO2 Max)',
        date: '20 Sep 2026',
        score: 'Level 13.4',
        unit: 'level',
        percentage: 80,
        category: 'Endurance',
        notes: 'Excellent cardiovascular foundation for sprint recovery.',
      ),
      AssessmentRecord(
        id: 'ASM-4',
        athleteName: 'Mayur Ghadi',
        testName: 'Pro Agility Shuttle 5-10-5',
        date: '15 Sep 2026',
        score: '4.32',
        unit: 'seconds',
        percentage: 84,
        category: 'Agility',
        notes: 'Quick deceleration and lateral change of direction.',
      ),
      AssessmentRecord(
        id: 'ASM-5',
        athleteName: 'Mayur Ghadi',
        testName: 'Back Squat 1RM',
        date: '10 Sep 2026',
        score: '135',
        unit: 'kg',
        percentage: 74,
        category: 'Strength',
        notes: 'Proper depth achieved. Recommended slight grip adjustment.',
      ),
    ];

    achievements = [
      AchievementItem(
        id: 'ACH-1',
        title: 'District Champion - 100m Sprint',
        competition: 'Pune District Athletics Meet 2026',
        date: '18 Aug 2026',
        medal: 'Gold',
        category: 'Athletics',
        description:
            'Finished 1st with a timing of 11.82s in the Men\'s U-21 100m category.',
      ),
      AchievementItem(
        id: 'ACH-2',
        title: 'Best Athlete of the Tournament',
        competition: 'Maharashtra State Youth Games',
        date: '02 Jun 2026',
        medal: 'Trophy',
        category: 'Athletics',
        description:
            'Awarded overall best athlete after bagging Gold in 100m and Silver in 200m.',
      ),
      AchievementItem(
        id: 'ACH-3',
        title: 'State Championship Silver - 200m',
        competition: 'Maharashtra State Track Championship',
        date: '01 Jun 2026',
        medal: 'Silver',
        category: 'Athletics',
        description:
            'Secured 2nd position clocking 24.12s in a photo finish final.',
      ),
      AchievementItem(
        id: 'ACH-4',
        title: '4x100m Relay Zonal Champions',
        competition: 'West Zone Inter-Club Invitational',
        date: '14 Feb 2026',
        medal: 'Gold',
        category: 'Team Relay',
        description:
            'Anchor runner for KhelSutra Elite team, securing zonal record with 42.15s.',
      ),
    ];

    notifications = [
      AppNotificationItem(
        id: 'NOT-1',
        title: 'Training Updated',
        message: 'Tomorrow\'s training starts at 6:00 AM at Athletics Track.',
        timeAgo: '15m ago',
        category: 'Training',
        icon: Icons.update,
        iconColor: const Color(0xFF1E3A8A),
        isRead: false,
      ),
      AppNotificationItem(
        id: 'NOT-2',
        title: 'Tournament Selection',
        message:
            'Congratulations! You have been selected for the State Championship Squad.',
        timeAgo: '2h ago',
        category: 'Tournament',
        icon: Icons.emoji_events,
        iconColor: const Color(0xFFF97316),
        isRead: false,
      ),
      AppNotificationItem(
        id: 'NOT-3',
        title: 'Attendance Marked',
        message: 'Your attendance for today\'s Morning session has been marked Present.',
        timeAgo: '5h ago',
        category: 'Attendance',
        icon: Icons.check_circle_outline,
        iconColor: const Color(0xFF16A34A),
        isRead: true,
      ),
      AppNotificationItem(
        id: 'NOT-4',
        title: 'Performance Report Available',
        message: 'Coach Rahul added your 100m Sprint test evaluation.',
        timeAgo: '1d ago',
        category: 'General',
        icon: Icons.insights,
        iconColor: const Color(0xFF6366F1),
        isRead: true,
      ),
      AppNotificationItem(
        id: 'NOT-5',
        title: 'Physio Checkup Scheduled',
        message: 'Routine musculoskeletal screening scheduled for Friday 3:00 PM.',
        timeAgo: '2d ago',
        category: 'General',
        icon: Icons.medical_services_outlined,
        iconColor: const Color(0xFF0F766E),
        isRead: true,
      ),
    ];

    coachAthletes = [
      CoachAthleteItem(
        id: 'ATH-001',
        name: 'Mayur Ghadi',
        sport: 'Athletics',
        event: '100m Sprint',
        team: 'KhelSutra Elite',
        attendancePct: 92,
        performancePct: 82,
        jerseyNo: '#07',
        status: 'Active',
        isPresentToday: true,
      ),
      CoachAthleteItem(
        id: 'ATH-002',
        name: 'Arjun Kumar',
        sport: 'Athletics',
        event: '200m Sprint',
        team: 'KhelSutra Elite',
        attendancePct: 94,
        performancePct: 86,
        jerseyNo: '#10',
        status: 'Active',
        isPresentToday: true,
      ),
      CoachAthleteItem(
        id: 'ATH-003',
        name: 'Rahul Patil',
        sport: 'Athletics',
        event: 'Long Jump',
        team: 'KhelSutra Elite',
        attendancePct: 88,
        performancePct: 79,
        jerseyNo: '#14',
        status: 'Active',
        isPresentToday: true,
      ),
      CoachAthleteItem(
        id: 'ATH-004',
        name: 'Amit Naik',
        sport: 'Athletics',
        event: '400m Hurdles',
        team: 'KhelSutra Elite',
        attendancePct: 72,
        performancePct: 68,
        jerseyNo: '#22',
        status: 'Low Attendance',
        isPresentToday: false,
      ),
      CoachAthleteItem(
        id: 'ATH-005',
        name: 'Vikram Shinde',
        sport: 'Athletics',
        event: 'Triple Jump',
        team: 'KhelSutra Elite',
        attendancePct: 74,
        performancePct: 71,
        jerseyNo: '#18',
        status: 'Low Attendance',
        isPresentToday: false,
      ),
      CoachAthleteItem(
        id: 'ATH-006',
        name: 'Rohan Deshmukh',
        sport: 'Athletics',
        event: 'Shot Put',
        team: 'KhelSutra Elite',
        attendancePct: 96,
        performancePct: 88,
        jerseyNo: '#03',
        status: 'Active',
        isPresentToday: true,
      ),
      CoachAthleteItem(
        id: 'ATH-007',
        name: 'Karan Mehra',
        sport: 'Athletics',
        event: '110m Hurdles',
        team: 'KhelSutra Elite',
        attendancePct: 90,
        performancePct: 81,
        jerseyNo: '#11',
        status: 'Active',
        isPresentToday: true,
      ),
      CoachAthleteItem(
        id: 'ATH-008',
        name: 'Siddharth Rao',
        sport: 'Athletics',
        event: 'High Jump',
        team: 'KhelSutra Elite',
        attendancePct: 91,
        performancePct: 84,
        jerseyNo: '#05',
        status: 'Active',
        isPresentToday: true,
      ),
      CoachAthleteItem(
        id: 'ATH-009',
        name: 'Aditya Kulkarni',
        sport: 'Athletics',
        event: 'Javelin Throw',
        team: 'KhelSutra Elite',
        attendancePct: 95,
        performancePct: 89,
        jerseyNo: '#09',
        status: 'Active',
        isPresentToday: true,
      ),
      CoachAthleteItem(
        id: 'ATH-010',
        name: 'Pratik Joshi',
        sport: 'Athletics',
        event: '800m Run',
        team: 'KhelSutra Elite',
        attendancePct: 86,
        performancePct: 77,
        jerseyNo: '#16',
        status: 'Active',
        isPresentToday: true,
      ),
      CoachAthleteItem(
        id: 'ATH-011',
        name: 'Tanmay Salunkhe',
        sport: 'Athletics',
        event: 'Pole Vault',
        team: 'KhelSutra Elite',
        attendancePct: 89,
        performancePct: 83,
        jerseyNo: '#19',
        status: 'Active',
        isPresentToday: true,
      ),
      CoachAthleteItem(
        id: 'ATH-012',
        name: 'Chetan Jadhav',
        sport: 'Athletics',
        event: 'Discus Throw',
        team: 'KhelSutra Elite',
        attendancePct: 93,
        performancePct: 85,
        jerseyNo: '#02',
        status: 'Active',
        isPresentToday: true,
      ),
    ];
  }

  // --- Coach In-Memory Actions ---
  void addTrainingSession(TrainingSession session) {
    coachTrainings.insert(0, session);
    athleteTrainings.insert(0, session);
  }

  void addAssessment(AssessmentRecord assessment) {
    assessments.insert(0, assessment);
  }

  void toggleAthleteAttendance(String athleteId) {
    final index = coachAthletes.indexWhere((a) => a.id == athleteId);
    if (index != -1) {
      coachAthletes[index].isPresentToday = !coachAthletes[index].isPresentToday;
      _updateCoachAttendanceCounts();
    }
  }

  void markAllPresent() {
    for (var athlete in coachAthletes) {
      athlete.isPresentToday = true;
    }
    _updateCoachAttendanceCounts();
  }

  void _updateCoachAttendanceCounts() {
    final present = coachAthletes.where((a) => a.isPresentToday).length;
    final absent = coachAthletes.length - present;
    coachProfile['presentToday'] = present;
    coachProfile['absentToday'] = absent;
  }
}
