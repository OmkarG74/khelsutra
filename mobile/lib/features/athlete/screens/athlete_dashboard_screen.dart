import 'package:flutter/material.dart';
import '../../../app/theme/app_theme.dart';
import '../../../core/mock/mock_data.dart';
import '../../../core/models/athlete_models.dart';
import '../../../core/widgets/khelsutra_app_bar.dart';
import '../../../core/widgets/section_header.dart';
import '../../../core/widgets/stat_card.dart';
import '../data/athlete_repository.dart';
import '../widgets/athlete_summary_card.dart';
import '../widgets/training_card.dart';
import 'athlete_achievements_screen.dart';
import 'athlete_attendance_screen.dart';
import 'athlete_notifications_screen.dart';
import 'athlete_performance_screen.dart';
import 'athlete_profile_screen.dart';
import 'athlete_training_details_screen.dart';
import 'athlete_training_screen.dart';

class AthleteDashboardScreen extends StatefulWidget {
  const AthleteDashboardScreen({super.key});

  @override
  State<AthleteDashboardScreen> createState() => _AthleteDashboardScreenState();
}

class _AthleteDashboardScreenState extends State<AthleteDashboardScreen> {
  int _selectedIndex = 0;
  final AthleteRepository _repo = AthleteRepository();

  bool _isLoading = true;
  String? _errorMessage;

  AthleteProfile? _profile;
  AthleteAttendanceSummary? _attendance;
  List<TrainingSessionItem> _trainings = [];
  List<PerformanceRecordItem> _performances = [];
  List<NotificationItemModel> _notifications = [];
  List<AchievementItemModel> _achievements = [];

  @override
  void initState() {
    super.initState();
    _loadDashboardData();
  }

  Future<void> _loadDashboardData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final profileFuture = _repo.getProfile();
      final attFuture = _repo.getAttendance();
      final trainFuture = _repo.getTrainingSessions();
      final perfFuture = _repo.getPerformance();
      final notifFuture = _repo.getNotifications();
      final achFuture = _repo.getAchievements();

      final results = await Future.wait([
        profileFuture,
        attFuture,
        trainFuture,
        perfFuture,
        notifFuture,
        achFuture,
      ]);

      if (mounted) {
        setState(() {
          _profile = results[0] as AthleteProfile;
          _attendance = results[1] as AthleteAttendanceSummary;
          _trainings = results[2] as List<TrainingSessionItem>;
          _performances = results[3] as List<PerformanceRecordItem>;
          _notifications = results[4] as List<NotificationItemModel>;
          _achievements = results[5] as List<AchievementItemModel>;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = e.toString().replaceAll('Exception:', '').trim();
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    if (_errorMessage != null && _profile == null) {
      return Scaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.error_outline, size: 54, color: AppTheme.dangerColor),
                const SizedBox(height: 16),
                Text(
                  _errorMessage!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 15, color: AppTheme.textMuted),
                ),
                const SizedBox(height: 16),
                ElevatedButton.icon(
                  onPressed: _loadDashboardData,
                  icon: const Icon(Icons.refresh),
                  label: const Text('Retry'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    final unreadCount = _notifications.where((n) => !n.isRead).length;

    final List<Widget> pages = [
      _buildHomeDashboard(context),
      const AthleteTrainingScreen(isStandalone: false),
      const AthletePerformanceScreen(isStandalone: false),
      const AthleteNotificationsScreen(isStandalone: false),
      const AthleteProfileScreen(),
    ];

    final List<String> titles = [
      'Athlete Dashboard',
      'My Training',
      'Performance Analytics',
      'Notifications',
      'Athlete Profile',
    ];

    final firstName = _profile?.firstName ?? 'Athlete';
    final List<String> subtitles = [
      'Good Morning, $firstName',
      'Active Sessions & Schedules',
      'Physical & Athletic Assessments',
      'Team & Event Updates',
      _profile?.athleteCode ?? 'Profile Details',
    ];

    return Scaffold(
      appBar: KhelSutraAppBar(
        title: titles[_selectedIndex],
        subtitle: subtitles[_selectedIndex],
        unreadNotifications: unreadCount,
        onNotificationTap: () {
          setState(() {
            _selectedIndex = 3; // Switch to Notifications tab
          });
        },
      ),
      body: IndexedStack(
        index: _selectedIndex,
        children: pages,
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _selectedIndex,
        onDestinationSelected: (idx) => setState(() => _selectedIndex = idx),
        destinations: [
          const NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home),
            label: 'Home',
          ),
          const NavigationDestination(
            icon: Icon(Icons.fitness_center_outlined),
            selectedIcon: Icon(Icons.fitness_center),
            label: 'Training',
          ),
          const NavigationDestination(
            icon: Icon(Icons.insights_outlined),
            selectedIcon: Icon(Icons.insights),
            label: 'Performance',
          ),
          NavigationDestination(
            icon: Stack(
              children: [
                const Icon(Icons.notifications_outlined),
                if (unreadCount > 0)
                  Positioned(
                    right: 0,
                    top: 0,
                    child: Container(
                      width: 8,
                      height: 8,
                      decoration: const BoxDecoration(
                        color: AppTheme.accentColor,
                        shape: BoxShape.circle,
                      ),
                    ),
                  ),
              ],
            ),
            selectedIcon: const Icon(Icons.notifications),
            label: 'Notifications',
          ),
          const NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: 'Profile',
          ),
        ],
      ),
    );
  }

