import 'package:flutter/material.dart';
import '../../../app/theme/app_theme.dart';
import '../../../core/models/athlete_models.dart';
import '../../../core/models/coach_models.dart';
import '../../../core/widgets/common_widgets.dart';
import '../../../core/widgets/khelsutra_app_bar.dart';
import '../../../core/widgets/section_header.dart';
import '../../../core/widgets/stat_card.dart';
import '../data/coach_repository.dart';

class CoachReportsScreen extends StatefulWidget {
  final bool isStandalone;

  const CoachReportsScreen({
    super.key,
    this.isStandalone = false,
  });

  @override
  State<CoachReportsScreen> createState() => _CoachReportsScreenState();
}

class _CoachReportsScreenState extends State<CoachReportsScreen> {
  final CoachRepository _repo = CoachRepository();

  bool _isLoading = true;
  String? _errorMessage;
  CoachDashboardData? _dashboardData;
  List<TrainingSessionItem> _trainings = [];
  List<CoachRosterAthleteItem> _athletes = [];

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
      final data = await _repo.getDashboard();
      final trainings = await _repo.getTrainingSessions();
      final athletes = await _repo.getAthletes();
      if (!mounted) return;
      setState(() {
        _dashboardData = data;
        _trainings = trainings;
        _athletes = athletes;
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
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        appBar: widget.isStandalone
            ? const KhelSutraAppBar(
                title: 'Team Analytics & Reports',
                showBackButton: true,
              )
            : null,
        body: const Center(
          child: CircularProgressIndicator(),
        ),
      );
    }

    if (_errorMessage != null) {
      return Scaffold(
        appBar: widget.isStandalone
            ? const KhelSutraAppBar(
                title: 'Team Analytics & Reports',
                showBackButton: true,
              )
            : null,
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

    final totalAthletes = _dashboardData?.athletesCount ?? _athletes.length;
    final todayTrainingsCount = _trainings.where((t) => t.isToday).length;
    final totalTrainingsCount = _trainings.length;
    final presentToday = _dashboardData?.presentToday ?? 0;
    final absentToday = _dashboardData?.absentToday ?? 0;
    final totalLoggedToday = presentToday + absentToday;
    final double attendanceRate = totalLoggedToday > 0 ? (presentToday / totalLoggedToday * 100) : 0;

    return Scaffold(
      appBar: widget.isStandalone
          ? const KhelSutraAppBar(
              title: 'Team Analytics & Reports',
              showBackButton: true,
            )
          : null,
      body: RefreshIndicator(
        onRefresh: _loadData,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // KPI Stat Cards Grid
              Row(
                children: [
                  Expanded(
                    child: StatCard(
                      title: 'Athlete Attendance',
                      value: totalLoggedToday > 0 ? '${attendanceRate.toStringAsFixed(1)}%' : 'No data',
                      subtitle: totalLoggedToday > 0 ? '$presentToday present of $totalLoggedToday' : 'No drills marked today',
                      icon: Icons.how_to_reg,
                      color: AppTheme.successColor,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: StatCard(
                      title: "Today's Training",
                      value: '$todayTrainingsCount',
                      subtitle: '$totalTrainingsCount total scheduled',
                      icon: Icons.fitness_center,
                      color: AppTheme.primaryLight,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),

              Row(
                children: [
                  Expanded(
                    child: StatCard(
                      title: 'Total Athletes',
                      value: '$totalAthletes',
                      subtitle: 'Supervised Squad Roster',
                      icon: Icons.groups,
                      color: const Color(0xFF7C3AED),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: StatCard(
                      title: 'Squad Health',
                      value: _dashboardData?.lowAttendanceAthletes.isEmpty == true ? '100%' : '${_dashboardData?.lowAttendanceAthletes.length ?? 0} Alert',
                      subtitle: 'Target: >80% Attendance',
                      icon: Icons.health_and_safety_outlined,
                      color: AppTheme.accentColor,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),

              // Roster Summary
              const SectionHeader(
                title: 'Squad Roster Overview',
                subtitle: 'Assigned athletes enrolled in training camps',
              ),
              const SizedBox(height: 8),

              if (_athletes.isEmpty)
                const Card(
                  child: Padding(
                    padding: EdgeInsets.all(24.0),
                    child: Center(
                      child: EmptyState(
                        icon: Icons.groups_outlined,
                        title: 'No Athletes Assigned',
                        description: 'No data available for assigned squad roster.',
                      ),
                    ),
                  ),
                )
              else
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      children: _athletes.map((a) {
                        return Padding(
                          padding: const EdgeInsets.symmetric(vertical: 8.0),
                          child: Row(
                            children: [
                              CircleAvatar(
                                radius: 18,
                                backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.1),
                                child: Text(
                                  a.fullName.split(' ').map((n) => n.isNotEmpty ? n[0] : '').take(2).join(),
                                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppTheme.primaryColor),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      a.fullName,
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                                    ),
                                    Text(
                                      '${a.sportName} • ${a.teamName}',
                                      style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary),
                                    ),
                                  ],
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                decoration: BoxDecoration(
                                  color: AppTheme.successColor.withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: Text(
                                  a.status.isNotEmpty ? a.status.toUpperCase() : 'ACTIVE',
                                  style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppTheme.successColor),
                                ),
                              ),
                            ],
                          ),
                        );
                      }).toList(),
                    ),
                  ),
                ),
              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }
}
