import '../../../core/models/athlete_models.dart';
import '../../../core/models/coach_models.dart';
import '../../../core/network/api_client.dart';
import '../../../core/storage/token_storage.dart';

class CoachRepository {
  final ApiClient _client;

  CoachRepository({ApiClient? client}) : _client = client ?? ApiClient();

  Future<int?> _resolveCoachId(int? id) async {
    if (id != null) return id;
    final stored = await TokenStorage.getCoachId();
    return stored;
  }

  /// Get coach profile
  Future<CoachProfile> getProfile([int? coachId]) async {
    final resolvedId = await _resolveCoachId(coachId);
    if (resolvedId == null) {
      throw Exception('No coach profile associated with this account');
    }

    final res = await _client.get<CoachProfile>(
      '/coaches/$resolvedId',
      fromJson: (json) => CoachProfile.fromJson(Map<String, dynamic>.from(json as Map)),
    );

    if (res.success && res.data != null) {
      return res.data!;
    }
    throw Exception(res.message);
  }

  /// Get coach operational dashboard summary
  Future<CoachDashboardData> getDashboard([int? coachId]) async {
    final resolvedId = await _resolveCoachId(coachId);
    if (resolvedId == null) {
      throw Exception('No coach profile associated with this account');
    }

    final res = await _client.get<CoachDashboardData>(
      '/coaches/$resolvedId/dashboard',
      fromJson: (json) => CoachDashboardData.fromJson(Map<String, dynamic>.from(json as Map)),
    );

    if (res.success && res.data != null) {
      return res.data!;
    }
    throw Exception(res.message);
  }

  /// Get athletes assigned to this coach's teams
  Future<List<CoachRosterAthleteItem>> getAthletes({int? coachId, String? search}) async {
    final resolvedId = await _resolveCoachId(coachId);
    if (resolvedId == null) return [];

    var endpoint = '/coaches/$resolvedId/athletes';
    if (search != null && search.isNotEmpty) {
      endpoint += '?search=${Uri.encodeComponent(search)}';
    }

    final res = await _client.get<Map<String, dynamic>>(
      endpoint,
      fromJson: (json) => Map<String, dynamic>.from(json as Map),
    );

    if (res.success && res.data != null) {
      final list = (res.data!['data'] as List<dynamic>?)
              ?.map((e) => CoachRosterAthleteItem.fromJson(Map<String, dynamic>.from(e as Map)))
              .toList() ??
          [];
      return list;
    }
    return [];
  }

  /// Get full athlete details for Coach Athlete Details view
  Future<Map<String, dynamic>> getAthleteDetails(int athleteId) async {
    final profileRes = await _client.get<AthleteProfile>(
      '/athletes/$athleteId',
      fromJson: (json) => AthleteProfile.fromJson(Map<String, dynamic>.from(json as Map)),
    );
    final attendance = await getAthleteAttendance(athleteId);
    final performance = await getAthletePerformance(athleteId);
    final achievements = await getAthleteAchievements(athleteId);
    final trainings = await getTrainingSessions();

    return {
      'profile': profileRes.data,
      'attendance': attendance,
      'performance': performance,
      'achievements': achievements,
      'trainings': trainings,
    };
  }

  /// Get training sessions for coach's teams
  Future<List<TrainingSessionItem>> getTrainingSessions({int? coachId, String? status, String? date}) async {
    final resolvedId = await _resolveCoachId(coachId);
    var endpoint = '/training-sessions';
    final queryParams = <String>[];
    if (resolvedId != null) queryParams.add('coach_id=$resolvedId');
    if (status != null && status.isNotEmpty) queryParams.add('status=$status');
    if (date != null && date.isNotEmpty) queryParams.add('date=$date');
    if (queryParams.isNotEmpty) {
      endpoint += '?${queryParams.join('&')}';
    }

    final res = await _client.get<Map<String, dynamic>>(
      endpoint,
      fromJson: (json) => Map<String, dynamic>.from(json as Map),
    );

    if (res.success && res.data != null) {
      final list = (res.data!['data'] as List<dynamic>?)
              ?.map((e) => TrainingSessionItem.fromJson(Map<String, dynamic>.from(e as Map)))
              .toList() ??
          [];
      return list;
    }
    return [];
  }

  /// Get training session details with roster attendance
  Future<TrainingSessionItem> getTrainingSessionDetails(int sessionId) async {
    final res = await _client.get<TrainingSessionItem>(
      '/training-sessions/$sessionId',
      fromJson: (json) => TrainingSessionItem.fromJson(Map<String, dynamic>.from(json as Map)),
    );

    if (res.success && res.data != null) {
      return res.data!;
    }
    throw Exception(res.message);
  }

