# Validación Docker y frontend

## Construcción por alcance

Ejecutar únicamente los servicios afectados y sus dependencias reales.

```bash
# Frontend
docker compose up -d --build frontend

# Ejemplo de uno o varios servicios
docker compose up -d --build auth-service
docker compose up -d --build catalog-service order-service shipping-service notification-service

# Estado general
docker compose ps
```

No copiar el segundo comando de forma automática: reducir la lista a los servicios tocados.

## Logs

```bash
docker compose logs --tail=120 frontend
docker compose logs --tail=120 <servicio-afectado>
```

Confirmar arranque correcto, ausencia de error fatal y conexión con dependencias.

## Protocolo de caché Vite/HMR

Aplicarlo cuando el usuario no vea un cambio o exista evidencia de caché, no en cada tarea:

```bash
docker compose up -d --build frontend
docker compose exec frontend sh -c "rm -rf /app/node_modules/.vite"
docker compose restart frontend
docker compose logs --tail=120 frontend
```

Después realizar hard refresh del navegador con `Ctrl+Shift+R`.

Si el cambio sigue sin aparecer, verificar dentro del contenedor que el archivo montado contiene el código actualizado antes de seguir modificando la UI.

## Endpoints

- Probar el endpoint directamente o mediante el test automatizado existente.
- Validar especialmente los dominios afectados: direcciones, notificaciones, pedidos, favoritos, autenticación y archivos.
- No crear endpoints de debug permanentes.
- Borrar scripts temporales tanto en local como dentro del contenedor.

## Evidencia final

Incluir solo evidencia ejecutada:

- comandos relevantes;
- resultado de build/pruebas;
- estado de `docker compose ps` para contenedores afectados;
- resumen de logs;
- ruta o endpoint validado;
- limitaciones del entorno, si las hubo.
