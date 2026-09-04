import 'dart:io';

import 'package:file_selector/file_selector.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';

import '../../../../config/app_config.dart';
import '../../../../data/models/catalog_option.dart';
import '../../../../data/models/courier_profile.dart';
import '../../../../data/repositories/courier_repository.dart';
import '../../../../data/services/vehicle_catalog_service.dart';
import '../../../../domain/courier_registration_rules.dart';
import '../../../core/validation/app_validators.dart';
import '../../../core/widgets/angelow_loading_indicator.dart';
import '../../../core/widgets/form_app_bar.dart';
import '../../../core/widgets/searchable_picker_field.dart';

class CourierRegistrationView extends StatefulWidget {
  const CourierRegistrationView({
    super.key,
    required this.repository,
    required this.token,
    required this.onSubmitted,
    this.initialProfile,
  });

  final CourierRepository repository;
  final String token;
  final ValueChanged<CourierProfile> onSubmitted;
  final CourierProfile? initialProfile;

  @override
  State<CourierRegistrationView> createState() =>
      _CourierRegistrationViewState();
}

class _CourierRegistrationViewState extends State<CourierRegistrationView> {
  static const _stepLabels = [
    'Identidad',
    'Transporte',
    'Documentos',
    'Términos',
  ];
  static const _stepIcons = [
    Icons.badge_outlined,
    Icons.route_outlined,
    Icons.folder_copy_outlined,
    Icons.verified_user_outlined,
  ];

  final catalog = VehicleCatalogService();
  final identityFormKey = GlobalKey<FormState>();
  final vehicleFormKey = GlobalKey<FormState>();
  final values = <String, TextEditingController>{};
  final documents = <String, File>{};

  int step = 0;
  String documentType = 'cc';
  String vehicleType = 'foot';
  String ownership = 'owned';
  CatalogOption? make;
  CatalogOption? model;
  CatalogOption? color;
  List<CatalogOption> makes = const [];
  List<CatalogOption> models = const [];
  List<CatalogOption> colors = const [];
  bool loadingCatalog = false;
  bool acceptTerms = false;
  bool inspectionNotApplicable = false;
  bool saving = false;
  String? error;

  bool get isMotorized => CourierRegistrationRules.isMotorized(vehicleType);

  TextEditingController _c(String key) =>
      values.putIfAbsent(key, TextEditingController.new);

  List<String> get requiredDocuments =>
      CourierRegistrationRules.requiredDocuments(
        vehicleType,
        inspectionNotApplicable: inspectionNotApplicable,
      );

  List<String> get visibleDocuments =>
      CourierRegistrationRules.visibleDocuments(vehicleType);

  Set<String> get existingDocumentTypes =>
      widget.initialProfile?.documents
          .map((document) => document.type)
          .toSet() ??
      const {};

  Set<String> get documentsToReplace =>
      widget.initialProfile?.documentsRequiringChanges
          .map((document) => document.type)
          .toSet() ??
      const {};

  @override
  void initState() {
    super.initState();
    final initial = widget.initialProfile;
    if (initial == null) return;

    documentType = initial.documentType ?? documentType;
    vehicleType = initial.vehicle.type;
    ownership = initial.vehicle.ownershipType ?? ownership;
    _c('document_number').text = initial.documentNumber ?? '';
    _c('birth_date').text = initial.birthDate ?? '';
    _c('phone').text = initial.phone ?? '';
    _c('address').text = initial.address ?? '';
    _c('year').text = initial.vehicle.year?.toString() ?? '';
    _c('plate').text = initial.vehicle.plate ?? '';

    if (initial.vehicle.makeId != null && initial.vehicle.makeName != null) {
      make = CatalogOption(
        id: initial.vehicle.makeId!,
        label: initial.vehicle.makeName!,
      );
      makes = [make!];
    }
    if (initial.vehicle.modelId != null && initial.vehicle.modelName != null) {
      model = CatalogOption(
        id: initial.vehicle.modelId!,
        label: initial.vehicle.modelName!,
      );
      models = [model!];
    }
    if (initial.vehicle.colorName != null) {
      color = CatalogOption(
        id: initial.vehicle.colorHex?.replaceFirst('#', '') ?? 'other',
        label: initial.vehicle.colorName!,
        hex: initial.vehicle.colorHex,
      );
      colors = [color!];
    }
  }

