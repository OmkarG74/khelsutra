class AthleteProfile {
  final int id;
  final String athleteCode;
  final String firstName;
  final String? middleName;
  final String lastName;
  final String? photoPath;
  final String? dateOfBirth;
  final String? gender;
  final String? bloodGroup;
  final String? nationality;
  final String? phone;
  final String? email;
  final String? address;
  final String? city;
  final String? state;
  final String? country;
  final String? postalCode;
  final int? currentSportId;
  final String sportName;
  final String categoryName;
  final String teamName;
  final int? teamId;
  final String status;
  final String? joiningDate;
  final String? guardianName;
  final String? guardianPhone;

  AthleteProfile({
    required this.id,
    required this.athleteCode,
    required this.firstName,
    this.middleName,
    required this.lastName,
    this.photoPath,
    this.dateOfBirth,
    this.gender,
    this.bloodGroup,
    this.nationality,
    this.phone,
    this.email,
    this.address,
    this.city,
    this.state,
    this.country,
    this.postalCode,
    this.currentSportId,
    required this.sportName,
    required this.categoryName,
    required this.teamName,
    this.teamId,
    required this.status,
    this.joiningDate,
    this.guardianName,
    this.guardianPhone,
  });

  String get fullName => '$firstName $lastName'.trim();

  factory AthleteProfile.fromJson(Map<String, dynamic> json) {
    final guardian = json['guardian'] as Map<String, dynamic>?;
    return AthleteProfile(
      id: (json['id'] as num?)?.toInt() ?? 0,
      athleteCode: json['athlete_code']?.toString() ?? '',
      firstName: json['first_name']?.toString() ?? '',
      middleName: json['middle_name']?.toString(),
      lastName: json['last_name']?.toString() ?? '',
      photoPath: json['photo_path']?.toString(),
      dateOfBirth: json['date_of_birth']?.toString(),
      gender: json['gender']?.toString(),
      bloodGroup: json['blood_group']?.toString(),
      nationality: json['nationality']?.toString(),
      phone: json['phone']?.toString(),
      email: json['email']?.toString(),
      address: json['address_line1']?.toString() ?? json['address']?.toString(),
      city: json['city']?.toString(),
      state: json['state']?.toString(),
      country: json['country']?.toString() ?? 'India',
      postalCode: json['postal_code']?.toString(),
      currentSportId: (json['current_sport_id'] as num?)?.toInt(),
      sportName: json['sport_name']?.toString() ?? 'Badminton',
      categoryName: json['category_name']?.toString() ?? 'Senior Division',
      teamName: json['team_name']?.toString() ?? 'General Roster',
      teamId: (json['team_id'] as num?)?.toInt(),
      status: json['status']?.toString() ?? 'active',
      joiningDate: json['joining_date']?.toString(),
      guardianName: guardian?['full_name']?.toString(),
      guardianPhone: guardian?['phone']?.toString(),
    );
  }

  double? get weightKg => null;

  dynamic operator [](String key) {
    final map = toJson();
    if (map.containsKey(key)) return map[key];
    switch (key) {
      case 'id': return id;
      case 'name': return fullName;
      case 'athlete_code': return athleteCode;
      case 'first_name': return firstName;
      case 'last_name': return lastName;
      case 'phone': return phone;
      case 'email': return email;
      case 'gender': return gender;
      case 'date_of_birth': return dateOfBirth;
      case 'blood_group': return bloodGroup;
      case 'nationality': return nationality;
      case 'address': return address;
      case 'city': return city;
      case 'state': return state;
      case 'country': return country;
      case 'sport': return sportName;
      case 'sport_name': return sportName;
      case 'category': return categoryName;
      case 'category_name': return categoryName;
      case 'team': return teamName;
      case 'team_name': return teamName;
      case 'status': return status;
      case 'joining_date': return joiningDate;
      default: return null;
    }
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'athlete_code': athleteCode,
    'first_name': firstName,
    'last_name': lastName,
    'phone': phone,
    'email': email,
    'sport_name': sportName,
    'team_name': teamName,
    'status': status,
  };
}

class AttendanceRecordItem {
  final int id;
  final int trainingSessionId;
  final String sessionTitle;
  final String date;
  final String time;
  final String status;
  final String? remarks;

  AttendanceRecordItem({
    required this.id,
    required this.trainingSessionId,
    required this.sessionTitle,
    required this.date,
    required this.time,
    required this.status,
    this.remarks,
  });

  bool get isPresent => status.toLowerCase() == 'present';
  String get sessionDate => date;

  factory AttendanceRecordItem.fromJson(Map<String, dynamic> json) {
    final startTime = json['start_time']?.toString() ?? '';
    final endTime = json['end_time']?.toString() ?? '';
    final timeStr = startTime.isNotEmpty
        ? (endTime.isNotEmpty ? '$startTime - $endTime' : startTime)
        : '04:00 PM';

    return AttendanceRecordItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      trainingSessionId: (json['training_session_id'] as num?)?.toInt() ?? 0,
      sessionTitle: json['session_title']?.toString() ?? 'Training Session',
      date: json['training_date']?.toString() ?? '',
      time: timeStr,
      status: json['attendance_status']?.toString() ?? 'present',
      remarks: json['remarks']?.toString(),
    );
  }
}

