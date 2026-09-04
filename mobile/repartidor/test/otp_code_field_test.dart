import 'package:angelow_repartidor/ui/core/widgets/otp_code_field.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  testWidgets('el OTP distribuye un código pegado en seis casillas', (
    tester,
  ) async {
    final controller = TextEditingController();
    String? completed;

    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: OtpCodeField(
            controller: controller,
            onCompleted: (value) => completed = value,
          ),
        ),
      ),
    );

    await tester.enterText(find.byType(TextField), '123456');
    await tester.pump();

    expect(controller.text, '123456');
    expect(completed, '123456');
    for (final digit in ['1', '2', '3', '4', '5', '6']) {
      expect(find.text(digit), findsOneWidget);
    }
  });
}
