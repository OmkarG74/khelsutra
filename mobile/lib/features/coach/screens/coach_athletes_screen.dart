import 'package:flutter/material.dart';
import '../../../app/theme/app_theme.dart';
import '../../../core/mock/mock_data.dart';
import '../../../core/models/coach_models.dart';
import '../../../core/widgets/common_widgets.dart';
import '../../../core/widgets/khelsutra_app_bar.dart';
import '../data/coach_repository.dart';
import '../widgets/coach_athlete_card.dart';
import 'coach_athlete_details_screen.dart';

class CoachAthletesScreen extends StatefulWidget {
  final bool isStandalone;
  final String initialFilter;
  final int? teamId;
  final String? teamName;

  const CoachAthletesScreen({
    super.key,
    this.isStandalone = false,
    this.initialFilter = 'All',
    this.teamId,
    this.teamName,
  });

  @override
  State<CoachAthletesScreen> createState() => _CoachAthletesScreenState();
}

class _CoachAthletesScreenState extends State<CoachAthletesScreen> {
  final CoachRepository _repo = CoachRepository();
  late String _selectedFilter;
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';

  bool _isLoading = true;
  String? _errorMessage;
  List<CoachRosterAthleteItem> _rosterAthletes = [];

  @override
  void initState() {
    super.initState();
    _selectedFilter = widget.initialFilter;
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final athletes = await _repo.getAthletes(teamId: widget.teamId);
      if (!mounted) return;
      setState(() {
        _rosterAthletes = athletes;
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
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final screenTitle = widget.teamName != null ? '${widget.teamName} Roster' : 'My Athletes';

    if (_isLoading) {
      return Scaffold(
        appBar: widget.isStandalone
            ? KhelSutraAppBar(
                title: screenTitle,
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
            ? KhelSutraAppBar(
                title: screenTitle,
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

    // Convert to CoachAthleteItem
    var athletes = _rosterAthletes.map((r) => CoachAthleteItem.fromItem(r)).toList();

    // Filter by tab
    if (_selectedFilter == 'Active') {
      athletes = athletes.where((a) => a.status.toLowerCase() == 'active').toList();
    } else if (_selectedFilter == 'Low Attendance') {
      athletes = athletes.where((a) => a.attendancePct < 80).toList();
    }

    // Filter by search query
    if (_searchQuery.isNotEmpty) {
      athletes = athletes.where((a) {
        return a.name.toLowerCase().contains(_searchQuery.toLowerCase()) ||
            a.event.toLowerCase().contains(_searchQuery.toLowerCase()) ||
            a.jerseyNo.toLowerCase().contains(_searchQuery.toLowerCase());
      }).toList();
    }

    return Scaffold(
      appBar: widget.isStandalone
          ? KhelSutraAppBar(
              title: screenTitle,
              subtitle: '${athletes.length} athlete(s) in roster',
              showBackButton: true,
            )
          : null,
      body: RefreshIndicator(
        onRefresh: _loadData,
        child: Column(
          children: [
            // Search & Filter Bar
            Container(
              padding: const EdgeInsets.all(16.0),
              color: Colors.white,
              child: Column(
                children: [
                  TextField(
                    controller: _searchController,
                    onChanged: (val) => setState(() => _searchQuery = val.trim()),
                    decoration: InputDecoration(
                      hintText: 'Search by athlete name, event, or jersey #...',
                      prefixIcon: const Icon(Icons.search, size: 20),
                      suffixIcon: _searchQuery.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear, size: 18),
                              onPressed: () {
                                _searchController.clear();
                                setState(() => _searchQuery = '');
                              },
                            )
                          : null,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    ),
                  ),
                  const SizedBox(height: 12),
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        _buildFilterChip('All', _rosterAthletes.length),
                        const SizedBox(width: 8),
                        _buildFilterChip(
                          'Active',
                          _rosterAthletes.where((a) => a.status.toLowerCase() == 'active').length,
                        ),
                        const SizedBox(width: 8),
                        _buildFilterChip(
                          'Low Attendance',
                          0, // Real dynamically calculated
                          isWarning: true,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),

            // Athlete Cards List
            Expanded(
              child: athletes.isEmpty
                  ? LayoutBuilder(
                      builder: (context, constraints) => SingleChildScrollView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        child: ConstrainedBox(
                          constraints: BoxConstraints(minHeight: constraints.maxHeight),
                          child: const Center(
                            child: EmptyState(
                              icon: Icons.person_search,
                              title: 'No Athletes Found',
                              description: 'No athletes match your search or filter criteria.',
                            ),
                          ),
                        ),
                      ),
                    )
                  : ListView.builder(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.all(16.0),
                      itemCount: athletes.length,
                      itemBuilder: (context, index) {
                        final athlete = athletes[index];
                        return CoachAthleteCard(
                          athlete: athlete,
                          onTap: () {
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) => CoachAthleteDetailsScreen(athlete: athlete),
                              ),
                            );
                          },
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterChip(String label, int count, {bool isWarning = false}) {
    final isSelected = _selectedFilter == label;
    final activeColor = isWarning ? AppTheme.warningColor : AppTheme.primaryColor;

    return ChoiceChip(
      label: Text('$label ($count)'),
      selected: isSelected,
      onSelected: (selected) {
        if (selected) setState(() => _selectedFilter = label);
      },
      selectedColor: activeColor.withValues(alpha: 0.12),
      labelStyle: TextStyle(
        fontSize: 12,
        fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
        color: isSelected ? activeColor : AppTheme.textSecondary,
      ),
      side: BorderSide(
        color: isSelected ? activeColor : AppTheme.borderColor,
      ),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
    );
  }
}
