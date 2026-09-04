import 'package:angelow_repartidor/data/services/external_navigation_service.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  const service = ExternalNavigationService();

  test('crea el enlace universal de Google Maps con el destino exacto', () {
    final uri = service.destinationUri(
      app: ExternalNavigationApp.googleMaps,
      latitude: 6.252895,
      longitude: -75.538951,
    );

    expect(uri.host, 'www.google.com');
    expect(uri.path, '/maps/dir/');
    expect(uri.queryParameters['destination'], '6.252895,-75.538951');
    expect(uri.queryParameters['travelmode'], 'driving');
  });

  test('crea el enlace universal de Waze e inicia navegación', () {
    final uri = service.destinationUri(
      app: ExternalNavigationApp.waze,
      latitude: 6.252895,
      longitude: -75.538951,
    );

    expect(uri.host, 'waze.com');
    expect(uri.queryParameters['ll'], '6.252895,-75.538951');
    expect(uri.queryParameters['navigate'], 'yes');
  });
}
