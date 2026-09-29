import 'package:flutter/material.dart';
import '../../../app/theme/app_theme.dart';
import '../../../core/models/athlete_models.dart';
import '../../../core/widgets/common_widgets.dart';
import '../../../core/widgets/khelsutra_app_bar.dart';
import '../data/coach_repository.dart';

class CoachMatchesScreen extends StatefulWidget {
  final bool isStandalone;

  const CoachMatchesScreen({
    super.key,
    this.isStandalone = false,
  });

  @override
  State<CoachMatchesScreen> createState() => _CoachMatchesScreenState();
}

class _CoachMatchesScreenState extends State<CoachMatchesScreen> {
  final CoachRepository _repo = CoachRepository();

  bool _isLoading = true;
  String? _errorMessage;
  List<MatchItem> _matches = [];

  @override
  void initState() {
    super.initState();
    _loadMatches();
  }

  Future<void> _loadMatches() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final matches = await _repo.getMatches();
      if (!mounted) return;
      setState(() {
        _matches = matches;
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

  void _openMatchAttendance(MatchItem match) async {
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _MatchAttendanceSheet(match: match, repo: _repo),
    );

    if (result == true) {
      _loadMatches();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: KhelSutraAppBar(
        title: 'Fixtures & Matches',
        subtitle: 'Tournament schedule & attendance',
        showBackButton: widget.isStandalone,
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_errorMessage != null) {
      return Center(
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
                onPressed: _loadMatches,
                icon: const Icon(Icons.refresh),
                label: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    if (_matches.isEmpty) {
      return const Center(
        child: EmptyState(
          icon: Icons.sports_kabaddi,
          title: 'No Matches Scheduled',
          description: 'No tournament matches or competitive fixtures assigned to your teams yet.',
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadMatches,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _matches.length,
        itemBuilder: (context, index) {
          final m = _matches[index];
          final isCompleted = m.status.toLowerCase() == 'completed';

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            child: InkWell(
              borderRadius: BorderRadius.circular(12),
              onTap: () => _openMatchAttendance(m),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: Text(
                            m.tournamentName ?? 'Competitive Fixture',
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                              color: AppTheme.primaryColor,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: isCompleted
                                ? AppTheme.successColor.withValues(alpha: 0.12)
                                : AppTheme.accentColor.withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            m.status.toUpperCase(),
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                              color: isCompleted ? AppTheme.successColor : AppTheme.accentColor,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                m.homeTeamName,
                                style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                              ),
                              const SizedBox(height: 4),
                              const Text('VS', style: TextStyle(fontSize: 11, color: AppTheme.textMuted, fontWeight: FontWeight.bold)),
                              const SizedBox(height: 4),
                              Text(
                                m.awayTeamName,
                                style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                              ),
                            ],
                          ),
                        ),
                        if (m.homeScore != null || m.awayScore != null)
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                            decoration: BoxDecoration(
                              color: Colors.grey.shade100,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              '${m.homeScore ?? "-"} : ${m.awayScore ?? "-"}',
                              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    const Divider(height: 1),
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        const Icon(Icons.calendar_today, size: 13, color: AppTheme.textMuted),
                        const SizedBox(width: 4),
                        Text(
                          '${m.matchDate}${m.scheduledTime.isNotEmpty ? " • ${m.scheduledTime}" : ""}',
                          style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                        ),
                        const Spacer(),
                        if (m.venueName != null) ...[
                          const Icon(Icons.location_on_outlined, size: 14, color: AppTheme.textMuted),
                          const SizedBox(width: 4),
                          Flexible(
                            child: Text(
                              m.venueName!,
                              style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ],
                    ),
                    const SizedBox(height: 10),
                    SizedBox(
                      width: double.infinity,
                      child: OutlinedButton.icon(
                        onPressed: () => _openMatchAttendance(m),
                        icon: const Icon(Icons.how_to_reg, size: 16),
                        label: const Text('Mark / View Roster Attendance', style: TextStyle(fontSize: 12)),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: AppTheme.primaryColor,
                          side: const BorderSide(color: AppTheme.primaryColor),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

class _MatchAttendanceSheet extends StatefulWidget {
  final MatchItem match;
  final CoachRepository repo;

  const _MatchAttendanceSheet({
    required this.match,
    required this.repo,
  });

  @override
  State<_MatchAttendanceSheet> createState() => _MatchAttendanceSheetState();
}

class _MatchAttendanceSheetState extends State<_MatchAttendanceSheet> {
  bool _isLoading = true;
  bool _isSaving = false;
  String? _errorMessage;
  List<MatchRosterAthleteItem> _athletes = [];
  final Map<int, String> _statuses = {};
  final Map<int, String> _remarks = {};

  @override
  void initState() {
    super.initState();
    _fetchDetails();
  }

  Future<void> _fetchDetails() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final matchDetails = await widget.repo.getMatchDetails(widget.match.id);
      final athletes = matchDetails.rosterAttendance;
      if (!mounted) return;
      setState(() {
        _athletes = athletes;
        for (var a in athletes) {
          _statuses[a.athleteId] = a.attendanceStatus;
          if (a.attendanceRemarks != null) {
            _remarks[a.athleteId] = a.attendanceRemarks!;
          }
        }
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

  Future<void> _submitAttendance() async {
    if (_athletes.isEmpty) return;

    setState(() => _isSaving = true);

    try {
      final list = _athletes.map((a) {
        return {
          'athlete_id': a.athleteId,
          'attendance_status': _statuses[a.athleteId] ?? 'present',
          'remarks': _remarks[a.athleteId],
        };
      }).toList();

      await widget.repo.recordMatchAttendance(
        matchId: widget.match.id,
        attendance: list,
      );

      if (!mounted) return;
      setState(() => _isSaving = false);

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Match attendance recorded successfully!'),
          backgroundColor: AppTheme.successColor,
        ),
      );

      Navigator.pop(context, true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _isSaving = false);

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to save attendance: ${e.toString().replaceAll("Exception: ", "")}'),
          backgroundColor: AppTheme.dangerColor,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: MediaQuery.of(context).size.height * 0.85,
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              border: Border(bottom: BorderSide(color: Colors.grey.shade200)),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Match Attendance Roster', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      Text(
                        '${widget.match.homeTeamName} vs ${widget.match.awayTeamName}',
                        style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
          ),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _errorMessage != null
                    ? Center(child: Text(_errorMessage!, style: const TextStyle(color: AppTheme.dangerColor)))
                    : _athletes.isEmpty
                        ? const Center(
                            child: EmptyState(
                              icon: Icons.group_off,
                              title: 'No Athletes in Roster',
                              description: 'No registered team athletes found for this fixture.',
                            ),
                          )
                        : ListView.builder(
                            padding: const EdgeInsets.all(16),
                            itemCount: _athletes.length,
                            itemBuilder: (context, index) {
                              final a = _athletes[index];
                              final status = _statuses[a.athleteId] ?? 'present';

                              return Card(
                                margin: const EdgeInsets.only(bottom: 8),
                                child: Padding(
                                  padding: const EdgeInsets.all(12),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: [
                                          Expanded(
                                            child: Column(
                                              crossAxisAlignment: CrossAxisAlignment.start,
                                              children: [
                                                Text(a.athleteName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                                                Text('${a.teamName}${a.jerseyNumber != null ? " • #${a.jerseyNumber}" : ""}',
                                                    style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary)),
                                              ],
                                            ),
                                          ),
                                          Wrap(
                                            spacing: 4,
                                            children: ['present', 'absent', 'late', 'excused'].map((st) {
                                              final isSelected = status == st;
                                              Color c = AppTheme.successColor;
                                              if (st == 'absent') c = AppTheme.dangerColor;
                                              if (st == 'late') c = Colors.orange;
                                              if (st == 'excused') c = Colors.blue;

                                              return ChoiceChip(
                                                label: Text(
                                                  st[0].toUpperCase() + st.substring(1),
                                                  style: TextStyle(
                                                    fontSize: 11,
                                                    color: isSelected ? Colors.white : c,
                                                    fontWeight: FontWeight.bold,
                                                  ),
                                                ),
                                                selected: isSelected,
                                                selectedColor: c,
                                                backgroundColor: c.withValues(alpha: 0.1),
                                                showCheckmark: false,
                                                onSelected: (val) {
                                                  if (val) {
                                                    setState(() => _statuses[a.athleteId] = st);
                                                  }
                                                },
                                              );
                                            }).toList(),
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
          if (!_isLoading && _athletes.isNotEmpty)
            Padding(
              padding: const EdgeInsets.all(16),
              child: PrimaryButton(
                label: 'Save Match Attendance',
                icon: Icons.check,
                isLoading: _isSaving,
                onPressed: _submitAttendance,
              ),
            ),
        ],
      ),
    );
  }
}
