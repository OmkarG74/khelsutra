import 'package:flutter/material.dart';
import '../../../app/theme/app_theme.dart';
import '../../../core/mock/mock_data.dart';
import '../../../core/models/athlete_models.dart';
import '../../../core/widgets/common_widgets.dart';
import '../../../core/widgets/khelsutra_app_bar.dart';
import '../data/athlete_repository.dart';

class AthleteAchievementsScreen extends StatefulWidget {
  final bool isStandalone;
  final int? athleteId;

  const AthleteAchievementsScreen({
    super.key,
    this.isStandalone = true,
    this.athleteId,
  });

  @override
  State<AthleteAchievementsScreen> createState() => _AthleteAchievementsScreenState();
}

class _AthleteAchievementsScreenState extends State<AthleteAchievementsScreen> {
  final AthleteRepository _repository = AthleteRepository();

  bool _isLoading = true;
  String? _errorMessage;
  List<AchievementItemModel> _achievements = [];

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
      final items = await _repository.getAchievements(athleteId: widget.athleteId);
      if (!mounted) return;
      setState(() {
        _achievements = items;
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
                title: 'Achievements & Honors',
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
                title: 'Achievements & Honors',
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

    final achievementItems = _achievements.map((item) => AchievementItem.fromItem(item)).toList();

    return Scaffold(
      appBar: widget.isStandalone
          ? const KhelSutraAppBar(
              title: 'Achievements & Honors',
              showBackButton: true,
            )
          : null,
      body: RefreshIndicator(
        onRefresh: _loadData,
        child: achievementItems.isEmpty
            ? LayoutBuilder(
                builder: (context, constraints) => SingleChildScrollView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  child: ConstrainedBox(
                    constraints: BoxConstraints(minHeight: constraints.maxHeight),
                    child: const Center(
                      child: EmptyState(
                        icon: Icons.emoji_events_outlined,
                        title: 'No Achievements Found',
                        description: 'Your competitive tournament medals, trophies, and certifications will appear here.',
                      ),
                    ),
                  ),
                ),
              )
            : ListView.builder(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16.0),
                itemCount: achievementItems.length,
                itemBuilder: (context, index) {
                  final item = achievementItems[index];
                  final medalColor = _getMedalColor(item.medal);

                  return Card(
                    margin: const EdgeInsets.only(bottom: 14),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
                      side: const BorderSide(color: AppTheme.borderColor),
                    ),
                    child: InkWell(
                      onTap: () => _showAchievementDetails(context, item),
                      borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
                      child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Container(
                              width: 50,
                              height: 50,
                              decoration: BoxDecoration(
                                color: medalColor.withValues(alpha: 0.15),
                                shape: BoxShape.circle,
                                border: Border.all(color: medalColor.withValues(alpha: 0.4), width: 1.5),
                              ),
                              child: Icon(
                                item.medal == 'Trophy' ? Icons.emoji_events : Icons.military_tech,
                                color: medalColor,
                                size: 28,
                              ),
                            ),
                            const SizedBox(width: 14),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                        decoration: BoxDecoration(
                                          color: medalColor.withValues(alpha: 0.12),
                                          borderRadius: BorderRadius.circular(4),
                                        ),
                                        child: Text(
                                          item.medal.toUpperCase(),
                                          style: TextStyle(
                                            fontSize: 10,
                                            fontWeight: FontWeight.bold,
                                            color: medalColor,
                                          ),
                                        ),
                                      ),
                                      Text(
                                        item.date,
                                        style: const TextStyle(
                                          fontSize: 12,
                                          color: AppTheme.textMuted,
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 6),
                                  Text(
                                    item.title,
                                    style: const TextStyle(
                                      fontSize: 15,
                                      fontWeight: FontWeight.bold,
                                      color: AppTheme.textColor,
                                    ),
                                  ),
                                  if (item.competition.isNotEmpty) ...[
                                    const SizedBox(height: 4),
                                    Text(
                                      item.competition,
                                      style: const TextStyle(
                                        fontSize: 13,
                                        color: AppTheme.textSecondary,
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                },
              ),
      ),
    );
  }

  Color _getMedalColor(String medal) {
    switch (medal.toLowerCase()) {
      case 'gold':
        return const Color(0xFFD97706); // Rich Gold
      case 'silver':
        return const Color(0xFF64748B); // Silver Slate
      case 'bronze':
        return const Color(0xFFB45309); // Bronze Amber
      case 'trophy':
        return const Color(0xFF2563EB); // Trophy Blue
      default:
        return AppTheme.accentColor;
    }
  }

  void _showAchievementDetails(BuildContext context, AchievementItem item) {
    final medalColor = _getMedalColor(item.medal);

    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(AppTheme.radiusLarge)),
      ),
      builder: (context) {
        return Padding(
          padding: const EdgeInsets.all(22.0),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: AppTheme.borderColor,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 20),
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: medalColor.withValues(alpha: 0.15),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      item.medal == 'Trophy' ? Icons.emoji_events : Icons.military_tech,
                      color: medalColor,
                      size: 32,
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          item.title,
                          style: const TextStyle(
                            fontSize: 17,
                            fontWeight: FontWeight.bold,
                            color: AppTheme.textColor,
                          ),
                        ),
                        if (item.competition.isNotEmpty) ...[
                          const SizedBox(height: 2),
                          Text(
                            item.competition,
                            style: const TextStyle(
                              fontSize: 13,
                              color: AppTheme.textSecondary,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              const Divider(height: 1),
              const SizedBox(height: 16),
              const Text(
                'Achievement Summary',
                style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 6),
              Text(
                item.description.isNotEmpty ? item.description : 'Official tournament honor recorded on athlete permanent record.',
                style: const TextStyle(
                  fontSize: 14,
                  color: AppTheme.textColor,
                  height: 1.5,
                ),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  const Icon(Icons.event, size: 16, color: AppTheme.textMuted),
                  const SizedBox(width: 6),
                  Text(
                    'Recorded Date: ${item.date}',
                    style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                  ),
                ],
              ),
              const SizedBox(height: 20),
            ],
          ),
        );
      },
    );
  }
}
