class DeliveryAssignment {
  const DeliveryAssignment({
    required this.id,
    required this.orderId,
    required this.orderNumber,
    required this.status,
    required this.shippingMethod,
    required this.deliveryTime,
    required this.address,
    required this.city,
    this.latitude,
    this.longitude,
    this.sharingLocation = false,
    this.createdAt,
    this.acceptedAt,
  });

  final int id;
  final int orderId;
  final String orderNumber;
  final String status;
  final String shippingMethod;
  final String deliveryTime;
  final String address;
  final String city;
  final double? latitude;
  final double? longitude;
  final bool sharingLocation;
  final DateTime? createdAt;
  final DateTime? acceptedAt;

  factory DeliveryAssignment.fromJson(
    Map<String, dynamic> json,
  ) => DeliveryAssignment(
    id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
    orderId: int.tryParse(json['order_id']?.toString() ?? '') ?? 0,
    orderNumber:
        json['order_number']?.toString() ?? '#${json['order_id'] ?? ''}',
    status: json['status']?.toString() ?? 'pending',
    shippingMethod:
        json['shipping_method_name']?.toString() ?? 'Envío a domicilio',
    deliveryTime: json['delivery_time']?.toString() ?? 'Tiempo no definido',
    address: json['destination_address']?.toString() ?? '',
    city: json['destination_city']?.toString() ?? '',
    latitude: double.tryParse(json['destination_latitude']?.toString() ?? ''),
    longitude: double.tryParse(json['destination_longitude']?.toString() ?? ''),
    sharingLocation:
        json['sharing_location'] == true ||
        json['sharing_location']?.toString() == '1',
    createdAt: DateTime.tryParse(json['created_at']?.toString() ?? ''),
    acceptedAt: DateTime.tryParse(json['accepted_at']?.toString() ?? ''),
  );
}
