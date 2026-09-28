import 'package:flutter/material.dart';
import '../../../app/theme/app_theme.dart';
import '../../../core/mock/mock_data.dart';
import '../../../core/models/athlete_models.dart';
import '../../../core/widgets/common_widgets.dart';
import '../../../core/widgets/khelsutra_app_bar.dart';
import '../../../core/widgets/section_header.dart';
import '../../../core/widgets/stat_card.dart';
import '../data/coach_repository.dart';
import 'coach_attendance_screen.dart';
import 'coach_performance_screen.dart';

class CoachAthleteDetailsScreen extends StatefulWidget {
  final CoachAthleteItem athlete;

  const CoachAthleteDetailsScreen({
    super.key,
    required this.athlete,
  });

  @override
  State<CoachAthleteDetailsScreen> createState() =>
      _CoachAthleteDetailsScreenState();
}

class _CoachAthleteDetailsScreenState extends State<CoachAthleteDetailsScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final CoachRepository _repo = CoachRepository();

  bool _isLoading = true;
  String? _errorMessage;

  AthleteProfile? _profile;
  AthleteAttendanceSummary? _attendance;
  List<PerformanceRecordItem> _performanceRecords = [];
  List<AchievementItemModel> _achievements = [];
  List<TrainingSessionItem> _trainings = [];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 6, vsync: this);
    _loadAthleteDetails();
  }

  Future<void> _loadAthleteDetails() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final athleteId = int.tryParse(widget.athlete.id) ?? 1;
      final details = await _repo.getAthleteDetails(athleteId);
      if (!mounted) return;
      setState(() {
        _profile = details['profile'] as AthleteProfile?;
        _attendance = details['attendance'] as AthleteAttendanceSummary?;
        _performanceRecords = (details['performance'] as List<PerformanceRecordItem>?) ?? [];
        _achievements = (details['achievements'] as List<AchievementItemModel>?) ?? [];
        _trainings = (details['trainings'] as List<TrainingSessionItem>?) ?? [];
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

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final athlete = widget.athlete;
    final initials = athlete.name.split(' ').map((n) => n.isNotEmpty ? n[0] : '').take(2).join().toUpperCase();

    if (_isLoading) {
      return Scaffold(
        appBar: KhelSutraAppBar(
          title: athlete.name,
          subtitle: '${athlete.jerseyNo} • ${athlete.event}',
          showBackButton: true,
        ),
        body: const Center(
          child: CircularProgressIndicator(),
        ),
      );
    }

    if (_errorMessage != null) {
      return Scaffold(
        appBar: KhelSutraAppBar(
          title: athlete.name,
          subtitle: '${athlete.jerseyNo} • ${athlete.event}',
          showBackButton: true,
        ),
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
                  onPressed: _loadAthleteDetails,
                  icon: const Icon(Icons.refresh),
                  label: const Text('Retry'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    final attendancePct = _attendance?.percentage ?? 0.0;
    final assessments = <AssessmentRecord>[];
    for (var r in _performanceRecords) {
      for (var v in r.values) {
        assessments.add(AssessmentRecord.fromRecordAndValue(r, v));
      }
    }

    return Scaffold(
      appBar: KhelSutraAppBar(
        title: _profile?.fullName ?? athlete.name,
        subtitle: '${_profile?.athleteCode ?? athlete.jerseyNo} • ${_profile?.sportName ?? athlete.sport}',
        showBackButton: true,
      ),
      body: NestedScrollView(
        headerSliverBuilder: (context, innerBoxIsScrolled) {
          return [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  children: [
                    // Profile Overview Card
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Column(
                          children: [
                            Row(
                              children: [
                                CircleAvatar(
                                  radius: 30,
                                  backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.1),
                                  child: Text(
                                    initials.isNotEmpty ? initials : 'AT',
                                    style: const TextStyle(
                                      fontSize: 18,
                                      fontWeight: FontWeight.bold,
                                      color: AppTheme.primaryColor,
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 14),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        _profile?.fullName ?? athlete.name,
                                        style: const TextStyle(
                                          fontSize: 18,
                                          fontWeight: FontWeight.bold,
                                          color: AppTheme.textColor,
                                        ),
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        '${_profile?.sportName ?? athlete.sport} • ${_profile?.categoryName ?? "Elite"}',
                                        style: const TextStyle(
                                          fontSize: 13,
                                          color: AppTheme.textSecondary,
                                        ),
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        'Team: ${_profile?.teamName ?? athlete.team}',
                                        style: const TextStyle(
                                          fontSize: 12,
                                          fontWeight: FontWeight.w600,
                                          color: AppTheme.primaryColor,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 16),
                            const Divider(height: 1),
                            const SizedBox(height: 14),
                            // Quick Stats
                            Row(
                              children: [
                                Expanded(
                                  child: StatCard(
                                    title: 'Attendance',
                                    value: _attendance != null && _attendance!.totalSessions > 0
                                        ? '${attendancePct.toStringAsFixed(1)}%'
                                        : 'N/A',
                                    subtitle: attendancePct >= 80 ? 'Target met' : 'Target: 80%',
                                    icon: Icons.how_to_reg,
                                    color: attendancePct >= 80
                                        ? AppTheme.successColor
                                        : AppTheme.warningColor,
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: StatCard(
                                    title: 'Assessments',
                                    value: '${assessments.length}',
                                    subtitle: 'Evaluations logged',
                                    icon: Icons.speed,
                                    color: AppTheme.primaryLight,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 14),
                            // Action Buttons
                            Row(
                              children: [
                                Expanded(
                                  child: ElevatedButton.icon(
                                    onPressed: () {
                                      Navigator.of(context).push(
                                        MaterialPageRoute(
                                          builder: (_) => CoachPerformanceScreen(
                                            initialAthleteId: athlete.id,
                                          ),
                                        ),
                                      );
                                    },
                                    icon: const Icon(Icons.add_chart, size: 16),
                                    label: const Text('Add Performance'),
                                  ),
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: OutlinedButton.icon(
                                    onPressed: () {
                                      Navigator.of(context).push(
                                        MaterialPageRoute(
                                          builder: (_) => CoachAttendanceScreen(
                                            sessionTitle: 'Attendance: ${athlete.name}',
                                          ),
                                        ),
                                      );
                                    },
                                    icon: const Icon(Icons.fact_check_outlined, size: 16),
                                    label: const Text('View Attendance'),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            SliverPersistentHeader(
              pinned: true,
              delegate: _SliverAppBarDelegate(
                TabBar(
                  controller: _tabController,
                  isScrollable: true,
                  labelColor: AppTheme.primaryColor,
                  unselectedLabelColor: AppTheme.textSecondary,
                  indicatorColor: AppTheme.primaryColor,
                  indicatorWeight: 3,
                  labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                  unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w500, fontSize: 13),
                  tabs: const [
                    Tab(text: 'Training History'),
                    Tab(text: 'Attendance'),
                    Tab(text: 'Performance'),
                    Tab(text: 'Medical & Info'),
                    Tab(text: 'Achievements'),
                    Tab(text: 'Documents'),
                  ],
                ),
              ),
            ),
          ];
        },
        body: TabBarView(
          controller: _tabController,
          children: [
            _buildTrainingHistoryTab(),
            _buildAttendanceTab(),
            _buildPerformanceTab(assessments),
            _buildMedicalTab(),
            _buildAchievementsTab(),
            _buildDocumentsTab(),
          ],
        ),
      ),
    );
  }

  Widget _buildTrainingHistoryTab() {
    if (_trainings.isEmpty) {
      return const Center(
        child: EmptyState(
          icon: Icons.fitness_center_outlined,
          title: 'No Training Sessions',
          description: 'No training drills recorded for this squad.',
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _trainings.length,
      itemBuilder: (context, index) {
        final t = _trainings[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 10),
          child: ListTile(
            leading: const Icon(Icons.fitness_center, color: AppTheme.primaryColor),
            title: Text(t.title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
            subtitle: Text('${t.date} • ${t.venue}'),
            trailing: Text(
              t.status.isNotEmpty ? t.status : 'Scheduled',
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 12,
                color: AppTheme.primaryColor,
              ),
            ),
          ),
        );
      },
    );
  }

  Widget _buildAttendanceTab() {
    final records = _attendance?.records ?? [];
    if (records.isEmpty) {
      return const Center(
        child: EmptyState(
          icon: Icons.how_to_reg,
          title: 'No Attendance Records',
          description: 'No attendance records logged for this athlete.',
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: records.length,
      itemBuilder: (context, index) {
        final r = records[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 8),
          child: ListTile(
            leading: Icon(
              r.isPresent ? Icons.check_circle : Icons.cancel,
              color: r.isPresent ? AppTheme.successColor : AppTheme.dangerColor,
            ),
            title: Text(r.sessionTitle, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            subtitle: Text(r.sessionDate),
            trailing: Text(
              r.status.toUpperCase(),
              style: TextStyle(
                fontWeight: FontWeight.bold,
                color: r.isPresent ? AppTheme.successColor : AppTheme.dangerColor,
              ),
            ),
          ),
        );
      },
    );
  }

  Widget _buildPerformanceTab(List<AssessmentRecord> assessments) {
    if (assessments.isEmpty) {
      return const Center(
        child: EmptyState(
          icon: Icons.trending_up,
          title: 'No Performance Records',
          description: 'No physical evaluations logged for this athlete yet.',
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: assessments.length,
      itemBuilder: (context, index) {
        final a = assessments[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 10),
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(a.testName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    Text('${a.score} ${a.unit}',
                        style: const TextStyle(
                            fontWeight: FontWeight.bold, fontSize: 14, color: AppTheme.primaryColor)),
                  ],
                ),
                const SizedBox(height: 4),
                Text('${a.date} • ${a.category}',
                    style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary)),
                if (a.notes.isNotEmpty) ...[
                  const SizedBox(height: 6),
                  Text(a.notes, style: const TextStyle(fontSize: 12, color: AppTheme.textColor)),
                ],
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildMedicalTab() {
    final p = _profile;
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SectionHeader(title: 'Medical Fitness & Profile'),
              const SizedBox(height: 12),
              Text('Blood Group: ${p?.bloodGroup ?? "Not Disclosed"}', style: const TextStyle(fontSize: 13)),
              const SizedBox(height: 6),
              Text('Weight: ${p?.weightKg != null ? "${p!.weightKg} kg" : "Not recorded"}', style: const TextStyle(fontSize: 13)),
              const SizedBox(height: 6),
              Text('Nationality: ${p?.nationality ?? "India"}', style: const TextStyle(fontSize: 13)),
              const SizedBox(height: 6),
              Text('Status: ${p?.status.toUpperCase() ?? "ACTIVE"}',
                  style: const TextStyle(fontSize: 13, color: AppTheme.successColor, fontWeight: FontWeight.bold)),
              const SizedBox(height: 10),
              const Text(
                'Note: Sensitive clinical records and medical history are protected under athlete privacy permissions.',
                style: TextStyle(fontSize: 11, color: AppTheme.textMuted, fontStyle: FontStyle.italic),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildAchievementsTab() {
    if (_achievements.isEmpty) {
      return const Center(
        child: EmptyState(
          icon: Icons.emoji_events_outlined,
          title: 'No Achievements',
          description: 'No honors or tournament medals logged for this athlete yet.',
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _achievements.length,
      itemBuilder: (context, index) {
        final ach = _achievements[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 10),
          child: ListTile(
            leading: const Icon(Icons.emoji_events, color: Color(0xFFD97706)),
            title: Text(ach.title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            subtitle: Text('${ach.tournamentName ?? ach.type} • ${ach.date}'),
          ),
        );
      },
    );
  }

  Widget _buildDocumentsTab() {
    return const Padding(
      padding: EdgeInsets.all(16),
      child: Column(
        children: [
          Card(
            child: ListTile(
              leading: Icon(Icons.badge, color: AppTheme.primaryColor),
              title: Text('Official Sports Federation Card'),
              subtitle: Text('Registered under KhelSutra Organization'),
              trailing: Icon(Icons.verified, color: AppTheme.successColor, size: 20),
            ),
          ),
        ],
      ),
    );
  }
}

class _SliverAppBarDelegate extends SliverPersistentHeaderDelegate {
  final TabBar _tabBar;

  _SliverAppBarDelegate(this._tabBar);

  @override
  double get minExtent => _tabBar.preferredSize.height;
  @override
  double get maxExtent => _tabBar.preferredSize.height;

  @override
  Widget build(BuildContext context, double shrinkOffset, bool overlapsContent) {
    return Container(
      color: Colors.white,
      child: _tabBar,
    );
  }

  @override
  bool shouldRebuild(_SliverAppBarDelegate oldDelegate) {
    return false;
  }
}
