import 'package:flutter/material.dart';
import '../../../app/theme/app_theme.dart';
import '../../../core/mock/mock_data.dart';
import '../../../core/models/athlete_models.dart';
import '../../../core/widgets/common_widgets.dart';
import '../../../core/widgets/khelsutra_app_bar.dart';
import '../data/athlete_repository.dart';

class AthleteNotificationsScreen extends StatefulWidget {
  final bool isStandalone;

  const AthleteNotificationsScreen({
    super.key,
    this.isStandalone = false,
  });

  @override
  State<AthleteNotificationsScreen> createState() =>
      _AthleteNotificationsScreenState();
}

class _AthleteNotificationsScreenState
    extends State<AthleteNotificationsScreen> {
  final AthleteRepository _repository = AthleteRepository();

  bool _isLoading = true;
  String? _errorMessage;
  List<NotificationItemModel> _notifications = [];

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
      final items = await _repository.getNotifications();
      if (!mounted) return;
      setState(() {
        _notifications = items;
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

  void _markAllAsRead() async {
    for (var item in _notifications) {
      if (!item.isRead) {
        _repository.markNotificationRead(item.id);
      }
    }
    setState(() {
      _notifications = _notifications.map((n) {
        return NotificationItemModel(
          id: n.id,
          title: n.title,
          message: n.message,
          type: n.type,
          isRead: true,
          createdAt: n.createdAt,
        );
      }).toList();
    });
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('All notifications marked as read')),
    );
  }

  void _markAsRead(NotificationItemModel notif) async {
    if (!notif.isRead) {
      await _repository.markNotificationRead(notif.id);
      setState(() {
        final index = _notifications.indexWhere((n) => n.id == notif.id);
        if (index != -1) {
          _notifications[index] = NotificationItemModel(
            id: notif.id,
            title: notif.title,
            message: notif.message,
            type: notif.type,
            isRead: true,
            createdAt: notif.createdAt,
          );
        }
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        appBar: widget.isStandalone
            ? const KhelSutraAppBar(
                title: 'Notifications',
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
                title: 'Notifications',
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

    final appNotifications = _notifications.map((n) => AppNotificationItem.fromItem(n)).toList();

    return Scaffold(
      appBar: widget.isStandalone
          ? KhelSutraAppBar(
              title: 'Notifications',
              showBackButton: true,
              actions: [
                if (appNotifications.any((n) => !n.isRead))
                  TextButton(
                    onPressed: _markAllAsRead,
                    child: const Text(
                      'Mark all read',
                      style: TextStyle(color: Colors.white, fontSize: 13),
                    ),
                  ),
              ],
            )
          : null,
      body: RefreshIndicator(
        onRefresh: _loadData,
        child: appNotifications.isEmpty
            ? LayoutBuilder(
                builder: (context, constraints) => SingleChildScrollView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  child: ConstrainedBox(
                    constraints: BoxConstraints(minHeight: constraints.maxHeight),
                    child: const Center(
                      child: EmptyState(
                        icon: Icons.notifications_none,
                        title: 'No Notifications',
                        description: 'You are all caught up with your training and team alerts.',
                      ),
                    ),
                  ),
                ),
              )
            : ListView.builder(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16.0),
                itemCount: appNotifications.length,
                itemBuilder: (context, index) {
                  final notif = appNotifications[index];
                  final originalModel = _notifications[index];

                  return Card(
                    margin: const EdgeInsets.only(bottom: 10),
                    color: notif.isRead ? Colors.white : const Color(0xFFF0F7FF),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
                      side: BorderSide(
                        color: notif.isRead
                            ? AppTheme.borderColor
                            : AppTheme.primaryLight.withValues(alpha: 0.3),
                      ),
                    ),
                    child: InkWell(
                      onTap: () => _markAsRead(originalModel),
                      borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
                      child: Padding(
                        padding: const EdgeInsets.all(14.0),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Container(
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: notif.iconColor.withValues(alpha: 0.12),
                                shape: BoxShape.circle,
                              ),
                              child: Icon(notif.icon, color: notif.iconColor, size: 22),
                            ),
                            const SizedBox(width: 14),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Expanded(
                                        child: Text(
                                          notif.title,
                                          style: TextStyle(
                                            fontSize: 14,
                                            fontWeight: notif.isRead
                                                ? FontWeight.w600
                                                : FontWeight.bold,
                                            color: AppTheme.textColor,
                                          ),
                                        ),
                                      ),
                                      Text(
                                        notif.timeAgo,
                                        style: const TextStyle(
                                          fontSize: 11,
                                          color: AppTheme.textMuted,
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    notif.message,
                                    style: TextStyle(
                                      fontSize: 13,
                                      color: notif.isRead
                                          ? AppTheme.textSecondary
                                          : AppTheme.textColor,
                                      height: 1.35,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            if (!notif.isRead) ...[
                              const SizedBox(width: 8),
                              Container(
                                width: 8,
                                height: 8,
                                margin: const EdgeInsets.only(top: 4),
                                decoration: const BoxDecoration(
                                  color: AppTheme.primaryLight,
                                  shape: BoxShape.circle,
                                ),
                              ),
                            ],
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
}
