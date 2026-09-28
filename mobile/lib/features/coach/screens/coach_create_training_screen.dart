import 'package:flutter/material.dart';
import '../../../app/theme/app_theme.dart';
import '../../../core/models/coach_models.dart';
import '../../../core/widgets/common_widgets.dart';
import '../../../core/widgets/khelsutra_app_bar.dart';
import '../data/coach_repository.dart';

class CoachCreateTrainingScreen extends StatefulWidget {
  const CoachCreateTrainingScreen({super.key});

  @override
  State<CoachCreateTrainingScreen> createState() =>
      _CoachCreateTrainingScreenState();
}

class _CoachCreateTrainingScreenState extends State<CoachCreateTrainingScreen> {
  final _formKey = GlobalKey<FormState>();
  final CoachRepository _repo = CoachRepository();

  final _titleController = TextEditingController();
  final _dateController = TextEditingController(text: '2026-09-29');
  final _startTimeController = TextEditingController(text: '06:00:00');
  final _endTimeController = TextEditingController(text: '08:00:00');
  final _venueController = TextEditingController(text: 'Cricket Ground');
  final _teamController = TextEditingController(text: 'Team FB');
  final _instructionsController = TextEditingController();
  int? _selectedTeamId;
  List<CoachAssignedTeam> _coachTeams = [];
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _loadCoachTeams();
  }

  void _loadCoachTeams() async {
    try {
      final profile = await _repo.getProfile();
      if (profile.assignedTeams.isNotEmpty && mounted) {
        setState(() {
          _coachTeams = profile.assignedTeams;
          _selectedTeamId = profile.assignedTeams.first.teamId;
          _teamController.text = profile.assignedTeams.first.teamName;
        });
      }
    } catch (_) {
      // Graceful fallback to default values
    }
  }

  @override
  void dispose() {
    _titleController.dispose();
    _dateController.dispose();
    _startTimeController.dispose();
    _endTimeController.dispose();
    _venueController.dispose();
    _teamController.dispose();
    _instructionsController.dispose();
    super.dispose();
  }

  void _handleCreate() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSaving = true);

    try {
      final newSession = await _repo.createTrainingSession(
        title: _titleController.text.trim(),
        date: _dateController.text.trim(),
        startTime: _startTimeController.text.trim(),
        endTime: _endTimeController.text.trim(),
        teamId: _selectedTeamId,
        teamName: _teamController.text.trim(),
        venueName: _venueController.text.trim(),
        objectives: _instructionsController.text.trim().isNotEmpty
            ? _instructionsController.text.trim()
            : 'General conditioning and tactical training drill.',
        notes: 'Created via KhelSutra Coach Mobile App',
      );

      if (!mounted) return;
      setState(() => _isSaving = false);

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Training session "${newSession.title}" created successfully!'),
          backgroundColor: AppTheme.successColor,
        ),
      );

      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _isSaving = false);

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to create training: ${e.toString().replaceAll('Exception: ', '')}'),
          backgroundColor: AppTheme.dangerColor,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const KhelSutraAppBar(
        title: 'Create Training',
        subtitle: 'Schedule a new coaching session',
        showBackButton: true,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(18.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Session Information',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: AppTheme.textColor,
                        ),
                      ),
                      const SizedBox(height: 16),
                      TextFormField(
                        controller: _titleController,
                        decoration: const InputDecoration(
                          labelText: 'Training Name *',
                          hintText: 'e.g. Speed & Agility Drills',
                          prefixIcon: Icon(Icons.fitness_center),
                        ),
                        validator: (val) {
                          if (val == null || val.trim().isEmpty) {
                            return 'Please enter a training title';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: _teamController,
                        decoration: const InputDecoration(
                          labelText: 'Assigned Team / Squad *',
                          prefixIcon: Icon(Icons.groups),
                        ),
                        validator: (val) {
                          if (val == null || val.trim().isEmpty) {
                            return 'Please specify the team';
                          }
                          return null;
                        },
                      ),
                      if (_coachTeams.isNotEmpty) ...[
                        const SizedBox(height: 10),
                        Wrap(
                          spacing: 8,
                          runSpacing: 6,
                          children: _coachTeams.map((t) {
                            final isSelected = _selectedTeamId == t.teamId;
                            return ChoiceChip(
                              label: Text(t.teamName, style: const TextStyle(fontSize: 12)),
                              selected: isSelected,
                              onSelected: (selected) {
                                if (selected) {
                                  setState(() {
                                    _selectedTeamId = t.teamId;
                                    _teamController.text = t.teamName;
                                  });
                                }
                              },
                            );
                          }).toList(),
                        ),
                      ],
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: _venueController,
                        decoration: const InputDecoration(
                          labelText: 'Venue / Facility *',
                          prefixIcon: Icon(Icons.location_on_outlined),
                        ),
                        validator: (val) {
                          if (val == null || val.trim().isEmpty) {
                            return 'Please enter the venue';
                          }
                          return null;
                        },
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),

              Card(
                child: Padding(
                  padding: const EdgeInsets.all(18.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Schedule & Timings',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: AppTheme.textColor,
                        ),
                      ),
                      const SizedBox(height: 16),
                      TextFormField(
                        controller: _dateController,
                        decoration: const InputDecoration(
                          labelText: 'Date (YYYY-MM-DD) *',
                          hintText: 'e.g. 2026-09-29',
                          prefixIcon: Icon(Icons.calendar_today_outlined),
                        ),
                        validator: (val) {
                          if (val == null || val.trim().isEmpty) {
                            return 'Please enter a date';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 14),
                      Row(
                        children: [
                          Expanded(
                            child: TextFormField(
                              controller: _startTimeController,
                              decoration: const InputDecoration(
                                labelText: 'Start Time *',
                                hintText: '06:00:00',
                                prefixIcon: Icon(Icons.access_time),
                              ),
                              validator: (val) {
                                if (val == null || val.trim().isEmpty) {
                                  return 'Required';
                                }
                                return null;
                              },
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: TextFormField(
                              controller: _endTimeController,
                              decoration: const InputDecoration(
                                labelText: 'End Time *',
                                hintText: '08:00:00',
                                prefixIcon: Icon(Icons.access_time_filled),
                              ),
                              validator: (val) {
                                if (val == null || val.trim().isEmpty) {
                                  return 'Required';
                                }
                                return null;
                              },
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),

              Card(
                child: Padding(
                  padding: const EdgeInsets.all(18.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Instructions for Athletes',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: AppTheme.textColor,
                        ),
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: _instructionsController,
                        maxLines: 3,
                        decoration: const InputDecoration(
                          hintText: 'Enter specific drill focus, required kits, warm-up targets...',
                          alignLabelWithHint: true,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 24),

              PrimaryButton(
                label: 'Create Training',
                icon: Icons.add_circle_outline,
                isLoading: _isSaving,
                onPressed: _handleCreate,
              ),
              const SizedBox(height: 16),
            ],
          ),
        ),
      ),
    );
  }
}
