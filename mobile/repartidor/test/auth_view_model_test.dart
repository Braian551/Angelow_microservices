import 'package:angelow_repartidor/data/repositories/auth_repository.dart';
import 'package:angelow_repartidor/ui/features/auth/view_models/auth_view_model.dart';
import 'package:flutter_test/flutter_test.dart';

class _FakeAuthRepository extends AuthRepository {
  @override
  Future<int> requestCode(String email, {bool resend = false}) async => 60;
}

void main() {
  test('inicia el contador de un minuto después de enviar el OTP', () async {
    final viewModel = AuthViewModel(repository: _FakeAuthRepository());

    await viewModel.sendCode('repartidor@example.com');

    expect(viewModel.step, AuthStep.code);
    expect(viewModel.resendCooldown, 60);
    expect(viewModel.resendCooldownLabel, '01:00');
    expect(viewModel.canResend, isFalse);

    viewModel.dispose();
  });
}
