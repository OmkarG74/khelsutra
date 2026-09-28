import 'package:flutter/material.dart';
import '../../../app/theme/app_theme.dart';
import '../../../core/mock/mock_data.dart';
import '../../../core/widgets/khelsutra_app_bar.dart';
import '../../../core/widgets/status_badge.dart';

class AthleteTrainingDetailsScreen extends StatelessWidget {
  final TrainingSession session;

  const AthleteTrainingDetailsScreen({
    super.key,
    required this.session,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: KhelSutraAppBar(
        title: 'Training Details',
        subtitle: session.id,
        showBackButton: true,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Status and Title Header Card
            Card(
              child: Padding(
                padding: const EdgeInsets.all(18.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: AppTheme.primaryColor.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            session.teamName,
                            style: const TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                              color: AppTheme.primaryColor,
                            ),
                          ),
                        ),
                        StatusBadge(label: session.attendanceStatus),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Text(
                      session.title,
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: AppTheme.textColor,
                      ),
                    ),
                    const SizedBox(height: 16),
                    const Divider(height: 1),
                    const SizedBox(height: 16),
                    _buildDetailRow(
                      icon: Icons.calendar_today_outlined,
                      label: 'Date',
                      value: session.date,
                    ),
                    const SizedBox(height: 12),
                    _buildDetailRow(
                      icon: Icons.access_time_outlined,
                      label: 'Timing',
                      value: '${session.startTime} - ${session.endTime}',
                    ),
                    const SizedBox(height: 12),
                    _buildDetailRow(
                      icon: Icons.location_on_outlined,
                      label: 'Venue',
                      value: session.venue,
                    ),
                    const SizedBox(height: 12),
                    _buildDetailRow(
                      icon: Icons.sports_outlined,
                      label: 'Assigned Coach',
                      value: session.coachName,
                    ),
                    const SizedBox(height: 12),
                    _buildDetailRow(
                      icon: Icons.flag_outlined,
                      label: 'Session Status',
                      value: session.status,
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Instructions Card
            Card(
              child: Padding(
                padding: const EdgeInsets.all(18.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Row(
                      children: [
                        Icon(Icons.notes, color: AppTheme.primaryColor, size: 20),
                        SizedBox(width: 8),
                        Text(
                          'Coach Instructions & Focus',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: AppTheme.textColor,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Text(
                      session.instructions,
                      style: const TextStyle(
                        fontSize: 14,
                        color: AppTheme.textColor,
                        height: 1.5,
                      ),
                    ),
                    const SizedBox(height: 16),
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: AppTheme.surfaceColor,
                        borderRadius: BorderRadius.circular(AppTheme.radiusSmall),
                        border: Border.all(color: AppTheme.borderColor),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.info_outline, size: 18, color: AppTheme.primaryLight),
                          SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'Please report to venue 15 minutes before scheduled start time with full kit.',
                              style: TextStyle(
                                fontSize: 12,
                                color: AppTheme.textSecondary,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Attendance Status Banner
            Card(
              color: session.attendanceStatus.toLowerCase() == 'present'
                  ? AppTheme.successColor.withValues(alpha: 0.08)
                  : (session.attendanceStatus.toLowerCase() == 'absent'
                      ? AppTheme.dangerColor.withValues(alpha: 0.08)
                      : AppTheme.warningColor.withValues(alpha: 0.08)),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
                side: BorderSide(
                  color: session.attendanceStatus.toLowerCase() == 'present'
                      ? AppTheme.successColor.withValues(alpha: 0.3)
                      : (session.attendanceStatus.toLowerCase() == 'absent'
                          ? AppTheme.dangerColor.withValues(alpha: 0.3)
                          : AppTheme.warningColor.withValues(alpha: 0.3)),
                ),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Row(
                  children: [
                    Icon(
                      session.attendanceStatus.toLowerCase() == 'present'
                          ? Icons.check_circle
                          : (session.attendanceStatus.toLowerCase() == 'absent'
                              ? Icons.cancel
                              : Icons.schedule),
                      color: session.attendanceStatus.toLowerCase() == 'present'
                          ? AppTheme.successColor
                          : (session.attendanceStatus.toLowerCase() == 'absent'
                              ? AppTheme.dangerColor
                              : AppTheme.warningColor),
                      size: 28,
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Attendance Status: ${session.attendanceStatus}',
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                              color: AppTheme.textColor,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            session.attendanceStatus.toLowerCase() == 'present'
                                ? 'Your attendance has been confirmed by Coach.'
                                : (session.attendanceStatus.toLowerCase() == 'absent'
                                    ? 'Marked absent for this session.'
                                    : 'Awaiting attendance roll call by Coach.'),
                            style: const TextStyle(
                              fontSize: 12,
                              color: AppTheme.textSecondary,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDetailRow({
    required IconData icon,
    required String label,
    required String value,
  }) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: AppTheme.textMuted),
        const SizedBox(width: 10),
        SizedBox(
          width: 110,
          child: Text(
            label,
            style: const TextStyle(
              fontSize: 13,
              color: AppTheme.textSecondary,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: AppTheme.textColor,
            ),
          ),
        ),
      ],
    );
  }
}
