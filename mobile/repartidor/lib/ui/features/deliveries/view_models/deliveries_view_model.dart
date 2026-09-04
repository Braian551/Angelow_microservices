import 'package:flutter/foundation.dart';

import '../../../../data/models/delivery_assignment.dart';
import '../../../../data/repositories/courier_repository.dart';

enum DeliverySection { available, mine }

class DeliveriesViewModel extends ChangeNotifier {
  DeliveriesViewModel({
    required CourierRepository repository,
    required String token,
  }) : _repository = repository,
       _token = token;

  final CourierRepository _repository;
  final String _token;

  DeliverySection _section = DeliverySection.available;
  bool _loading = true;
  String? _error;
  String? _actionError;
  int? _acceptingId;
  String _mapboxAccessToken = '';
  List<DeliveryAssignment> _available = const [];
  List<DeliveryAssignment> _mine = const [];

  DeliverySection get section => _section;
  bool get loading => _loading;
  String? get error => _error;
  String? get actionError => _actionError;
  int? get acceptingId => _acceptingId;
  String get mapboxAccessToken => _mapboxAccessToken;
  List<DeliveryAssignment> get available => List.unmodifiable(_available);
  List<DeliveryAssignment> get mine => List.unmodifiable(_mine);
  List<DeliveryAssignment> get visible =>
      _section == DeliverySection.available ? available : mine;

  void select(DeliverySection value) {
    if (_section == value) return;
    _section = value;
    _actionError = null;
    notifyListeners();
  }

  Future<void> load({bool showLoading = true}) async {
    if (showLoading) {
      _loading = true;
      notifyListeners();
    }
    _error = null;

    try {
      final rows = await Future.wait([
        _repository.assignments(_token, scope: 'available'),
        _repository.assignments(_token, scope: 'mine'),
      ]);
      _available = rows[0];
      _mine = rows[1];

      if (_mapboxAccessToken.isEmpty) {
        try {
          _mapboxAccessToken = await _repository.mapboxAccessToken(_token);
        } catch (_) {
          // Las entregas siguen disponibles aunque el proveedor del mapa falle.
        }
      }
    } catch (exception) {
      _error = exception.toString();
    } finally {
      _loading = false;
      notifyListeners();
    }
  }

  Future<DeliveryAssignment?> accept(DeliveryAssignment assignment) async {
    if (_acceptingId != null) return null;
    _acceptingId = assignment.id;
    _actionError = null;
    notifyListeners();

    try {
      final accepted = await _repository.accept(_token, assignment.id);
      _section = DeliverySection.mine;
      await load(showLoading: false);
      return accepted;
    } catch (exception) {
      _actionError = exception.toString();
      return null;
    } finally {
      _acceptingId = null;
      notifyListeners();
    }
  }
}
