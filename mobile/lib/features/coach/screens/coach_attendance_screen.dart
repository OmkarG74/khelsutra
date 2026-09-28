import 'package:flutter/material.dart';
import '../../../app/theme/app_theme.dart';
import '../../../core/models/athlete_models.dart';
import '../../../core/widgets/common_widgets.dart';
import '../../../core/widgets/khelsutra_app_bar.dart';
import '../data/coach_repository.dart';

class CoachAttendanceScreen extends StatefulWidget {
  final int? sessionId;
  final String sessionTitle;
  final String sessionTime;
  final String? teamName;
  final String? venueName;

  const CoachAttendanceScreen({
    super.key,
    this.sessionId,
    this.sessionTitle = 'Tactical Training Session',
    this.sessionTime = 'Today • 06:00 AM - 08:00 AM',
    this.teamName,
    this.venueName,
  });

  @override
  State<CoachAttendanceScreen> createState() => _CoachAttendanceScreenState();
}

class _CoachAttendanceScreenState extends State<CoachAttendanceScreen> {
  final CoachRepository _repo = CoachRepository();
  bool _isLoading = true;
  bool _isSaving = false;
  String? _errorMessage;

  int? _resolvedSessionId;
  String _resolvedTitle = '';
  String _resolvedTime = '';
  String _resolvedTeamName = '';
  String _resolvedVenueName = '';

  List<SessionAttendanceItem> _athletes = [];

