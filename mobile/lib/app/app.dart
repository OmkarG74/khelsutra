import 'package:flutter/material.dart';
import 'routes/app_routes.dart';
import 'routes/role_router.dart';
import 'theme/app_theme.dart';
import '../features/auth/screens/login_screen.dart';
import '../core/storage/token_storage.dart';

class KhelSutraApp extends StatelessWidget {
  const KhelSutraApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'KhelSutra',
      theme: AppTheme.lightTheme,
      debugShowCheckedModeBanner: false,
      home: FutureBuilder<bool>(
        future: TokenStorage.hasValidSession(),
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Scaffold(
              body: Center(child: CircularProgressIndicator()),
            );
          }
          if (snapshot.data == true) {
            final role = TokenStorage.getRole();
            return FutureBuilder<String?>(
              future: role,
              builder: (context, roleSnap) {
                if (roleSnap.hasData && roleSnap.data != null && roleSnap.data!.isNotEmpty) {
                  return RoleRouter(role: roleSnap.data!);
                }
                return const LoginScreen();
              },
            );
          }
          return const LoginScreen();
        },
      ),
      routes: AppRoutes.routes,
    );
  }
}
