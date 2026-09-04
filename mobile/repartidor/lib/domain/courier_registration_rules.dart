class CourierRegistrationRules {
  const CourierRegistrationRules._();

  static const motorizedTypes = {'motorcycle', 'car', 'truck'};

  static bool isMotorized(String vehicleType) =>
      motorizedTypes.contains(vehicleType);

  static List<String> visibleDocuments(String vehicleType) => [
    'identity_front',
    'identity_back',
    'profile_photo',
    if (isMotorized(vehicleType)) ...[
      'driving_license',
      'vehicle_registration',
      'soat',
      'technical_inspection',
    ],
  ];

  static List<String> requiredDocuments(
    String vehicleType, {
    required bool inspectionNotApplicable,
  }) => [
    'identity_front',
    'identity_back',
    'profile_photo',
    if (isMotorized(vehicleType)) ...[
      'driving_license',
      'vehicle_registration',
      'soat',
      if (!inspectionNotApplicable) 'technical_inspection',
    ],
  ];
}