  Widget _buildHomeDashboard(BuildContext context) {
    final athlete = _profile;
    if (athlete == null) {
      return const Center(child: CircularProgressIndicator());
    }
    final todayTrainings = _trainings.where((t) => t.isToday).toList();
    final todaySessionItem = todayTrainings.isNotEmpty
        ? todayTrainings.first
        : (_trainings.isNotEmpty ? _trainings.first : null);

    final todaySession = todaySessionItem != null ? TrainingSession.fromItem(todaySessionItem) : null;
    final notificationsPreview = _notifications.take(2).toList();

    final att = _attendance;
    final attString = (att != null && att.totalSessions > 0)
        ? '${att.overallPercentage}%'
        : '0%';
    final attSubtitle = (att != null && att.totalSessions > 0)
        ? '${att.presentCount}/${att.totalSessions} sessions'
        : 'No attendance data available';

    final hasPerf = _performances.isNotEmpty;
    final latestPerf = hasPerf ? _performances.first : null;
    final perfString = latestPerf?.overallRating != null
        ? '${latestPerf!.overallRating}/10'
        : (hasPerf ? 'Rated' : 'N/A');
    final perfSubtitle = hasPerf
        ? (latestPerf?.values.isNotEmpty == true ? latestPerf!.values.first.metricName : latestPerf!.sportName)
        : 'No evaluation data';

    return RefreshIndicator(
      onRefresh: _loadDashboardData,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Athlete Profile Summary Card
            AthleteSummaryCard(
              name: athlete.fullName,
              sport: athlete.sportName,
              team: athlete.teamName,
              coach: _trainings.isNotEmpty ? _trainings.first.coachName : 'Assigned Coach',
              onTap: () {
                setState(() => _selectedIndex = 4); // Switch to Profile
              },
            ),
            const SizedBox(height: 20),

            // Attendance & Performance Quick Stats Row
            Row(
              children: [
                Expanded(
                  child: StatCard(
                    title: 'Attendance',
                    value: attString,
                    subtitle: attSubtitle,
                    icon: Icons.how_to_reg,
                    color: AppTheme.successColor,
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => const AthleteAttendanceScreen(isStandalone: true),
                        ),
                      );
                    },
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: StatCard(
                    title: 'Performance',
                    value: perfString,
                    subtitle: perfSubtitle,
                    icon: Icons.speed,
                    color: AppTheme.primaryLight,
                    onTap: () {
                      setState(() => _selectedIndex = 2); // Switch to Performance
                    },
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),

            // Today's / Upcoming Training Section
            SectionHeader(
              title: todayTrainings.isNotEmpty ? "Today's Training" : "Upcoming Training",
              subtitle: todayTrainings.isNotEmpty ? 'Mandatory on-field session' : 'Scheduled team session',
              actionText: 'View All (${_trainings.length})',
              onActionTap: () {
                setState(() => _selectedIndex = 1); // Switch to Training tab
              },
            ),
            const SizedBox(height: 8),

            if (todaySession != null)
              TrainingCard(
                session: todaySession,
                showDate: true,
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => AthleteTrainingDetailsScreen(session: todaySession),
                    ),
                  );
                },
              )
            else
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Row(
                    children: const [
                      Icon(Icons.check_circle_outline, color: AppTheme.successColor),
                      SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          'No training sessions scheduled. Rest and recover!',
                          style: TextStyle(fontSize: 13, color: AppTheme.textMuted),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            const SizedBox(height: 16),

            // Performance Summary Section
            SectionHeader(
              title: 'Performance Benchmarks',
              subtitle: latestPerf != null ? 'Evaluated on ${latestPerf.evaluationDate}' : 'Physical test parameters',
              actionText: 'Analytics',
              onActionTap: () {
                setState(() => _selectedIndex = 2);
              },
            ),
            const SizedBox(height: 8),

            Card(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: latestPerf != null && latestPerf.values.isNotEmpty
                    ? Column(
                        children: latestPerf.values.map((v) {
                          final label = '${v.metricName} (${v.unit ?? ''})'.trim();
                          final valStr = v.displayValue;
                          final progress = v.numericValue != null
                              ? ((v.numericValue! > 100 ? (v.numericValue! / 400) : (v.numericValue! / 100))).clamp(0.0, 1.0)
                              : 0.75;
                          return Padding(
                            padding: const EdgeInsets.only(bottom: 12.0),
                            child: _buildDashboardMetricRow(
                              label: label,
                              value: valStr,
                              progress: progress,
                              color: const Color(0xFF2563EB),
                            ),
                          );
                        }).toList(),
                      )
                    : const Padding(
                        padding: EdgeInsets.symmetric(vertical: 8.0),
                        child: Text(
                          'No performance metrics evaluated yet for this athlete.',
                          style: TextStyle(fontSize: 13, color: AppTheme.textMuted),
                        ),
                      ),
              ),
            ),
            const SizedBox(height: 20),

            // Quick Achievements & Honors link
            Card(
              child: ListTile(
                leading: const Icon(Icons.military_tech, color: Color(0xFFD97706), size: 28),
                title: const Text(
                  'My Achievements & Medals',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                ),
                subtitle: Text(
                  _achievements.isNotEmpty
                      ? '${_achievements.length} verified competition title${_achievements.length == 1 ? '' : 's'}'
                      : 'Verified competition titles and honors',
                  style: const TextStyle(fontSize: 12),
                ),
                trailing: const Icon(Icons.arrow_forward_ios, size: 14, color: AppTheme.textMuted),
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => const AthleteAchievementsScreen(isStandalone: true),
                    ),
                  );
                },
              ),
            ),
            const SizedBox(height: 20),

            // Recent Notifications Preview
            SectionHeader(
              title: 'Recent Notifications',
              actionText: 'View All',
              onActionTap: () {
                setState(() => _selectedIndex = 3);
              },
            ),
            const SizedBox(height: 8),

            if (notificationsPreview.isNotEmpty)
              ...notificationsPreview.map((notif) {
                return Card(
                  margin: const EdgeInsets.only(bottom: 8),
                  child: ListTile(
                    leading: const Icon(Icons.notifications_active_outlined, color: AppTheme.primaryColor, size: 22),
                    title: Text(
                      notif.title,
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                    ),
                    subtitle: Text(
                      notif.message,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 12),
                    ),
                    trailing: Text(
                      notif.createdAt.length >= 10 ? notif.createdAt.substring(0, 10) : notif.createdAt,
                      style: const TextStyle(fontSize: 11, color: AppTheme.textMuted),
                    ),
                    onTap: () {
                      setState(() => _selectedIndex = 3);
                    },
                  ),
                );
              })
            else
              const Card(
                child: Padding(
                  padding: EdgeInsets.all(16.0),
                  child: Text(
                    'No notifications at this time.',
                    style: TextStyle(fontSize: 13, color: AppTheme.textMuted),
                  ),
                ),
              ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Widget _buildDashboardMetricRow({
    required String label,
    required String value,
    required double progress,
    required Color color,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Expanded(
              child: Text(
                label,
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: AppTheme.textColor,
                ),
              ),
            ),
            Text(
              value,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
          ],
        ),
        const SizedBox(height: 6),
        ClipRRect(
          borderRadius: BorderRadius.circular(4),
          child: LinearProgressIndicator(
            value: progress,
            minHeight: 6,
            backgroundColor: AppTheme.borderColor,
            valueColor: AlwaysStoppedAnimation<Color>(color),
          ),
        ),
      ],
    );
  }
}
