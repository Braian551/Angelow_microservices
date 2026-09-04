import 'dart:io';

import '../models/courier_profile.dart';
import '../models/delivery_assignment.dart';
import '../services/courier_api_service.dart';

class CourierRepository {
  CourierRepository({CourierApiService? api})
    : _api = api ?? CourierApiService();
  final CourierApiService _api;

  Future<CourierProfile?> profile(String token) => _api.profile(token);
  Future<CourierProfile> saveProfile({
    required String token,
    required Map<String, String> fields,
    required Map<String, File> documents,
  }) => _api.saveProfile(token: token, fields: fields, documents: documents);
  Future<List<DeliveryAssignment>> assignments(
    String token, {
    required String scope,
  }) => _api.assignments(token, scope: scope);
  Future<String> mapboxAccessToken(String token) =>
      _api.mapboxAccessToken(token);
  Future<DeliveryAssignment> accept(String token, int id) =>
      _api.accept(token, id);
  Future<DeliveryAssignment> startRoute(
    String token,
    int id,
    bool shareLocation,
  ) => _api.startRoute(token, id, shareLocation);
  Future<void> sendLocation(
    String token,
    int id, {
    required double latitude,
    required double longitude,
    double? heading,
    double? speed,
    double? accuracy,
  }) => _api.sendLocation(
    token,
    id,
    latitude: latitude,
    longitude: longitude,
    heading: heading,
    speed: speed,
    accuracy: accuracy,
  );
  Future<DeliveryAssignment> arrive(String token, int id) =>
      _api.arrive(token, id);
  Future<DeliveryAssignment> complete(String token, int id, String code) =>
      _api.complete(token, id, code);
}
