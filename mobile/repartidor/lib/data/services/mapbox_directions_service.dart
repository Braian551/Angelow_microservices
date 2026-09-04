import 'dart:convert';

import 'package:http/http.dart' as http;

class RoutePlan {
  const RoutePlan({
    required this.coordinates,
    required this.distanceMeters,
    required this.durationSeconds,
    required this.instructions,
  });
  final List<List<double>> coordinates;
  final double distanceMeters;
  final double durationSeconds;
  final List<String> instructions;
}

class MapboxDirectionsService {
  MapboxDirectionsService({required this.accessToken, http.Client? client})
    : _client = client ?? http.Client();
  final String accessToken;
  final http.Client _client;

  Future<RoutePlan> route({
    required double originLatitude,
    required double originLongitude,
    required double destinationLatitude,
    required double destinationLongitude,
    required String vehicleType,
  }) async {
    if (accessToken.isEmpty) {
      throw StateError('La navegación no está disponible temporalmente.');
    }
    final profile = switch (vehicleType) {
      'foot' => 'walking',
      'bicycle' => 'cycling',
      _ => 'driving-traffic',
    };
    final uri =
        Uri.parse(
          'https://api.mapbox.com/directions/v5/mapbox/$profile/$originLongitude,$originLatitude;$destinationLongitude,$destinationLatitude',
        ).replace(
          queryParameters: {
            'access_token': accessToken,
            'geometries': 'geojson',
            'overview': 'full',
            'steps': 'true',
            'language': 'es',
          },
        );
    final response = await _client.get(uri);
    if (response.statusCode != 200) {
      throw StateError('No se pudo calcular la ruta.');
    }
    final decoded = jsonDecode(response.body) as Map<String, dynamic>;
    final route =
        (decoded['routes'] as List?)?.firstOrNull as Map<String, dynamic>?;
    if (route == null) {
      throw StateError('Mapbox no encontró una ruta disponible.');
    }
    final coordinates =
        ((route['geometry']?['coordinates'] as List?) ?? const [])
            .map(
              (point) => (point as List)
                  .map((value) => (value as num).toDouble())
                  .toList(),
            )
            .toList();
    final legs = route['legs'] as List? ?? const [];
    final instructions = <String>[];
    for (final leg in legs) {
      for (final step in (leg['steps'] as List? ?? const [])) {
        final instruction = step['maneuver']?['instruction']?.toString();
        if (instruction?.isNotEmpty == true) {
          instructions.add(instruction!);
        }
      }
    }
    return RoutePlan(
      coordinates: coordinates,
      distanceMeters: (route['distance'] as num?)?.toDouble() ?? 0,
      durationSeconds: (route['duration'] as num?)?.toDouble() ?? 0,
      instructions: instructions,
    );
  }
}
