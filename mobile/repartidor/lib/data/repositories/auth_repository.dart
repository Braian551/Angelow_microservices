import '../models/session_data.dart';
import '../services/auth_api_service.dart';
import '../services/firebase_identity_service.dart';
import '../services/session_store.dart';

class AuthRepository {
  AuthRepository({
    AuthApiService? api,
    FirebaseIdentityService? firebase,
    SessionStore? store,
  }) : _api = api ?? AuthApiService(),
       _firebase = firebase ?? FirebaseIdentityService(),
       _store = store ?? const SessionStore();

  final AuthApiService _api;
  final FirebaseIdentityService _firebase;
  final SessionStore _store;

  Future<int> requestCode(String email, {bool resend = false}) =>
      _api.requestCode(email, resend: resend);
  Future<({String token, String nextStep, String? message})> verifyCode(
    String email,
    String code,
  ) => _api.verifyCode(email, code);

  Future<SessionData> login(
    String email,
    String password,
    String verificationToken,
  ) async {
    final session = await _api.login(email, password, verificationToken);
    await _store.save(session);
    return session;
  }

  Future<SessionData> registerIdentity({
    required String name,
    required String email,
    required String phone,
    required String password,
    required String passwordConfirmation,
    required String verificationToken,
  }) async {
    final session = await _api.registerIdentity(
      name: name,
      email: email,
      phone: phone,
      password: password,
      passwordConfirmation: passwordConfirmation,
      verificationToken: verificationToken,
    );
    await _store.save(session);
    return session;
  }

  Future<SessionData> continueWithGoogle() async {
    final session = await _api.google(await _firebase.signInWithGoogle());
    await _store.save(session);
    return session;
  }

  Future<SessionData?> restore() => _store.read();
  Future<void> logout() async {
    await Future.wait([_store.clear(), _firebase.signOut()]);
  }
}
