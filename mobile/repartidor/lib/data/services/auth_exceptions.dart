class AuthFlowException implements Exception {
  const AuthFlowException(this.message);

  final String message;

  @override
  String toString() => message;
}

class SignInCancelledException extends AuthFlowException {
  const SignInCancelledException()
    : super('Se canceló el inicio de sesión con Google.');
}

class SignInFailedException extends AuthFlowException {
  const SignInFailedException()
    : super('No se pudo iniciar sesión con Google. Inténtalo de nuevo.');
}
