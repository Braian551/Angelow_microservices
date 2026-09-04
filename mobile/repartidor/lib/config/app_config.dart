abstract final class AppConfig {
  static const _publicApiOrigin = 'https://angelow.online/api';

  static const googleIosClientId =
      '77139939464-8qoe6n4a5r85sg9j2b553hg46pu8sb1m.apps.googleusercontent.com';
  static const authApiUrl = String.fromEnvironment(
    'AUTH_API_URL',
    defaultValue: '$_publicApiOrigin/auth-service',
  );
  static const authApiFallbackUrl = String.fromEnvironment(
    'AUTH_API_FALLBACK_URL',
    defaultValue: 'http://10.0.2.2:8001/api',
  );
  static const authApiUrls = <String>[
    authApiUrl,
    authApiFallbackUrl,
  ];
  static const shippingApiUrl = String.fromEnvironment(
    'SHIPPING_API_URL',
    defaultValue: '$_publicApiOrigin/shipping-service',
  );
  static const shippingApiFallbackUrl = String.fromEnvironment(
    'SHIPPING_API_FALLBACK_URL',
    defaultValue: 'http://10.0.2.2:8007/api',
  );
  static const shippingApiUrls = <String>[
    shippingApiUrl,
    shippingApiFallbackUrl,
  ];
  static const termsVersion = '2026-07-22';
  static const webPortalUrl = 'https://angelow.online';
}