  /// Create new training session
  Future<TrainingSessionItem> createTrainingSession({
    required String title,
    required String date,
    required String startTime,
    required String endTime,
    String? objectives,
    String? notes,
    int? venueId,
    int? teamId,
    String? teamName,
    String? venueName,
  }) async {
    final res = await _client.post<Map<String, dynamic>>(
      '/training-sessions',
      body: {
        'title': title,
        'training_date': date,
        'session_date': date,
        'start_time': startTime,
        'end_time': endTime,
        if (objectives != null) 'objectives': objectives,
        if (notes != null) 'notes': notes,
        if (venueId != null) 'venue_id': venueId,
        if (teamId != null) 'team_id': teamId,
        if (teamName != null) 'team_name': teamName,
        if (venueName != null) 'venue_name': venueName,
      },
      fromJson: (json) => Map<String, dynamic>.from(json as Map),
    );

    if (res.success && res.data != null) {
      return TrainingSessionItem.fromJson(res.data!);
    }
    throw Exception(res.message);
  }

  /// Record attendance for an athlete or batch of athletes in a training session
  Future<Map<String, dynamic>> recordTrainingAttendance({
    required int sessionId,
    int? athleteId,
    String? status,
    String? remarks,
    List<Map<String, dynamic>>? attendanceData,
  }) async {
    if (attendanceData != null && attendanceData.isNotEmpty) {
      final formattedList = attendanceData.map((item) {
        return {
          'athlete_id': item['athlete_id'],
          'status': (item['status']?.toString() ?? 'present').toLowerCase(),
          if (item['remarks'] != null) 'remarks': item['remarks'],
        };
      }).toList();

      final res = await _client.post<Map<String, dynamic>>(
        '/attendance/training/$sessionId',
        body: {
          'attendance': formattedList,
        },
        fromJson: (json) => Map<String, dynamic>.from(json as Map),
      );

      if (!res.success) {
        throw Exception(res.message);
      }
      return res.data ?? {'message': res.message};
    } else if (athleteId != null && status != null) {
      final res = await _client.post<Map<String, dynamic>>(
        '/attendance/training/$sessionId',
        body: {
          'attendance': [
            {
              'athlete_id': athleteId,
              'status': status.toLowerCase(),
              if (remarks != null) 'remarks': remarks,
            }
          ],
        },
        fromJson: (json) => Map<String, dynamic>.from(json as Map),
      );
      if (!res.success) {
        throw Exception(res.message);
      }
      return res.data ?? {'message': res.message};
    }
    return {'message': 'No attendance data provided.'};
  }

  /// Get configurable performance metrics
  Future<List<PerformanceMetricItem>> getPerformanceMetrics([int? sportId]) async {
    final endpoint = sportId != null ? '/performance/metrics?sport_id=$sportId' : '/performance/metrics';
    final res = await _client.get<List<dynamic>>(
      endpoint,
      fromJson: (json) => json is List ? json : [],
    );

    if (res.success && res.data != null) {
      return res.data!
          .map((e) => PerformanceMetricItem.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList();
    }
    return [];
  }

  /// Record athlete performance evaluation
  Future<void> recordPerformance({
    required int athleteId,
    int? sportId,
    int? teamId,
    int? trainingSessionId,
    double? overallRating,
    String? evaluationDate,
    String? coachRemarks,
    String? remarks,
    List<Map<String, dynamic>>? values,
    List<Map<String, dynamic>>? metrics,
  }) async {
    final body = {
      'athlete_id': athleteId,
      if (sportId != null) 'sport_id': sportId,
      if (teamId != null) 'team_id': teamId,
      if (trainingSessionId != null) 'training_session_id': trainingSessionId,
      if (overallRating != null) 'overall_rating': overallRating,
      'coach_remarks': coachRemarks ?? remarks ?? '',
      'evaluation_date': evaluationDate ?? DateTime.now().toIso8601String().substring(0, 10),
      if (values != null) 'values': values,
      if (metrics != null) 'metrics': metrics,
    };

    final res = await _client.post('/performance', body: body);
    if (!res.success) {
      throw Exception(res.message);
    }
  }

  /// Get athlete attendance summary for detail screen
  Future<AthleteAttendanceSummary> getAthleteAttendance(int athleteId) async {
    final res = await _client.get<List<dynamic>>(
      '/attendance/training/history?athlete_id=$athleteId',
      fromJson: (json) => json is List ? json : [],
    );

    if (res.success && res.data != null) {
      final records = res.data!
          .map((e) => AttendanceRecordItem.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList();
      return AthleteAttendanceSummary.fromRecords(records);
    }
    return AthleteAttendanceSummary.fromRecords([]);
  }

  /// Get athlete performance history for detail screen
  Future<List<PerformanceRecordItem>> getAthletePerformance(int athleteId) async {
    final res = await _client.get<List<dynamic>>(
      '/performance?athlete_id=$athleteId',
      fromJson: (json) => json is List ? json : [],
    );

    if (res.success && res.data != null) {
      return res.data!
          .map((e) => PerformanceRecordItem.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList();
    }
    return [];
  }

  /// Get athlete achievements for detail screen
  Future<List<AchievementItemModel>> getAthleteAchievements(int athleteId) async {
    final res = await _client.get<List<dynamic>>(
      '/achievements?athlete_id=$athleteId',
      fromJson: (json) => json is List ? json : [],
    );

    if (res.success && res.data != null) {
      return res.data!
          .map((e) => AchievementItemModel.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList();
    }
    return [];
  }
}
