import 'package:angelow_repartidor/data/models/courier_profile.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('expone los documentos rechazados y sus instrucciones', () {
    final profile = CourierProfile.fromJson({
      'id': 7,
      'status': 'rejected',
      'is_active': false,
      'rejection_reason': 'Corrige los documentos indicados.',
      'vehicle': {'type': 'motorcycle', 'plate': 'ABC12D'},
      'documents': [
        {'type': 'identity_front', 'status': 'approved', 'review_note': null},
        {
          'type': 'driving_license',
          'status': 'rejected',
          'review_note': 'La imagen no es legible.',
        },
      ],
    });

    expect(profile.vehicleType, 'motorcycle');
    expect(profile.documentsRequiringChanges, hasLength(1));
    expect(
      profile.documentsRequiringChanges.single.reviewNote,
      'La imagen no es legible.',
    );
  });
}
