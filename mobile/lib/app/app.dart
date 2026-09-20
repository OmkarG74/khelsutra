import 'package:flutter/material.dart';
import 'routes/app_routes.dart';
import 'theme/app_theme.dart';
import '../features/auth/screens/login_screen.dart';

class KhelSutraApp extends StatelessWidget {
  const KhelSutraApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'KhelSutra',
      theme: AppTheme.lightTheme,
      debugShowCheckedModeBanner: false,
      home: const LoginScreen(),
      routes: AppRoutes.routes,
    );
  }
}
