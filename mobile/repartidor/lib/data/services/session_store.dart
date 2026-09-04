import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../models/session_data.dart';

class SessionStore {
  const SessionStore({
    FlutterSecureStorage storage = const FlutterSecureStorage(),
  }) : _storage = storage;
  final FlutterSecureStorage _storage;

  Future<void> save(SessionData session) async {
    for (final entry in session.toStorage().entries) {
      await _storage.write(key: 'angelow_${entry.key}', value: entry.value);
    }
  }

  Future<SessionData?> read() async {
    final keys = ['token', 'user_id', 'email', 'name', 'role'];
    final values = <String, String>{};
    for (final key in keys) {
      final value = await _storage.read(key: 'angelow_$key');
      if (value != null) {
        values[key] = value;
      }
    }
    if ((values['token'] ?? '').isEmpty || (values['user_id'] ?? '').isEmpty) {
      return null;
    }
    return SessionData.fromStorage(values);
  }

  Future<void> clear() => _storage.deleteAll();
}
