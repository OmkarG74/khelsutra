import 'package:flutter/material.dart';
import '../../../app/routes/role_router.dart';
import '../services/auth_service.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailController = TextEditingController(text: 'coach@khelsutra.local');
  final _passwordController = TextEditingController(text: 'KhelSutra@123');
  final _orgCodeController = TextEditingController(text: 'ORG-DEMO');
  bool _isLoading = false;
  String? _errorMessage;

  final _authService = AuthService();

  Future<void> _handleLogin() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await _authService.login(
        email: _emailController.text.trim(),
        password: _passwordController.text.trim(),
        organizationCode: _orgCodeController.text.trim(),
      );

      if (!mounted) return;

      if (res.success) {
        final role = res.data?['role']?['name'] ?? 'Coach';
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => RoleRouter(role: role)),
        );
      } else {
        setState(() {
          _errorMessage = res.message;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = e.toString().replaceAll('Exception: ', '');
      });
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  void _fillAthlete() {
    setState(() {
      _emailController.text = 'athlete@khelsutra.local';
      _passwordController.text = 'KhelSutra@123';
      _orgCodeController.text = 'ORG-DEMO';
    });
    _handleLogin();
  }

  void _fillCoach() {
    setState(() {
      _emailController.text = 'coach@khelsutra.local';
      _passwordController.text = 'KhelSutra@123';
      _orgCodeController.text = 'ORG-DEMO';
    });
    _handleLogin();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Icon(Icons.emoji_events, size: 72, color: Color(0xFF1E3A8A)),
                const SizedBox(height: 12),
                const Text(
                  'KhelSutra',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1E3A8A),
                    letterSpacing: 1,
                  ),
                ),
                const Text(
                  'Sports Management Mobile Client',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: Colors.grey),
                ),
                const SizedBox(height: 32),
                if (_errorMessage != null) ...[
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.red.shade50,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.red.shade200),
                    ),
                    child: Text(
                      _errorMessage!,
                      style: TextStyle(color: Colors.red.shade800, fontSize: 13),
                    ),
                  ),
                  const SizedBox(height: 16),
                ],
                TextField(
                  controller: _emailController,
                  decoration: const InputDecoration(
                    labelText: 'Email Address',
                    prefixIcon: Icon(Icons.email_outlined),
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: _passwordController,
                  obscureText: true,
                  decoration: const InputDecoration(
                    labelText: 'Password',
                    prefixIcon: Icon(Icons.lock_outline),
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: _orgCodeController,
                  decoration: const InputDecoration(
                    labelText: 'Organisation Code',
                    prefixIcon: Icon(Icons.business_outlined),
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 24),
                ElevatedButton(
                  onPressed: _isLoading ? null : _handleLogin,
                  child: _isLoading
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : const Text('Sign In', style: TextStyle(fontSize: 16)),
                ),
                const SizedBox(height: 16),
                Wrap(
                  alignment: WrapAlignment.center,
                  crossAxisAlignment: WrapCrossAlignment.center,
                  children: [
                    TextButton(
                      onPressed: _isLoading ? null : _fillCoach,
                      child: const Text('Login as Coach'),
                    ),
                    const Text('•', style: TextStyle(color: Colors.grey)),
                    TextButton(
                      onPressed: _isLoading ? null : _fillAthlete,
                      child: const Text('Login as Athlete'),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