class AthleteAttendanceSummary {
  final double overallPercentage;
  final int totalSessions;
  final int presentCount;
  final int absentCount;
  final int excusedCount;
  final List<AttendanceRecordItem> records;

  AthleteAttendanceSummary({
    required this.overallPercentage,
    required this.totalSessions,
    required this.presentCount,
    required this.absentCount,
    required this.excusedCount,
    required this.records,
  });

  double get percentage => overallPercentage;
  int get presentSessions => presentCount;
  int get absentSessions => absentCount;

  static AthleteAttendanceSummary empty() => AthleteAttendanceSummary.fromRecords([]);

  factory AthleteAttendanceSummary.fromRecords(List<AttendanceRecordItem> records) {
    if (records.isEmpty) {
      return AthleteAttendanceSummary(
        overallPercentage: 0,
        totalSessions: 0,
        presentCount: 0,
        absentCount: 0,
        excusedCount: 0,
        records: [],
      );
    }

    int present = 0;
    int absent = 0;
    int excused = 0;

    for (final r in records) {
      final st = r.status.toLowerCase();
      if (st == 'present') {
        present++;
      } else if (st == 'absent') {
        absent++;
      } else {
        excused++;
      }
    }

    final total = records.length;
    final pct = total > 0 ? (present / total) * 100 : 0.0;

    return AthleteAttendanceSummary(
      overallPercentage: double.parse(pct.toStringAsFixed(1)),
      totalSessions: total,
      presentCount: present,
      absentCount: absent,
      excusedCount: excused,
      records: records,
    );
  }
}

class TrainingSessionItem {
  final int id;
  final String reference;
  final String title;
  final String date;
  final String startTime;
  final String endTime;
  final String venue;
  final String coachName;
  final String teamName;
  final int? teamId;
  final int? venueId;
  final int? coachId;
  final String status;
  final String? objectives;
  final String? notes;
  final int totalRosterCount;
  final int presentCount;
  final List<Map<String, dynamic>> rosterAttendance;

  TrainingSessionItem({
    required this.id,
    required this.reference,
    required this.title,
    required this.date,
    required this.startTime,
    required this.endTime,
    required this.venue,
    required this.coachName,
    required this.teamName,
    this.teamId,
    this.venueId,
    this.coachId,
    required this.status,
    this.objectives,
    this.notes,
    this.totalRosterCount = 0,
    this.presentCount = 0,
    this.rosterAttendance = const [],
  });

  bool get isToday {
    final now = DateTime.now();
    final todayStr = '${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';
    return date == todayStr;
  }

  bool get isCompleted => status.toLowerCase() == 'completed';
  bool get isUpcoming => !isCompleted && !isToday;

  factory TrainingSessionItem.fromJson(Map<String, dynamic> json) {
    final rosterList = (json['roster_attendance'] as List<dynamic>?)
            ?.map((e) => Map<String, dynamic>.from(e as Map))
            .toList() ??
        [];

    final venueName = json['venue_name']?.toString() ??
        (json['facility_name'] != null ? 'Apex Sports Complex' : 'Main Ground');

    return TrainingSessionItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      reference: json['training_reference']?.toString() ?? '',
      title: json['title']?.toString() ?? 'Training Session',
      date: json['training_date']?.toString() ?? '',
      startTime: json['start_time']?.toString() ?? '06:00 AM',
      endTime: json['end_time']?.toString() ?? '08:00 AM',
      venue: venueName,
      coachName: json['coach_name']?.toString() ?? 'Head Coach',
      teamName: json['team_name']?.toString() ?? 'Team Roster',
      teamId: (json['team_id'] as num?)?.toInt(),
      venueId: (json['venue_id'] as num?)?.toInt(),
      coachId: (json['coach_id'] as num?)?.toInt(),
      status: json['status']?.toString() ?? 'scheduled',
      objectives: json['objectives']?.toString(),
      notes: json['notes']?.toString(),
      totalRosterCount: (json['total_roster_count'] as num?)?.toInt() ?? rosterList.length,
      presentCount: int.tryParse(json['present_count']?.toString() ?? '0') ?? 0,
      rosterAttendance: rosterList,
    );
  }
}

class SessionAttendanceItem {
  final int athleteId;
  final String athleteName;
  final String athleteCode;
  String status; // 'not_marked', 'present', 'absent', 'late', 'leave'
  String? remarks;
  final String? jerseyNumber;
  final String? memberRole;

  SessionAttendanceItem({
    required this.athleteId,
    required this.athleteName,
    required this.athleteCode,
    this.status = 'not_marked',
    this.remarks,
    this.jerseyNumber,
    this.memberRole,
  });

