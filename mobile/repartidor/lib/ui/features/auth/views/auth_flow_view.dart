import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/validation/app_validators.dart';
import '../../../core/widgets/angelow_loading_indicator.dart';
import '../../../core/widgets/auth_scaffold.dart';
import '../../../core/widgets/brand_header.dart';
import '../../../core/widgets/otp_code_field.dart';
import '../view_models/auth_view_model.dart';

class AuthFlowView extends StatefulWidget {
  const AuthFlowView({super.key, required this.viewModel});

  final AuthViewModel viewModel;

  @override
  State<AuthFlowView> createState() => _AuthFlowViewState();
}

class _AuthFlowViewState extends State<AuthFlowView> {
  final _emailFormKey = GlobalKey<FormState>();
  final _passwordFormKey = GlobalKey<FormState>();
  final email = TextEditingController();
  final code = TextEditingController();
  final password = TextEditingController();
  String? codeError;
  bool passwordVisible = false;

  @override
  void dispose() {
    email.dispose();
    code.dispose();
    password.dispose();
    super.dispose();
  }

  bool get _emailIsValid => AppValidators.email(email.text) == null;
  bool get _codeIsValid => AppValidators.verificationCode(code.text) == null;
  bool get _passwordIsValid => AppValidators.password(password.text) == null;

  void _sendCode(AuthViewModel vm) {
    if (!(_emailFormKey.currentState?.validate() ?? false)) {
      return;
    }
    vm.sendCode(email.text);
  }

  void _verifyCode(AuthViewModel vm) {
    final validationError = AppValidators.verificationCode(code.text);
    setState(() => codeError = validationError);
    if (validationError == null) {
      vm.verifyCode(code.text);
    }
  }

  void _login(AuthViewModel vm) {
    if (!(_passwordFormKey.currentState?.validate() ?? false)) {
      return;
    }
    vm.login(password.text);
  }

