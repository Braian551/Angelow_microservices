import 'package:flutter/foundation.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:google_sign_in/google_sign_in.dart';

import '../../config/app_config.dart';
import 'auth_exceptions.dart';

class FirebaseIdentityService {
  final GoogleSignIn _google = GoogleSignIn.instance;
  bool _initialized = false;

  Future<String> signInWithGoogle() async {
    try {
      if (!_initialized) {
        await _google.initialize(
          clientId: defaultTargetPlatform == TargetPlatform.iOS
              ? AppConfig.googleIosClientId
              : null,
        );
        _initialized = true;
      }
      final account = await _google.authenticate();
      final authentication = account.authentication;
      final credential = GoogleAuthProvider.credential(
        idToken: authentication.idToken,
      );
      final result = await FirebaseAuth.instance.signInWithCredential(
        credential,
      );
      final token = await result.user?.getIdToken();
      if (token == null || token.isEmpty) {
        throw const SignInFailedException();
      }
      return token;
    } on GoogleSignInException catch (exception) {
      if (exception.code == GoogleSignInExceptionCode.canceled) {
        throw const SignInCancelledException();
      }
      throw const SignInFailedException();
    } on FirebaseAuthException {
      throw const SignInFailedException();
    }
  }

  Future<void> signOut() async {
    await Future.wait([FirebaseAuth.instance.signOut(), _google.signOut()]);
  }
}
