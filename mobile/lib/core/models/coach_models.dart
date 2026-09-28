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
      id: (json['id'] as num?)?.toInt() ?? (json['coach_profile_id'] as num?)?.toInt() ?? 0,
      coachCode: json['coach_code']?.toString() ?? '',
      employeeId: (json['employee_id'] as num?)?.toInt() ?? 0,
      employeeCode: json['employee_code']?.toString() ?? '',
      firstName: json['first_name']?.toString() ?? '',
      lastName: json['last_name']?.toString() ?? '',
      specialization: json['specialization']?.toString() ?? 'General Coach',
      qualification: json['qualification']?.toString(),
      experienceYears: json['experience_years'] != null ? double.tryParse(json['experience_years'].toString()) : null,
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
      teamId: (json['team_id'] as num?)?.toInt() ?? 0,
      teamName: json['team_name']?.toString() ?? 'Team',
      sportName: json['sport_name']?.toString(),
      coachRole: json['coach_role']?.toString() ?? 'head_coach',
      isPrimary: json['is_primary'] == 1 || json['is_primary'] == true,
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
      teamsCount: (json['teams_count'] as num?)?.toInt() ?? 0,
      athletesCount: (json['athletes_count'] as num?)?.toInt() ?? 0,
      todaySessionsCount: (json['today_sessions_count'] as num?)?.toInt() ?? 0,
      todaySessions: list,
      attendanceRate: (json['attendance_rate'] as num?)?.toDouble() ?? 0.0,
      totalAttendanceRecords: (json['total_attendance_records'] as num?)?.toInt() ?? 0,
      presentCount: (json['present_count'] as num?)?.toInt() ?? 0,
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
    required this.sportName,
    this.teamId,
    required this.teamName,
    this.jerseyNumber,
    required this.memberRole,
  });

  String get fullName => '$firstName $lastName'.trim();

  factory CoachRosterAthleteItem.fromJson(Map<String, dynamic> json) {
    return CoachRosterAthleteItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      athleteCode: json['athlete_code']?.toString() ?? '',
      firstName: json['first_name']?.toString() ?? '',
      middleName: json['middle_name']?.toString(),
      lastName: json['last_name']?.toString() ?? '',
      photoPath: json['photo_path']?.toString(),
      gender: json['gender']?.toString(),
      phone: json['phone']?.toString(),
      email: json['email']?.toString(),
      status: json['status']?.toString() ?? 'active',
      sportName: json['sport_name']?.toString() ?? 'Sports',
      teamId: (json['team_id'] as num?)?.toInt(),
      teamName: json['team_name']?.toString() ?? 'Roster Team',
      jerseyNumber: json['jersey_number']?.toString(),
      memberRole: json['member_role']?.toString() ?? 'player',
    );
  }
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
      id: (json['id'] as num?)?.toInt() ?? 0,
      sportId: (json['sport_id'] as num?)?.toInt(),
      name: json['name']?.toString() ?? '',
      code: json['code']?.toString() ?? '',
      metricType: json['metric_type']?.toString() ?? 'number',
      unit: json['unit']?.toString(),
      minValue: json['min_value'] != null ? double.tryParse(json['min_value'].toString()) : null,
      maxValue: json['max_value'] != null ? double.tryParse(json['max_value'].toString()) : null,
      description: json['description']?.toString(),
    );
  }
}
