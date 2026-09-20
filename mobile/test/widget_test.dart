import 'package:flutter_test/flutter_test.dart';
import 'package:khelsutra_mobile/app/app.dart';

void main() {
  testWidgets('KhelSutra smoke test boots login screen', (WidgetTester tester) async {
    await tester.pumpWidget(const KhelSutraApp());
    expect(find.text('KhelSutra'), findsOneWidget);
    expect(find.text('Sign In'), findsOneWidget);
  });
}
