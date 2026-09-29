import 'package:flutter/material.dart';
import '../../../app/theme/app_theme.dart';
import '../../../core/widgets/khelsutra_app_bar.dart';
import '../../../core/widgets/section_header.dart';
import '../data/athlete_repository.dart';

class AthleteMedicalScreen extends StatefulWidget {
  final bool isStandalone;
  final int? athleteId;

  const AthleteMedicalScreen({
    super.key,
    this.isStandalone = true,
    this.athleteId,
  });

  @override
  State<AthleteMedicalScreen> createState() => _AthleteMedicalScreenState();
}

class _AthleteMedicalScreenState extends State<AthleteMedicalScreen> {
  final AthleteRepository _repo = AthleteRepository();

  bool _isLoading = true;
  String? _errorMessage;
  String _fitnessStatus = 'fit';
  List<dynamic> _injuries = [];
  List<dynamic> _clearances = [];

  @override
  void initState() {
    super.initState();
    _loadMedical();
  }

  Future<void> _loadMedical() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final data = await _repo.getMedical(athleteId: widget.athleteId);
      if (!mounted) return;
      setState(() {
        _fitnessStatus = (data['fitness_status'] as String?) ?? 'fit';
        _injuries = (data['injuries'] as List<dynamic>?) ?? [];
        _clearances = (data['clearances'] as List<dynamic>?) ?? [];
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
    return Scaffold(
      appBar: KhelSutraAppBar(
        title: 'Medical & Fitness',
        subtitle: 'Physical status & clearances',
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
                onPressed: _loadMedical,
                icon: const Icon(Icons.refresh),
                label: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    Color bannerColor = AppTheme.successColor;
    String bannerText = 'CLEAR FOR FULL COMPETITIVE TRAINING';
    IconData bannerIcon = Icons.check_circle_outline;

    if (_fitnessStatus == 'unfit') {
      bannerColor = AppTheme.dangerColor;
      bannerText = 'TEMPORARILY UNFIT — MEDICAL REST REQUIRED';
      bannerIcon = Icons.warning_amber_outlined;
    } else if (_fitnessStatus == 'fit_with_restrictions') {
      bannerColor = Colors.orange;
      bannerText = 'FIT WITH TRAINING RESTRICTIONS';
      bannerIcon = Icons.info_outline;
    }

    return RefreshIndicator(
      onRefresh: _loadMedical,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Fitness Status Banner
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: bannerColor.withValues(alpha: 0.1),
                border: Border.all(color: bannerColor.withValues(alpha: 0.3)),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                children: [
                  Icon(bannerIcon, color: bannerColor, size: 28),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Current Fitness Assessment',
                          style: TextStyle(fontSize: 12, color: AppTheme.textSecondary, fontWeight: FontWeight.w600),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          bannerText,
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.bold,
                            color: bannerColor,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Injuries Section
            const SectionHeader(
              title: 'Injury & Recovery Log',
              subtitle: 'Reported athletic injuries',
            ),
            const SizedBox(height: 8),

            if (_injuries.isEmpty)
              const Card(
                child: Padding(
                  padding: EdgeInsets.all(16.0),
                  child: Row(
                    children: [
                      Icon(Icons.health_and_safety_outlined, color: AppTheme.successColor),
                      SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          'No injury records logged. Athlete is in peak condition.',
                          style: TextStyle(fontSize: 13, color: AppTheme.textSecondary),
                        ),
                      ),
                    ],
                  ),
                ),
              )
            else
              ..._injuries.map((inj) {
                final status = (inj['status'] ?? 'recovered').toString();
                final isActive = status == 'active' || status == 'recovering';

                return Card(
                  margin: const EdgeInsets.only(bottom: 10),
                  child: Padding(
                    padding: const EdgeInsets.all(14),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              inj['injury_type'] ?? 'Injury',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: isActive
                                    ? AppTheme.dangerColor.withValues(alpha: 0.12)
                                    : AppTheme.successColor.withValues(alpha: 0.12),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                status.toUpperCase(),
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.bold,
                                  color: isActive ? AppTheme.dangerColor : AppTheme.successColor,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 6),
                        Text(
                          'Date: ${inj['injury_date'] ?? ""} • Part: ${inj['body_part'] ?? "N/A"} • Severity: ${inj['severity'] ?? "N/A"}',
                          style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                        ),
                        if (inj['treatment'] != null && inj['treatment'].toString().isNotEmpty) ...[
                          const SizedBox(height: 6),
                          Text(
                            'Treatment: ${inj['treatment']}',
                            style: const TextStyle(fontSize: 12, color: AppTheme.textColor),
                          ),
                        ],
                      ],
                    ),
                  ),
                );
              }),

            const SizedBox(height: 20),

            // Clearances Section
            const SectionHeader(
              title: 'Medical Clearances',
              subtitle: 'Certified physician validations',
            ),
            const SizedBox(height: 8),

            if (_clearances.isEmpty)
              const Card(
                child: Padding(
                  padding: EdgeInsets.all(16.0),
                  child: Text(
                    'No physician clearance certificates on file.',
                    style: TextStyle(fontSize: 13, color: AppTheme.textMuted),
                  ),
                ),
              )
            else
              ..._clearances.map((c) {
                return Card(
                  margin: const EdgeInsets.only(bottom: 10),
                  child: ListTile(
                    leading: const Icon(Icons.verified_user_outlined, color: AppTheme.primaryColor),
                    title: Text(
                      'Clearance: ${(c['clearance_status'] ?? '').toString().toUpperCase()}',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                    ),
                    subtitle: Text(
                      'Issued on ${c['clearance_date'] ?? ""}${c['doctor_name'] != null ? " by ${c['doctor_name']}" : ""}',
                      style: const TextStyle(fontSize: 12),
                    ),
                    trailing: c['valid_until'] != null
                        ? Text('Valid: ${c['valid_until']}', style: const TextStyle(fontSize: 11, color: AppTheme.textMuted))
                        : null,
                  ),
                );
              }),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }
}
