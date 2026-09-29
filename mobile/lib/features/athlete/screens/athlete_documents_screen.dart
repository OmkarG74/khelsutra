import 'package:flutter/material.dart';
import '../../../app/theme/app_theme.dart';
import '../../../core/models/athlete_models.dart';
import '../../../core/widgets/common_widgets.dart';
import '../../../core/widgets/khelsutra_app_bar.dart';
import '../data/athlete_repository.dart';

class AthleteDocumentsScreen extends StatefulWidget {
  final bool isStandalone;
  final int? athleteId;

  const AthleteDocumentsScreen({
    super.key,
    this.isStandalone = true,
    this.athleteId,
  });

  @override
  State<AthleteDocumentsScreen> createState() => _AthleteDocumentsScreenState();
}

class _AthleteDocumentsScreenState extends State<AthleteDocumentsScreen> {
  final AthleteRepository _repo = AthleteRepository();

  bool _isLoading = true;
  String? _errorMessage;
  List<AthleteDocumentItem> _documents = [];

  @override
  void initState() {
    super.initState();
    _loadDocuments();
  }

  Future<void> _loadDocuments() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final docs = await _repo.getDocuments(athleteId: widget.athleteId);
      if (!mounted) return;
      setState(() {
        _documents = docs;
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
        title: 'Verified Documents',
        subtitle: 'Identification & sports certificates',
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
                onPressed: _loadDocuments,
                icon: const Icon(Icons.refresh),
                label: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    if (_documents.isEmpty) {
      return const Center(
        child: EmptyState(
          icon: Icons.description_outlined,
          title: 'No Documents Uploaded',
          description: 'No verified identification or federation certificates found for this athlete profile.',
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadDocuments,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _documents.length,
        itemBuilder: (context, index) {
          final doc = _documents[index];
          final isVerified = doc.isVerified;

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: AppTheme.primaryColor.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(Icons.badge_outlined, color: AppTheme.primaryColor, size: 24),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          doc.documentName,
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Type: ${doc.documentType}',
                          style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                        ),
                        if (doc.expiryDate != null) ...[
                          const SizedBox(height: 2),
                          Text(
                            'Expiry: ${doc.expiryDate}',
                            style: const TextStyle(fontSize: 12, color: AppTheme.textMuted),
                          ),
                        ],
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: isVerified
                          ? AppTheme.successColor.withValues(alpha: 0.12)
                          : Colors.orange.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          isVerified ? Icons.verified : Icons.hourglass_empty,
                          size: 14,
                          color: isVerified ? AppTheme.successColor : Colors.orange,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          doc.verificationStatus.toUpperCase(),
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                            color: isVerified ? AppTheme.successColor : Colors.orange,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
