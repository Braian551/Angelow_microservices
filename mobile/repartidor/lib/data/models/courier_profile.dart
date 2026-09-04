class CourierDocumentReview {
  const CourierDocumentReview({
    required this.type,
    required this.status,
    this.reviewNote,
  });

  final String type;
  final String status;
  final String? reviewNote;

  bool get requiresChange => status == 'rejected';

  factory CourierDocumentReview.fromJson(Map<String, dynamic> json) =>
      CourierDocumentReview(
        type: json['type']?.toString() ?? '',
        status: json['status']?.toString() ?? 'pending',
        reviewNote: _nullableText(json['review_note']),
      );
}

class CourierVehicleProfile {
  const CourierVehicleProfile({
    required this.type,
    this.makeId,
    this.makeName,
    this.modelId,
    this.modelName,
    this.colorName,
    this.colorHex,
    this.year,
    this.plate,
    this.ownershipType,
  });

  final String type;
  final String? makeId;
  final String? makeName;
  final String? modelId;
  final String? modelName;
  final String? colorName;
  final String? colorHex;
  final int? year;
  final String? plate;
  final String? ownershipType;

  factory CourierVehicleProfile.fromJson(Map<String, dynamic> json) =>
      CourierVehicleProfile(
        type: json['type']?.toString() ?? 'foot',
        makeId: _nullableText(json['make_id']),
        makeName: _nullableText(json['make_name']),
        modelId: _nullableText(json['model_id']),
        modelName: _nullableText(json['model_name']),
        colorName: _nullableText(json['color_name']),
        colorHex: _nullableText(json['color_hex']),
        year: int.tryParse(json['year']?.toString() ?? ''),
        plate: _nullableText(json['plate']),
        ownershipType: _nullableText(json['ownership_type']),
      );
}

class CourierProfile {
  const CourierProfile({
    required this.id,
    required this.status,
    required this.isActive,
    required this.vehicle,
    this.email,
    this.documentType,
    this.documentNumber,
    this.birthDate,
    this.phone,
    this.address,
    this.rejectionReason,
    this.documents = const [],
  });

  final int id;
  final String status;
  final bool isActive;
  final CourierVehicleProfile vehicle;
  final String? email;
  final String? documentType;
  final String? documentNumber;
  final String? birthDate;
  final String? phone;
  final String? address;
  final String? rejectionReason;
  final List<CourierDocumentReview> documents;

  String get vehicleType => vehicle.type;
  bool get isApproved => status == 'approved' && isActive;
  List<CourierDocumentReview> get documentsRequiringChanges =>
      documents.where((document) => document.requiresChange).toList();

  factory CourierProfile.fromJson(Map<String, dynamic> json) {
    final rawVehicle = json['vehicle'];
    final rawDocuments = json['documents'];

    return CourierProfile(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      status: json['status']?.toString() ?? 'pending',
      isActive: json['is_active'] != false,
      vehicle: CourierVehicleProfile.fromJson(
        rawVehicle is Map
            ? Map<String, dynamic>.from(rawVehicle)
            : const <String, dynamic>{},
      ),
      email: _nullableText(json['email']),
      documentType: _nullableText(json['document_type']),
      documentNumber: _nullableText(json['document_number']),
      birthDate: _nullableText(json['birth_date'])?.split('T').first,
      phone: _nullableText(json['phone']),
      address: _nullableText(json['address']),
      rejectionReason: _nullableText(json['rejection_reason']),
      documents: rawDocuments is List
          ? rawDocuments
                .whereType<Map>()
                .map(
                  (item) => CourierDocumentReview.fromJson(
                    Map<String, dynamic>.from(item),
                  ),
                )
                .toList()
          : const [],
    );
  }
}

String? _nullableText(dynamic value) {
  final text = value?.toString().trim() ?? '';
  return text.isEmpty ? null : text;
}
