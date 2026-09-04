import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/catalog_option.dart';

class VehicleCatalogService {
  VehicleCatalogService({http.Client? client})
    : _client = client ?? http.Client();
  final http.Client _client;

  Future<List<CatalogOption>> makes(String vehicleType) async {
    if (!['motorcycle', 'car', 'truck'].contains(vehicleType)) {
      return const [
        CatalogOption(id: 'specialized', label: 'Specialized'),
        CatalogOption(id: 'trek', label: 'Trek'),
        CatalogOption(id: 'gw', label: 'GW'),
        CatalogOption(id: 'other', label: 'Otra'),
      ];
    }
    final type = vehicleType == 'motorcycle'
        ? 'motorcycle'
        : (vehicleType == 'truck' ? 'truck' : 'car');
    final response = await _client.get(
      Uri.parse(
        'https://vpic.nhtsa.dot.gov/api/vehicles/GetMakesForVehicleType/$type?format=json',
      ),
    );
    if (response.statusCode != 200) {
      throw StateError('No se pudo cargar el catálogo de marcas.');
    }
    final rows = (jsonDecode(response.body)['Results'] as List? ?? const []);
    return rows
        .map(
          (row) => CatalogOption(
            id: row['MakeId'].toString(),
            label: row['MakeName'].toString(),
          ),
        )
        .toList()
      ..sort((a, b) => a.label.compareTo(b.label));
  }

  Future<List<CatalogOption>> models(
    String makeId,
    String makeName,
    String vehicleType,
  ) async {
    if (!['motorcycle', 'car', 'truck'].contains(vehicleType)) {
      return const [
        CatalogOption(id: 'urban', label: 'Urbana'),
        CatalogOption(id: 'mountain', label: 'Montaña'),
        CatalogOption(id: 'other', label: 'Otro'),
      ];
    }
    final response = await _client.get(
      Uri.parse(
        'https://vpic.nhtsa.dot.gov/api/vehicles/GetModelsForMakeId/$makeId?format=json',
      ),
    );
    if (response.statusCode != 200) {
      throw StateError('No se pudo cargar el catálogo de modelos.');
    }
    final rows = (jsonDecode(response.body)['Results'] as List? ?? const []);
    return rows
        .map(
          (row) => CatalogOption(
            id: row['Model_ID'].toString(),
            label: row['Model_Name'].toString(),
          ),
        )
        .toList()
      ..sort((a, b) => a.label.compareTo(b.label));
  }

  Future<List<CatalogOption>> colors() async {
    return const [
      CatalogOption(id: '000000', label: 'Negro', hex: '#000000'),
      CatalogOption(id: 'FFFFFF', label: 'Blanco', hex: '#FFFFFF'),
      CatalogOption(id: '808080', label: 'Gris', hex: '#808080'),
      CatalogOption(id: 'C0C0C0', label: 'Plateado', hex: '#C0C0C0'),
      CatalogOption(id: 'FF0000', label: 'Rojo', hex: '#FF0000'),
      CatalogOption(id: '0000FF', label: 'Azul', hex: '#0000FF'),
      CatalogOption(id: '964B00', label: 'Café', hex: '#964B00'),
      CatalogOption(id: '008000', label: 'Verde', hex: '#008000'),
      CatalogOption(id: 'FFD700', label: 'Dorado', hex: '#FFD700'),
      CatalogOption(id: 'F5F5DC', label: 'Beige', hex: '#F5F5DC'),
      CatalogOption(id: 'FFFFFF-2', label: 'Perlado', hex: '#F8F6F0'),
      CatalogOption(id: '800000', label: 'Vinotinto', hex: '#800000'),
      CatalogOption(id: 'FFA500', label: 'Naranja', hex: '#FFA500'),
      CatalogOption(id: 'FFFF00', label: 'Amarillo', hex: '#FFFF00'),
    ];
  }
}
