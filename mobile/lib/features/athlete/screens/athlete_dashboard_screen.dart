import 'package:flutter/material.dart';
import '../../auth/screens/login_screen.dart';

class AthleteDashboardScreen extends StatefulWidget {
  const AthleteDashboardScreen({super.key});

  @override
  State<AthleteDashboardScreen> createState() => _AthleteDashboardScreenState();
}

class _AthleteDashboardScreenState extends State<AthleteDashboardScreen> {
  int _selectedIndex = 0;

  final List<Map<String, dynamic>> _athleteModules = [
    {'title': 'My Profile', 'icon': Icons.badge},
    {'title': 'My Team', 'icon': Icons.groups_2},
    {'title': 'Training', 'icon': Icons.fitness_center},
    {'title': 'Attendance', 'icon': Icons.how_to_reg},
    {'title': 'Fixtures', 'icon': Icons.calendar_month},
    {'title': 'Match Info', 'icon': Icons.sports},
    {'title': 'Performance', 'icon': Icons.show_chart},
    {'title': 'Achievements', 'icon': Icons.military_tech},
    {'title': 'Leave', 'icon': Icons.event_busy},
    {'title': 'Notifications', 'icon': Icons.notifications_active},
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Athlete Portal'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Sign Out',
            onPressed: () {
              Navigator.of(context).pushReplacement(
                MaterialPageRoute(builder: (_) => const LoginScreen()),
              );
            },
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Athlete Profile Banner
            Card(
              color: const Color(0xFF0F766E),
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Row(
                  children: [
                    const CircleAvatar(
                      radius: 28,
                      backgroundColor: Colors.white24,
                      child: Icon(Icons.person, color: Colors.white, size: 30),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: const [
                          Text(
                            'Aarav Patel',
                            style: TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.bold,
                              fontSize: 18,
                            ),
                          ),
                          SizedBox(height: 4),
                          Text(
                            'Football • Jersey #10 • Forward\nTitans FC U-18',
                            style: TextStyle(color: Colors.white70, fontSize: 13),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),

            const Text(
              'Athlete Center',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),

            // Grid of Athlete Modules
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 3,
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
                childAspectRatio: 1.0,
              ),
              itemCount: _athleteModules.length,
              itemBuilder: (context, index) {
                final module = _athleteModules[index];
                return Card(
                  elevation: 1,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  child: InkWell(
                    borderRadius: BorderRadius.circular(8),
                    onTap: () {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text('${module['title']} module skeleton ready')),
                      );
                    },
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(module['icon'] as IconData, size: 30, color: const Color(0xFF0F766E)),
                        const SizedBox(height: 8),
                        Text(
                          module['title'] as String,
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),

            const SizedBox(height: 24),
            const Text(
              'Upcoming Match',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),

            Card(
              child: ListTile(
                leading: const Icon(Icons.shield_outlined, color: Color(0xFFF97316), size: 34),
                title: const Text('State Championship Semi-Final'),
                subtitle: const Text('Titans FC vs Phoenix Academy\nTuesday, 22 Sep • 03:30 PM'),
                isThreeLine: true,
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _selectedIndex,
        onDestinationSelected: (idx) => setState(() => _selectedIndex = idx),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.home), label: 'Home'),
          NavigationDestination(icon: Icon(Icons.fitness_center), label: 'Training'),
          NavigationDestination(icon: Icon(Icons.event), label: 'Matches'),
          NavigationDestination(icon: Icon(Icons.person), label: 'Profile'),
        ],
      ),
    );
  }
}
