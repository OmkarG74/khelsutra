import 'package:flutter/material.dart';
import '../../../app/theme/app_theme.dart';
import '../../../core/models/athlete_models.dart';
import '../../../core/widgets/common_widgets.dart';
import '../../../core/widgets/khelsutra_app_bar.dart';
import '../../../core/widgets/section_header.dart';
import '../../../core/widgets/stat_card.dart';
import '../data/athlete_repository.dart';

class AthleteAttendanceScreen extends StatefulWidget {
  final bool isStandalone;

  const AthleteAttendanceScreen({
    super.key,
    this.isStandalone = false,
  });

  @override
  State<AthleteAttendanceScreen> createState() => _AthleteAttendanceScreenState();
}

class _AthleteAttendanceScreenState extends State<AthleteAttendanceScreen> {
  final AthleteRepository _repository = AthleteRepository();

  bool _isLoading = true;
  String? _errorMessage;
  AthleteAttendanceSummary? _summary;

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
      final summary = await _repository.getAttendance();
      if (!mounted) return;
      setState(() {
        _summary = summary;
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
                title: 'Attendance Record',
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
                title: 'Attendance Record',
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

    final summary = _summary ?? AthleteAttendanceSummary.empty();
    final records = summary.records;

    return Scaffold(
      appBar: widget.isStandalone
          ? const KhelSutraAppBar(
              title: 'Attendance Record',
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
              // Stat Cards Row
              Row(
                children: [
                  Expanded(
                    child: StatCard(
                      title: 'Overall Attendance',
                      value: summary.totalSessions > 0
                          ? '${summary.percentage.toStringAsFixed(1)}%'
                          : 'N/A',
                      subtitle: summary.percentage >= 80 ? 'Meeting attendance target' : 'Target is 80%',
                      icon: Icons.how_to_reg,
                      color: summary.percentage >= 80 ? AppTheme.successColor : AppTheme.warningColor,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: StatCard(
                      title: 'Present Sessions',
                      value: '${summary.presentSessions}',
                      subtitle: 'of ${summary.totalSessions} total',
                      icon: Icons.check_circle_outline,
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
                      title: 'Absent Sessions',
                      value: '${summary.absentSessions}',
                      subtitle: 'Sessions missed',
                      icon: Icons.cancel_outlined,
                      color: AppTheme.dangerColor,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: StatCard(
                      title: 'Total Tracked',
                      value: '${summary.totalSessions}',
                      subtitle: 'Recorded training drills',
                      icon: Icons.fitness_center,
                      color: AppTheme.accentColor,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),

              // Progress Card
              if (summary.totalSessions > 0)
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text(
                              'Attendance Rate',
                              style: TextStyle(
                                fontSize: 15,
                                fontWeight: FontWeight.bold,
                                color: AppTheme.textColor,
                              ),
                            ),
                            Text(
                              '${summary.percentage.toStringAsFixed(1)}%',
                              style: TextStyle(
                                fontSize: 15,
                                fontWeight: FontWeight.bold,
                                color: summary.percentage >= 80 ? AppTheme.successColor : AppTheme.dangerColor,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        ClipRRect(
                          borderRadius: BorderRadius.circular(6),
                          child: LinearProgressIndicator(
                            value: summary.totalSessions > 0
                                ? (summary.presentSessions / summary.totalSessions).clamp(0.0, 1.0)
                                : 0.0,
                            minHeight: 10,
                            backgroundColor: AppTheme.borderColor,
                            valueColor: AlwaysStoppedAnimation<Color>(
                              summary.percentage >= 80 ? AppTheme.successColor : AppTheme.warningColor,
                            ),
                          ),
                        ),
                        const SizedBox(height: 12),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              '${summary.presentSessions} Sessions Attended',
                              style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                            ),
                            Text(
                              '${summary.absentSessions} Sessions Missed',
                              style: const TextStyle(fontSize: 12, color: AppTheme.dangerColor),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              if (summary.totalSessions > 0) const SizedBox(height: 24),

              // Recent Attendance List
              SectionHeader(
                title: 'Attendance History',
                subtitle: records.isNotEmpty
                    ? '${records.length} logged sessions'
                    : 'No attendance data available',
              ),
              const SizedBox(height: 8),

              if (records.isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 32.0),
                  child: Center(
                    child: EmptyState(
                      icon: Icons.how_to_reg,
                      title: 'No attendance data available',
                      description: 'Your attendance records will appear here as soon as training drills are logged.',
                    ),
                  ),
                )
              else
                ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: records.length,
                  itemBuilder: (context, index) {
                    final rec = records[index];
                    final isPresent = rec.isPresent;

                    return Card(
                      margin: const EdgeInsets.only(bottom: 10),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                        child: Row(
                          children: [
                            Container(
                              width: 38,
                              height: 38,
                              decoration: BoxDecoration(
                                color: isPresent
                                    ? AppTheme.successColor.withValues(alpha: 0.12)
                                    : AppTheme.dangerColor.withValues(alpha: 0.12),
                                shape: BoxShape.circle,
                              ),
                              child: Icon(
                                isPresent ? Icons.check : Icons.close,
                                color: isPresent ? AppTheme.successColor : AppTheme.dangerColor,
                                size: 20,
                              ),
                            ),
                            const SizedBox(width: 14),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    rec.sessionTitle,
                                    style: const TextStyle(
                                      fontSize: 14,
                                      fontWeight: FontWeight.bold,
                                      color: AppTheme.textColor,
                                    ),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    rec.sessionDate,
                                    style: const TextStyle(
                                      fontSize: 12,
                                      color: AppTheme.textSecondary,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: isPresent
                                    ? AppTheme.successColor.withValues(alpha: 0.1)
                                    : AppTheme.dangerColor.withValues(alpha: 0.1),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                rec.status.isNotEmpty
                                    ? (rec.status[0].toUpperCase() + rec.status.substring(1))
                                    : (isPresent ? 'Present' : 'Absent'),
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.bold,
                                  color: isPresent ? AppTheme.successColor : AppTheme.dangerColor,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
            ],
          ),
        ),
      ),
    );
  }
}
