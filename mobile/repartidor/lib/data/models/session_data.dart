class SessionData {
  const SessionData({
    required this.token,
    required this.userId,
    required this.email,
    required this.name,
    required this.role,
    this.requiresProfile = false,
  });

  final String token;
  final String userId;
  final String email;
  final String name;
  final String role;
  final bool requiresProfile;

  factory SessionData.fromApi(Map<String, dynamic> json) {
    final data = Map<String, dynamic>.from(json['data'] as Map? ?? const {});
    final user = Map<String, dynamic>.from(data['user'] as Map? ?? const {});
    return SessionData(
      token: data['token']?.toString() ?? '',
      userId: user['id']?.toString() ?? '',
      email: user['email']?.toString() ?? '',
      name: user['name']?.toString() ?? '',
      role: user['role']?.toString() ?? '',
      requiresProfile: data['requires_profile'] == true,
    );
  }

  Map<String, String> toStorage() => {
    'token': token,
    'user_id': userId,
    'email': email,
    'name': name,
    'role': role,
  };

  factory SessionData.fromStorage(Map<String, String> values) => SessionData(
    token: values['token'] ?? '',
    userId: values['user_id'] ?? '',
    email: values['email'] ?? '',
    name: values['name'] ?? '',
    role: values['role'] ?? '',
  );
}
