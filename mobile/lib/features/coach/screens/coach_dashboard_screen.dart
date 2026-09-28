import 'package:flutter/material.dart';
import '../../../app/theme/app_theme.dart';
import '../../../core/models/coach_models.dart';
import '../../../core/models/athlete_models.dart';
import '../../../core/widgets/khelsutra_app_bar.dart';
import '../../../core/widgets/section_header.dart';
import '../../../core/widgets/stat_card.dart';
import '../data/coach_repository.dart';
import 'coach_athletes_screen.dart';
import 'coach_select_attendance_session_screen.dart';
import 'coach_create_training_screen.dart';
import 'coach_performance_screen.dart';
import 'coach_profile_screen.dart';
import 'coach_reports_screen.dart';
import 'coach_training_screen.dart';

class CoachDashboardScreen extends StatefulWidget {
  const CoachDashboardScreen({super.key});

  @override
  State<CoachDashboardScreen> createState() => _CoachDashboardScreenState();
}

class _CoachDashboardScreenState extends State<CoachDashboardScreen> {
  int _selectedIndex = 0;
  final CoachRepository _repo = CoachRepository();

  CoachProfile? _profile;
  CoachDashboardData? _dashboardData;
  List<TrainingSessionItem> _trainings = [];
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final profile = await _repo.getProfile();
      final data = await _repo.getDashboard();
      final trainings = await _repo.getTrainingSessions();
      if (!mounted) return;
      setState(() {
        _profile = profile;
        _dashboardData = data;
        _trainings = trainings;
        _isLoading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _errorMessage = e.toString().replaceAll('Exception: ', '');
        _isLoading = false;
      });
    }
  }

  void _openCreateTraining() async {
    final res = await Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => const CoachCreateTrainingScreen()),
    );
    if (res == true) {
      _loadData();
    }
  }

  void _openAttendance() async {
    final res = await Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => const CoachSelectAttendanceSessionScreen()),
    );
    if (res == true) {
      _loadData();
    }
  }

  void _openPerformance() async {
    final res = await Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => const CoachPerformanceScreen()),
    );
    if (res == true) {
      _loadData();
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        body: Center(
          child: CircularProgressIndicator(),
        ),
      );
    }

    if (_errorMessage != null) {
      return Scaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.error_outline, size: 48, color: AppTheme.dangerColor),
                const SizedBox(height: 16),
                Text(
                  _errorMessage!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppTheme.textSecondary),
                ),
                const SizedBox(height: 16),
                ElevatedButton.icon(
                  onPressed: _loadData,
                  icon: const Icon(Icons.refresh),
                  label: const Text('Retry'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    final coachName = _profile?.fullName ?? 'Coach';
    final coachCode = _profile?.coachCode ?? 'Coach';
    final lowAttendanceCount = _dashboardData?.lowAttendanceAthletes.length ?? 0;

    final List<Widget> pages = [
      _buildHomeDashboard(context),
      const CoachAthletesScreen(isStandalone: false),
      const CoachTrainingScreen(isStandalone: false),
      const CoachReportsScreen(isStandalone: false),
      const CoachProfileScreen(),
    ];

    final List<String> titles = [
      'Coach Dashboard',
      'My Athletes',
      'Training Operations',
      'Reports & Analytics',
      'Coach Profile',
    ];

    final List<String> subtitles = [
      'Welcome, ${coachName.split(' ').first}',
      'Manage & Monitor Squad',
      'Schedules & Field Drills',
      'Performance Overview',
      coachCode,
    ];

    return Scaffold(
      appBar: KhelSutraAppBar(
        title: titles[_selectedIndex],
        subtitle: subtitles[_selectedIndex],
        unreadNotifications: lowAttendanceCount > 0 ? 1 : 0,
        onNotificationTap: () {
          setState(() => _selectedIndex = 1); // Switch to Athletes
        },
      ),
      body: IndexedStack(
        index: _selectedIndex,
        children: pages,
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _selectedIndex,
        onDestinationSelected: (idx) => setState(() => _selectedIndex = idx),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home),
            label: 'Home',
          ),
          NavigationDestination(
            icon: Icon(Icons.groups_outlined),
            selectedIcon: Icon(Icons.groups),
            label: 'Athletes',
          ),
          NavigationDestination(
            icon: Icon(Icons.fitness_center_outlined),
            selectedIcon: Icon(Icons.fitness_center),
            label: 'Training',
          ),
          NavigationDestination(
            icon: Icon(Icons.analytics_outlined),
            selectedIcon: Icon(Icons.analytics),
            label: 'Reports',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: 'Profile',
          ),
        ],
      ),
    );
  }

  Widget _buildHomeDashboard(BuildContext context) {
    final todayTrainings = _trainings.where((t) => t.isToday).toList();
    final todaySession = todayTrainings.isNotEmpty ? todayTrainings.first : null;
    final presentToday = _dashboardData?.presentToday ?? 0;
    final absentToday = _dashboardData?.absentToday ?? 0;
    final athletesCount = _dashboardData?.athletesCount ?? 0;
    final lowAttendanceCount = _dashboardData?.lowAttendanceAthletes.length ?? 0;
    final coachName = _profile?.fullName ?? 'Coach';
    final coachInitials = coachName.split(' ').map((n) => n.isNotEmpty ? n[0] : '').take(2).join().toUpperCase();
    final designation = _profile?.designation ?? 'Coach';
    final teamName = _profile?.assignedTeams.isNotEmpty == true
        ? _profile!.assignedTeams.first.teamName
        : 'Assigned Squad';

    return RefreshIndicator(
      onRefresh: _loadData,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Coach Profile Hero Card
            Container(
              width: double.infinity,
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF1E3A8A), Color(0xFF1E40AF)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF1E3A8A).withValues(alpha: 0.25),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Material(
                color: Colors.transparent,
                child: InkWell(
                  onTap: () => setState(() => _selectedIndex = 4),
                  borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
                  child: Padding(
                    padding: const EdgeInsets.all(18.0),
                    child: Row(
                      children: [
                        CircleAvatar(
                          radius: 28,
                          backgroundColor: Colors.white.withValues(alpha: 0.2),
                          child: Text(
                            coachInitials.isNotEmpty ? coachInitials : 'CH',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                coachName,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                '$designation • KhelSutra',
                                style: TextStyle(
                                  color: Colors.white.withValues(alpha: 0.85),
                                  fontSize: 12,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                '$teamName • $athletesCount Athletes Supervised',
                                style: TextStyle(
                                  color: Colors.white.withValues(alpha: 0.9),
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                        ),
                        Icon(
                          Icons.arrow_forward_ios,
                          size: 16,
                          color: Colors.white.withValues(alpha: 0.7),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Active Athletes Summary Card
            Row(
              children: [
                Expanded(
                  child: StatCard(
                    title: 'Active Athletes',
                    value: '$athletesCount',
                    subtitle: teamName,
                    icon: Icons.groups,
                    color: AppTheme.primaryLight,
                    onTap: () => setState(() => _selectedIndex = 1),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: StatCard(
                    title: "Today's Present",
                    value: '$presentToday',
                    subtitle: '$absentToday absent from drill',
                    icon: Icons.how_to_reg,
                    color: AppTheme.successColor,
                    onTap: _openAttendance,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),

            // Quick Action Buttons
            const SectionHeader(
              title: 'Quick Operations',
              subtitle: 'Direct management tools',
            ),
            const SizedBox(height: 8),

            Row(
              children: [
                Expanded(
                  child: _buildQuickActionButton(
                    icon: Icons.add_circle_outline,
                    label: '+ Training',
                    color: AppTheme.primaryColor,
                    onTap: _openCreateTraining,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _buildQuickActionButton(
                    icon: Icons.how_to_reg,
                    label: 'Attendance',
                    color: AppTheme.successColor,
                    onTap: _openAttendance,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _buildQuickActionButton(
                    icon: Icons.groups,
                    label: 'Athletes',
                    color: const Color(0xFF7C3AED),
                    onTap: () => setState(() => _selectedIndex = 1),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _buildQuickActionButton(
                    icon: Icons.speed,
                    label: 'Performance',
                    color: AppTheme.accentColor,
                    onTap: _openPerformance,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),

            // Today's Training Section
            SectionHeader(
              title: "Today's Training Session",
              subtitle: 'Scheduled squad workout',
              actionText: 'Manage All',
              onActionTap: () => setState(() => _selectedIndex = 2),
            ),
            const SizedBox(height: 8),

            if (todaySession != null)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: AppTheme.primaryColor.withValues(alpha: 0.1),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              todaySession.teamName,
                              style: const TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                color: AppTheme.primaryColor,
                              ),
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: AppTheme.successColor.withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              '$athletesCount Athletes Assigned',
                              style: const TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                color: AppTheme.successColor,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Text(
                        todaySession.title,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: AppTheme.textColor,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          const Icon(Icons.access_time, size: 14, color: AppTheme.textSecondary),
                          const SizedBox(width: 4),
                          Text(
                            '${todaySession.startTime} - ${todaySession.endTime}',
                            style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                          ),
                          const SizedBox(width: 12),
                          const Icon(Icons.location_on_outlined, size: 14, color: AppTheme.textSecondary),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              todaySession.venue,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),
                      const Divider(height: 1),
                      const SizedBox(height: 12),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Row(
                              children: [
                                const Icon(Icons.fact_check, size: 16, color: AppTheme.textMuted),
                                const SizedBox(width: 6),
                                Flexible(
                                  child: Text(
                                    'Present: $presentToday  •  Absent: $absentToday',
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w600,
                                      color: AppTheme.textColor,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 8),
                          ElevatedButton(
                            onPressed: _openAttendance,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppTheme.primaryColor,
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                            ),
                            child: const Text('Mark Attendance', style: TextStyle(fontSize: 12)),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
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
                        child: Text('No training scheduled for today.'),
                      ),
                    ],
                  ),
                ),
              ),
            const SizedBox(height: 20),

            // Action Alerts
            const SectionHeader(
              title: 'Action Alerts',
              subtitle: 'Requires coach intervention',
            ),
            const SizedBox(height: 8),

            if (lowAttendanceCount > 0)
              Card(
                color: const Color(0xFFFFFBEB),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
                  side: const BorderSide(color: Color(0xFFFDE68A)),
                ),
                child: ListTile(
                  leading: Container(
                    padding: const EdgeInsets.all(8),
                    decoration: const BoxDecoration(
                      color: Color(0xFFF59E0B),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.warning_amber_rounded, color: Colors.white, size: 20),
                  ),
                  title: Text(
                    '$lowAttendanceCount athlete(s) have low attendance (<80%)',
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF92400E),
                    ),
                  ),
                  subtitle: Text(
                    _dashboardData!.lowAttendanceAthletes.map((a) => '${a.fullName} (${a.attendanceRate.toInt()}%)').join(', '),
                    style: const TextStyle(fontSize: 12, color: Color(0xFFB45309)),
                  ),
                  trailing: const Icon(Icons.arrow_forward_ios, size: 14, color: Color(0xFF92400E)),
                  onTap: () {
                    setState(() => _selectedIndex = 1); // Switch to Athletes
                  },
                ),
              )
            else
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(14.0),
                  child: Row(
                    children: const [
                      Icon(Icons.thumb_up_alt_outlined, color: AppTheme.successColor),
                      SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          'All athletes maintain healthy attendance targets (>80%).',
                          style: TextStyle(fontSize: 13),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  Widget _buildQuickActionButton({
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback onTap,
  }) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppTheme.radiusSmall),
        side: const BorderSide(color: AppTheme.borderColor),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppTheme.radiusSmall),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 14.0, horizontal: 4.0),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, color: color, size: 20),
              ),
              const SizedBox(height: 8),
              Text(
                label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  color: AppTheme.textColor,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
