import 'dart:async';

import 'package:flutter/foundation.dart';

import '../../../../data/models/session_data.dart';
import '../../../../data/repositories/auth_repository.dart';
import '../../../../data/services/auth_exceptions.dart';

enum AuthStep {
  loading,
  welcome,
  email,
  code,
  password,
  blocked,
  authenticated,
}

class AuthViewModel extends ChangeNotifier {
  AuthViewModel({AuthRepository? repository})
    : _repository = repository ?? AuthRepository();
  final AuthRepository _repository;

  AuthStep _step = AuthStep.loading;
  AuthStep get step => _step;
  SessionData? _session;
  SessionData? get session => _session;
  String email = '';
  String verificationToken = '';
  String? message;
  String? error;
  bool busy = false;
  int resendCooldown = 0;
  Timer? _resendTimer;

  bool get canResend => resendCooldown == 0;
  String get resendCooldownLabel {
    final minutes = (resendCooldown ~/ 60).toString().padLeft(2, '0');
    final seconds = (resendCooldown % 60).toString().padLeft(2, '0');
    return '$minutes:$seconds';
  }

  Future<void> initialize() async {
    _session = await _repository.restore();
    _step = _session == null ? AuthStep.welcome : AuthStep.authenticated;
    notifyListeners();
  }

  void showEmail() {
    error = null;
    _step = AuthStep.email;
    notifyListeners();
  }

  void back() {
    error = null;
    _step = switch (_step) {
      AuthStep.email => AuthStep.welcome,
      AuthStep.code => AuthStep.email,
      AuthStep.password || AuthStep.blocked => AuthStep.code,
      _ => AuthStep.welcome,
    };
    notifyListeners();
  }

  Future<void> sendCode(String value, {bool resend = false}) async {
    if (resend && !canResend) return;
    await _guard(() async {
      email = value.trim().toLowerCase();
      final cooldown = await _repository.requestCode(email, resend: resend);
      _startResendCooldown(cooldown);
      message = 'Enviamos un código de 6 dígitos a $email.';
      _step = AuthStep.code;
    });
  }

  Future<void> verifyCode(String code) async {
    await _guard(() async {
      final result = await _repository.verifyCode(email, code);
      verificationToken = result.token;
      message = result.message;
      _step = switch (result.nextStep) {
        'password' => AuthStep.password,
        'register' => AuthStep.authenticated,
        _ => AuthStep.blocked,
      };
      if (result.nextStep == 'register') {
        _session = SessionData(
          token: '',
          userId: '',
          email: email,
          name: '',
          role: 'courier',
          requiresProfile: true,
        );
      }
    });
  }

  Future<void> login(String password) async {
    await _guard(() async {
      _session = await _repository.login(email, password, verificationToken);
      _step = AuthStep.authenticated;
    });
  }

  Future<void> registerIdentity({
    required String name,
    required String phone,
    required String password,
    required String passwordConfirmation,
  }) async {
    await _guard(() async {
      _session = await _repository.registerIdentity(
        name: name,
        email: email,
        phone: phone,
        password: password,
        passwordConfirmation: passwordConfirmation,
        verificationToken: verificationToken,
      );
      _step = AuthStep.authenticated;
    });
  }

  Future<void> continueWithGoogle() async {
    await _guard(() async {
      _session = await _repository.continueWithGoogle();
      email = _session!.email;
      _step = AuthStep.authenticated;
    });
  }

  Future<void> logout() async {
    busy = true;
    notifyListeners();
    await _repository.logout();
    _resendTimer?.cancel();
    resendCooldown = 0;
    _session = null;
    verificationToken = '';
    _step = AuthStep.welcome;
    busy = false;
    notifyListeners();
  }

  Future<void> _guard(Future<void> Function() action) async {
    if (busy) return;
    busy = true;
    error = null;
    notifyListeners();
    try {
      await action();
    } catch (exception) {
      error = switch (exception) {
        AuthFlowException value => value.message,
        _ => exception.toString().replaceFirst('ApiException: ', ''),
      };
    } finally {
      busy = false;
      notifyListeners();
    }
  }

  void _startResendCooldown(int seconds) {
    _resendTimer?.cancel();
    resendCooldown = seconds.clamp(1, 3600);
    _resendTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (resendCooldown <= 1) {
        resendCooldown = 0;
        timer.cancel();
      } else {
        resendCooldown--;
      }
      notifyListeners();
    });
  }

  @override
  void dispose() {
    _resendTimer?.cancel();
    super.dispose();
  }
}
