import 'package:angelow_repartidor/data/models/delivery_assignment.dart';
import 'package:angelow_repartidor/data/repositories/courier_repository.dart';
import 'package:angelow_repartidor/ui/features/deliveries/view_models/deliveries_view_model.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('carga disponibles, asignadas y configuración de mapa', () async {
    final repository = _FakeCourierRepository();
    final viewModel = DeliveriesViewModel(
      repository: repository,
      token: 'token',
    );

    await viewModel.load();

    expect(viewModel.loading, isFalse);
    expect(viewModel.available, hasLength(1));
    expect(viewModel.mine, isEmpty);
    expect(viewModel.mapboxAccessToken, 'pk.runtime');
  });

  test('aceptar mueve la entrega a mis entregas y evita duplicados', () async {
    final repository = _FakeCourierRepository();
    final viewModel = DeliveriesViewModel(
      repository: repository,
      token: 'token',
    );
    await viewModel.load();

    final accepted = await viewModel.accept(viewModel.available.first);

    expect(accepted?.status, 'assigned');
    expect(repository.acceptCalls, 1);
    expect(viewModel.section, DeliverySection.mine);
    expect(viewModel.available, isEmpty);
    expect(viewModel.mine.single.status, 'assigned');
  });
}

class _FakeCourierRepository extends CourierRepository {
  final availableDelivery = const DeliveryAssignment(
    id: 1,
    orderId: 20,
    orderNumber: 'ORD-20',
    status: 'pending',
    shippingMethod: 'Envío estándar',
    deliveryTime: 'Hoy',
    address: 'Carrera 67 # 10-20',
    city: 'Medellín',
  );

  DeliveryAssignment? acceptedDelivery;
  int acceptCalls = 0;

  @override
  Future<List<DeliveryAssignment>> assignments(
    String token, {
    required String scope,
  }) async {
    if (scope == 'available') {
      return acceptedDelivery == null ? [availableDelivery] : [];
    }
    return acceptedDelivery == null ? [] : [acceptedDelivery!];
  }

  @override
  Future<String> mapboxAccessToken(String token) async => 'pk.runtime';

  @override
  Future<DeliveryAssignment> accept(String token, int id) async {
    acceptCalls++;
    acceptedDelivery = DeliveryAssignment(
      id: availableDelivery.id,
      orderId: availableDelivery.orderId,
      orderNumber: availableDelivery.orderNumber,
      status: 'assigned',
      shippingMethod: availableDelivery.shippingMethod,
      deliveryTime: availableDelivery.deliveryTime,
      address: availableDelivery.address,
      city: availableDelivery.city,
    );
    return acceptedDelivery!;
  }
}
