import 'package:flutter/material.dart';
import '../../features/athlete/screens/athlete_achievements_screen.dart';
import '../../features/athlete/screens/athlete_attendance_screen.dart';
import '../../features/athlete/screens/athlete_dashboard_screen.dart';
import '../../features/athlete/screens/athlete_performance_screen.dart';
import '../../features/athlete/screens/athlete_training_screen.dart';
import '../../features/auth/screens/login_screen.dart';
import '../../features/coach/screens/coach_athletes_screen.dart';
import '../../features/coach/screens/coach_attendance_screen.dart';
import '../../features/coach/screens/coach_create_training_screen.dart';
import '../../features/coach/screens/coach_dashboard_screen.dart';
import '../../features/coach/screens/coach_performance_screen.dart';
import '../../features/coach/screens/coach_reports_screen.dart';
import '../../features/coach/screens/coach_training_screen.dart';

class AppRoutes {
  static const String login = '/login';
  static const String home = '/home';

  // Athlete Routes
  static const String athleteDashboard = '/athlete/dashboard';
  static const String athleteTraining = '/athlete/training';
  static const String athletePerformance = '/athlete/performance';
  static const String athleteAttendance = '/athlete/attendance';
  static const String athleteAchievements = '/athlete/achievements';

  // Coach Routes
  static const String coachDashboard = '/coach/dashboard';
  static const String coachAthletes = '/coach/athletes';
  static const String coachTraining = '/coach/training';
  static const String coachCreateTraining = '/coach/training/create';
  static const String coachAttendance = '/coach/attendance';
  static const String coachPerformance = '/coach/performance';
  static const String coachReports = '/coach/reports';

  static Map<String, WidgetBuilder> get routes => {
        login: (context) => const LoginScreen(),
        athleteDashboard: (context) => const AthleteDashboardScreen(),
        athleteTraining: (context) => const AthleteTrainingScreen(isStandalone: true),
        athletePerformance: (context) => const AthletePerformanceScreen(isStandalone: true),
        athleteAttendance: (context) => const AthleteAttendanceScreen(isStandalone: true),
        athleteAchievements: (context) => const AthleteAchievementsScreen(isStandalone: true),
        coachDashboard: (context) => const CoachDashboardScreen(),
        coachAthletes: (context) => const CoachAthletesScreen(isStandalone: true),
        coachTraining: (context) => const CoachTrainingScreen(isStandalone: true),
        coachCreateTraining: (context) => const CoachCreateTrainingScreen(),
        coachAttendance: (context) => const CoachAttendanceScreen(),
        coachPerformance: (context) => const CoachPerformanceScreen(),
        coachReports: (context) => const CoachReportsScreen(isStandalone: true),
      };
}
