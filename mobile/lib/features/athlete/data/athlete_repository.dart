import '../../../core/models/athlete_models.dart';
import '../../../core/network/api_client.dart';
import '../../../core/storage/token_storage.dart';

class AthleteRepository {
  final ApiClient _client;

  AthleteRepository({ApiClient? client}) : _client = client ?? ApiClient();

  Future<int?> _resolveAthleteId(int? id) async {
    if (id != null) return id;
    final stored = await TokenStorage.getAthleteId();
    return stored;
  }

  /// Get athlete profile
  Future<AthleteProfile> getProfile({int? athleteId}) async {
    final resolvedId = await _resolveAthleteId(athleteId);
    if (resolvedId == null) {
      throw Exception('No athlete profile associated with this account');
    }

    final res = await _client.get<AthleteProfile>(
      '/athletes/$resolvedId',
      fromJson: (json) => AthleteProfile.fromJson(Map<String, dynamic>.from(json as Map)),
    );

    if (res.success && res.data != null) {
      return res.data!;
    }
    throw Exception(res.message);
  }

  /// Get athlete training sessions
  Future<List<TrainingSessionItem>> getTrainingSessions({int? athleteId, String? status}) async {
    final resolvedId = await _resolveAthleteId(athleteId);
    var endpoint = '/training-sessions';
    final queryParams = <String>[];
    if (resolvedId != null) queryParams.add('athlete_id=$resolvedId');
    if (status != null && status.isNotEmpty) queryParams.add('status=$status');
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

  /// Get single training session details
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

  /// Get athlete attendance records and calculated summary
  Future<AthleteAttendanceSummary> getAttendance({int? athleteId}) async {
    final resolvedId = await _resolveAthleteId(athleteId);
    final endpoint = resolvedId != null
        ? '/attendance/training/history?athlete_id=$resolvedId'
        : '/attendance/training/history';

    final res = await _client.get<List<dynamic>>(
      endpoint,
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

  /// Get athlete performance evaluations with dynamic metrics
  Future<List<PerformanceRecordItem>> getPerformance({int? athleteId}) async {
    final resolvedId = await _resolveAthleteId(athleteId);
    final endpoint = resolvedId != null
        ? '/performance?athlete_id=$resolvedId'
        : '/performance';

    final res = await _client.get<List<dynamic>>(
      endpoint,
      fromJson: (json) => json is List ? json : [],
    );

    if (res.success && res.data != null) {
      return res.data!
          .map((e) => PerformanceRecordItem.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList();
    }
    return [];
  }

  /// Get athlete achievements
  Future<List<AchievementItemModel>> getAchievements({int? athleteId}) async {
    final resolvedId = await _resolveAthleteId(athleteId);
    final endpoint = resolvedId != null
        ? '/achievements?athlete_id=$resolvedId'
        : '/achievements';

    final res = await _client.get<List<dynamic>>(
      endpoint,
      fromJson: (json) => json is List ? json : [],
    );

    if (res.success && res.data != null) {
      return res.data!
          .map((e) => AchievementItemModel.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList();
    }
    return [];
  }

  /// Get notifications for logged in user
  Future<List<NotificationItemModel>> getNotifications() async {
    final res = await _client.get<List<dynamic>>(
      '/notifications',
      fromJson: (json) => json is List ? json : [],
    );

    if (res.success && res.data != null) {
      return res.data!
          .map((e) => NotificationItemModel.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList();
    }
    return [];
  }

  /// Mark notification as read
  Future<void> markNotificationAsRead(int notificationId) async {
    try {
      await _client.post('/notifications/$notificationId/read');
    } catch (_) {}
  }

  Future<void> markNotificationRead(int notificationId) => markNotificationAsRead(notificationId);
}
