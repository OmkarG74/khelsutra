import '../network/json_parser.dart';

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
      id: parseInt(json['id']),
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
      currentSportId: parseNullableInt(json['current_sport_id']),
      sportName: json['sport_name']?.toString() ?? 'Badminton',
      categoryName: json['category_name']?.toString() ?? 'Senior Division',
      teamName: json['team_name']?.toString() ?? 'General Roster',
      teamId: parseNullableInt(json['team_id']),
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
      id: parseInt(json['id']),
      trainingSessionId: parseInt(json['training_session_id']),
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
      id: parseInt(json['id']),
      reference: json['training_reference']?.toString() ?? '',
      title: json['title']?.toString() ?? 'Training Session',
      date: json['training_date']?.toString() ?? '',
      startTime: json['start_time']?.toString() ?? '06:00 AM',
      endTime: json['end_time']?.toString() ?? '08:00 AM',
      venue: venueName,
      coachName: json['coach_name']?.toString() ?? 'Head Coach',
      teamName: json['team_name']?.toString() ?? 'Team Roster',
      teamId: parseNullableInt(json['team_id']),
      venueId: parseNullableInt(json['venue_id']),
      coachId: parseNullableInt(json['coach_id']),
      status: json['status']?.toString() ?? 'scheduled',
      objectives: json['objectives']?.toString(),
      notes: json['notes']?.toString(),
      totalRosterCount: parseInt(json['total_roster_count'], rosterList.length),
      presentCount: parseInt(json['present_count']),
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
      athleteId: parseInt(json['athlete_id']),
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
      metricId: parseInt(json['metric_id']),
      metricName: json['metric_name']?.toString() ?? 'Metric',
      metricCode: json['metric_code']?.toString() ?? '',
      numericValue: parseNullableDouble(json['numeric_value']),
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

  double? get trainingScore => overallRating;

  factory PerformanceRecordItem.fromJson(Map<String, dynamic> json) {
    final valsList = (json['values'] as List<dynamic>?)
            ?.map((e) => PerformanceValueItem.fromJson(Map<String, dynamic>.from(e as Map)))
            .toList() ??
        [];

    final scoreVal = json['training_score'] ?? json['overall_rating'];

    return PerformanceRecordItem(
      id: parseInt(json['id']),
      evaluationDate: json['evaluation_date']?.toString() ?? '',
      overallRating: parseNullableDouble(scoreVal),
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
      id: parseInt(json['id']),
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
      id: parseInt(json['id']),
      title: json['title']?.toString() ?? 'Notification',
      message: json['message']?.toString() ?? '',
      type: json['notification_type']?.toString() ?? 'general',
      isRead: json['is_read'] == 1 || json['is_read'] == true || json['is_read']?.toString() == '1',
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

class AthleteDocumentItem {
  final int id;
  final int athleteId;
  final String documentType;
  final String documentName;
  final String? filePath;
  final int? fileSize;
  final String? mimeType;
  final String? expiryDate;
  final String? verifiedAt;
  final String createdAt;

  AthleteDocumentItem({
    required this.id,
    required this.athleteId,
    required this.documentType,
    required this.documentName,
    this.filePath,
    this.fileSize,
    this.mimeType,
    this.expiryDate,
    this.verifiedAt,
    required this.createdAt,
  });

  bool get isVerified => verifiedAt != null && verifiedAt!.isNotEmpty;
  String get verificationStatus => isVerified ? 'Verified' : 'Pending Verification';

  String get typeLabel {
    switch (documentType) {
      case 'id_proof':
        return 'Government ID';
      case 'birth_certificate':
        return 'Birth Certificate';
      case 'passport':
        return 'Passport';
      case 'medical_certificate':
        return 'Medical Certificate';
      case 'sports_certificate':
        return 'Sports Certificate';
      case 'consent_form':
        return 'Consent Form';
      default:
        return 'Official Document';
    }
  }

  factory AthleteDocumentItem.fromJson(Map<String, dynamic> json) {
    return AthleteDocumentItem(
      id: parseInt(json['id']),
      athleteId: parseInt(json['athlete_id']),
      documentType: json['document_type']?.toString() ?? 'other',
      documentName: json['document_name']?.toString() ?? 'Document',
      filePath: json['file_path']?.toString(),
      fileSize: parseNullableInt(json['file_size']),
      mimeType: json['mime_type']?.toString(),
      expiryDate: json['expiry_date']?.toString(),
      verifiedAt: json['verified_at']?.toString(),
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

class MatchItem {
  final int id;
  final int fixtureId;
  final String matchReference;
  final String fixtureReference;
  final String scheduledDate;
  final String scheduledStartTime;
  final String? scheduledEndTime;
  final String matchStatus;
  final String fixtureStatus;
  final String? roundName;
  final int? homeTeamId;
  final int? awayTeamId;
  final String homeTeamName;
  final String awayTeamName;
  final double? homeScore;
  final double? awayScore;
  final String? tournamentName;
  final String? sportName;
  final String? venueName;
  final String? refereeName;
  final String? matchNotes;
  final List<MatchRosterAthleteItem> rosterAttendance;

  MatchItem({
    required this.id,
    required this.fixtureId,
    required this.matchReference,
    required this.fixtureReference,
    required this.scheduledDate,
    required this.scheduledStartTime,
    this.scheduledEndTime,
    required this.matchStatus,
    required this.fixtureStatus,
    this.roundName,
    this.homeTeamId,
    this.awayTeamId,
    required this.homeTeamName,
    required this.awayTeamName,
    this.homeScore,
    this.awayScore,
    this.tournamentName,
    this.sportName,
    this.venueName,
    this.refereeName,
    this.matchNotes,
    this.rosterAttendance = const [],
  });

  bool get isCompleted => matchStatus.toLowerCase() == 'completed';
  bool get isLive => matchStatus.toLowerCase() == 'live';
  String get status => matchStatus;
  String get matchDate => scheduledDate;
  String get scheduledTime => scheduledStartTime;

  String get scoreDisplay {
    if (homeScore != null && awayScore != null) {
      return '${homeScore!.toInt()} - ${awayScore!.toInt()}';
    }
    return 'VS';
  }

  factory MatchItem.fromJson(Map<String, dynamic> json) {
    final rosterList = (json['roster_attendance'] as List<dynamic>?)
            ?.map((e) => MatchRosterAthleteItem.fromJson(Map<String, dynamic>.from(e as Map)))
            .toList() ??
        [];

    return MatchItem(
      id: parseInt(json['match_id'] ?? json['id']),
      fixtureId: parseInt(json['fixture_id']),
      matchReference: json['match_reference']?.toString() ?? '',
      fixtureReference: json['fixture_reference']?.toString() ?? '',
      scheduledDate: json['scheduled_date']?.toString() ?? '',
      scheduledStartTime: json['scheduled_start_time']?.toString() ?? '',
      scheduledEndTime: json['scheduled_end_time']?.toString(),
      matchStatus: json['match_status']?.toString() ?? json['status']?.toString() ?? 'scheduled',
      fixtureStatus: json['fixture_status']?.toString() ?? 'scheduled',
      roundName: json['round_name']?.toString(),
      homeTeamId: parseNullableInt(json['home_team_id']),
      awayTeamId: parseNullableInt(json['away_team_id']),
      homeTeamName: json['home_team_name']?.toString() ?? 'Home Team',
      awayTeamName: json['away_team_name']?.toString() ?? 'Away Team',
      homeScore: parseNullableDouble(json['home_score']),
      awayScore: parseNullableDouble(json['away_score']),
      tournamentName: json['tournament_name']?.toString(),
      sportName: json['sport_name']?.toString(),
      venueName: json['venue_name']?.toString(),
      refereeName: json['referee_name']?.toString(),
      matchNotes: json['match_notes']?.toString(),
      rosterAttendance: rosterList,
    );
  }
}

class MatchRosterAthleteItem {
  final int athleteId;
  final String athleteName;
  final String athleteCode;
  final String? photoPath;
  final int teamId;
  final String? jerseyNumber;
  final String teamName;
  final String attendanceStatus;
  final String? remarks;

  MatchRosterAthleteItem({
    required this.athleteId,
    required this.athleteName,
    required this.athleteCode,
    this.photoPath,
    required this.teamId,
    this.jerseyNumber,
    required this.teamName,
    this.attendanceStatus = 'present',
    this.remarks,
  });

  String? get attendanceRemarks => remarks;

  factory MatchRosterAthleteItem.fromJson(Map<String, dynamic> json) {
    return MatchRosterAthleteItem(
      athleteId: parseInt(json['athlete_id']),
      athleteName: json['athlete_name']?.toString() ?? '',
      athleteCode: json['athlete_code']?.toString() ?? '',
      photoPath: json['photo_path']?.toString(),
      teamId: parseInt(json['team_id']),
      jerseyNumber: json['jersey_number']?.toString(),
      teamName: json['team_name']?.toString() ?? '',
      attendanceStatus: json['attendance_status']?.toString() ?? 'present',
      remarks: json['remarks']?.toString(),
    );
  }
}

class MatchAttendanceRecordItem {
  final int id;
  final int matchId;
  final String matchReference;
  final String fixtureReference;
  final String scheduledDate;
  final String scheduledStartTime;
  final String homeTeamName;
  final String awayTeamName;
  final String? tournamentName;
  final String attendanceStatus;
  final String? remarks;

  MatchAttendanceRecordItem({
    required this.id,
    required this.matchId,
    required this.matchReference,
    required this.fixtureReference,
    required this.scheduledDate,
    required this.scheduledStartTime,
    required this.homeTeamName,
    required this.awayTeamName,
    this.tournamentName,
    required this.attendanceStatus,
    this.remarks,
  });

  bool get isPresent => attendanceStatus.toLowerCase() == 'present';
  String get matchDate => scheduledDate;

  factory MatchAttendanceRecordItem.fromJson(Map<String, dynamic> json) {
    return MatchAttendanceRecordItem(
      id: parseInt(json['id']),
      matchId: parseInt(json['match_id']),
      matchReference: json['match_reference']?.toString() ?? '',
      fixtureReference: json['fixture_reference']?.toString() ?? '',
      scheduledDate: json['scheduled_date']?.toString() ?? '',
      scheduledStartTime: json['scheduled_start_time']?.toString() ?? '',
      homeTeamName: json['home_team_name']?.toString() ?? '',
      awayTeamName: json['away_team_name']?.toString() ?? '',
      tournamentName: json['tournament_name']?.toString(),
      attendanceStatus: json['attendance_status']?.toString() ?? 'present',
      remarks: json['remarks']?.toString(),
    );
  }
}

class LeaveRequestItem {
  final int id;
  final String leaveTypeName;
  final String startDate;
  final String endDate;
  final double totalDays;
  final String? reason;
  final String status; // 'pending', 'approved', 'rejected', 'cancelled'
  final String? rejectionReason;
  final String createdAt;

  LeaveRequestItem({
    required this.id,
    required this.leaveTypeName,
    required this.startDate,
    required this.endDate,
    required this.totalDays,
    this.reason,
    required this.status,
    this.rejectionReason,
    required this.createdAt,
  });

  factory LeaveRequestItem.fromJson(Map<String, dynamic> json) {
    return LeaveRequestItem(
      id: parseInt(json['id']),
      leaveTypeName: json['leave_type_name']?.toString() ?? 'General Leave',
      startDate: json['start_date']?.toString() ?? '',
      endDate: json['end_date']?.toString() ?? '',
      totalDays: parseDouble(json['total_days'], 1.0),
      reason: json['reason']?.toString(),
      status: json['status']?.toString() ?? 'pending',
      rejectionReason: json['rejection_reason']?.toString(),
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

