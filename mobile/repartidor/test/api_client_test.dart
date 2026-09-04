import 'dart:io';

import 'package:angelow_repartidor/data/services/api_client.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

void main() {
  test('usa el respaldo local cuando el servidor público no responde', () async {
    final requestedUrls = <String>[];
    final client = MockClient((request) async {
      requestedUrls.add(request.url.toString());
      if (request.url.host == 'angelow.online') {
        throw const SocketException('Sin conexión');
      }
      return http.Response('{"data": {"ok": true}}', 200);
    });
    final api = ApiClient(
      baseUrls: const [
        'https://angelow.online/api/shipping-service',
        'http://10.0.2.2:8007/api',
      ],
      client: client,
    );

    final response = await api.get('/courier/profile');

    expect(response['data'], {'ok': true});
    expect(requestedUrls, [
      'https://angelow.online/api/shipping-service/courier/profile',
      'http://10.0.2.2:8007/api/courier/profile',
    ]);
  });

  test('no cambia al respaldo ante una respuesta funcional del servidor', () async {
    final client = MockClient(
      (_) async => http.Response('{"message": "No autorizado"}', 401),
    );
    final api = ApiClient(
      baseUrls: const [
        'https://angelow.online/api/auth-service',
        'http://10.0.2.2:8001/api',
      ],
      client: client,
    );

    await expectLater(
      api.get('/auth/courier/profile'),
      throwsA(isA<ApiException>().having((error) => error.statusCode, 'estado', 401)),
    );
  });
}
