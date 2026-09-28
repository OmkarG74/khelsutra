import 'package:flutter/material.dart';
import '../../../app/theme/app_theme.dart';
import '../../../core/mock/mock_data.dart';
import '../../../core/widgets/common_widgets.dart';
import '../../../core/widgets/khelsutra_app_bar.dart';
import '../data/athlete_repository.dart';
import '../widgets/training_card.dart';
import 'athlete_training_details_screen.dart';

class AthleteTrainingScreen extends StatefulWidget {
  final bool isStandalone;

  const AthleteTrainingScreen({
    super.key,
    this.isStandalone = false,
  });

  @override
  State<AthleteTrainingScreen> createState() => _AthleteTrainingScreenState();
}

class _AthleteTrainingScreenState extends State<AthleteTrainingScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final AthleteRepository _repository = AthleteRepository();

  bool _isLoading = true;
  String? _errorMessage;
  List<TrainingSession> _sessions = [];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final items = await _repository.getTrainingSessions();
      if (!mounted) return;
      setState(() {
        _sessions = items.map((item) => TrainingSession.fromItem(item)).toList();
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
    if (_isLoading) {
      return Scaffold(
        appBar: widget.isStandalone
            ? const KhelSutraAppBar(
                title: 'Training Schedule',
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
                title: 'Training Schedule',
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

    final todayTrainings = _sessions.where((s) => s.isToday).toList();
    final upcomingTrainings = _sessions.where((s) => s.isUpcoming).toList();
    final completedTrainings = _sessions.where((s) => s.isCompleted).toList();

    return Scaffold(
      appBar: widget.isStandalone
          ? const KhelSutraAppBar(
              title: 'Training Schedule',
              showBackButton: true,
            )
          : null,
      body: Column(
        children: [
          Container(
            color: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Container(
              height: 40,
              decoration: BoxDecoration(
                color: AppTheme.surfaceColor,
                borderRadius: BorderRadius.circular(AppTheme.radiusSmall),
                border: Border.all(color: AppTheme.borderColor),
              ),
              child: TabBar(
                controller: _tabController,
                indicator: BoxDecoration(
                  borderRadius: BorderRadius.circular(6),
                  color: AppTheme.primaryColor,
                ),
                indicatorSize: TabBarIndicatorSize.tab,
                labelColor: Colors.white,
                unselectedLabelColor: AppTheme.textSecondary,
                labelStyle: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                ),
                unselectedLabelStyle: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w500,
                ),
                tabs: [
                  Tab(text: 'Today (${todayTrainings.length})'),
                  Tab(text: 'Upcoming (${upcomingTrainings.length})'),
                  Tab(text: 'Completed (${completedTrainings.length})'),
                ],
              ),
            ),
          ),
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                _buildTrainingList(todayTrainings, 'No training scheduled for today'),
                _buildTrainingList(upcomingTrainings, 'No upcoming training sessions'),
                _buildTrainingList(completedTrainings, 'No completed training sessions'),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTrainingList(List<TrainingSession> sessions, String emptyMsg) {
    if (sessions.isEmpty) {
      return RefreshIndicator(
        onRefresh: _loadData,
        child: LayoutBuilder(
          builder: (context, constraints) => SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            child: ConstrainedBox(
              constraints: BoxConstraints(minHeight: constraints.maxHeight),
              child: Center(
                child: EmptyState(
                  icon: Icons.fitness_center_outlined,
                  title: 'No Sessions',
                  description: emptyMsg,
                ),
              ),
            ),
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadData,
      child: ListView.builder(
        padding: const EdgeInsets.all(16.0),
        itemCount: sessions.length,
        itemBuilder: (context, index) {
          final session = sessions[index];
          return TrainingCard(
            session: session,
            onTap: () {
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => AthleteTrainingDetailsScreen(session: session),
                ),
              );
            },
          );
        },
      ),
    );
  }
}
