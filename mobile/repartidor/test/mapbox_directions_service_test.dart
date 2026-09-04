import 'package:angelow_repartidor/data/services/mapbox_directions_service.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

void main() {
  test('solicita y convierte una ruta completa de Mapbox', () async {
    final client = MockClient((request) async {
      expect(request.url.host, 'api.mapbox.com');
      expect(request.url.path, contains('/driving-traffic/'));
      expect(request.url.queryParameters['geometries'], 'geojson');
      expect(request.url.queryParameters['overview'], 'full');
      expect(request.url.queryParameters['access_token'], 'token-prueba');

      return http.Response('''
        {
          "routes": [{
            "distance": 3250.4,
            "duration": 780.2,
            "geometry": {
              "coordinates": [[-75.58, 6.24], [-75.54, 6.25]]
            },
            "legs": [{
              "steps": [{"maneuver": {"instruction": "Continúa recto"}}]
            }]
          }]
        }
        ''', 200);
    });
    final service = MapboxDirectionsService(
      accessToken: 'token-prueba',
      client: client,
    );

    final route = await service.route(
      originLatitude: 6.24,
      originLongitude: -75.58,
      destinationLatitude: 6.25,
      destinationLongitude: -75.54,
      vehicleType: 'motorcycle',
    );

    expect(route.coordinates, [
      [-75.58, 6.24],
      [-75.54, 6.25],
    ]);
    expect(route.distanceMeters, 3250.4);
    expect(route.durationSeconds, 780.2);
    expect(route.instructions, ['Continúa recto']);
  });

  test('informa cuando Mapbox no devuelve rutas', () async {
    final service = MapboxDirectionsService(
      accessToken: 'token-prueba',
      client: MockClient((_) async => http.Response('{"routes": []}', 200)),
    );

    expect(
      () => service.route(
        originLatitude: 6.24,
        originLongitude: -75.58,
        destinationLatitude: 6.25,
        destinationLongitude: -75.54,
        vehicleType: 'bicycle',
      ),
      throwsA(isA<StateError>()),
    );
  });
}
