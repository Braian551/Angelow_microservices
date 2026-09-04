abstract final class AppValidators {
  static final RegExp _emailPattern = RegExp(
    r"^[A-Za-z0-9.!#$%&'*+/=?^_`{|}~-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)+$",
  );
  static final RegExp _namePattern = RegExp(
    r"^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:[ .'-][A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)+$",
  );
  static final RegExp _passwordLetter = RegExp(r'[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]');
  static final RegExp _passwordNumber = RegExp(r'[0-9]');

  static String? email(String? value) {
    final normalized = value?.trim() ?? '';
    if (normalized.isEmpty) {
      return 'Ingresa tu correo electrónico.';
    }
    if (normalized.length > 100 || !_emailPattern.hasMatch(normalized)) {
      return 'Ingresa un correo electrónico válido.';
    }
    return null;
  }

  static String? verificationCode(String? value) {
    if (!RegExp(r'^[0-9]{6}$').hasMatch(value?.trim() ?? '')) {
      return 'El código debe tener 6 dígitos.';
    }
    return null;
  }

  static String? fullName(String? value) {
    final normalized = value?.trim().replaceAll(RegExp(r'\s+'), ' ') ?? '';
    if (normalized.isEmpty) {
      return 'Ingresa tu nombre completo.';
    }
    if (normalized.length < 3 ||
        normalized.length > 100 ||
        !_namePattern.hasMatch(normalized)) {
      return 'Escribe nombre y apellido usando solo letras.';
    }
    return null;
  }

  static String? colombianPhone(String? value) {
    if (!RegExp(r'^3[0-9]{9}$').hasMatch(value?.trim() ?? '')) {
      return 'Ingresa un celular colombiano de 10 dígitos.';
    }
    return null;
  }

  static String? password(String? value) {
    final password = value ?? '';
    if (password.isEmpty) {
      return 'Ingresa tu contraseña.';
    }
    if (password.length < 8 ||
        !_passwordLetter.hasMatch(password) ||
        !_passwordNumber.hasMatch(password)) {
      return 'Usa mínimo 8 caracteres, con letras y números.';
    }
    if (password.length > 64) {
      return 'La contraseña no puede superar 64 caracteres.';
    }
    return null;
  }

  static String? passwordConfirmation(String? value, String password) {
    if ((value ?? '').isEmpty) {
      return 'Confirma tu contraseña.';
    }
    if (value != password) {
      return 'Las contraseñas no coinciden.';
    }
    return null;
  }

  static String? documentNumber(String? value, String documentType) {
    final normalized = value?.trim() ?? '';
    if (normalized.isEmpty) {
      return 'Ingresa el número de documento.';
    }
    if (documentType == 'cc' || documentType == 'ce') {
      if (!RegExp(r'^[0-9]{6,10}$').hasMatch(normalized)) {
        return 'La cédula debe tener entre 6 y 10 dígitos.';
      }
      return null;
    }
    if (!RegExp(r'^[A-Za-z0-9-]{5,20}$').hasMatch(normalized)) {
      return 'Usa entre 5 y 20 letras o números.';
    }
    return null;
  }

  static String? birthDate(String? value) {
    final normalized = value?.trim() ?? '';
    final date = DateTime.tryParse(normalized);
    if (date == null ||
        normalized.length != 10 ||
        date.toIso8601String().substring(0, 10) != normalized) {
      return 'Selecciona una fecha de nacimiento válida.';
    }
    final today = DateTime.now();
    final latest = DateTime(today.year - 18, today.month, today.day);
    final earliest = DateTime(today.year - 100, today.month, today.day);
    if (date.isAfter(latest)) {
      return 'Debes ser mayor de 18 años.';
    }
    if (date.isBefore(earliest)) {
      return 'Revisa la fecha de nacimiento.';
    }
    return null;
  }

  static String? requiredText(
    String? value, {
    required String label,
    int maxLength = 180,
  }) {
    final normalized = value?.trim() ?? '';
    if (normalized.isEmpty) {
      return 'Ingresa $label.';
    }
    if (normalized.length > maxLength) {
      return '$label no puede superar $maxLength caracteres.';
    }
    return null;
  }

  static String? vehicleYear(String? value) {
    final year = int.tryParse(value?.trim() ?? '');
    if (year == null) {
      return 'Ingresa el año del vehículo.';
    }
    if (year < 1950 || year > DateTime.now().year + 1) {
      return 'Ingresa un año de vehículo válido.';
    }
    return null;
  }

  static String? plate(String? value) {
    final normalized = value?.trim().toUpperCase() ?? '';
    if (!RegExp(r'^[A-Z0-9-]{5,12}$').hasMatch(normalized)) {
      return 'Ingresa una placa válida.';
    }
    return null;
  }
}
