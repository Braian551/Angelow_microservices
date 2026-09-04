import 'package:angelow_repartidor/data/services/nominatim_geocoding_service.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

void main() {
  test('convierte el primer resultado de Nominatim en coordenadas', () async {
    final client = MockClient((request) async {
      expect(request.url.host, 'nominatim.openstreetmap.org');
      expect(request.url.queryParameters['countrycodes'], 'co');
      expect(request.headers['User-Agent'], 'Angelow-Repartidor/1.0');

      return http.Response('[{"lat":"6.252895","lon":"-75.538951"}]', 200);
    });
    final service = NominatimGeocodingService(client: client);

    final location = await service.geocode('Calle 59, Medellín');

    expect(location?.latitude, 6.252895);
    expect(location?.longitude, -75.538951);
  });

  test('retorna null cuando Nominatim no encuentra la dirección', () async {
    final client = MockClient((_) async => http.Response('[]', 200));
    final service = NominatimGeocodingService(client: client);

    expect(await service.geocode('Dirección inexistente'), isNull);
  });
}
