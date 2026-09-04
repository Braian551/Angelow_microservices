import 'package:angelow_repartidor/data/models/delivery_assignment.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('restaura coordenadas y permiso de compartir ubicación', () {
    final assignment = DeliveryAssignment.fromJson({
      'id': 20,
      'order_id': 33,
      'status': 'en_route',
      'destination_latitude': '6.252895',
      'destination_longitude': '-75.538951',
      'sharing_location': true,
    });

    expect(assignment.latitude, 6.252895);
    expect(assignment.longitude, -75.538951);
    expect(assignment.sharingLocation, isTrue);
  });
}
