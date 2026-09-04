import 'dart:io';

import '../../config/app_config.dart';
import '../models/courier_profile.dart';
import '../models/delivery_assignment.dart';
import 'api_client.dart';

class CourierApiService {
  CourierApiService({ApiClient? client})
    : _client = client ?? ApiClient(baseUrls: AppConfig.shippingApiUrls);
  final ApiClient _client;

  Future<CourierProfile?> profile(String token) async {
    final json = await _client.get('/courier/profile', token: token);
    final data = json['data'];
    return data is Map
        ? CourierProfile.fromJson(Map<String, dynamic>.from(data))
        : null;
  }

  Future<CourierProfile> saveProfile({
    required String token,
    required Map<String, String> fields,
    required Map<String, File> documents,
  }) async {
    final json = await _client.multipart(
      '/courier/profile',
      token: token,
      fields: fields,
      files: documents,
    );
    return CourierProfile.fromJson(
      Map<String, dynamic>.from(json['data'] as Map),
    );
  }

  Future<List<DeliveryAssignment>> assignments(
    String token, {
    required String scope,
  }) async {
    final json = await _client.get(
      '/courier/assignments',
      token: token,
      query: {'scope': scope},
    );
    final rows = json['data'] as List? ?? const [];
    return rows
        .map(
          (item) => DeliveryAssignment.fromJson(
            Map<String, dynamic>.from(item as Map),
          ),
        )
        .toList();
  }

  Future<String> mapboxAccessToken(String token) async {
    final json = await _client.get('/courier/map-config', token: token);
    final data = json['data'];
    if (data is! Map) {
      return '';
    }
    return data['access_token']?.toString().trim() ?? '';
  }

  Future<DeliveryAssignment> accept(String token, int id) async {
    final json = await _client.post(
      '/courier/assignments/$id/accept',
      token: token,
    );
    return DeliveryAssignment.fromJson(
      Map<String, dynamic>.from(json['data'] as Map),
    );
  }

  Future<DeliveryAssignment> startRoute(
    String token,
    int id,
    bool shareLocation,
  ) async {
    final json = await _client.post(
      '/courier/assignments/$id/start-route',
      token: token,
      body: {'share_location': shareLocation},
    );
    return DeliveryAssignment.fromJson(
      Map<String, dynamic>.from(json['data'] as Map),
    );
  }

  Future<void> sendLocation(
    String token,
    int id, {
    required double latitude,
    required double longitude,
    double? heading,
    double? speed,
    double? accuracy,
  }) async {
    await _client.post(
      '/courier/assignments/$id/location',
      token: token,
      body: {
        'latitude': latitude,
        'longitude': longitude,
        'heading': heading,
        'speed': speed,
        'accuracy': accuracy,
      },
    );
  }

  Future<DeliveryAssignment> arrive(String token, int id) async {
    final json = await _client.post(
      '/courier/assignments/$id/arrive',
      token: token,
    );
    return DeliveryAssignment.fromJson(
      Map<String, dynamic>.from(json['data'] as Map),
    );
  }

  Future<DeliveryAssignment> complete(String token, int id, String code) async {
    final json = await _client.post(
      '/courier/assignments/$id/complete',
      token: token,
      body: {'code': code},
    );
    return DeliveryAssignment.fromJson(
      Map<String, dynamic>.from(json['data'] as Map),
    );
  }
}
