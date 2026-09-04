import 'package:url_launcher/url_launcher.dart';

enum ExternalNavigationApp { googleMaps, waze }

class ExternalNavigationService {
  const ExternalNavigationService();

  Uri destinationUri({
    required ExternalNavigationApp app,
    required double latitude,
    required double longitude,
  }) {
    final coordinates = '$latitude,$longitude';
    return switch (app) {
      ExternalNavigationApp.googleMaps => Uri.https(
        'www.google.com',
        '/maps/dir/',
        {'api': '1', 'destination': coordinates, 'travelmode': 'driving'},
      ),
      ExternalNavigationApp.waze => Uri.https('waze.com', '/ul', {
        'll': coordinates,
        'navigate': 'yes',
        'utm_source': 'angelow',
      }),
    };
  }

  Future<bool> open({
    required ExternalNavigationApp app,
    required double latitude,
    required double longitude,
  }) => launchUrl(
    destinationUri(app: app, latitude: latitude, longitude: longitude),
    mode: LaunchMode.externalApplication,
  );
}