  @override
  void dispose() {
    for (final controller in values.values) {
      controller.dispose();
    }
    super.dispose();
  }

  void _setDocumentType(String value) {
    setState(() {
      documentType = value;
      if (value == 'cc' || value == 'ce') {
        _c('document_number').text = _c(
          'document_number',
        ).text.replaceAll(RegExp(r'[^0-9]'), '');
      }
    });
    identityFormKey.currentState?.validate();
  }

  Future<void> _selectBirthDate() async {
    final today = DateTime.now();
    final initialDate =
        DateTime.tryParse(_c('birth_date').text) ??
        DateTime(today.year - 25, today.month, today.day);
    final selected = await showDatePicker(
      context: context,
      initialDate: initialDate,
      firstDate: DateTime(today.year - 100, today.month, today.day),
      lastDate: DateTime(today.year - 18, today.month, today.day),
      helpText: 'Selecciona tu fecha de nacimiento',
      cancelText: 'Cancelar',
      confirmText: 'Aceptar',
    );
    if (selected != null) {
      setState(
        () => _c('birth_date').text = DateFormat('yyyy-MM-dd').format(selected),
      );
      identityFormKey.currentState?.validate();
    }
  }

  Future<void> _selectVehicle(String type) async {
    final shouldLoadCatalog = CourierRegistrationRules.isMotorized(type);
    setState(() {
      vehicleType = type;
      make = model = color = null;
      makes = models = colors = const [];
      loadingCatalog = shouldLoadCatalog;
      inspectionNotApplicable = false;
      error = null;
    });
    if (!shouldLoadCatalog) {
      return;
    }
    try {
      final result = await Future.wait([catalog.makes(type), catalog.colors()]);
      makes = result[0];
      colors = result[1];
    } catch (_) {
      error =
          'No pudimos cargar todo el catálogo. Puedes usar la opción “Otra”.';
      makes = const [CatalogOption(id: 'other', label: 'Otra')];
      colors = const [
        CatalogOption(id: '000000', label: 'Negro', hex: '#000000'),
        CatalogOption(id: 'other', label: 'Otro', hex: '#808080'),
      ];
    }
    if (mounted) {
      setState(() => loadingCatalog = false);
    }
  }

  Future<void> _selectMake(CatalogOption? value) async {
    if (value == null) {
      return;
    }
    setState(() {
      make = value;
      model = null;
      loadingCatalog = true;
      error = null;
    });
    try {
      models = await catalog.models(value.id, value.label, vehicleType);
    } catch (_) {
      error =
          'No pudimos cargar los modelos. Selecciona “Otro” para continuar.';
      models = const [CatalogOption(id: 'other', label: 'Otro')];
    }
    if (mounted) {
      setState(() => loadingCatalog = false);
    }
  }

  Future<void> _pick(String type) async {
    const documentTypes = XTypeGroup(
      label: 'documentos',
      extensions: ['jpg', 'jpeg', 'png', 'pdf'],
      mimeTypes: ['image/jpeg', 'image/png', 'application/pdf'],
    );
    final result = await openFile(acceptedTypeGroups: [documentTypes]);
    if (result == null) {
      return;
    }
    final file = File(result.path);
    if (await file.length() > 8 * 1024 * 1024) {
      setState(
        () => error = 'El archivo supera el tamaño máximo permitido de 8 MB.',
      );
      return;
    }
    setState(() {
      documents[type] = file;
      error = null;
    });
  }

  void _previous() {
    if (step == 0) {
      return;
    }
    setState(() {
      step--;
      error = null;
    });
  }

