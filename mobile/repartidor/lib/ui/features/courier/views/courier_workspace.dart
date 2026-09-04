import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../../data/models/courier_profile.dart';
import '../../../../data/repositories/courier_repository.dart';
import '../../../core/validation/app_validators.dart';
import '../../../core/widgets/form_app_bar.dart';
import '../../auth/view_models/auth_view_model.dart';
import '../../deliveries/views/deliveries_home_view.dart';
import '../../registration/views/courier_registration_view.dart';

class CourierWorkspace extends StatefulWidget {
  const CourierWorkspace({
    super.key,
    required this.auth,
    required this.repository,
  });

  final AuthViewModel auth;
  final CourierRepository repository;

  @override
  State<CourierWorkspace> createState() => _CourierWorkspaceState();
}

class _CourierWorkspaceState extends State<CourierWorkspace> {
  CourierProfile? profile;
  bool loading = true;
  bool editingApplication = false;
  String? error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final token = widget.auth.session?.token ?? '';
    if (token.isEmpty) {
      setState(() => loading = false);
      return;
    }
    try {
      profile = await widget.repository.profile(token);
    } catch (exception) {
      error = exception.toString();
    }
    if (mounted) {
      setState(() => loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final session = widget.auth.session!;
    if (loading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    if (session.token.isEmpty) {
      return CourierIdentityView(auth: widget.auth);
    }
    if (profile == null) {
      return CourierRegistrationView(
        repository: widget.repository,
        token: session.token,
        onSubmitted: (value) => setState(() => profile = value),
      );
    }
    if (editingApplication) {
      return CourierRegistrationView(
        repository: widget.repository,
        token: session.token,
        initialProfile: profile,
        onSubmitted: (value) => setState(() {
          profile = value;
          editingApplication = false;
        }),
      );
    }
    if (!profile!.isApproved) {
      return _CourierReviewStatus(
        profile: profile!,
        onRefresh: _load,
        onLogout: widget.auth.logout,
        onCorrect: profile!.status == 'rejected'
            ? () => setState(() => editingApplication = true)
            : null,
      );
    }
    return DeliveriesHomeView(
      repository: widget.repository,
      token: session.token,
      vehicleType: profile!.vehicleType,
      onLogout: widget.auth.logout,
    );
  }
}

class _CourierReviewStatus extends StatelessWidget {
  const _CourierReviewStatus({
    required this.profile,
    required this.onRefresh,
    required this.onLogout,
    this.onCorrect,
  });

  final CourierProfile profile;
  final Future<void> Function() onRefresh;
  final VoidCallback onLogout;
  final VoidCallback? onCorrect;

  bool get requiresChanges => profile.status == 'rejected';

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final accent = requiresChanges ? scheme.error : scheme.primary;
    final softAccent = requiresChanges
        ? scheme.errorContainer
        : scheme.primaryContainer;

    return Scaffold(
      backgroundColor: scheme.surfaceContainerLowest,
      appBar: AppBar(
        title: const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Angelow Repartidor'),
            Text(
              'Estado de tu solicitud',
              style: TextStyle(fontSize: 12, fontWeight: FontWeight.w400),
            ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Cerrar sesión',
            onPressed: onLogout,
            icon: const Icon(Icons.logout_rounded),
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: onRefresh,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(22, 30, 22, 36),
          children: [
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [softAccent, scheme.surface],
                ),
                borderRadius: BorderRadius.circular(28),
                border: Border.all(color: accent.withValues(alpha: .22)),
                boxShadow: [
                  BoxShadow(
                    color: accent.withValues(alpha: .09),
                    blurRadius: 28,
                    offset: const Offset(0, 14),
                  ),
                ],
              ),
              child: Column(
                children: [
                  Align(
                    alignment: Alignment.centerLeft,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 7,
                      ),
                      decoration: BoxDecoration(
                        color: scheme.surface.withValues(alpha: .8),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            requiresChanges
                                ? Icons.edit_document
                                : Icons.schedule_rounded,
                            size: 16,
                            color: accent,
                          ),
                          const SizedBox(width: 7),
                          Text(
                            requiresChanges
                                ? 'Cambios solicitados'
                                : 'Revisión en curso',
                            style: TextStyle(
                              color: accent,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),
                  Container(
                    width: 82,
                    height: 82,
                    decoration: BoxDecoration(
                      color: scheme.surface,
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: accent.withValues(alpha: .15),
                          blurRadius: 22,
                        ),
                      ],
                    ),
                    child: Icon(
                      requiresChanges
                          ? Icons.description_outlined
                          : Icons.manage_search_rounded,
                      size: 42,
                      color: accent,
                    ),
                  ),
                  const SizedBox(height: 20),
                  Text(
                    requiresChanges
                        ? 'Necesitamos que ajustes tu solicitud'
                        : 'Estamos verificando tus datos',
                    textAlign: TextAlign.center,
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.w800,
                      height: 1.18,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    requiresChanges
                        ? profile.rejectionReason ??
                              'Revisa las observaciones y adjunta los archivos corregidos.'
                        : 'Nuestro equipo comprobará tu identidad y documentos. Te notificaremos apenas finalice la revisión.',
                    textAlign: TextAlign.center,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: scheme.onSurfaceVariant,
                      height: 1.45,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 22),
            if (requiresChanges) ...[
              _feedbackCard(context),
              const SizedBox(height: 18),
              ElevatedButton.icon(
                onPressed: onCorrect,
                icon: const Icon(Icons.upload_file_rounded),
                label: const Text('Corregir y volver a enviar'),
              ),
            ] else ...[
              _progressCard(context),
              const SizedBox(height: 18),
              OutlinedButton.icon(
                onPressed: onRefresh,
                icon: const Icon(Icons.refresh_rounded),
                label: const Text('Actualizar estado'),
              ),
              const SizedBox(height: 12),
              Text(
                'También puedes deslizar hacia abajo para actualizar.',
                textAlign: TextAlign.center,
                style: Theme.of(
                  context,
                ).textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _progressCard(BuildContext context) => Card(
    elevation: 0,
    child: Padding(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Progreso de activación',
            style: Theme.of(
              context,
            ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 18),
          _progressItem(
            context,
            icon: Icons.check_rounded,
            title: 'Solicitud recibida',
            subtitle: 'Tus datos y archivos se guardaron correctamente.',
            complete: true,
          ),
          _progressItem(
            context,
            icon: Icons.search_rounded,
            title: 'Validación administrativa',
            subtitle: 'Estamos comprobando la información enviada.',
            active: true,
          ),
          _progressItem(
            context,
            icon: Icons.delivery_dining_rounded,
            title: 'Perfil habilitado',
            subtitle: 'Podrás aceptar entregas cuando te aprobemos.',
            last: true,
          ),
        ],
      ),
    ),
  );

  Widget _progressItem(
    BuildContext context, {
    required IconData icon,
    required String title,
    required String subtitle,
    bool complete = false,
    bool active = false,
    bool last = false,
  }) {
    final scheme = Theme.of(context).colorScheme;
    final color = complete || active ? scheme.primary : scheme.outlineVariant;
    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          SizedBox(
            width: 36,
            child: Column(
              children: [
                Container(
                  width: 30,
                  height: 30,
                  decoration: BoxDecoration(
                    color: complete || active
                        ? scheme.primaryContainer
                        : scheme.surfaceContainerHighest,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(icon, size: 17, color: color),
                ),
                if (!last)
                  Expanded(
                    child: Container(
                      width: 2,
                      color: color.withValues(alpha: .35),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.only(bottom: 18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    subtitle,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: scheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _feedbackCard(BuildContext context) {
    final documents = profile.documentsRequiringChanges;
    return Card(
      elevation: 0,
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Documentos por corregir',
              style: Theme.of(
                context,
              ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 6),
            Text(
              'Adjunta una nueva versión para cada documento señalado.',
              style: Theme.of(context).textTheme.bodySmall,
            ),
            const SizedBox(height: 14),
            if (documents.isEmpty)
              const Text('Revisa el mensaje general antes de volver a enviar.')
            else
              ...documents.map(
                (document) => Container(
                  width: double.infinity,
                  margin: const EdgeInsets.only(bottom: 10),
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.errorContainer,
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Icon(Icons.file_present_outlined),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _documentLabel(document.type),
                              style: const TextStyle(
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                            if (document.reviewNote != null) ...[
                              const SizedBox(height: 4),
                              Text(document.reviewNote!),
                            ],
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  String _documentLabel(String type) =>
      const {
        'identity_front': 'Documento de identidad · frente',
        'identity_back': 'Documento de identidad · reverso',
        'profile_photo': 'Foto de perfil',
        'driving_license': 'Licencia de conducir',
        'vehicle_registration': 'Tarjeta de propiedad',
        'soat': 'SOAT',
        'technical_inspection': 'Revisión técnico-mecánica',
      }[type] ??
      'Documento';
}

class CourierIdentityView extends StatefulWidget {
  const CourierIdentityView({super.key, required this.auth});

  final AuthViewModel auth;

  @override
  State<CourierIdentityView> createState() => _CourierIdentityViewState();
}

class _CourierIdentityViewState extends State<CourierIdentityView> {
  final formKey = GlobalKey<FormState>();
  final name = TextEditingController();
  final phone = TextEditingController();
  final password = TextEditingController();
  final passwordConfirmation = TextEditingController();
  bool passwordVisible = false;
  bool confirmationVisible = false;

  bool get formIsReady =>
      AppValidators.fullName(name.text) == null &&
      AppValidators.colombianPhone(phone.text) == null &&
      AppValidators.password(password.text) == null &&
      AppValidators.passwordConfirmation(
            passwordConfirmation.text,
            password.text,
          ) ==
          null;

  @override
  void dispose() {
    name.dispose();
    phone.dispose();
    password.dispose();
    passwordConfirmation.dispose();
    super.dispose();
  }

  void submit() {
    if (!(formKey.currentState?.validate() ?? false)) {
      return;
    }
    widget.auth.registerIdentity(
      name: name.text.trim().replaceAll(RegExp(r'\s+'), ' '),
      phone: phone.text.trim(),
      password: password.text,
      passwordConfirmation: passwordConfirmation.text,
    );
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: FormAppBar(
      title: 'Crea tu perfil',
      subtitle: 'Protege tu cuenta de repartidor',
      onBack: widget.auth.busy ? null : widget.auth.logout,
    ),
    body: Form(
      key: formKey,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(24, 28, 24, 36),
        children: [
          DecoratedBox(
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.primaryContainer,
              borderRadius: BorderRadius.circular(16),
            ),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Icon(
                    Icons.verified_outlined,
                    color: Theme.of(context).colorScheme.primary,
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Correo verificado',
                          style: Theme.of(context).textTheme.labelMedium,
                        ),
                        Text(
                          widget.auth.email,
                          style: Theme.of(context).textTheme.titleMedium
                              ?.copyWith(fontWeight: FontWeight.w700),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),
          TextFormField(
            controller: name,
            textCapitalization: TextCapitalization.words,
            textInputAction: TextInputAction.next,
            autofillHints: const [AutofillHints.name],
            autovalidateMode: AutovalidateMode.onUserInteraction,
            validator: AppValidators.fullName,
            inputFormatters: [LengthLimitingTextInputFormatter(100)],
            onChanged: (_) => setState(() {}),
            decoration: const InputDecoration(
              labelText: 'Nombre completo',
              prefixIcon: Icon(Icons.person_outline_rounded),
            ),
          ),
          const SizedBox(height: 16),
          TextFormField(
            controller: phone,
            keyboardType: TextInputType.phone,
            textInputAction: TextInputAction.next,
            autofillHints: const [AutofillHints.telephoneNumber],
            autovalidateMode: AutovalidateMode.onUserInteraction,
            validator: AppValidators.colombianPhone,
            inputFormatters: [
              FilteringTextInputFormatter.digitsOnly,
              LengthLimitingTextInputFormatter(10),
            ],
            onChanged: (_) => setState(() {}),
            decoration: const InputDecoration(
              labelText: 'Celular',
              hintText: '3XXXXXXXXX',
              prefixIcon: Icon(Icons.phone_outlined),
            ),
          ),
          const SizedBox(height: 16),
          TextFormField(
            controller: password,
            obscureText: !passwordVisible,
            textInputAction: TextInputAction.next,
            autofillHints: const [AutofillHints.newPassword],
            autovalidateMode: AutovalidateMode.onUserInteraction,
            validator: AppValidators.password,
            inputFormatters: [LengthLimitingTextInputFormatter(64)],
            onChanged: (_) => setState(() {
              if (passwordConfirmation.text.isNotEmpty) {
                formKey.currentState?.validate();
              }
            }),
            decoration: InputDecoration(
              labelText: 'Crea una contraseña',
              helperText: 'Mínimo 8 caracteres, con letras y números',
              prefixIcon: const Icon(Icons.lock_outline_rounded),
              suffixIcon: IconButton(
                tooltip: passwordVisible
                    ? 'Ocultar contraseña'
                    : 'Mostrar contraseña',
                onPressed: () =>
                    setState(() => passwordVisible = !passwordVisible),
                icon: Icon(
                  passwordVisible
                      ? Icons.visibility_off_outlined
                      : Icons.visibility_outlined,
                ),
              ),
            ),
          ),
          const SizedBox(height: 16),
          TextFormField(
            controller: passwordConfirmation,
            obscureText: !confirmationVisible,
            textInputAction: TextInputAction.done,
            autofillHints: const [AutofillHints.newPassword],
            autovalidateMode: AutovalidateMode.onUserInteraction,
            validator: (value) =>
                AppValidators.passwordConfirmation(value, password.text),
            inputFormatters: [LengthLimitingTextInputFormatter(64)],
            onChanged: (_) => setState(() {}),
            onFieldSubmitted: (_) {
              if (formIsReady && !widget.auth.busy) {
                submit();
              }
            },
            decoration: InputDecoration(
              labelText: 'Confirma tu contraseña',
              prefixIcon: const Icon(Icons.lock_reset_rounded),
              suffixIcon: IconButton(
                tooltip: confirmationVisible
                    ? 'Ocultar confirmación'
                    : 'Mostrar confirmación',
                onPressed: () =>
                    setState(() => confirmationVisible = !confirmationVisible),
                icon: Icon(
                  confirmationVisible
                      ? Icons.visibility_off_outlined
                      : Icons.visibility_outlined,
                ),
              ),
            ),
          ),
          const SizedBox(height: 26),
          ElevatedButton(
            onPressed: widget.auth.busy || !formIsReady ? null : submit,
            child: Text(widget.auth.busy ? 'Creando perfil…' : 'Continuar'),
          ),
          if (widget.auth.error != null)
            Container(
              margin: const EdgeInsets.only(top: 16),
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.errorContainer,
                borderRadius: BorderRadius.circular(14),
              ),
              child: Text(
                widget.auth.error!,
                style: TextStyle(
                  color: Theme.of(context).colorScheme.onErrorContainer,
                ),
              ),
            ),
        ],
      ),
    ),
  );
}
