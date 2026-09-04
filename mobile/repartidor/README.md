# Angelow Repartidor

Aplicación Flutter para vinculación y operación de repartidores de Angelow. Usa arquitectura por capas: las vistas y ViewModels viven en `lib/ui`, repositorios/modelos en `lib/data` y configuración transversal en `lib/config`.

## Ejecución local

```powershell
flutter pub get
flutter run
```

La aplicación consulta primero `https://angelow.online`. Si no puede conectarse o el servidor está temporalmente no disponible, reintenta una vez contra el entorno local del emulador Android (`10.0.2.2`). Se puede reemplazar cualquiera de ambas URLs al ejecutar:

```powershell
flutter run --dart-define=AUTH_API_URL=https://angelow.online/api/auth-service --dart-define=SHIPPING_API_URL=https://angelow.online/api/shipping-service --dart-define=AUTH_API_FALLBACK_URL=http://10.0.2.2:8001/api --dart-define=SHIPPING_API_FALLBACK_URL=http://10.0.2.2:8007/api
```

En un dispositivo físico, reemplaza las URLs de respaldo por una IP alcanzable del equipo de desarrollo. La configuración pública de Mapbox se obtiene en tiempo de ejecución desde `shipping-service`, solo para repartidores autenticados, aprobados y activos; no se compila dentro de la aplicación.

## Configuración externa

- Firebase: proyecto `angelow-4e5fe`, inicializado desde `lib/firebase_options.dart`.
- Google Sign-In: configuración Android en `android/app/google-services.json` y URL scheme iOS en `ios/Runner/Info.plist`.
- Mapbox: mapa nativo 3D y Directions API con configuración entregada por el servidor.
- NHTSA vPIC y The Color API: catálogos con fallback local para marca, modelo y color.

## Validación

```powershell
flutter analyze
flutter test
dart run flutter_native_splash:create
```

La ubicación se solicita al iniciar una ruta y solo se comparte si el repartidor acepta el consentimiento mostrado en ese momento.

Las entregas disponibles y asignadas se presentan en tarjetas resumidas. Al tocar una tarjeta se abre su detalle; la aceptación solo se ejecuta desde ese detalle y bloquea envíos repetidos mientras responde el servidor.

## Revisión de vinculación

Después de enviar el registro, la app muestra el progreso de revisión y permite actualizar el estado manualmente o con gesto de recarga. Si el administrador solicita cambios, se presentan el mensaje general y las observaciones de cada documento; el repartidor puede conservar sus datos y adjuntos aprobados, reemplazar únicamente los archivos señalados y volver a enviar la solicitud.
