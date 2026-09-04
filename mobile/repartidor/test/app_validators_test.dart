import 'package:angelow_repartidor/ui/core/validation/app_validators.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('AppValidators', () {
    test('valida correo y código OTP', () {
      expect(AppValidators.email('correo-invalido'), isNotNull);
      expect(AppValidators.email('leidy@example.com'), isNull);
      expect(AppValidators.verificationCode('12345'), isNotNull);
      expect(AppValidators.verificationCode('123456'), isNull);
    });

    test('exige identidad completa y celular colombiano', () {
      expect(AppValidators.fullName('Leidy'), isNotNull);
      expect(AppValidators.fullName('Leidy Tracon'), isNull);
      expect(AppValidators.colombianPhone('12345'), isNotNull);
      expect(AppValidators.colombianPhone('3001234567'), isNull);
    });

    test('exige contraseña fuerte y confirmación coincidente', () {
      expect(AppValidators.password('soloclave'), isNotNull);
      expect(AppValidators.password('Clave1234'), isNull);
      expect(
        AppValidators.passwordConfirmation('Otra1234', 'Clave1234'),
        isNotNull,
      );
      expect(
        AppValidators.passwordConfirmation('Clave1234', 'Clave1234'),
        isNull,
      );
    });

    test('rechaza cédula y fecha de nacimiento inválidas', () {
      expect(AppValidators.documentNumber('43245', 'cc'), isNotNull);
      expect(AppValidators.documentNumber('1030123456', 'cc'), isNull);
      expect(AppValidators.birthDate('43245'), isNotNull);
      expect(AppValidators.birthDate('1995-05-20'), isNull);
    });
  });
}
