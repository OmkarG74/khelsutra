import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:khelsutra_mobile/features/auth/screens/login_screen.dart';

void main() {
  testWidgets('KhelSutra smoke test boots login screen', (WidgetTester tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: LoginScreen(),
      ),
    );
    expect(find.text('KhelSutra'), findsOneWidget);
    expect(find.text('Sign In'), findsOneWidget);
  });
}
