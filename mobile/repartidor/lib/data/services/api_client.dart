import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

class ApiException implements Exception {
  const ApiException(this.message, {this.statusCode, this.payload});
  final String message;
  final int? statusCode;
  final Map<String, dynamic>? payload;
  @override
  String toString() => message;
}

class ApiClient {
  ApiClient({required List<String> baseUrls, http.Client? client})
    : baseUrls = _normalizeBaseUrls(baseUrls),
      _client = client ?? http.Client();

  static const _requestTimeout = Duration(seconds: 12);

  final List<String> baseUrls;
  final http.Client _client;

  Future<Map<String, dynamic>> get(
    String path, {
    String? token,
    Map<String, String>? query,
  }) {
    return _send('GET', path, token: token, query: query);
  }

  Future<Map<String, dynamic>> post(
    String path, {
    String? token,
    Map<String, dynamic>? body,
  }) {
    return _send('POST', path, token: token, body: body);
  }

  Future<Map<String, dynamic>> _send(
    String method,
    String path, {
    String? token,
    Map<String, dynamic>? body,
    Map<String, String>? query,
  }) {
    return _sendWithFallback(
      (baseUrl) => _sendTo(
        baseUrl,
        method,
        path,
        token: token,
        body: body,
        query: query,
      ),
    );
  }

  Future<Map<String, dynamic>> _sendTo(
    String baseUrl,
    String method,
    String path, {
    String? token,
    Map<String, dynamic>? body,
    Map<String, String>? query,
  }) async {
    final uri = Uri.parse('$baseUrl$path').replace(queryParameters: query);
    final request = http.Request(method, uri)
      ..headers.addAll({
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (token?.isNotEmpty == true) 'Authorization': 'Bearer $token',
      });
    if (body != null) request.body = jsonEncode(body);
    final response = await http.Response.fromStream(
      await _client.send(request).timeout(_requestTimeout),
    );
    return _decode(response);
  }

  Future<Map<String, dynamic>> multipart(
    String path, {
    required String token,
    required Map<String, String> fields,
    required Map<String, File> files,
  }) {
    return _sendWithFallback(
      (baseUrl) => _multipartTo(
        baseUrl,
        path,
        token: token,
        fields: fields,
        files: files,
      ),
    );
  }

  Future<Map<String, dynamic>> _multipartTo(
    String baseUrl,
    String path, {
    required String token,
    required Map<String, String> fields,
    required Map<String, File> files,
  }) async {
    final request = http.MultipartRequest('POST', Uri.parse('$baseUrl$path'))
      ..headers.addAll({
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      })
      ..fields.addAll(fields);
    for (final entry in files.entries) {
      request.files.add(
        await http.MultipartFile.fromPath(entry.key, entry.value.path),
      );
    }
    return _decode(
      await http.Response.fromStream(
        await _client.send(request).timeout(_requestTimeout),
      ),
    );
  }

  Future<T> _sendWithFallback<T>(Future<T> Function(String baseUrl) send) async {
    Object? lastError;
    StackTrace? lastStackTrace;

    for (final baseUrl in baseUrls) {
      try {
        return await send(baseUrl);
      } on SocketException catch (error, stackTrace) {
        lastError = error;
        lastStackTrace = stackTrace;
      } on http.ClientException catch (error, stackTrace) {
        lastError = error;
        lastStackTrace = stackTrace;
      } on TimeoutException catch (error, stackTrace) {
        lastError = error;
        lastStackTrace = stackTrace;
      } on ApiException catch (error, stackTrace) {
        if (!_isTemporaryServerError(error.statusCode)) rethrow;
        lastError = error;
        lastStackTrace = stackTrace;
      }
    }

    Error.throwWithStackTrace(lastError!, lastStackTrace!);
  }

  static List<String> _normalizeBaseUrls(List<String> baseUrls) {
    final uniqueUrls = <String>{};
    for (final baseUrl in baseUrls) {
      final normalized = baseUrl.trim().replaceFirst(RegExp(r'/+$'), '');
      if (normalized.isNotEmpty) uniqueUrls.add(normalized);
    }
    if (uniqueUrls.isEmpty) {
      throw ArgumentError.value(baseUrls, 'baseUrls', 'Debe incluir una URL.');
    }
    return List.unmodifiable(uniqueUrls);
  }

  static bool _isTemporaryServerError(int? statusCode) {
    return statusCode == 502 || statusCode == 503 || statusCode == 504;
  }

  Map<String, dynamic> _decode(http.Response response) {
    final decoded = response.body.isEmpty
        ? <String, dynamic>{}
        : jsonDecode(response.body);
    final payload = decoded is Map
        ? Map<String, dynamic>.from(decoded)
        : <String, dynamic>{};
    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw ApiException(
        payload['message']?.toString() ??
            'No fue posible completar la solicitud.',
        statusCode: response.statusCode,
        payload: payload,
      );
    }
    return payload;
  }
}
