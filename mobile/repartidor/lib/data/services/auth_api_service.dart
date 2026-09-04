import '../../config/app_config.dart';
import '../models/session_data.dart';
import 'api_client.dart';

class AuthApiService {
  AuthApiService({ApiClient? client})
    : _client = client ?? ApiClient(baseUrls: AppConfig.authApiUrls);
  final ApiClient _client;

  Future<int> requestCode(String email, {bool resend = false}) async {
    final json = await _client.post(
      '/auth/courier/${resend ? 'resend-code' : 'request-code'}',
      body: {'email': email},
    );
    final data = Map<String, dynamic>.from(json['data'] as Map? ?? const {});
    return int.tryParse(data['resend_cooldown']?.toString() ?? '') ?? 60;
  }

  Future<({String token, String nextStep, String? message})> verifyCode(
    String email,
    String code,
  ) async {
    final json = await _client.post(
      '/auth/courier/verify-code',
      body: {'email': email, 'code': code},
    );
    final data = Map<String, dynamic>.from(json['data'] as Map? ?? const {});
    return (
      token: data['verification_token']?.toString() ?? '',
      nextStep: data['next_step']?.toString() ?? 'blocked',
      message: data['message']?.toString(),
    );
  }

  Future<SessionData> login(
    String email,
    String password,
    String verificationToken,
  ) async {
    return SessionData.fromApi(
      await _client.post(
        '/auth/courier/login',
        body: {
          'email': email,
          'password': password,
          'verification_token': verificationToken,
        },
      ),
    );
  }

  Future<SessionData> registerIdentity({
    required String name,
    required String email,
    required String phone,
    required String password,
    required String passwordConfirmation,
    required String verificationToken,
  }) async {
    return SessionData.fromApi(
      await _client.post(
        '/auth/courier/register',
        body: {
          'name': name,
          'email': email,
          'phone': phone,
          'password': password,
          'password_confirmation': passwordConfirmation,
          'registration_token': verificationToken,
        },
      ),
    );
  }

  Future<SessionData> google(String idToken) async {
    return SessionData.fromApi(
      await _client.post('/auth/courier/google', body: {'id_token': idToken}),
    );
  }
}
