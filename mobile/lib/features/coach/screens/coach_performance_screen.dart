import 'package:flutter/material.dart';
import '../../../app/theme/app_theme.dart';
import '../../../core/models/coach_models.dart';
import '../../../core/widgets/common_widgets.dart';
import '../../../core/widgets/khelsutra_app_bar.dart';
import '../data/coach_repository.dart';

class CoachPerformanceScreen extends StatefulWidget {
  final String? initialAthleteId;

  const CoachPerformanceScreen({
    super.key,
    this.initialAthleteId,
  });

  @override
  State<CoachPerformanceScreen> createState() => _CoachPerformanceScreenState();
}

class _CoachPerformanceScreenState extends State<CoachPerformanceScreen> {
  final _formKey = GlobalKey<FormState>();
  final CoachRepository _repo = CoachRepository();

  bool _isLoading = true;
  bool _isSaving = false;
  String? _errorMessage;

  List<CoachRosterAthleteItem> _athletes = [];
  List<PerformanceMetricItem> _metrics = [];

  String? _selectedAthleteId;
  PerformanceMetricItem? _selectedMetric;
  final _valueController = TextEditingController(text: '12.4');
  final _ratingController = TextEditingController(text: '8.5');
  final _dateController = TextEditingController(text: '2026-09-28');
  final _notesController = TextEditingController();

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
      final athletes = await _repo.getAthletes();
      final metrics = await _repo.getPerformanceMetrics();
      if (!mounted) return;
      setState(() {
        _athletes = athletes;
        _metrics = metrics;
        if (_athletes.isNotEmpty) {
          _selectedAthleteId = widget.initialAthleteId ?? _athletes.first.id.toString();
        }
        if (_metrics.isNotEmpty) {
          _selectedMetric = _metrics.first;
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

  @override
  void dispose() {
    _valueController.dispose();
    _ratingController.dispose();
    _dateController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  void _handleSave() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedAthleteId == null || _selectedMetric == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select an athlete and metric.')),
      );
      return;
    }

    setState(() => _isSaving = true);

    try {
      final athleteId = int.tryParse(_selectedAthleteId!) ?? 1;
      final numericVal = double.tryParse(_valueController.text.trim());
      final rating = double.tryParse(_ratingController.text.trim()) ?? 8.0;

      await _repo.recordPerformance(
        athleteId: athleteId,
        evaluationDate: _dateController.text.trim(),
        overallRating: rating,
        coachRemarks: _notesController.text.trim().isNotEmpty
            ? _notesController.text.trim()
            : 'Performance evaluation recorded by coach.',
        values: [
          {
            'metric_id': _selectedMetric!.id,
            'numeric_value': numericVal,
            'text_value': numericVal == null ? _valueController.text.trim() : null,
          }
        ],
      );

      if (!mounted) return;
      setState(() => _isSaving = false);

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Performance recorded for ${_selectedMetric!.name}!'),
          backgroundColor: AppTheme.successColor,
        ),
      );

      Navigator.of(context).maybePop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _isSaving = false);

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to save performance: ${e.toString().replaceAll('Exception: ', '')}'),
          backgroundColor: AppTheme.dangerColor,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        appBar: KhelSutraAppBar(
          title: 'Record Performance',
          showBackButton: true,
        ),
        body: Center(
          child: CircularProgressIndicator(),
        ),
      );
    }

    if (_errorMessage != null) {
      return Scaffold(
        appBar: const KhelSutraAppBar(
          title: 'Record Performance',
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

    return Scaffold(
      appBar: const KhelSutraAppBar(
        title: 'Record Performance',
        subtitle: 'Log physical metrics and test results',
        showBackButton: true,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Target Athlete Card
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Select Athlete',
                        style: TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.bold,
                          color: AppTheme.textColor,
                        ),
                      ),
                      const SizedBox(height: 12),
                      DropdownButtonFormField<String>(
                        initialValue: _selectedAthleteId,
                        decoration: const InputDecoration(
                          labelText: 'Athlete *',
                          prefixIcon: Icon(Icons.person_outline),
                        ),
                        items: _athletes.map((a) {
                          return DropdownMenuItem<String>(
                            value: a.id.toString(),
                            child: Text('${a.fullName} (${a.sportName})'),
                          );
                        }).toList(),
                        onChanged: (val) {
                          if (val != null) {
                            setState(() => _selectedAthleteId = val);
                          }
                        },
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Metric Selection & Score Card
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Assessment Metric',
                        style: TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.bold,
                          color: AppTheme.textColor,
                        ),
                      ),
                      const SizedBox(height: 12),
                      DropdownButtonFormField<PerformanceMetricItem>(
                        initialValue: _selectedMetric,
                        decoration: const InputDecoration(
                          labelText: 'Performance Metric *',
                          prefixIcon: Icon(Icons.speed),
                        ),
                        items: _metrics.map((m) {
                          return DropdownMenuItem<PerformanceMetricItem>(
                            value: m,
                            child: Text('${m.name} (${m.unit})'),
                          );
                        }).toList(),
                        onChanged: (val) {
                          if (val != null) {
                            setState(() => _selectedMetric = val);
                          }
                        },
                      ),
                      const SizedBox(height: 14),
                      Row(
                        children: [
                          Expanded(
                            child: TextFormField(
                              controller: _valueController,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              decoration: InputDecoration(
                                labelText: 'Result Value *',
                                suffixText: _selectedMetric?.unit ?? '',
                                prefixIcon: const Icon(Icons.timer_outlined),
                              ),
                              validator: (val) {
                                if (val == null || val.trim().isEmpty) {
                                  return 'Enter value';
                                }
                                return null;
                              },
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: TextFormField(
                              controller: _ratingController,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              decoration: const InputDecoration(
                                labelText: 'Rating (0 - 10) *',
                                prefixIcon: Icon(Icons.star_outline),
                              ),
                              validator: (val) {
                                if (val == null || val.trim().isEmpty) {
                                  return 'Enter rating';
                                }
                                return null;
                              },
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: _dateController,
                        decoration: const InputDecoration(
                          labelText: 'Assessment Date (YYYY-MM-DD) *',
                          prefixIcon: Icon(Icons.calendar_today),
                        ),
                        validator: (val) {
                          if (val == null || val.trim().isEmpty) {
                            return 'Enter date';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: _notesController,
                        maxLines: 3,
                        decoration: const InputDecoration(
                          labelText: 'Coach Remarks & Recommendations',
                          hintText: 'Enter technical feedback, sprint cadence, posture notes...',
                          alignLabelWithHint: true,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 24),

              PrimaryButton(
                label: 'Save Performance Record',
                icon: Icons.save_outlined,
                isLoading: _isSaving,
                onPressed: _handleSave,
              ),
              const SizedBox(height: 16),
            ],
          ),
        ),
      ),
    );
  }
}
