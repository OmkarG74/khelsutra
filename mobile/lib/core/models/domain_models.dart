import 'package:flutter/material.dart';
import 'athlete_models.dart';
import 'coach_models.dart';

/// Clean domain view-models mapped directly from live API responses

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
  final double? trainingScore;
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
    this.trainingScore,
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
      trainingScore: record.trainingScore,
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
    required this.isRead,
  });

  factory AppNotificationItem.fromItem(NotificationItemModel item) {
    IconData icon = Icons.notifications;
    Color color = Colors.blue;

    final typeLower = item.type.toLowerCase();
    if (typeLower.contains('training') || typeLower.contains('session')) {
      icon = Icons.fitness_center;
      color = Colors.orange;
    } else if (typeLower.contains('match') || typeLower.contains('tournament')) {
      icon = Icons.emoji_events;
      color = Colors.amber;
    } else if (typeLower.contains('attendance')) {
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