  @override
  void initState() {
    super.initState();
    _resolvedSessionId = widget.sessionId;
    _resolvedTitle = widget.sessionTitle;
    _resolvedTime = widget.sessionTime;
    _resolvedTeamName = widget.teamName ?? 'Team Squad';
    _resolvedVenueName = widget.venueName ?? 'Main Arena';
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      if (_resolvedSessionId == null) {
        final sessions = await _repo.getTrainingSessions(date: 'today');
        if (sessions.isNotEmpty) {
          _resolvedSessionId = sessions.first.id;
          _resolvedTitle = sessions.first.title;
          _resolvedTime = 'Today • ${sessions.first.startTime} - ${sessions.first.endTime}';
          _resolvedTeamName = sessions.first.teamName;
          _resolvedVenueName = sessions.first.venue;
        } else {
          setState(() {
            _isLoading = false;
            _errorMessage = 'No training session selected or scheduled for today.';
          });
          return;
        }
      }

      // Load session details with roster attendance from backend
      final details = await _repo.getTrainingSessionDetails(_resolvedSessionId!);
      if (!mounted) return;

      _resolvedTitle = details.title.isNotEmpty ? details.title : _resolvedTitle;
      _resolvedTeamName = details.teamName.isNotEmpty ? details.teamName : _resolvedTeamName;
      _resolvedVenueName = details.venue.isNotEmpty ? details.venue : _resolvedVenueName;
      _resolvedTime = details.isToday
          ? 'Today • ${details.startTime} - ${details.endTime}'
          : '${details.date} • ${details.startTime} - ${details.endTime}';

      final roster = details.rosterAttendance
          .map((item) => SessionAttendanceItem.fromJson(item))
          .toList();

      setState(() {
        _athletes = roster;
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

  void _markAllPresent() {
    setState(() {
      for (var a in _athletes) {
        if (a.status == 'not_marked') {
          a.status = 'present';
        }
      }
    });

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('All unmarked athletes set to Present. Tap "Save Attendance" to confirm.'),
        duration: Duration(seconds: 2),
      ),
    );
  }

  void _setAthleteStatus(SessionAttendanceItem athlete, String status) {
    setState(() {
      if (athlete.status == status) {
        // Toggle back to not_marked if clicked again
        athlete.status = 'not_marked';
      } else {
        athlete.status = status;
      }
    });
  }

  void _saveAttendance() async {
    if (_athletes.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No athletes available for this session.')),
      );
      return;
    }

    // Option B: Validate that all athletes have been marked
    final unmarkedCount = _athletes.where((a) => a.status == 'not_marked').length;
    if (unmarkedCount > 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Please mark attendance (Present or Absent) for all athletes. ($unmarkedCount Not Marked)',
          ),
          backgroundColor: AppTheme.warningColor,
          duration: const Duration(seconds: 4),
          action: SnackBarAction(
            label: 'Mark All Present',
            textColor: Colors.white,
            onPressed: _markAllPresent,
          ),
        ),
      );
      return;
    }

    setState(() => _isSaving = true);

    try {
      final payload = _athletes
          .where((a) => a.status == 'present' || a.status == 'absent')
          .map((a) {
        return {
          'athlete_id': a.athleteId,
          'status': a.status,
          if (a.remarks != null) 'remarks': a.remarks,
        };
      }).toList();

      final res = await _repo.recordTrainingAttendance(
        sessionId: _resolvedSessionId!,
        attendanceData: payload,
      );

      if (!mounted) return;
      setState(() => _isSaving = false);

      final presentCount = _athletes.where((a) => a.status == 'present').length;
      final absentCount = _athletes.where((a) => a.status == 'absent').length;

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            res['message'] ?? 'Attendance saved successfully! ($presentCount Present, $absentCount Absent)',
          ),
          backgroundColor: AppTheme.successColor,
        ),
      );

      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _isSaving = false);

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to save attendance: ${e.toString().replaceAll('Exception: ', '')}'),
          backgroundColor: AppTheme.dangerColor,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        appBar: KhelSutraAppBar(
          title: 'Mark Attendance',
          subtitle: _resolvedTitle,
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
          title: 'Mark Attendance',
          subtitle: _resolvedTitle,
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

    final presentCount = _athletes.where((a) => a.status == 'present').length;
    final absentCount = _athletes.where((a) => a.status == 'absent').length;
    final notMarkedCount = _athletes.where((a) => a.status == 'not_marked').length;

    return Scaffold(
      appBar: KhelSutraAppBar(
        title: 'Mark Attendance',
        subtitle: _resolvedTitle,
        showBackButton: true,
        actions: [
          if (notMarkedCount > 0)
            TextButton.icon(
              onPressed: _markAllPresent,
              icon: const Icon(Icons.done_all, color: Colors.white, size: 16),
              label: const Text(
                'Mark All',
                style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
              ),
            ),
        ],
      ),
      body: Column(
        children: [
          // Session Information & Summary Banner
          Container(
            padding: const EdgeInsets.all(16.0),
            decoration: BoxDecoration(
              color: Colors.white,
              border: Border(
                bottom: BorderSide(color: AppTheme.borderColor.withValues(alpha: 0.8)),
              ),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.03),
                  blurRadius: 6,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Session Header Info
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _resolvedTitle,
                            style: const TextStyle(
                              fontSize: 17,
                              fontWeight: FontWeight.bold,
                              color: AppTheme.textColor,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Row(
                            children: [
                              const Icon(Icons.access_time_filled, size: 13, color: AppTheme.primaryColor),
                              const SizedBox(width: 4),
                              Text(
                                _resolvedTime,
                                style: const TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                  color: AppTheme.textSecondary,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    if (notMarkedCount > 0)
                      ElevatedButton.icon(
                        onPressed: _markAllPresent,
                        icon: const Icon(Icons.done_all, size: 15),
                        label: const Text('Mark All Present', style: TextStyle(fontSize: 11)),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppTheme.primaryLight,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                          elevation: 0,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(6),
                          ),
                        ),
                      ),
                  ],
                ),
                const SizedBox(height: 10),

                // Team & Venue Meta
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: AppTheme.surfaceColor,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.groups, size: 14, color: AppTheme.primaryColor),
                      const SizedBox(width: 6),
                      Text(
                        'Team: $_resolvedTeamName',
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          color: AppTheme.textColor,
                        ),
                      ),
                      const SizedBox(width: 14),
                      const Icon(Icons.place_outlined, size: 14, color: AppTheme.textMuted),
                      const SizedBox(width: 4),
                      Expanded(
                        child: Text(
                          'Venue: $_resolvedVenueName',
                          style: const TextStyle(
                            fontSize: 12,
                            color: AppTheme.textSecondary,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),

                // Dynamic 3-State Summary Counters (Present, Absent, Not Marked)
                Row(
                  children: [
                    // Present Counter
                    Expanded(
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 8),
                        decoration: BoxDecoration(
                          color: AppTheme.successColor.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(
                            color: AppTheme.successColor.withValues(alpha: 0.3),
                          ),
                        ),
                        child: Column(
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                const Icon(Icons.check_circle, size: 14, color: AppTheme.successColor),
                                const SizedBox(width: 4),
                                const Text(
                                  'Present',
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                    color: AppTheme.successColor,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 2),
                            Text(
                              '$presentCount',
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: AppTheme.successColor,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),

                    // Absent Counter
                    Expanded(
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 8),
                        decoration: BoxDecoration(
                          color: AppTheme.dangerColor.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(
                            color: AppTheme.dangerColor.withValues(alpha: 0.3),
                          ),
                        ),
                        child: Column(
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                const Icon(Icons.cancel, size: 14, color: AppTheme.dangerColor),
                                const SizedBox(width: 4),
                                const Text(
                                  'Absent',
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                    color: AppTheme.dangerColor,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 2),
                            Text(
                              '$absentCount',
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: AppTheme.dangerColor,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),

                    // Not Marked Counter
                    Expanded(
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 8),
                        decoration: BoxDecoration(
                          color: AppTheme.warningColor.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(
                            color: AppTheme.warningColor.withValues(alpha: 0.3),
                          ),
                        ),
                        child: Column(
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                const Icon(Icons.help_outline, size: 14, color: AppTheme.warningColor),
                                const SizedBox(width: 4),
                                const Text(
                                  'Not Marked',
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                    color: AppTheme.warningColor,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 2),
                            Text(
                              '$notMarkedCount',
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: AppTheme.warningColor,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          // Athletes Attendance Roster List
          Expanded(
            child: _athletes.isEmpty
                ? Center(
                    child: Padding(
                      padding: const EdgeInsets.all(24.0),
                      child: EmptyState(
                        icon: Icons.groups_outlined,
                        title: 'No Athletes Assigned',
                        description: 'No registered athletes found in $_resolvedTeamName.',
                      ),
                    ),
                  )
                : ListView.builder(
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
                    itemCount: _athletes.length,
                    itemBuilder: (context, index) {
                      final athlete = _athletes[index];
                      final status = athlete.status;

                      return Card(
                        margin: const EdgeInsets.only(bottom: 10),
                        elevation: 1.5,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                          side: BorderSide(
                            color: status == 'present'
                                ? AppTheme.successColor.withValues(alpha: 0.4)
                                : status == 'absent'
                                    ? AppTheme.dangerColor.withValues(alpha: 0.4)
                                    : AppTheme.borderColor,
                            width: status == 'not_marked' ? 1.0 : 1.5,
                          ),
                        ),
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // Athlete Info Row
                              Row(
                                children: [
                                  Container(
                                    width: 38,
                                    height: 38,
                                    decoration: BoxDecoration(
                                      color: status == 'present'
                                          ? AppTheme.successColor.withValues(alpha: 0.12)
                                          : status == 'absent'
                                              ? AppTheme.dangerColor.withValues(alpha: 0.12)
                                              : AppTheme.primaryColor.withValues(alpha: 0.08),
                                      shape: BoxShape.circle,
                                    ),
                                    child: Center(
                                      child: Text(
                                        (athlete.jerseyNumber != null && athlete.jerseyNumber!.isNotEmpty)
                                            ? '#${athlete.jerseyNumber}'
                                            : '${index + 1}',
                                        style: TextStyle(
                                          fontSize: 12,
                                          fontWeight: FontWeight.bold,
                                          color: status == 'present'
                                              ? AppTheme.successColor
                                              : status == 'absent'
                                                  ? AppTheme.dangerColor
                                                  : AppTheme.primaryColor,
                                        ),
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          athlete.athleteName,
                                          style: const TextStyle(
                                            fontSize: 15,
                                            fontWeight: FontWeight.bold,
                                            color: AppTheme.textColor,
                                          ),
                                        ),
                                        const SizedBox(height: 2),
                                        Text(
                                          '$_resolvedTeamName • ${athlete.athleteCode}',
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
                              const SizedBox(height: 10),
                              const Divider(height: 1),
                              const SizedBox(height: 10),

                              // Interactive 3-State Choice Chips: [ Not Marked ] [ Present ] [ Absent ]
                              Row(
                                children: [
                                  // Not Marked Chip
                                  Expanded(
                                    child: InkWell(
                                      onTap: () => _setAthleteStatus(athlete, 'not_marked'),
                                      borderRadius: BorderRadius.circular(6),
                                      child: AnimatedContainer(
                                        duration: const Duration(milliseconds: 150),
                                        padding: const EdgeInsets.symmetric(vertical: 7),
                                        decoration: BoxDecoration(
                                          color: status == 'not_marked'
                                              ? AppTheme.warningColor.withValues(alpha: 0.15)
                                              : AppTheme.surfaceColor,
                                          borderRadius: BorderRadius.circular(6),
                                          border: Border.all(
                                            color: status == 'not_marked'
                                                ? AppTheme.warningColor
                                                : AppTheme.borderColor,
                                            width: status == 'not_marked' ? 1.5 : 1.0,
                                          ),
                                        ),
                                        child: Center(
                                          child: Text(
                                            'Not Marked',
                                            style: TextStyle(
                                              fontSize: 11,
                                              fontWeight: status == 'not_marked'
                                                  ? FontWeight.bold
                                                  : FontWeight.normal,
                                              color: status == 'not_marked'
                                                  ? AppTheme.warningColor
                                                  : AppTheme.textSecondary,
                                            ),
                                          ),
                                        ),
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 8),

                                  // Present Chip
                                  Expanded(
                                    child: InkWell(
                                      onTap: () => _setAthleteStatus(athlete, 'present'),
                                      borderRadius: BorderRadius.circular(6),
                                      child: AnimatedContainer(
                                        duration: const Duration(milliseconds: 150),
                                        padding: const EdgeInsets.symmetric(vertical: 7),
                                        decoration: BoxDecoration(
                                          color: status == 'present'
                                              ? AppTheme.successColor
                                              : AppTheme.surfaceColor,
                                          borderRadius: BorderRadius.circular(6),
                                          border: Border.all(
                                            color: status == 'present'
                                                ? AppTheme.successColor
                                                : AppTheme.borderColor,
                                            width: status == 'present' ? 1.5 : 1.0,
                                          ),
                                        ),
                                        child: Center(
                                          child: Row(
                                            mainAxisAlignment: MainAxisAlignment.center,
                                            children: [
                                              if (status == 'present')
                                                const Padding(
                                                  padding: EdgeInsets.only(right: 4.0),
                                                  child: Icon(Icons.check, size: 13, color: Colors.white),
                                                ),
                                              Text(
                                                'Present',
                                                style: TextStyle(
                                                  fontSize: 11,
                                                  fontWeight: status == 'present'
                                                      ? FontWeight.bold
                                                      : FontWeight.normal,
                                                  color: status == 'present'
                                                      ? Colors.white
                                                      : AppTheme.textSecondary,
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 8),

                                  // Absent Chip
                                  Expanded(
                                    child: InkWell(
                                      onTap: () => _setAthleteStatus(athlete, 'absent'),
                                      borderRadius: BorderRadius.circular(6),
                                      child: AnimatedContainer(
                                        duration: const Duration(milliseconds: 150),
                                        padding: const EdgeInsets.symmetric(vertical: 7),
                                        decoration: BoxDecoration(
                                          color: status == 'absent'
                                              ? AppTheme.dangerColor
                                              : AppTheme.surfaceColor,
                                          borderRadius: BorderRadius.circular(6),
                                          border: Border.all(
                                            color: status == 'absent'
                                                ? AppTheme.dangerColor
                                                : AppTheme.borderColor,
                                            width: status == 'absent' ? 1.5 : 1.0,
                                          ),
                                        ),
                                        child: Center(
                                          child: Row(
                                            mainAxisAlignment: MainAxisAlignment.center,
                                            children: [
                                              if (status == 'absent')
                                                const Padding(
                                                  padding: EdgeInsets.only(right: 4.0),
                                                  child: Icon(Icons.close, size: 13, color: Colors.white),
                                                ),
                                              Text(
                                                'Absent',
                                                style: TextStyle(
                                                  fontSize: 11,
                                                  fontWeight: status == 'absent'
                                                      ? FontWeight.bold
                                                      : FontWeight.normal,
                                                  color: status == 'absent'
                                                      ? Colors.white
                                                      : AppTheme.textSecondary,
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
          ),

          // Bottom Action Bar: Save Attendance Button
          Container(
            padding: const EdgeInsets.all(16.0),
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.08),
                  blurRadius: 8,
                  offset: const Offset(0, -2),
                ),
              ],
            ),
            child: SafeArea(
              child: PrimaryButton(
                label: 'Save Attendance',
                icon: Icons.save_outlined,
                isLoading: _isSaving,
                onPressed: _saveAttendance,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
