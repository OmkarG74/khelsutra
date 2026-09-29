import 'package:flutter/material.dart';
import '../../../app/theme/app_theme.dart';
import '../../../core/models/athlete_models.dart';
import '../../../core/network/json_parser.dart';
import '../../../core/widgets/common_widgets.dart';
import '../../../core/widgets/khelsutra_app_bar.dart';
import '../data/athlete_repository.dart';

class AthleteLeaveScreen extends StatefulWidget {
  final bool isStandalone;

  const AthleteLeaveScreen({
    super.key,
    this.isStandalone = true,
  });

  @override
  State<AthleteLeaveScreen> createState() => _AthleteLeaveScreenState();
}

class _AthleteLeaveScreenState extends State<AthleteLeaveScreen> {
  final AthleteRepository _repo = AthleteRepository();

  bool _isLoading = true;
  String? _errorMessage;
  List<LeaveRequestItem> _leaveRequests = [];

  @override
  void initState() {
    super.initState();
    _loadRequests();
  }

  Future<void> _loadRequests() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final list = await _repo.getLeaveRequests();
      if (!mounted) return;
      setState(() {
        _leaveRequests = list;
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

  void _openApplyLeave() async {
    final res = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _ApplyLeaveSheet(repo: _repo),
    );

    if (res == true) {
      _loadRequests();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: KhelSutraAppBar(
        title: 'Leave & Absences',
        subtitle: 'Official leave requests & approvals',
        showBackButton: widget.isStandalone,
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openApplyLeave,
        backgroundColor: AppTheme.primaryColor,
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text('Apply Leave', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
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
                onPressed: _loadRequests,
                icon: const Icon(Icons.refresh),
                label: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    if (_leaveRequests.isEmpty) {
      return const Center(
        child: EmptyState(
          icon: Icons.event_busy,
          title: 'No Leave Applications',
          description: 'You have not submitted any training leave requests. Tap "Apply Leave" to request time off.',
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadRequests,
      child: ListView.builder(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 80),
        itemCount: _leaveRequests.length,
        itemBuilder: (context, index) {
          final req = _leaveRequests[index];
          Color statusColor = Colors.orange;
          if (req.status == 'approved') statusColor = AppTheme.successColor;
          if (req.status == 'rejected') statusColor = AppTheme.dangerColor;

          return Card(
            margin: const EdgeInsets.only(bottom: 12),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        req.leaveTypeName,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: statusColor.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          req.status.toUpperCase(),
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                            color: statusColor,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      const Icon(Icons.date_range, size: 14, color: AppTheme.textMuted),
                      const SizedBox(width: 6),
                      Text(
                        '${req.startDate} to ${req.endDate} (${req.totalDays} day${req.totalDays == 1 ? '' : 's'})',
                        style: const TextStyle(fontSize: 13, color: AppTheme.textColor),
                      ),
                    ],
                  ),
                  if (req.reason != null && req.reason!.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Text(
                      'Reason: ${req.reason}',
                      style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                    ),
                  ],
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

class _ApplyLeaveSheet extends StatefulWidget {
  final AthleteRepository repo;

  const _ApplyLeaveSheet({required this.repo});

  @override
  State<_ApplyLeaveSheet> createState() => _ApplyLeaveSheetState();
}

class _ApplyLeaveSheetState extends State<_ApplyLeaveSheet> {
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = true;
  bool _isSubmitting = false;

  List<Map<String, dynamic>> _types = [];
  int? _selectedTypeId;
  final _startDateController = TextEditingController();
  final _endDateController = TextEditingController();
  final _reasonController = TextEditingController();

  @override
  void initState() {
    super.initState();
    final today = DateTime.now().toIso8601String().split('T').first;
    _startDateController.text = today;
    _endDateController.text = today;
    _fetchTypes();
  }

  Future<void> _fetchTypes() async {
    try {
      final types = await widget.repo.getLeaveTypes();
      if (!mounted) return;
      setState(() {
        _types = types;
        if (_types.isNotEmpty) {
          _selectedTypeId = parseInt(types.first['id']);
        }
        _isLoading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  void dispose() {
    _startDateController.dispose();
    _endDateController.dispose();
    _reasonController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedTypeId == null) return;

    setState(() => _isSubmitting = true);

    try {
      await widget.repo.applyLeave(
        leaveTypeId: _selectedTypeId!,
        startDate: _startDateController.text.trim(),
        endDate: _endDateController.text.trim(),
        reason: _reasonController.text.trim(),
      );

      if (!mounted) return;
      setState(() => _isSubmitting = false);

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Leave application submitted successfully!'),
          backgroundColor: AppTheme.successColor,
        ),
      );
      Navigator.pop(context, true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _isSubmitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to submit leave: ${e.toString().replaceAll("Exception: ", "")}'),
          backgroundColor: AppTheme.dangerColor,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;

    return Container(
      padding: EdgeInsets.fromLTRB(20, 20, 20, 20 + bottomInset),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: SingleChildScrollView(
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Apply for Training Leave', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                  IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                ],
              ),
              const SizedBox(height: 16),
              if (_isLoading)
                const Center(child: CircularProgressIndicator())
              else ...[
                DropdownButtonFormField<int>(
                  initialValue: _selectedTypeId,
                  decoration: const InputDecoration(labelText: 'Leave Category *'),
                  items: _types.map((t) {
                    return DropdownMenuItem<int>(
                      value: parseInt(t['id']),
                      child: Text(t['name']?.toString() ?? 'Leave'),
                    );
                  }).toList(),
                  onChanged: (val) {
                    if (val != null) setState(() => _selectedTypeId = val);
                  },
                ),
                const SizedBox(height: 14),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _startDateController,
                        decoration: const InputDecoration(
                          labelText: 'Start Date (YYYY-MM-DD) *',
                          prefixIcon: Icon(Icons.calendar_today, size: 18),
                        ),
                        validator: (v) => (v == null || v.isEmpty) ? 'Required' : null,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: TextFormField(
                        controller: _endDateController,
                        decoration: const InputDecoration(
                          labelText: 'End Date (YYYY-MM-DD) *',
                          prefixIcon: Icon(Icons.calendar_today, size: 18),
                        ),
                        validator: (v) => (v == null || v.isEmpty) ? 'Required' : null,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _reasonController,
                  maxLines: 2,
                  decoration: const InputDecoration(
                    labelText: 'Reason for Absence',
                    hintText: 'Examinations, family emergency, academic schedule...',
                  ),
                ),
                const SizedBox(height: 20),
                PrimaryButton(
                  label: 'Submit Leave Request',
                  icon: Icons.send,
                  isLoading: _isSubmitting,
                  onPressed: _submit,
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
