import 'dart:convert';

import 'package:http/http.dart' as http;

class GeocodedLocation {
  const GeocodedLocation({required this.latitude, required this.longitude});

  final double latitude;
  final double longitude;
}

class NominatimGeocodingService {
  NominatimGeocodingService({http.Client? client})
    : _client = client ?? http.Client();

  final http.Client _client;

  Future<GeocodedLocation?> geocode(String address) async {
    final query = address.trim();
    if (query.length < 5) return null;

    try {
      final colombiaResult = await _search(query, countryCode: 'co');
      if (colombiaResult != null) return colombiaResult;
    } catch (_) {
      // El segundo intento global puede resolver direcciones fuera de Colombia.
    }

    try {
      return await _search(query);
    } catch (_) {
      return null;
    }
  }

  Future<GeocodedLocation?> _search(String query, {String? countryCode}) async {
    final parameters = {
      'format': 'jsonv2',
      'addressdetails': '1',
      'accept-language': 'es',
      'limit': '1',
      'q': query,
    };
    if (countryCode != null) parameters['countrycodes'] = countryCode;

    final uri = Uri.https('nominatim.openstreetmap.org', '/search', parameters);
    final response = await _client
        .get(
          uri,
          headers: const {
            'Accept': 'application/json',
            'User-Agent': 'Angelow-Repartidor/1.0',
          },
        )
        .timeout(const Duration(seconds: 8));

    if (response.statusCode < 200 || response.statusCode >= 300) return null;

    final payload = jsonDecode(response.body);
    if (payload is! List || payload.isEmpty || payload.first is! Map) {
      return null;
    }

    final first = Map<String, dynamic>.from(payload.first as Map);
    final latitude = double.tryParse(first['lat']?.toString() ?? '');
    final longitude = double.tryParse(first['lon']?.toString() ?? '');
    if (!_isValidCoordinatePair(latitude, longitude)) return null;

    return GeocodedLocation(latitude: latitude!, longitude: longitude!);
  }

  bool _isValidCoordinatePair(double? latitude, double? longitude) {
    return latitude != null &&
        longitude != null &&
        latitude >= -90 &&
        latitude <= 90 &&
        longitude >= -180 &&
        longitude <= 180;
  }
}
