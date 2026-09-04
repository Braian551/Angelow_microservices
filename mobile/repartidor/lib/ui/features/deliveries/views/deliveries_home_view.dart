import 'dart:async';
import 'dart:typed_data';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart' as geo;
import 'package:intl/intl.dart';
import 'package:mapbox_maps_flutter/mapbox_maps_flutter.dart';

import '../../../../data/models/delivery_assignment.dart';
import '../../../../data/repositories/courier_repository.dart';
import '../../../../data/services/external_navigation_service.dart';
import '../../../../data/services/mapbox_directions_service.dart';
import '../../../../data/services/nominatim_geocoding_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/angelow_loading_indicator.dart';
import '../view_models/deliveries_view_model.dart';

class DeliveriesHomeView extends StatefulWidget {
  const DeliveriesHomeView({
    super.key,
    required this.repository,
    required this.token,
    required this.vehicleType,
    required this.onLogout,
  });
  final CourierRepository repository;
  final String token;
  final String vehicleType;
  final VoidCallback onLogout;

  @override
  State<DeliveriesHomeView> createState() => _DeliveriesHomeViewState();
}

class _DeliveriesHomeViewState extends State<DeliveriesHomeView> {
  late final DeliveriesViewModel viewModel;

  @override
  void initState() {
    super.initState();
    viewModel = DeliveriesViewModel(
      repository: widget.repository,
      token: widget.token,
    );
    _load();
  }

  Future<void> _load() async {
    await viewModel.load();
    if (viewModel.mapboxAccessToken.isNotEmpty) {
      MapboxOptions.setAccessToken(viewModel.mapboxAccessToken);
    }
  }

  @override
  void dispose() {
    viewModel.dispose();
    super.dispose();
  }

