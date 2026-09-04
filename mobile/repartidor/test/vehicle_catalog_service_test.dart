import 'package:angelow_repartidor/data/services/vehicle_catalog_service.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test(
    'el catálogo presenta colores en español y con muestra hexadecimal',
    () async {
      final colors = await VehicleCatalogService().colors();

      expect(
        colors.map((item) => item.label),
        containsAll(['Negro', 'Blanco', 'Café']),
      );
      expect(colors.every((item) => item.hex?.startsWith('#') == true), isTrue);
      expect(colors.map((item) => item.label), isNot(contains('Black')));
    },
  );
}
