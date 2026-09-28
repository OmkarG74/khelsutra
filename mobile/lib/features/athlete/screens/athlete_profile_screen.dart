import 'package:flutter/material.dart';
import '../../../app/theme/app_theme.dart';
import '../../../core/models/athlete_models.dart';
import '../../auth/screens/login_screen.dart';
import '../../auth/services/auth_service.dart';
import '../data/athlete_repository.dart';
import 'athlete_achievements_screen.dart';

class AthleteProfileScreen extends StatefulWidget {
  final int? athleteId;

  const AthleteProfileScreen({super.key, this.athleteId});

  @override
  State<AthleteProfileScreen> createState() => _AthleteProfileScreenState();
}

class _AthleteProfileScreenState extends State<AthleteProfileScreen> {
  final AthleteRepository _repo = AthleteRepository();
  final AuthService _authService = AuthService();

  bool _isLoading = true;
  String? _errorMessage;
  AthleteProfile? _profile;

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  Future<void> _loadProfile() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final p = await _repo.getProfile(athleteId: widget.athleteId);
      if (mounted) {
        setState(() {
          _profile = p;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = e.toString().replaceAll('Exception:', '').trim();
          _isLoading = false;
        });
      }
    }
  }

  String _getInitials(String name) {
    final parts = name.trim().split(' ').where((p) => p.isNotEmpty).toList();
    if (parts.length >= 2) {
      return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
    } else if (parts.isNotEmpty) {
      return parts[0].substring(0, parts[0].length >= 2 ? 2 : 1).toUpperCase();
    }
    return 'AP';
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    if (_errorMessage != null || _profile == null) {
      return Scaffold(
        appBar: widget.athleteId != null ? AppBar(title: const Text('Athlete Profile')) : null,
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.error_outline, size: 54, color: AppTheme.dangerColor),
                const SizedBox(height: 16),
                Text(
                  _errorMessage ?? 'Failed to load athlete profile',
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 15, color: AppTheme.textMuted),
                ),
                const SizedBox(height: 16),
                ElevatedButton.icon(
                  onPressed: _loadProfile,
                  icon: const Icon(Icons.refresh),
                  label: const Text('Retry'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    final p = _profile!;

    return Scaffold(
      appBar: widget.athleteId != null ? AppBar(title: Text(p.fullName)) : null,
      body: SingleChildScrollView(
        child: Column(
          children: [
            // Profile Header Container
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(20, 24, 20, 28),
              decoration: const BoxDecoration(
                color: AppTheme.primaryColor,
                borderRadius: BorderRadius.vertical(
                  bottom: Radius.circular(AppTheme.radiusLarge),
                ),
              ),
              child: Column(
                children: [
                  Stack(
                    alignment: Alignment.bottomRight,
                    children: [
                      Container(
                        width: 86,
                        height: 86,
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.15),
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.white, width: 2),
                        ),
                        child: Center(
                          child: Text(
                            _getInitials(p.fullName),
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 28,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.all(4),
                        decoration: const BoxDecoration(
                          color: AppTheme.accentColor,
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.verified, size: 16, color: Colors.white),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    p.fullName,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${p.athleteCode} • ${p.sportName}',
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.85),
                      fontSize: 13,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      'Team: ${p.teamName} • Status: ${p.status.toUpperCase()}',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Profile Sections List
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16.0),
              child: Column(
                children: [
                  // Personal Information Section
                  _buildSectionCard(
                    title: 'Personal Information',
                    icon: Icons.person_outline,
                    items: [
                      _buildInfoRow('Full Name', p.fullName),
                      _buildInfoRow('Athlete Code', p.athleteCode),
                      _buildInfoRow('Date of Birth', p.dateOfBirth ?? 'Not Specified'),
                      _buildInfoRow('Gender', p.gender != null ? (p.gender![0].toUpperCase() + p.gender!.substring(1)) : 'Not Specified'),
                      _buildInfoRow('Blood Group', p.bloodGroup ?? 'Not Specified'),
                      _buildInfoRow('Nationality', p.nationality ?? 'Indian'),
                    ],
                  ),
                  const SizedBox(height: 14),

                  // Sports & Academy Affiliation Section
                  _buildSectionCard(
                    title: 'Athletic & Category Affiliation',
                    icon: Icons.emoji_events_outlined,
                    items: [
                      _buildInfoRow('Primary Sport', p.sportName),
                      _buildInfoRow('Category', p.categoryName),
                      _buildInfoRow('Assigned Team', p.teamName),
                      _buildInfoRow('Joining Date', p.joiningDate ?? 'Registered'),
                      _buildInfoRow('Roster Status', p.status.toUpperCase()),
                    ],
                  ),
                  const SizedBox(height: 14),

                  // Contact & Location Section
                  _buildSectionCard(
                    title: 'Contact Details',
                    icon: Icons.contact_phone_outlined,
                    items: [
                      _buildInfoRow('Phone', p.phone ?? 'Not Available'),
                      _buildInfoRow('Email Address', p.email ?? 'Not Available'),
                      _buildInfoRow('Address', p.address ?? 'Not Specified'),
                      _buildInfoRow('City & State', '${p.city ?? ''}, ${p.state ?? ''}'.trim().replaceAll(RegExp(r'^,|,$'), '')),
                      _buildInfoRow('Country', p.country ?? 'India'),
                      if (p.postalCode != null) _buildInfoRow('Postal Code', p.postalCode!),
                    ],
                  ),
                  const SizedBox(height: 14),

                  // Emergency & Guardian Section
                  if (p.guardianName != null || p.guardianPhone != null) ...[
                    _buildSectionCard(
                      title: 'Guardian / Emergency Contact',
                      icon: Icons.contact_emergency_outlined,
                      items: [
                        _buildInfoRow('Guardian Name', p.guardianName ?? 'Guardian on File'),
                        _buildInfoRow('Emergency Phone', p.guardianPhone ?? 'Not Provided'),
                      ],
                    ),
                    const SizedBox(height: 14),
                  ],

                  // Quick Action Links
                  Card(
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
                      side: const BorderSide(color: AppTheme.borderColor),
                    ),
                    child: Column(
                      children: [
                        ListTile(
                          leading: const Icon(Icons.military_tech_outlined, color: Color(0xFFD97706)),
                          title: const Text('Achievements & Honors', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
                          subtitle: const Text('Verified competition titles and medals', style: TextStyle(fontSize: 12)),
                          trailing: const Icon(Icons.arrow_forward_ios, size: 14, color: AppTheme.textMuted),
                          onTap: () {
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) => AthleteAchievementsScreen(
                                  isStandalone: true,
                                  athleteId: p.id,
                                ),
                              ),
                            );
                          },
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Sign Out Button (for own profile)
                  if (widget.athleteId == null)
                    SizedBox(
                      width: double.infinity,
                      child: OutlinedButton.icon(
                        onPressed: () async {
                          final confirm = await showDialog<bool>(
                            context: context,
                            builder: (ctx) => AlertDialog(
                              title: const Text('Confirm Sign Out'),
                              content: const Text('Are you sure you want to sign out from your KhelSutra session?'),
                              actions: [
                                TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
                                ElevatedButton(
                                  onPressed: () => Navigator.pop(ctx, true),
                                  style: ElevatedButton.styleFrom(backgroundColor: AppTheme.dangerColor),
                                  child: const Text('Sign Out', style: TextStyle(color: Colors.white)),
                                ),
                              ],
                            ),
                          );

                          if (confirm == true && mounted) {
                            await _authService.logout();
                            if (mounted) {
                              Navigator.of(context).pushAndRemoveUntil(
                                MaterialPageRoute(builder: (_) => const LoginScreen()),
                                (route) => false,
                              );
                            }
                          }
                        },
                        icon: const Icon(Icons.logout, color: AppTheme.dangerColor),
                        label: const Text('Sign Out', style: TextStyle(color: AppTheme.dangerColor, fontWeight: FontWeight.bold)),
                        style: OutlinedButton.styleFrom(
                          side: const BorderSide(color: AppTheme.dangerColor),
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppTheme.radiusMedium)),
                        ),
                      ),
                    ),
                  const SizedBox(height: 32),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionCard({
    required String title,
    required IconData icon,
    required List<Widget> items,
  }) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppTheme.radiusMedium),
        side: const BorderSide(color: AppTheme.borderColor),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, size: 20, color: AppTheme.primaryColor),
                const SizedBox(width: 8),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.textColor,
                  ),
                ),
              ],
            ),
            const Divider(height: 20, color: AppTheme.borderColor),
            ...items,
          ],
        ),
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            flex: 2,
            child: Text(
              label,
              style: const TextStyle(
                fontSize: 13,
                color: AppTheme.textMuted,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          Expanded(
            flex: 3,
            child: Text(
              value,
              textAlign: TextAlign.end,
              style: const TextStyle(
                fontSize: 13,
                color: AppTheme.textColor,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