  factory SessionAttendanceItem.fromJson(Map<String, dynamic> json) {
    final first = json['first_name']?.toString() ?? '';
    final last = json['last_name']?.toString() ?? '';
    final name = (first.isEmpty && last.isEmpty)
        ? (json['name']?.toString() ?? 'Athlete')
        : '$first $last'.trim();

    final rawStatus = (json['attendance_status'] ?? json['status'] ?? 'not_marked')
        .toString()
        .toLowerCase()
        .trim();
    final validStatus = (rawStatus == 'present' || rawStatus == 'absent' || rawStatus == 'late' || rawStatus == 'leave')
        ? rawStatus
        : 'not_marked';

    return SessionAttendanceItem(
      athleteId: (json['athlete_id'] as num?)?.toInt() ?? 0,
      athleteName: name,
      athleteCode: json['athlete_code']?.toString() ?? '',
      status: validStatus,
      remarks: json['remarks']?.toString(),
      jerseyNumber: json['jersey_number']?.toString(),
      memberRole: json['member_role']?.toString(),
    );
  }
}

class PerformanceValueItem {
  final int metricId;
  final String metricName;
  final String metricCode;
  final double? numericValue;
  final String? textValue;
  final String? unit;
  final String metricType;

  PerformanceValueItem({
    required this.metricId,
    required this.metricName,
    required this.metricCode,
    this.numericValue,
    this.textValue,
    this.unit,
    required this.metricType,
  });

  String get displayValue {
    if (numericValue != null) {
      final formatted = numericValue! % 1 == 0 ? numericValue!.toInt().toString() : numericValue!.toStringAsFixed(1);
      return unit != null && unit!.isNotEmpty ? '$formatted $unit' : formatted;
    }
    return textValue ?? '-';
  }

  factory PerformanceValueItem.fromJson(Map<String, dynamic> json) {
    return PerformanceValueItem(
      metricId: (json['metric_id'] as num?)?.toInt() ?? 0,
      metricName: json['metric_name']?.toString() ?? 'Metric',
      metricCode: json['metric_code']?.toString() ?? '',
      numericValue: json['numeric_value'] != null ? double.tryParse(json['numeric_value'].toString()) : null,
      textValue: json['text_value']?.toString(),
      unit: json['unit']?.toString(),
      metricType: json['metric_type']?.toString() ?? 'number',
    );
  }
}

class PerformanceRecordItem {
  final int id;
  final String evaluationDate;
  final double? overallRating;
  final String? coachRemarks;
  final String sportName;
  final String? teamName;
  final String? sessionTitle;
  final List<PerformanceValueItem> values;

  PerformanceRecordItem({
    required this.id,
    required this.evaluationDate,
    this.overallRating,
    this.coachRemarks,
    required this.sportName,
    this.teamName,
    this.sessionTitle,
    required this.values,
  });

  factory PerformanceRecordItem.fromJson(Map<String, dynamic> json) {
    final valsList = (json['values'] as List<dynamic>?)
            ?.map((e) => PerformanceValueItem.fromJson(Map<String, dynamic>.from(e as Map)))
            .toList() ??
        [];

    return PerformanceRecordItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      evaluationDate: json['evaluation_date']?.toString() ?? '',
      overallRating: json['overall_rating'] != null ? double.tryParse(json['overall_rating'].toString()) : null,
      coachRemarks: json['coach_remarks']?.toString(),
      sportName: json['sport_name']?.toString() ?? 'Sports',
      teamName: json['team_name']?.toString(),
      sessionTitle: json['training_session_title']?.toString(),
      values: valsList,
    );
  }
}

class AchievementItemModel {
  final int id;
  final String title;
  final String type;
  final String? positionOrMedal;
  final String date;
  final String description;
  final String? tournamentName;
  final String? certificatePath;

  AchievementItemModel({
    required this.id,
    required this.title,
    required this.type,
    this.positionOrMedal,
    required this.date,
    required this.description,
    this.tournamentName,
    this.certificatePath,
  });

  factory AchievementItemModel.fromJson(Map<String, dynamic> json) {
    return AchievementItemModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: json['title']?.toString() ?? 'Achievement',
      type: json['achievement_type']?.toString() ?? 'Tournament',
      positionOrMedal: json['position_or_medal']?.toString(),
      date: json['achievement_date']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      tournamentName: json['tournament_name']?.toString(),
      certificatePath: json['certificate_path']?.toString(),
    );
  }
}

class NotificationItemModel {
  final int id;
  final String title;
  final String message;
  final String type;
  final bool isRead;
  final String createdAt;

  NotificationItemModel({
    required this.id,
    required this.title,
    required this.message,
    required this.type,
    required this.isRead,
    required this.createdAt,
  });

  factory NotificationItemModel.fromJson(Map<String, dynamic> json) {
    return NotificationItemModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: json['title']?.toString() ?? 'Notification',
      message: json['message']?.toString() ?? '',
      type: json['notification_type']?.toString() ?? 'general',
      isRead: json['is_read'] == 1 || json['is_read'] == true,
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}
