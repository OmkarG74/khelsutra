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
  bool _isLoadingMetrics = false;
  bool _isSaving = false;
  String? _errorMessage;

  List<CoachRosterAthleteItem> _athletes = [];
  List<PerformanceMetricItem> _metrics = [];

  int? _selectedAthleteId;
  int? _selectedMetricId;

  final _valueController = TextEditingController();
  final _scoreController = TextEditingController(text: '8.0');
  final _dateController = TextEditingController(
    text: DateTime.now().toIso8601String().split('T').first,
  );
  final _notesController = TextEditingController();

  CoachRosterAthleteItem? get _selectedAthlete {
    if (_selectedAthleteId == null) return null;
    for (final a in _athletes) {
      if (a.id == _selectedAthleteId) return a;
    }
    return null;
  }

  PerformanceMetricItem? get _selectedMetric {
    if (_selectedMetricId == null) return null;
    for (final m in _metrics) {
      if (m.id == _selectedMetricId) return m;
    }
    return null;
  }

  @override
  void initState() {
    super.initState();
    _loadInitialData();
  }

  Future<void> _loadInitialData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
      _selectedAthleteId = null;
      _selectedMetricId = null;
    });

    try {
      final rawAthletes = await _repo.getAthletes();
      if (!mounted) return;

      // 1. Deduplicate athletes by ID and filter out invalid records
      final athleteMap = <int, CoachRosterAthleteItem>{};
      for (final a in rawAthletes) {
        if (a.id > 0 && !athleteMap.containsKey(a.id)) {
          athleteMap[a.id] = a;
        }
      }
      final cleanAthletes = athleteMap.values.toList();

      // 2. Resolve initial/selected athlete safely
      final targetInitialId = int.tryParse(widget.initialAthleteId ?? '');
      int? initialAthleteId;
      CoachRosterAthleteItem? initialAthlete;

      if (targetInitialId != null && cleanAthletes.any((a) => a.id == targetInitialId)) {
        initialAthleteId = targetInitialId;
        initialAthlete = cleanAthletes.firstWhere((a) => a.id == targetInitialId);
      } else if (cleanAthletes.isNotEmpty) {
        initialAthleteId = cleanAthletes.first.id;
        initialAthlete = cleanAthletes.first;
      }

      // 3. Load sport-aware metrics based on the initial athlete's sport
      List<PerformanceMetricItem> cleanMetrics = [];
      int? initialMetricId;
      if (initialAthlete != null) {
        final rawMetrics = await _repo.getPerformanceMetrics(initialAthlete.sportId);
        final metricMap = <int, PerformanceMetricItem>{};
        for (final m in rawMetrics) {
          if (m.id > 0 && m.name.trim().isNotEmpty && !metricMap.containsKey(m.id)) {
            metricMap[m.id] = m;
          }
        }
        cleanMetrics = metricMap.values.toList();
        if (cleanMetrics.isNotEmpty) {
          initialMetricId = cleanMetrics.first.id;
        }
      }

      if (!mounted) return;
      setState(() {
        _athletes = cleanAthletes;
        _metrics = cleanMetrics;
        _selectedAthleteId = initialAthleteId;
        _selectedMetricId = initialMetricId;
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

  Future<void> _loadMetricsForSport(int? sportId) async {
    setState(() => _isLoadingMetrics = true);

    try {
      final rawMetrics = await _repo.getPerformanceMetrics(sportId);
      if (!mounted) return;

      final metricMap = <int, PerformanceMetricItem>{};
      for (final m in rawMetrics) {
        if (m.id > 0 && m.name.trim().isNotEmpty && !metricMap.containsKey(m.id)) {
          metricMap[m.id] = m;
        }
      }
      final cleanMetrics = metricMap.values.toList();

      // Reset selected metric or pick first applicable metric for this sport
      int? newMetricId;
      if (cleanMetrics.isNotEmpty) {
        newMetricId = cleanMetrics.first.id;
      }

      setState(() {
        _metrics = cleanMetrics;
        _selectedMetricId = newMetricId;
        _isLoadingMetrics = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _isLoadingMetrics = false);
    }
  }

  void _onAthleteChanged(int newAthleteId) {
    if (_selectedAthleteId == newAthleteId) return;

    CoachRosterAthleteItem? newAthlete;
    for (final a in _athletes) {
      if (a.id == newAthleteId) {
        newAthlete = a;
        break;
      }
    }

    setState(() {
      _selectedAthleteId = newAthleteId;
      _selectedMetricId = null; // Clear previous metric selection immediately
    });

    if (newAthlete != null) {
      _loadMetricsForSport(newAthlete.sportId);
    }
  }

  @override
  void dispose() {
    _valueController.dispose();
    _scoreController.dispose();
    _dateController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  void _handleSave() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedAthleteId == null || _selectedMetricId == null || _selectedMetric == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select an athlete and metric.')),
      );
      return;
    }

    final score = double.tryParse(_scoreController.text.trim());
    if (score == null || score < 0 || score > 10) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Training Score must be between 0 and 10.')),
      );
      return;
    }

    setState(() => _isSaving = true);

    try {
      final athlete = _selectedAthlete!;
      final metric = _selectedMetric!;
      final numericVal = double.tryParse(_valueController.text.trim());

      await _repo.recordPerformance(
        athleteId: athlete.id,
        sportId: athlete.sportId,
        teamId: athlete.teamId,
        evaluationDate: _dateController.text.trim(),
        trainingScore: score,
        overallRating: score,
        coachRemarks: _notesController.text.trim().isNotEmpty
            ? _notesController.text.trim()
            : 'Performance evaluation recorded by coach.',
        values: [
          {
            'metric_id': metric.id,
            'numeric_value': numericVal,
            'text_value': numericVal == null ? _valueController.text.trim() : null,
          }
        ],
        metrics: [
          {
            'metric_id': metric.id,
            'numeric_value': numericVal,
            'text_value': numericVal == null ? _valueController.text.trim() : null,
          }
        ],
      );

      if (!mounted) return;
      setState(() => _isSaving = false);

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Performance recorded for ${metric.name} (Score: ${score.toStringAsFixed(1)})!'),
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
                  onPressed: _loadInitialData,
                  icon: const Icon(Icons.refresh),
                  label: const Text('Retry'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    final validSelectedAthlete = (_selectedAthleteId != null &&
            _athletes.any((a) => a.id == _selectedAthleteId))
        ? _selectedAthleteId
        : null;

    final validSelectedMetric = (_selectedMetricId != null &&
            _metrics.any((m) => m.id == _selectedMetricId))
        ? _selectedMetricId
        : null;

    final athlete = _selectedAthlete;
    final metric = _selectedMetric;

    return Scaffold(
      appBar: const KhelSutraAppBar(
        title: 'Record Performance',
        subtitle: 'Log physical metrics and training score',
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
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'Select Athlete',
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.bold,
                              color: AppTheme.textColor,
                            ),
                          ),
                          if (athlete != null)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: AppTheme.primaryColor.withValues(alpha: 0.1),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                athlete.sportName,
                                style: const TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.bold,
                                  color: AppTheme.primaryColor,
                                ),
                              ),
                            ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      if (_athletes.isEmpty)
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.amber.shade50,
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: Colors.amber.shade300),
                          ),
                          child: const Row(
                            children: [
                              Icon(Icons.info_outline, color: Colors.orange, size: 20),
                              SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  'No athletes found in assigned teams.',
                                  style: TextStyle(color: Colors.black87, fontSize: 13),
                                ),
                              ),
                            ],
                          ),
                        )
                      else
                        DropdownButtonFormField<int>(
                          key: ValueKey('athlete_${validSelectedAthlete ?? 0}'),
                          initialValue: validSelectedAthlete,
                          decoration: const InputDecoration(
                            labelText: 'Athlete *',
                            prefixIcon: Icon(Icons.person_outline),
                          ),
                          items: _athletes.map((a) {
                            return DropdownMenuItem<int>(
                              value: a.id,
                              child: Text(
                                '${a.fullName} (${a.sportName})',
                                overflow: TextOverflow.ellipsis,
                              ),
                            );
                          }).toList(),
                          onChanged: (val) {
                            if (val != null) {
                              _onAthleteChanged(val);
                            }
                          },
                          validator: (val) {
                            if (val == null) return 'Please select an athlete';
                            return null;
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
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'Assessment Metric',
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.bold,
                              color: AppTheme.textColor,
                            ),
                          ),
                          if (_isLoadingMetrics)
                            const SizedBox(
                              width: 14,
                              height: 14,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      if (_metrics.isEmpty && !_isLoadingMetrics)
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.amber.shade50,
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: Colors.amber.shade300),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.info_outline, color: Colors.orange, size: 20),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  athlete != null
                                      ? 'No performance metrics available for ${athlete.sportName}.'
                                      : 'No performance metrics available.',
                                  style: const TextStyle(color: Colors.black87, fontSize: 13),
                                ),
                              ),
                            ],
                          ),
                        )
                      else
                        DropdownButtonFormField<int>(
                          key: ValueKey('metric_${validSelectedMetric ?? 0}_${athlete?.sportId ?? 0}'),
                          initialValue: validSelectedMetric,
                          decoration: InputDecoration(
                            labelText: 'Performance Metric *',
                            prefixIcon: const Icon(Icons.speed),
                            helperText: athlete != null
                                ? 'Showing metrics for ${athlete.sportName} & common athletic tests'
                                : null,
                          ),
                          items: _metrics.map((m) {
                            final unitStr = (m.unit != null && m.unit!.isNotEmpty) ? ' (${m.unit})' : '';
                            return DropdownMenuItem<int>(
                              value: m.id,
                              child: Text(
                                '${m.name}$unitStr',
                                overflow: TextOverflow.ellipsis,
                              ),
                            );
                          }).toList(),
                          onChanged: (val) {
                            if (val != null) {
                              setState(() => _selectedMetricId = val);
                            }
                          },
                          validator: (val) {
                            if (val == null) return 'Please select a metric';
                            return null;
                          },
                        ),
                      if (metric?.description != null && metric!.description!.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Text(
                          metric.description!,
                          style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary, fontStyle: FontStyle.italic),
                        ),
                      ],
                      const SizedBox(height: 14),

                      // Result Value & Training Score Fields
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(
                            child: TextFormField(
                              controller: _valueController,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              decoration: InputDecoration(
                                labelText: 'Result Value *',
                                hintText: 'Measured test result',
                                suffixText: metric?.unit ?? '',
                                prefixIcon: const Icon(Icons.timer_outlined),
                              ),
                              validator: (val) {
                                if (val == null || val.trim().isEmpty) {
                                  return 'Enter result value';
                                }
                                return null;
                              },
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: TextFormField(
                              controller: _scoreController,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              decoration: const InputDecoration(
                                labelText: 'Training Score (0 - 10) *',
                                hintText: 'Score for this session',
                                helperText: 'Session performance score',
                                prefixIcon: Icon(Icons.star_outline),
                              ),
                              validator: (val) {
                                if (val == null || val.trim().isEmpty) {
                                  return 'Enter score';
                                }
                                final num = double.tryParse(val.trim());
                                if (num == null) {
                                  return 'Invalid number';
                                }
                                if (num < 0 || num > 10) {
                                  return 'Must be 0 - 10';
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
                onPressed: (_athletes.isEmpty || _metrics.isEmpty) ? null : _handleSave,
              ),
              const SizedBox(height: 16),
            ],
          ),
        ),
      ),
    );
  }
}