  Future<void> _openRoute(DeliveryAssignment item) async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => DeliveryRouteView(
          repository: widget.repository,
          token: widget.token,
          vehicleType: widget.vehicleType,
          mapboxAccessToken: viewModel.mapboxAccessToken,
          assignment: item,
        ),
      ),
    );
    await _load();
  }

  Future<void> _showDetails(
    DeliveryAssignment item, {
    required bool available,
  }) async {
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetContext) => ListenableBuilder(
        listenable: viewModel,
        builder: (context, _) {
          final accepting = viewModel.acceptingId == item.id;
          return Container(
            constraints: BoxConstraints(
              maxHeight: MediaQuery.sizeOf(context).height * .86,
            ),
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(30)),
            ),
            child: SafeArea(
              top: false,
              child: SingleChildScrollView(
                padding: EdgeInsets.fromLTRB(
                  22,
                  12,
                  22,
                  22 + MediaQuery.viewInsetsOf(context).bottom,
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Center(
                      child: Container(
                        width: 44,
                        height: 5,
                        decoration: BoxDecoration(
                          color: const Color(0xFFD5E1E8),
                          borderRadius: BorderRadius.circular(99),
                        ),
                      ),
                    ),
                    const SizedBox(height: 22),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          width: 48,
                          height: 48,
                          decoration: BoxDecoration(
                            color: AppTheme.primary.withValues(alpha: .12),
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: const Icon(
                            Icons.local_shipping_outlined,
                            color: AppTheme.primary,
                          ),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                available ? 'Entrega disponible' : 'Tu entrega',
                                style: Theme.of(context).textTheme.titleLarge
                                    ?.copyWith(fontWeight: FontWeight.w800),
                              ),
                              const SizedBox(height: 3),
                              Text(
                                item.orderNumber,
                                style: const TextStyle(
                                  color: Color(0xFF64748B),
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ],
                          ),
                        ),
                        IconButton(
                          tooltip: 'Cerrar',
                          onPressed: accepting
                              ? null
                              : () => Navigator.pop(sheetContext),
                          icon: const Icon(Icons.close_rounded),
                        ),
                      ],
                    ),
                    const SizedBox(height: 24),
                    _DetailRow(
                      icon: Icons.flag_outlined,
                      label: 'Estado',
                      value: _statusLabel(item.status),
                    ),
                    _DetailRow(
                      icon: Icons.inventory_2_outlined,
                      label: 'Tipo de envío',
                      value: item.shippingMethod,
                    ),
                    _DetailRow(
                      icon: Icons.schedule_outlined,
                      label: 'Tiempo estimado',
                      value: item.deliveryTime,
                    ),
                    _DetailRow(
                      icon: Icons.location_on_outlined,
                      label: 'Destino',
                      value: item.address.isEmpty
                          ? 'Dirección pendiente de confirmar'
                          : item.address,
                    ),
                    if (item.createdAt != null)
                      _DetailRow(
                        icon: Icons.calendar_today_outlined,
                        label: 'Publicada',
                        value: DateFormat(
                          'dd/MM/yyyy · HH:mm',
                        ).format(item.createdAt!.toLocal()),
                      ),
                    if (viewModel.actionError != null && available) ...[
                      const SizedBox(height: 8),
                      _InlineMessage(text: viewModel.actionError!),
                    ],
                    const SizedBox(height: 22),
                    if (available)
                      FilledButton.icon(
                        onPressed: accepting
                            ? null
                            : () async {
                                final accepted = await viewModel.accept(item);
                                if (!mounted ||
                                    !sheetContext.mounted ||
                                    accepted == null) {
                                  return;
                                }
                                Navigator.pop(sheetContext);
                                ScaffoldMessenger.of(this.context).showSnackBar(
                                  const SnackBar(
                                    content: Text(
                                      'Entrega asignada. El cliente recibirá el código por notificación y correo.',
                                    ),
                                  ),
                                );
                              },
                        icon: accepting
                            ? const SizedBox.square(
                                dimension: 18,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: Colors.white,
                                ),
                              )
                            : const Icon(Icons.check_circle_outline_rounded),
                        label: Text(
                          accepting ? 'Asignando entrega…' : 'Aceptar entrega',
                        ),
                      )
                    else
                      FilledButton.icon(
                        onPressed: () {
                          Navigator.pop(sheetContext);
                          _openRoute(item);
                        },
                        icon: Icon(_actionIcon(item.status)),
                        label: Text(_actionLabel(item.status)),
                      ),
                    const SizedBox(height: 10),
                    TextButton(
                      onPressed: accepting
                          ? null
                          : () => Navigator.pop(sheetContext),
                      child: const Text('Cerrar'),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  String _statusLabel(String status) => switch (status) {
    'pending' => 'Disponible',
    'assigned' => 'Asignada',
    'en_route' => 'En ruta',
    'arrived' => 'En el destino',
    'delivered' => 'Entregada',
    'cancelled' => 'Cancelada',
    _ => 'En seguimiento',
  };

  IconData _statusIcon(String status) => switch (status) {
    'pending' => Icons.inventory_2_outlined,
    'assigned' => Icons.assignment_turned_in_outlined,
    'en_route' => Icons.route_outlined,
    'arrived' => Icons.location_on_outlined,
    _ => Icons.local_shipping_outlined,
  };

  String _actionLabel(String status) => switch (status) {
    'en_route' => 'Continuar ruta',
    'arrived' => 'Completar entrega',
    _ => 'Abrir entrega',
  };

  IconData _actionIcon(String status) => switch (status) {
    'en_route' => Icons.navigation_rounded,
    'arrived' => Icons.verified_outlined,
    _ => Icons.arrow_forward_rounded,
  };

  Widget _deliveryCard(DeliveryAssignment item, bool available) {
    final scheme = Theme.of(context).colorScheme;
    return Card(
      margin: const EdgeInsets.only(bottom: 14),
      elevation: 0,
      color: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(22),
        side: const BorderSide(color: Color(0xFFDCE8EF)),
      ),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => _showDetails(item, available: available),
        child: Padding(
          padding: const EdgeInsets.all(18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: scheme.primaryContainer.withValues(alpha: .72),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Icon(
                      _statusIcon(item.status),
                      color: scheme.primary,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'ORDEN',
                          style: TextStyle(
                            color: Color(0xFF7C8B9A),
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            letterSpacing: .8,
                          ),
                        ),
                        Text(
                          item.orderNumber,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: AppTheme.ink,
                            fontSize: 16,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ],
                    ),
                  ),
                  _StatusPill(label: _statusLabel(item.status)),
                ],
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  const Icon(
                    Icons.schedule_outlined,
                    size: 18,
                    color: Color(0xFF64748B),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      '${item.shippingMethod} · ${item.deliveryTime}',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Color(0xFF475569),
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(
                    Icons.location_on_outlined,
                    size: 18,
                    color: Color(0xFF64748B),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      item.address.isEmpty ? item.city : item.address,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Color(0xFF475569),
                        height: 1.35,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              const Divider(height: 1, color: Color(0xFFE6EEF3)),
              const SizedBox(height: 12),
              Row(
                children: [
                  Text(
                    available ? 'Ver y aceptar' : 'Ver detalles',
                    style: const TextStyle(
                      color: AppTheme.primary,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const Spacer(),
                  const Icon(
                    Icons.arrow_forward_rounded,
                    color: AppTheme.primary,
                    size: 20,
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _emptyState(bool available) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(28, 76, 28, 24),
      children: [
        Container(
          width: 76,
          height: 76,
          decoration: BoxDecoration(
            color: AppTheme.primary.withValues(alpha: .1),
            shape: BoxShape.circle,
          ),
          child: Icon(
            available ? Icons.inventory_2_outlined : Icons.route_outlined,
            color: AppTheme.primary,
            size: 36,
          ),
        ),
        const SizedBox(height: 20),
        Text(
          available ? 'Todo está al día' : 'Aún no tienes entregas activas',
          textAlign: TextAlign.center,
          style: Theme.of(
            context,
          ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800),
        ),
        const SizedBox(height: 8),
        Text(
          available
              ? 'Las nuevas solicitudes aparecerán aquí. Desliza hacia abajo para actualizar.'
              : 'Cuando aceptes una entrega podrás consultar su estado y comenzar la ruta desde aquí.',
          textAlign: TextAlign.center,
          style: const TextStyle(color: Color(0xFF64748B), height: 1.45),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Angelow Repartidor'),
            Text(
              'Centro de entregas',
              style: TextStyle(fontSize: 12, fontWeight: FontWeight.w400),
            ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Actualizar',
            onPressed: _load,
            icon: const Icon(Icons.refresh_rounded),
          ),
          IconButton(
            tooltip: 'Cerrar sesión',
            onPressed: widget.onLogout,
            icon: const Icon(Icons.logout_rounded),
          ),
          const SizedBox(width: 6),
        ],
      ),
      body: ListenableBuilder(
        listenable: viewModel,
        builder: (context, _) {
          final available = viewModel.section == DeliverySection.available;
          final rows = viewModel.visible;
          return Column(
            children: [
              Container(
                width: double.infinity,
                padding: const EdgeInsets.fromLTRB(18, 16, 18, 14),
                color: Colors.white,
                child: SegmentedButton<DeliverySection>(
                  showSelectedIcon: false,
                  segments: [
                    ButtonSegment(
                      value: DeliverySection.available,
                      label: Text('Disponibles  ${viewModel.available.length}'),
                      icon: const Icon(Icons.inbox_outlined),
                    ),
                    ButtonSegment(
                      value: DeliverySection.mine,
                      label: Text('Mis entregas  ${viewModel.mine.length}'),
                      icon: const Icon(Icons.local_shipping_outlined),
                    ),
                  ],
                  selected: {viewModel.section},
                  onSelectionChanged: (value) => viewModel.select(value.first),
                ),
              ),
              if (viewModel.loading)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 12),
                  child: AngelowLoadingIndicator(
                    compact: true,
                    label: 'Actualizando entregas…',
                  ),
                ),
              if (viewModel.error != null)
                Padding(
                  padding: const EdgeInsets.fromLTRB(18, 14, 18, 0),
                  child: _InlineMessage(text: viewModel.error!),
                ),
              Expanded(
                child: RefreshIndicator(
                  onRefresh: _load,
                  child: rows.isEmpty
                      ? _emptyState(available)
                      : ListView.builder(
                          physics: const AlwaysScrollableScrollPhysics(),
                          padding: const EdgeInsets.fromLTRB(18, 18, 18, 30),
                          itemCount: rows.length,
                          itemBuilder: (context, index) =>
                              _deliveryCard(rows[index], available),
                        ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _StatusPill extends StatelessWidget {
  const _StatusPill({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: AppTheme.primary.withValues(alpha: .1),
        borderRadius: BorderRadius.circular(99),
      ),
      child: Text(
        label,
        style: const TextStyle(
          color: Color(0xFF0877A9),
          fontSize: 11,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  const _DetailRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 21, color: const Color(0xFF64748B)),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: const TextStyle(
                    color: Color(0xFF7C8B9A),
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  value,
                  style: const TextStyle(
                    color: AppTheme.ink,
                    fontWeight: FontWeight.w600,
                    height: 1.35,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _InlineMessage extends StatelessWidget {
  const _InlineMessage({required this.text});

  final String text;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.errorContainer,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            Icons.error_outline_rounded,
            color: Theme.of(context).colorScheme.onErrorContainer,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: TextStyle(
                color: Theme.of(context).colorScheme.onErrorContainer,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class DeliveryRouteView extends StatefulWidget {
  const DeliveryRouteView({
    super.key,
    required this.repository,
    required this.token,
    required this.vehicleType,
    required this.mapboxAccessToken,
    required this.assignment,
  });
  final CourierRepository repository;
  final String token;
  final String vehicleType;
  final String mapboxAccessToken;
  final DeliveryAssignment assignment;

  @override
  State<DeliveryRouteView> createState() => _DeliveryRouteViewState();
}

class _DeliveryRouteViewState extends State<DeliveryRouteView> {
  late final MapboxDirectionsService directions;
  late final NominatimGeocodingService geocoding;
  late final ExternalNavigationService externalNavigation;
  MapboxMap? map;
  PolylineAnnotationManager? routeLines;
  CircleAnnotationManager? mapMarkers;
  CircleAnnotation? destinationMarker;
  DeliveryAssignment? assignment;
  StreamSubscription<geo.Position>? locationSubscription;
  bool busy = false;
  bool sharing = false;
  bool resolvingDestination = false;
  bool calculatingRoute = false;
  String? routeSummary;
  String? routeError;
  double? destinationLatitude;
  double? destinationLongitude;
  Future<void>? destinationResolution;
  ExternalNavigationApp? openingNavigation;

  DeliveryAssignment get current => assignment ?? widget.assignment;

  @override
  void initState() {
    super.initState();
    directions = MapboxDirectionsService(accessToken: widget.mapboxAccessToken);
    geocoding = NominatimGeocodingService();
    externalNavigation = const ExternalNavigationService();
    destinationLatitude = widget.assignment.latitude;
    destinationLongitude = widget.assignment.longitude;
    sharing = widget.assignment.sharingLocation;
  }

  @override
  void dispose() {
    locationSubscription?.cancel();
    super.dispose();
  }

  Future<bool> _permission() async {
    if (!await geo.Geolocator.isLocationServiceEnabled()) {
      return false;
    }
    var permission = await geo.Geolocator.checkPermission();
    if (permission == geo.LocationPermission.denied) {
      permission = await geo.Geolocator.requestPermission();
    }
    return permission == geo.LocationPermission.always ||
        permission == geo.LocationPermission.whileInUse;
  }

  Future<void> _start() async {
    if (!await _permission()) {
      _message('Activa el permiso de ubicación para comenzar la ruta.');
      return;
    }
    await _resolveDestination();
    if (!_hasDestination) {
      _message('No fue posible ubicar la dirección de destino.');
      return;
    }
    if (!mounted) {
      return;
    }
    final consent =
        await showDialog<bool>(
          context: context,
          builder: (context) => AlertDialog(
            title: const Text('Compartir ubicación'),
            content: const Text(
              '¿Quieres compartir tu ubicación en tiempo real con el cliente durante esta entrega? Puedes continuar sin compartirla.',
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text('No compartir'),
              ),
              FilledButton(
                onPressed: () => Navigator.pop(context, true),
                child: const Text('Compartir'),
              ),
            ],
          ),
        ) ??
        false;
    setState(() => busy = true);
    try {
      final position = await geo.Geolocator.getCurrentPosition();
      await _calculateAndDrawRoute(position);
      assignment = await widget.repository.startRoute(
        widget.token,
        current.id,
        consent,
      );
      sharing = consent;
      await _startPositionUpdates();
    } catch (exception) {
      _message(exception.toString());
    }
    if (mounted) {
      setState(() => busy = false);
    }
  }

  Future<void> _arrive() async {
    setState(() => busy = true);
    try {
      assignment = await widget.repository.arrive(widget.token, current.id);
      await locationSubscription?.cancel();
      if (mounted) {
        setState(() {});
      }
    } catch (exception) {
      _message(exception.toString());
    }
    if (mounted) {
      setState(() => busy = false);
    }
  }

  Future<void> _complete() async {
    final controller = TextEditingController();
    final code = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Código de entrega'),
        content: TextField(
          controller: controller,
          maxLength: 6,
          keyboardType: TextInputType.number,
          decoration: const InputDecoration(labelText: 'Código del cliente'),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, controller.text),
            child: const Text('Finalizar entrega'),
          ),
        ],
      ),
    );
    if (code == null) {
      return;
    }
    try {
      assignment = await widget.repository.complete(
        widget.token,
        current.id,
        code,
      );
      if (mounted) {
        Navigator.pop(context);
      }
    } catch (exception) {
      _message(exception.toString());
    }
  }

  void _message(String text) {
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(text)));
    }
  }

  bool get _hasDestination {
    final latitude = destinationLatitude;
    final longitude = destinationLongitude;
    return latitude != null &&
        longitude != null &&
        latitude >= -90 &&
        latitude <= 90 &&
        longitude >= -180 &&
        longitude <= 180;
  }

  Future<void> _calculateAndDrawRoute(geo.Position position) async {
    calculatingRoute = true;
    routeError = null;
    if (mounted) setState(() {});

    try {
      await _resolveDestination();
      if (!_hasDestination) {
        throw StateError('No fue posible ubicar la dirección de destino.');
      }

      final plan = await directions.route(
        originLatitude: position.latitude,
        originLongitude: position.longitude,
        destinationLatitude: destinationLatitude!,
        destinationLongitude: destinationLongitude!,
        vehicleType: widget.vehicleType,
      );
      if (plan.coordinates.isEmpty) {
        throw StateError('Mapbox no devolvió el trazado de la ruta.');
      }

      final points = plan.coordinates
          .map(
            (coordinate) =>
                Point(coordinates: Position(coordinate[0], coordinate[1])),
          )
          .toList();
      await routeLines?.deleteAll();
      await routeLines?.create(
        PolylineAnnotationOptions(
          geometry: LineString(
            coordinates: plan.coordinates
                .map((coordinate) => Position(coordinate[0], coordinate[1]))
                .toList(),
          ),
          lineColor: AppTheme.primary.toARGB32(),
          lineWidth: 7,
        ),
      );

      routeSummary =
          '${(plan.distanceMeters / 1000).toStringAsFixed(1)} km · ${(plan.durationSeconds / 60).ceil()} min';
      final activeMap = map;
      if (activeMap != null) {
        final camera = await activeMap.cameraForCoordinatesPadding(
          points,
          CameraOptions(pitch: 42),
          MbxEdgeInsets(top: 54, left: 42, bottom: 72, right: 42),
          17,
          null,
        );
        await activeMap.flyTo(camera, MapAnimationOptions(duration: 1100));
      }
    } catch (exception) {
      routeError = _exceptionMessage(exception);
      rethrow;
    } finally {
      calculatingRoute = false;
      if (mounted) setState(() {});
    }
  }

  Future<void> _retryRoute() async {
    if (!await _permission()) {
      _message('Activa el permiso de ubicación para calcular la ruta.');
      return;
    }
    try {
      final position = await geo.Geolocator.getCurrentPosition(
        locationSettings: const geo.LocationSettings(
          accuracy: geo.LocationAccuracy.high,
        ),
      );
      await _calculateAndDrawRoute(position);
      if (current.status == 'en_route') {
        await _startPositionUpdates();
      }
    } catch (exception) {
      _message(_exceptionMessage(exception));
    }
  }

  Future<void> _startPositionUpdates() async {
    await locationSubscription?.cancel();
    locationSubscription =
        geo.Geolocator.getPositionStream(
          locationSettings: const geo.LocationSettings(
            accuracy: geo.LocationAccuracy.high,
            distanceFilter: 10,
          ),
        ).listen((value) {
          map?.easeTo(
            CameraOptions(
              center: Point(
                coordinates: Position(value.longitude, value.latitude),
              ),
              bearing: value.heading,
              pitch: 58,
              zoom: 17,
            ),
            MapAnimationOptions(duration: 700),
          );
          if (sharing) {
            unawaited(
              widget.repository
                  .sendLocation(
                    widget.token,
                    current.id,
                    latitude: value.latitude,
                    longitude: value.longitude,
                    heading: value.heading,
                    speed: value.speed,
                    accuracy: value.accuracy,
                  )
                  .catchError((_) {}),
            );
          }
        });
  }

  String _exceptionMessage(Object exception) => exception
      .toString()
      .replaceFirst('Bad state: ', '')
      .replaceFirst('Exception: ', '');

  Future<void> _openNavigation(ExternalNavigationApp app) async {
    openingNavigation = app;
    if (mounted) setState(() {});
    try {
      await _resolveDestination();
      if (!_hasDestination) {
        _message('No fue posible ubicar la dirección de destino.');
        return;
      }
      final opened = await externalNavigation.open(
        app: app,
        latitude: destinationLatitude!,
        longitude: destinationLongitude!,
      );
      if (!opened) {
        _message('No fue posible abrir la aplicación de navegación.');
      }
    } catch (exception) {
      _message(_exceptionMessage(exception));
    } finally {
      openingNavigation = null;
      if (mounted) setState(() {});
    }
  }

  Future<void> _prepareMap(MapboxMap value) async {
    map = value;
    routeLines = await value.annotations.createPolylineAnnotationManager();
    mapMarkers = await value.annotations.createCircleAnnotationManager();
    final navigationArrow = await _navigationArrowImage();
    await value.location.updateSettings(
      LocationComponentSettings(
        enabled: true,
        puckBearingEnabled: true,
        puckBearing: PuckBearing.HEADING,
        pulsingEnabled: true,
        showAccuracyRing: true,
        locationPuck: LocationPuck(
          locationPuck2D: DefaultLocationPuck2D(bearingImage: navigationArrow),
        ),
      ),
    );

    if (_hasDestination) {
      await _showDestination();
    } else {
      unawaited(_resolveDestination());
    }

    if (!await _permission()) return;

    try {
      final position = await geo.Geolocator.getCurrentPosition(
        locationSettings: const geo.LocationSettings(
          accuracy: geo.LocationAccuracy.high,
        ),
      );
      if (current.status == 'en_route') {
        await _calculateAndDrawRoute(position);
        await _startPositionUpdates();
      } else {
        await value.flyTo(
          CameraOptions(
            center: Point(
              coordinates: Position(position.longitude, position.latitude),
            ),
            zoom: 16,
          ),
          MapAnimationOptions(duration: 900),
        );
      }
    } catch (exception) {
      if (current.status == 'en_route' && routeError == null) {
        routeError = _exceptionMessage(exception);
        if (mounted) setState(() {});
      }
    }
  }

  Future<Uint8List> _navigationArrowImage() async {
    const dimension = 72.0;
    final recorder = ui.PictureRecorder();
    final canvas = Canvas(recorder);
    final arrow = Path()
      ..moveTo(36, 4)
      ..lineTo(68, 66)
      ..lineTo(36, 54)
      ..lineTo(4, 66)
      ..close();
    canvas.drawPath(
      arrow,
      Paint()
        ..color = Colors.white
        ..style = PaintingStyle.fill,
    );

    final centerArrow = Path()
      ..moveTo(36, 13)
      ..lineTo(59, 55)
      ..lineTo(36, 46)
      ..lineTo(13, 55)
      ..close();
    canvas.drawPath(
      centerArrow,
      Paint()
        ..color = AppTheme.primary
        ..style = PaintingStyle.fill,
    );

    final image = await recorder.endRecording().toImage(
      dimension.toInt(),
      dimension.toInt(),
    );
    final data = await image.toByteData(format: ui.ImageByteFormat.png);
    if (data == null) {
      throw StateError('No fue posible crear el indicador de navegación.');
    }
    return data.buffer.asUint8List();
  }

  Future<void> _resolveDestination() async {
    if (_hasDestination) return;

    final pendingResolution = destinationResolution;
    if (pendingResolution != null) {
      await pendingResolution;
      return;
    }

    final resolution = _geocodeDestination();
    destinationResolution = resolution;
    try {
      await resolution;
    } finally {
      if (identical(destinationResolution, resolution)) {
        destinationResolution = null;
      }
    }
  }

  Future<void> _geocodeDestination() async {
    final query = [current.address, current.city]
        .map((value) => value.trim())
        .where((value) => value.isNotEmpty)
        .join(', ');
    if (query.length < 5) return;

    resolvingDestination = true;
    if (mounted) setState(() {});
    try {
      final location = await geocoding.geocode(query);
      if (location != null) {
        destinationLatitude = location.latitude;
        destinationLongitude = location.longitude;
        await _showDestination();
      }
    } finally {
      resolvingDestination = false;
      if (mounted) setState(() {});
    }
  }

  Future<void> _showDestination() async {
    final manager = mapMarkers;
    if (manager == null || !_hasDestination) return;

    final previous = destinationMarker;
    if (previous != null) await manager.delete(previous);
    destinationMarker = await manager.create(
      CircleAnnotationOptions(
        geometry: Point(
          coordinates: Position(destinationLongitude!, destinationLatitude!),
        ),
        circleColor: const Color(0xFFE53935).toARGB32(),
        circleRadius: 8,
        circleStrokeColor: Colors.white.toARGB32(),
        circleStrokeWidth: 3,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final destination = _hasDestination
        ? Point(
            coordinates: Position(destinationLongitude!, destinationLatitude!),
          )
        : Point(coordinates: Position(-75.5812, 6.2442));
    return Scaffold(
      appBar: AppBar(title: Text('Orden ${current.orderNumber}')),
      body: Column(
        children: [
          Expanded(
            child: widget.mapboxAccessToken.isEmpty
                ? const Center(
                    child: Padding(
                      padding: EdgeInsets.all(30),
                      child: Text(
                        'La navegación no está disponible temporalmente. Actualiza tus entregas e inténtalo de nuevo.',
                        textAlign: TextAlign.center,
                      ),
                    ),
                  )
                : MapWidget(
                    key: const ValueKey('delivery-map'),
                    // ignore: deprecated_member_use
                    cameraOptions: CameraOptions(
                      center: destination,
                      zoom: 15,
                      pitch: 55,
                    ),
                    onMapCreated: _prepareMap,
                  ),
          ),
          Padding(
            padding: const EdgeInsets.all(18),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  current.address,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w800,
                  ),
                ),
                Text(
                  '${current.shippingMethod} · ${routeSummary ?? current.deliveryTime}',
                ),
                if (resolvingDestination) ...[
                  const SizedBox(height: 8),
                  const Text('Ubicando la dirección de destino…'),
                ],
                if (calculatingRoute) ...[
                  const SizedBox(height: 8),
                  const Row(
                    children: [
                      SizedBox.square(
                        dimension: 16,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      ),
                      SizedBox(width: 9),
                      Text('Calculando la mejor ruta…'),
                    ],
                  ),
                ],
                if (routeError != null && !calculatingRoute) ...[
                  const SizedBox(height: 8),
                  Text(
                    routeError!,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.error,
                    ),
                  ),
                  Align(
                    alignment: Alignment.centerLeft,
                    child: TextButton.icon(
                      onPressed: _retryRoute,
                      icon: const Icon(Icons.refresh_rounded),
                      label: const Text('Reintentar cálculo'),
                    ),
                  ),
                ],
                const SizedBox(height: 14),
                if (_hasDestination) ...[
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: openingNavigation == null
                              ? () => _openNavigation(
                                  ExternalNavigationApp.googleMaps,
                                )
                              : null,
                          icon:
                              openingNavigation ==
                                  ExternalNavigationApp.googleMaps
                              ? const SizedBox.square(
                                  dimension: 17,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                  ),
                                )
                              : const Icon(Icons.map_outlined),
                          label: const Text('Google Maps'),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: openingNavigation == null
                              ? () =>
                                    _openNavigation(ExternalNavigationApp.waze)
                              : null,
                          icon: openingNavigation == ExternalNavigationApp.waze
                              ? const SizedBox.square(
                                  dimension: 17,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                  ),
                                )
                              : const Icon(Icons.assistant_direction_outlined),
                          label: const Text('Waze'),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                ],
                if (current.status == 'assigned')
                  ElevatedButton.icon(
                    onPressed: busy ? null : _start,
                    icon: const Icon(Icons.navigation),
                    label: const Text('Comenzar ruta'),
                  ),
                if (current.status == 'en_route')
                  ElevatedButton.icon(
                    onPressed: busy ? null : _arrive,
                    icon: const Icon(Icons.location_on),
                    label: const Text('Llegué al destino'),
                  ),
                if (current.status == 'arrived')
                  ElevatedButton.icon(
                    onPressed: busy ? null : _complete,
                    icon: const Icon(Icons.verified),
                    label: const Text('Ingresar código y finalizar'),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