  void _next() {
    var valid = true;
    if (step == 0) {
      valid = identityFormKey.currentState?.validate() ?? false;
    } else if (step == 1) {
      valid = vehicleFormKey.currentState?.validate() ?? false;
    } else if (step == 2) {
      final missing = requiredDocuments
          .where(
            (type) =>
                !documents.containsKey(type) &&
                (!existingDocumentTypes.contains(type) ||
                    documentsToReplace.contains(type)),
          )
          .map(_documentLabel)
          .toList();
      valid = missing.isEmpty;
      if (!valid) {
        error = 'Adjunta los documentos obligatorios: ${missing.join(', ')}.';
      }
    }
    if (!valid) {
      setState(() {});
      return;
    }
    setState(() {
      step++;
      error = null;
    });
  }

  Future<void> _submit() async {
    if (!acceptTerms) {
      setState(
        () => error = 'Debes aceptar los términos y condiciones para enviar.',
      );
      return;
    }
    setState(() {
      saving = true;
      error = null;
    });
    try {
      final fields = <String, String>{
        'document_type': documentType,
        'document_number': _c('document_number').text.trim(),
        'birth_date': _c('birth_date').text.trim(),
        'phone': _c('phone').text.trim(),
        'address': _c('address').text.trim(),
        'terms_version': AppConfig.termsVersion,
        'accept_terms': '1',
        'technical_inspection_not_applicable': inspectionNotApplicable
            ? '1'
            : '0',
        'vehicle[type]': vehicleType,
        'vehicle[make_id]': isMotorized ? make?.id ?? '' : '',
        'vehicle[make_name]': isMotorized ? make?.label ?? '' : '',
        'vehicle[model_id]': isMotorized ? model?.id ?? '' : '',
        'vehicle[model_name]': isMotorized ? model?.label ?? '' : '',
        'vehicle[color_name]': isMotorized ? color?.label ?? '' : '',
        'vehicle[color_hex]': isMotorized ? color?.hex ?? '' : '',
        'vehicle[year]': isMotorized ? _c('year').text.trim() : '',
        'vehicle[plate]': isMotorized
            ? _c('plate').text.trim().toUpperCase()
            : '',
        'vehicle[ownership_type]': isMotorized ? ownership : '',
      };
      final files = Map<String, File>.fromEntries(
        documents.entries
            .where((entry) => visibleDocuments.contains(entry.key))
            .map((entry) => MapEntry('documents[${entry.key}]', entry.value)),
      );
      final profile = await widget.repository.saveProfile(
        token: widget.token,
        fields: fields,
        documents: files,
      );
      widget.onSubmitted(profile);
    } catch (exception) {
      if (mounted) {
        setState(() => error = exception.toString());
      }
    } finally {
      if (mounted) {
        setState(() => saving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: FormAppBar(
      title: 'Registro de repartidor',
      subtitle: 'Paso ${step + 1} de 4 · ${_stepLabels[step]}',
      onBack: step > 0 && !saving ? _previous : null,
    ),
    body: SafeArea(
      child: Column(
        children: [
          _progress(),
          Expanded(
            child: ListView(
              keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
              padding: const EdgeInsets.fromLTRB(22, 22, 22, 28),
              children: [
                AnimatedSwitcher(
                  duration: const Duration(milliseconds: 220),
                  child: KeyedSubtree(key: ValueKey(step), child: _content()),
                ),
                if (error != null)
                  Container(
                    margin: const EdgeInsets.only(top: 18),
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
                          color: Theme.of(context).colorScheme.error,
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            error!,
                            style: TextStyle(
                              color: Theme.of(
                                context,
                              ).colorScheme.onErrorContainer,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
              ],
            ),
          ),
          _navigation(),
        ],
      ),
    ),
  );

  Widget _progress() {
    final scheme = Theme.of(context).colorScheme;
    return Container(
      color: scheme.surface,
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 14),
      child: Row(
        children: List.generate(4, (index) {
          final selected = index == step;
          final completed = index < step;
          return Expanded(
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 180),
              margin: const EdgeInsets.symmetric(horizontal: 3),
              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 9),
              decoration: BoxDecoration(
                color: selected ? scheme.primaryContainer : Colors.transparent,
                borderRadius: BorderRadius.circular(14),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  AnimatedContainer(
                    duration: const Duration(milliseconds: 180),
                    width: 34,
                    height: 34,
                    decoration: BoxDecoration(
                      color: completed || selected
                          ? scheme.primary
                          : scheme.surfaceContainerHighest,
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      completed ? Icons.check_rounded : _stepIcons[index],
                      size: 19,
                      color: completed || selected
                          ? scheme.onPrimary
                          : scheme.onSurfaceVariant,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    _stepLabels[index],
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.labelSmall?.copyWith(
                      color: selected
                          ? scheme.onPrimaryContainer
                          : scheme.onSurfaceVariant,
                      fontWeight: selected ? FontWeight.w800 : FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
          );
        }),
      ),
    );
  }

  Widget _navigation() {
    final isLastStep = step == 3;
    return DecoratedBox(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: .07),
            blurRadius: 16,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(22, 12, 22, 18),
        child: Row(
          children: [
            if (step > 0)
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: saving ? null : _previous,
                  icon: const Icon(Icons.arrow_back_rounded),
                  label: const Text('Atrás'),
                ),
              ),
            if (step > 0) const SizedBox(width: 12),
            Expanded(
              flex: step > 0 ? 1 : 2,
              child: ElevatedButton.icon(
                onPressed: saving || (isLastStep && !acceptTerms)
                    ? null
                    : (isLastStep ? _submit : _next),
                icon: Icon(
                  isLastStep ? Icons.send_rounded : Icons.arrow_forward_rounded,
                ),
                label: Text(
                  saving
                      ? 'Enviando…'
                      : isLastStep
                      ? 'Enviar solicitud'
                      : 'Siguiente',
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _content() => switch (step) {
    0 => _personal(),
    1 => _vehicle(),
    2 => _documents(),
    _ => _consents(),
  };

  Widget _personal() => Form(
    key: identityFormKey,
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _title(
          'Tu identidad',
          'Completa únicamente los datos necesarios para validar tu solicitud.',
        ),
        _operationCity(),
        const SizedBox(height: 18),
        DropdownButtonFormField<String>(
          initialValue: documentType,
          decoration: const InputDecoration(
            labelText: 'Tipo de documento',
            prefixIcon: Icon(Icons.badge_outlined),
          ),
          items: const [
            DropdownMenuItem(value: 'cc', child: Text('Cédula de ciudadanía')),
            DropdownMenuItem(value: 'ce', child: Text('Cédula de extranjería')),
            DropdownMenuItem(value: 'passport', child: Text('Pasaporte')),
            DropdownMenuItem(value: 'ppt', child: Text('PPT')),
          ],
          onChanged: (value) {
            if (value != null) {
              _setDocumentType(value);
            }
          },
        ),
        const SizedBox(height: 16),
        _field(
          'document_number',
          'Número de documento',
          type: documentType == 'cc' || documentType == 'ce'
              ? TextInputType.number
              : TextInputType.text,
          icon: Icons.numbers_rounded,
          validator: (value) =>
              AppValidators.documentNumber(value, documentType),
          inputFormatters: [
            if (documentType == 'cc' || documentType == 'ce')
              FilteringTextInputFormatter.digitsOnly
            else
              FilteringTextInputFormatter.allow(RegExp(r'[A-Za-z0-9-]')),
            LengthLimitingTextInputFormatter(20),
          ],
        ),
        const SizedBox(height: 16),
        _field(
          'birth_date',
          'Fecha de nacimiento',
          type: TextInputType.datetime,
          icon: Icons.calendar_month_outlined,
          validator: AppValidators.birthDate,
          readOnly: true,
          onTap: _selectBirthDate,
          hintText: 'AAAA-MM-DD',
        ),
        const SizedBox(height: 16),
        _field(
          'phone',
          'Celular',
          type: TextInputType.phone,
          icon: Icons.phone_outlined,
          validator: AppValidators.colombianPhone,
          hintText: '3XXXXXXXXX',
          inputFormatters: [
            FilteringTextInputFormatter.digitsOnly,
            LengthLimitingTextInputFormatter(10),
          ],
        ),
        const SizedBox(height: 16),
        _field(
          'address',
          'Dirección de residencia',
          icon: Icons.home_outlined,
          validator: (value) =>
              AppValidators.requiredText(value, label: 'tu dirección'),
          inputFormatters: [LengthLimitingTextInputFormatter(180)],
        ),
      ],
    ),
  );

  Widget _operationCity() => DecoratedBox(
    decoration: BoxDecoration(
      color: Theme.of(context).colorScheme.secondaryContainer,
      borderRadius: BorderRadius.circular(14),
    ),
    child: Padding(
      padding: const EdgeInsets.all(14),
      child: Row(
        children: [
          Icon(
            Icons.location_on_outlined,
            color: Theme.of(context).colorScheme.onSecondaryContainer,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              'Zona de operación: Medellín',
              style: Theme.of(context).textTheme.titleSmall?.copyWith(
                color: Theme.of(context).colorScheme.onSecondaryContainer,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    ),
  );

  Widget _vehicle() => Form(
    key: vehicleFormKey,
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _title(
          'Tu medio de transporte',
          'Selecciona cómo realizarás las entregas.',
        ),
        Wrap(
          spacing: 10,
          runSpacing: 10,
          children:
              const [
                ('foot', 'A pie', Icons.directions_walk_rounded),
                ('bicycle', 'Bicicleta', Icons.pedal_bike_rounded),
                ('motorcycle', 'Moto', Icons.two_wheeler_rounded),
                ('car', 'Auto', Icons.directions_car_outlined),
                ('truck', 'Camión', Icons.local_shipping_outlined),
              ].map((option) {
                final (type, label, icon) = option;
                final selected = vehicleType == type;
                return ChoiceChip(
                  selected: selected,
                  showCheckmark: false,
                  avatar: Icon(
                    icon,
                    size: 20,
                    color: selected
                        ? Theme.of(context).colorScheme.onPrimaryContainer
                        : Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                  label: Text(label),
                  labelStyle: TextStyle(
                    fontWeight: FontWeight.w700,
                    color: selected
                        ? Theme.of(context).colorScheme.onPrimaryContainer
                        : Theme.of(context).colorScheme.onSurface,
                  ),
                  selectedColor: Theme.of(context).colorScheme.primaryContainer,
                  side: BorderSide(
                    color: selected
                        ? Theme.of(context).colorScheme.primary
                        : Theme.of(context).colorScheme.outlineVariant,
                  ),
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 9,
                  ),
                  onSelected: (_) => _selectVehicle(type),
                );
              }).toList(),
        ),
        if (!isMotorized) ...[const SizedBox(height: 22), _vehicleInfo()],
        if (loadingCatalog)
          const Padding(
            padding: EdgeInsets.all(24),
            child: Center(
              child: AngelowLoadingIndicator(
                compact: true,
                label: 'Cargando opciones…',
              ),
            ),
          ),
        if (isMotorized && !loadingCatalog) ...[
          const SizedBox(height: 22),
          SearchablePickerField<CatalogOption>(
            key: ValueKey('marca-$vehicleType-${make?.id}'),
            value: make,
            label: 'Marca',
            searchHint: 'Buscar marca',
            prefixIcon: Icons.factory_outlined,
            options: makes,
            itemLabel: (item) => item.label,
            validator: (value) => value == null ? 'Selecciona la marca.' : null,
            onChanged: _selectMake,
          ),
          const SizedBox(height: 16),
          SearchablePickerField<CatalogOption>(
            key: ValueKey('modelo-${make?.id}-${model?.id}'),
            value: model,
            label: 'Modelo',
            searchHint: 'Buscar modelo',
            prefixIcon: Icons.commute_outlined,
            options: models,
            itemLabel: (item) => item.label,
            enabled: make != null,
            validator: (value) =>
                value == null ? 'Selecciona el modelo.' : null,
            onChanged: (value) => setState(() => model = value),
          ),
          const SizedBox(height: 16),
          SearchablePickerField<CatalogOption>(
            key: ValueKey('color-${color?.id}'),
            value: color,
            label: 'Color',
            searchHint: 'Buscar color',
            prefixIcon: Icons.palette_outlined,
            options: colors,
            itemLabel: (item) => item.label,
            leadingBuilder: (_, item) => _colorSwatch(item),
            validator: (value) => value == null ? 'Selecciona el color.' : null,
            onChanged: (value) => setState(() => color = value),
          ),
          const SizedBox(height: 16),
          _field(
            'year',
            'Año',
            type: TextInputType.number,
            icon: Icons.event_outlined,
            validator: AppValidators.vehicleYear,
            inputFormatters: [
              FilteringTextInputFormatter.digitsOnly,
              LengthLimitingTextInputFormatter(4),
            ],
          ),
          const SizedBox(height: 16),
          _field(
            'plate',
            'Placa',
            icon: Icons.pin_outlined,
            textCapitalization: TextCapitalization.characters,
            validator: AppValidators.plate,
            inputFormatters: [
              FilteringTextInputFormatter.allow(RegExp(r'[A-Za-z0-9-]')),
              LengthLimitingTextInputFormatter(12),
            ],
          ),
          const SizedBox(height: 16),
          DropdownButtonFormField<String>(
            initialValue: ownership,
            decoration: const InputDecoration(
              labelText: 'Tenencia del vehículo',
              prefixIcon: Icon(Icons.key_outlined),
            ),
            items: const [
              DropdownMenuItem(value: 'owned', child: Text('Propio')),
              DropdownMenuItem(value: 'rented', child: Text('Alquilado')),
              DropdownMenuItem(value: 'borrowed', child: Text('Prestado')),
            ],
            onChanged: (value) {
              if (value != null) {
                setState(() => ownership = value);
              }
            },
          ),
        ],
      ],
    ),
  );

  Widget _vehicleInfo() => DecoratedBox(
    decoration: BoxDecoration(
      color: Theme.of(context).colorScheme.surfaceContainerHighest,
      borderRadius: BorderRadius.circular(16),
    ),
    child: Padding(
      padding: const EdgeInsets.all(18),
      child: Row(
        children: [
          Icon(
            vehicleType == 'foot'
                ? Icons.directions_walk_rounded
                : Icons.pedal_bike_rounded,
            size: 34,
            color: Theme.of(context).colorScheme.primary,
          ),
          const SizedBox(width: 14),
          const Expanded(
            child: Text(
              'Este medio no requiere marca, placa ni datos de propiedad.',
            ),
          ),
        ],
      ),
    ),
  );

  Widget _colorSwatch(CatalogOption option) {
    final hex = option.hex?.replaceFirst('#', '') ?? '808080';
    final value = int.tryParse('FF$hex', radix: 16) ?? 0xFF808080;
    return Container(
      width: 24,
      height: 24,
      decoration: BoxDecoration(
        color: Color(value),
        shape: BoxShape.circle,
        border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
      ),
    );
  }

  Widget _documents() => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      _title(
        'Documentos',
        'Adjunta JPG, PNG o PDF de máximo 8 MB. Los documentos del vehículo se exigen solo cuando aplican.',
      ),
      ...visibleDocuments.map((type) => _documentCard(type)),
      if (isMotorized)
        CheckboxListTile(
          value: inspectionNotApplicable,
          contentPadding: const EdgeInsets.symmetric(horizontal: 4),
          controlAffinity: ListTileControlAffinity.leading,
          onChanged: (value) => setState(() {
            inspectionNotApplicable = value ?? false;
            error = null;
          }),
          title: const Text(
            'La revisión técnico-mecánica aún no aplica por antigüedad del vehículo',
          ),
        ),
    ],
  );

  Widget _documentCard(String type) {
    final file = documents[type];
    final required = requiredDocuments.contains(type);
    final alreadyAttached = existingDocumentTypes.contains(type);
    final requiresReplacement = documentsToReplace.contains(type);
    final scheme = Theme.of(context).colorScheme;
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: saving ? null : () => _pick(type),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(
                  color: file == null
                      ? scheme.surfaceContainerHighest
                      : scheme.primaryContainer,
                  borderRadius: BorderRadius.circular(13),
                ),
                child: Icon(
                  file == null
                      ? Icons.upload_file_outlined
                      : Icons.check_circle_outline_rounded,
                  color: file == null
                      ? scheme.onSurfaceVariant
                      : scheme.primary,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _documentLabel(type),
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      file?.path.split(Platform.pathSeparator).last ??
                          (requiresReplacement
                              ? 'Debes adjuntar un archivo corregido'
                              : alreadyAttached
                              ? 'Archivo actual conservado · Toca para reemplazar'
                              : required
                              ? 'Obligatorio · Toca para adjuntar'
                              : 'Opcional para este medio'),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color:
                            file == null &&
                                (required && !alreadyAttached ||
                                    requiresReplacement)
                            ? scheme.error
                            : scheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Icon(Icons.chevron_right_rounded, color: scheme.outline),
            ],
          ),
        ),
      ),
    );
  }

  Widget _consents() => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      _title(
        'Términos y condiciones',
        'Lee y acepta las condiciones del servicio para enviar tu solicitud.',
      ),
      Card(
        clipBehavior: Clip.antiAlias,
        child: CheckboxListTile(
          value: acceptTerms,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 16,
            vertical: 8,
          ),
          controlAffinity: ListTileControlAffinity.leading,
          title: const Text(
            'Acepto los términos y condiciones del servicio para repartidores',
          ),
          subtitle: const Text(
            'La aceptación quedará registrada con la versión vigente.',
          ),
          onChanged: saving
              ? null
              : (value) => setState(() {
                  acceptTerms = value ?? false;
                  error = null;
                }),
        ),
      ),
    ],
  );

  Widget _title(String title, String subtitle) => Padding(
    padding: const EdgeInsets.only(bottom: 22),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: Theme.of(
            context,
          ).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w800),
        ),
        const SizedBox(height: 8),
        Text(
          subtitle,
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
            color: Theme.of(context).colorScheme.onSurfaceVariant,
          ),
        ),
      ],
    ),
  );

  Widget _field(
    String key,
    String label, {
    TextInputType? type,
    IconData? icon,
    String? hintText,
    String? Function(String?)? validator,
    List<TextInputFormatter>? inputFormatters,
    bool readOnly = false,
    VoidCallback? onTap,
    TextCapitalization textCapitalization = TextCapitalization.none,
  }) => TextFormField(
    controller: _c(key),
    keyboardType: type,
    textCapitalization: textCapitalization,
    textInputAction: TextInputAction.next,
    autovalidateMode: AutovalidateMode.onUserInteraction,
    validator: validator,
    inputFormatters: inputFormatters,
    readOnly: readOnly,
    onTap: onTap,
    decoration: InputDecoration(
      labelText: label,
      hintText: hintText,
      prefixIcon: icon == null ? null : Icon(icon),
      suffixIcon: readOnly ? const Icon(Icons.expand_more_rounded) : null,
    ),
  );

  String _documentLabel(String type) => const {
    'identity_front': 'Documento de identidad — frente',
    'identity_back': 'Documento de identidad — reverso',
    'profile_photo': 'Foto de perfil',
    'driving_license': 'Licencia de conducir',
    'vehicle_registration': 'Tarjeta de propiedad',
    'soat': 'SOAT vigente',
    'technical_inspection': 'Revisión técnico-mecánica',
  }[type]!;
}