  @override
  Widget build(BuildContext context) {
    final vm = widget.viewModel;
    return AuthScaffold(
      showBack: vm.step != AuthStep.welcome,
      onBack: vm.back,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const SizedBox(height: 40),
          AnimatedSwitcher(
            duration: const Duration(milliseconds: 220),
            child: KeyedSubtree(
              key: ValueKey(vm.step),
              child: vm.step == AuthStep.welcome ? _welcome(vm) : _step(vm),
            ),
          ),
          if (vm.error != null) ...[
            const SizedBox(height: 18),
            _Message(text: vm.error!, error: true),
          ],
          if (vm.busy) ...[
            const SizedBox(height: 18),
            const Center(child: AngelowLoadingIndicator(label: 'Procesando…')),
          ],
        ],
      ),
    );
  }

  Widget _welcome(AuthViewModel vm) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      const BrandHeader(subtitle: 'Entregas seguras, simples y a tiempo.'),
      const SizedBox(height: 36),
      Text(
        'Bienvenido a Angelow',
        textAlign: TextAlign.center,
        style: Theme.of(
          context,
        ).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w800),
      ),
      const SizedBox(height: 32),
      OutlinedButton.icon(
        onPressed: vm.busy ? null : vm.continueWithGoogle,
        icon: const Text(
          'G',
          style: TextStyle(fontWeight: FontWeight.w900, color: Colors.red),
        ),
        label: const Text('Continuar con Google'),
      ),
      const SizedBox(height: 14),
      OutlinedButton.icon(
        onPressed: vm.busy ? null : vm.showEmail,
        icon: const Icon(Icons.mail_outline_rounded),
        label: const Text('Continuar con correo'),
      ),
      const SizedBox(height: 28),
      const Text(
        'Al continuar, aceptas los Términos de Servicio y la Política de Privacidad.',
        textAlign: TextAlign.center,
      ),
    ],
  );

  Widget _step(AuthViewModel vm) {
    return switch (vm.step) {
      AuthStep.email => Form(
        key: _emailFormKey,
        child: _form(
          title: 'Ingresa tu correo',
          subtitle: 'Validaremos tu correo antes de continuar.',
          field: TextFormField(
            controller: email,
            keyboardType: TextInputType.emailAddress,
            textInputAction: TextInputAction.done,
            autofillHints: const [AutofillHints.email],
            autocorrect: false,
            enableSuggestions: false,
            autovalidateMode: AutovalidateMode.onUserInteraction,
            validator: AppValidators.email,
            inputFormatters: [LengthLimitingTextInputFormatter(100)],
            onChanged: (_) => setState(() {}),
            onFieldSubmitted: (_) {
              if (_emailIsValid && !vm.busy) {
                _sendCode(vm);
              }
            },
            decoration: const InputDecoration(
              labelText: 'Correo electrónico',
              hintText: 'nombre@correo.com',
              prefixIcon: Icon(Icons.mail_outline_rounded),
            ),
          ),
          action: 'Continuar',
          enabled: _emailIsValid,
          onPressed: () => _sendCode(vm),
        ),
      ),
      AuthStep.code => _form(
        title: 'Verifica tu correo',
        subtitle: 'Enviamos un código de 6 dígitos a ${vm.email}.',
        field: OtpCodeField(
          controller: code,
          enabled: !vm.busy,
          errorText: codeError,
          onChanged: (_) => setState(() {
            if (_codeIsValid) {
              codeError = null;
            }
          }),
        ),
        action: 'Verificar',
        enabled: _codeIsValid,
        onPressed: () => _verifyCode(vm),
        secondary: TextButton.icon(
          onPressed: vm.busy || !vm.canResend
              ? null
              : () => vm.sendCode(vm.email, resend: true),
          icon: Icon(
            vm.canResend ? Icons.refresh_rounded : Icons.timer_outlined,
          ),
          label: Text(
            vm.canResend
                ? 'Reenviar código'
                : 'Reenviar en ${vm.resendCooldownLabel}',
          ),
        ),
      ),
      AuthStep.password => Form(
        key: _passwordFormKey,
        child: _form(
          title: 'Ingresa tu contraseña',
          subtitle:
              'El correo fue verificado y corresponde a un perfil de repartidor.',
          field: TextFormField(
            controller: password,
            obscureText: !passwordVisible,
            textInputAction: TextInputAction.done,
            autofillHints: const [AutofillHints.password],
            autovalidateMode: AutovalidateMode.onUserInteraction,
            validator: AppValidators.password,
            inputFormatters: [LengthLimitingTextInputFormatter(64)],
            onChanged: (_) => setState(() {}),
            onFieldSubmitted: (_) {
              if (_passwordIsValid && !vm.busy) {
                _login(vm);
              }
            },
            decoration: InputDecoration(
              labelText: 'Contraseña',
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
          action: 'Iniciar sesión',
          enabled: _passwordIsValid,
          onPressed: () => _login(vm),
        ),
      ),
      AuthStep.blocked => Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Icon(Icons.account_circle_outlined, size: 72),
          const SizedBox(height: 24),
          Text(
            'Esta cuenta no es de repartidor',
            style: Theme.of(
              context,
            ).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 12),
          const Text(
            'Tu cuenta pertenece a otro rol. Ingresa desde angelow.online para continuar.',
          ),
        ],
      ),
      _ => const SizedBox.shrink(),
    };
  }

  Widget _form({
    required String title,
    required String subtitle,
    required Widget field,
    required String action,
    required bool enabled,
    required VoidCallback onPressed,
    Widget? secondary,
  }) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      Text(
        title,
        style: Theme.of(
          context,
        ).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w800),
      ),
      const SizedBox(height: 10),
      Text(subtitle, style: Theme.of(context).textTheme.bodyLarge),
      const SizedBox(height: 30),
      field,
      const SizedBox(height: 18),
      ElevatedButton(
        onPressed: widget.viewModel.busy || !enabled ? null : onPressed,
        child: Text(action),
      ),
      ?secondary,
    ],
  );
}

class _Message extends StatelessWidget {
  const _Message({required this.text, required this.error});

  final String text;
  final bool error;

  @override
  Widget build(BuildContext context) => DecoratedBox(
    decoration: BoxDecoration(
      color: error ? Colors.red.shade50 : Colors.green.shade50,
      borderRadius: BorderRadius.circular(12),
    ),
    child: Padding(
      padding: const EdgeInsets.all(14),
      child: Text(
        text,
        style: TextStyle(
          color: error ? Colors.red.shade800 : Colors.green.shade800,
        ),
      ),
    ),
  );
}
