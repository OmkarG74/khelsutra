import '../network/json_parser.dart';
import 'athlete_models.dart';

class CoachProfile {
  final int id;
  final String coachCode;
  final int employeeId;
  final String employeeCode;
  final String firstName;
  final String lastName;
  final String specialization;
  final String? qualification;
  final double? experienceYears;
  final String? phone;
  final String? email;
  final String? designation;
  final String? departmentName;
  final List<CoachAssignedTeam> teams;
  final String status;

  CoachProfile({
    required this.id,
    required this.coachCode,
    required this.employeeId,
    required this.employeeCode,
    required this.firstName,
    required this.lastName,
    required this.specialization,
    this.qualification,
    this.experienceYears,
    this.phone,
    this.email,
    this.designation,
    this.departmentName,
    this.teams = const [],
    required this.status,
  });

  String get fullName => '$firstName $lastName'.trim();
  List<CoachAssignedTeam> get assignedTeams => teams;
  String get bio => qualification ?? 'Dedicated sports coaching staff member at KhelSutra.';

  factory CoachProfile.fromJson(Map<String, dynamic> json) {
    final teamsList = (json['teams'] as List<dynamic>?)
            ?.map((e) => CoachAssignedTeam.fromJson(Map<String, dynamic>.from(e as Map)))
            .toList() ??
        [];

    return CoachProfile(
      id: parseInt(json['id'] ?? json['coach_profile_id']),
      coachCode: json['coach_code']?.toString() ?? '',
      employeeId: parseInt(json['employee_id']),
      employeeCode: json['employee_code']?.toString() ?? '',
      firstName: json['first_name']?.toString() ?? '',
      lastName: json['last_name']?.toString() ?? '',
      specialization: json['specialization']?.toString() ?? 'General Coach',
      qualification: json['qualification']?.toString(),
      experienceYears: parseNullableDouble(json['experience_years']),
      phone: json['phone']?.toString(),
      email: json['email']?.toString(),
      designation: json['designation']?.toString() ?? 'Coach',
      departmentName: json['department_name']?.toString(),
      teams: teamsList,
      status: json['status']?.toString() ?? json['coach_status']?.toString() ?? 'active',
    );
  }
}

class CoachAssignedTeam {
  final int teamId;
  final String teamName;
  final String? sportName;
  final String coachRole;
  final bool isPrimary;

  CoachAssignedTeam({
    required this.teamId,
    required this.teamName,
    this.sportName,
    required this.coachRole,
    required this.isPrimary,
  });

  String get roleInTeam => coachRole;

  factory CoachAssignedTeam.fromJson(Map<String, dynamic> json) {
    return CoachAssignedTeam(
      teamId: parseInt(json['team_id']),
      teamName: json['team_name']?.toString() ?? 'Team',
      sportName: json['sport_name']?.toString(),
      coachRole: json['coach_role']?.toString() ?? 'head_coach',
      isPrimary: json['is_primary'] == 1 || json['is_primary'] == true || json['is_primary']?.toString() == '1',
    );
  }
}

class CoachDashboardData {
  final int teamsCount;
  final int athletesCount;
  final int todaySessionsCount;
  final List<TrainingSessionItem> todaySessions;
  final double attendanceRate;
  final int totalAttendanceRecords;
  final int presentCount;

  CoachDashboardData({
    required this.teamsCount,
    required this.athletesCount,
    required this.todaySessionsCount,
    required this.todaySessions,
    required this.attendanceRate,
    required this.totalAttendanceRecords,
    required this.presentCount,
  });

  int get presentToday => presentCount;
  int get absentToday => (totalAttendanceRecords - presentCount).clamp(0, 9999);
  List<dynamic> get lowAttendanceAthletes => [];

  factory CoachDashboardData.fromJson(Map<String, dynamic> json) {
    final list = (json['today_sessions'] as List<dynamic>?)
            ?.map((e) => TrainingSessionItem.fromJson(Map<String, dynamic>.from(e as Map)))
            .toList() ??
        [];

    return CoachDashboardData(
      teamsCount: parseInt(json['teams_count']),
      athletesCount: parseInt(json['athletes_count']),
      todaySessionsCount: parseInt(json['today_sessions_count']),
      todaySessions: list,
      attendanceRate: parseDouble(json['attendance_rate']),
      totalAttendanceRecords: parseInt(json['total_attendance_records']),
      presentCount: parseInt(json['present_count']),
    );
  }
}

class CoachRosterAthleteItem {
  final int id;
  final String athleteCode;
  final String firstName;
  final String? middleName;
  final String lastName;
  final String? photoPath;
  final String? gender;
  final String? phone;
  final String? email;
  final String status;
  final int? sportId;
  final String sportName;
  final int? teamId;
  final String teamName;
  final String? jerseyNumber;
  final String memberRole;

  CoachRosterAthleteItem({
    required this.id,
    required this.athleteCode,
    required this.firstName,
    this.middleName,
    required this.lastName,
    this.photoPath,
    this.gender,
    this.phone,
    this.email,
    required this.status,
    this.sportId,
    required this.sportName,
    this.teamId,
    required this.teamName,
    this.jerseyNumber,
    required this.memberRole,
  });

  String get fullName => '$firstName $lastName'.trim();

  factory CoachRosterAthleteItem.fromJson(Map<String, dynamic> json) {
    return CoachRosterAthleteItem(
      id: parseInt(json['id']),
      athleteCode: json['athlete_code']?.toString() ?? '',
      firstName: json['first_name']?.toString() ?? '',
      middleName: json['middle_name']?.toString(),
      lastName: json['last_name']?.toString() ?? '',
      photoPath: json['photo_path']?.toString(),
      gender: json['gender']?.toString(),
      phone: json['phone']?.toString(),
      email: json['email']?.toString(),
      status: json['status']?.toString() ?? 'active',
      sportId: parseNullableInt(json['sport_id']),
      sportName: json['sport_name']?.toString() ?? 'Sports',
      teamId: parseNullableInt(json['team_id']),
      teamName: json['team_name']?.toString() ?? 'Roster Team',
      jerseyNumber: json['jersey_number']?.toString(),
      memberRole: json['member_role']?.toString() ?? 'player',
    );
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CoachRosterAthleteItem &&
          runtimeType == other.runtimeType &&
          id == other.id;

  @override
  int get hashCode => id.hashCode;
}

class PerformanceMetricItem {
  final int id;
  final int? sportId;
  final String name;
  final String code;
  final String metricType;
  final String? unit;
  final double? minValue;
  final double? maxValue;
  final String? description;

  PerformanceMetricItem({
    required this.id,
    this.sportId,
    required this.name,
    required this.code,
    required this.metricType,
    this.unit,
    this.minValue,
    this.maxValue,
    this.description,
  });

  factory PerformanceMetricItem.fromJson(Map<String, dynamic> json) {
    return PerformanceMetricItem(
      id: parseInt(json['id']),
      sportId: parseNullableInt(json['sport_id']),
      name: json['name']?.toString() ?? '',
      code: json['code']?.toString() ?? '',
      metricType: json['metric_type']?.toString() ?? 'number',
      unit: json['unit']?.toString(),
      minValue: parseNullableDouble(json['min_value']),
      maxValue: parseNullableDouble(json['max_value']),
      description: json['description']?.toString(),
    );
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PerformanceMetricItem &&
          runtimeType == other.runtimeType &&
          id == other.id;

  @override
  int get hashCode => id.hashCode;
}
