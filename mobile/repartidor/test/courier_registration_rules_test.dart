import 'package:angelow_repartidor/domain/courier_registration_rules.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('a pie y bicicleta solo muestran documentos personales', () {
    for (final type in ['foot', 'bicycle']) {
      final documents = CourierRegistrationRules.visibleDocuments(type);

      expect(documents, ['identity_front', 'identity_back', 'profile_photo']);
      expect(documents, isNot(contains('driving_license')));
      expect(documents, isNot(contains('vehicle_registration')));
    }
  });

  test('los vehículos motorizados muestran documentos del vehículo', () {
    final documents = CourierRegistrationRules.visibleDocuments('motorcycle');

    expect(documents, contains('driving_license'));
    expect(documents, contains('vehicle_registration'));
    expect(documents, contains('soat'));
    expect(documents, contains('technical_inspection'));
  });
}
