<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador del carrito de compras (cart-service)
 *
 * Expone los endpoints REST para gestionar el carrito: consultar,
 * agregar productos, actualizar cantidades, eliminar ítems y
 * disparar recordatorios de carritos abandonados.
 *
 * Delega toda la lógica de negocio a CartService y maneja las
 * respuestas JSON con mensajes de error en español.
 *
 * @see CartService
 */
class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    /**
     * GET /api/cart — Obtiene el carrito actual con detalle y totales.
     *
     * Requiere user_id (usuario autenticado) o session_id (visitante).
     * Retorna items con datos enriquecidos desde catalog-service.
     */
    public function index(Request $request): JsonResponse
    {
        $userId    = $request->query('user_id');
        $sessionId = $request->query('session_id');

        // El carrito siempre debe pertenecer a un usuario autenticado o a una sesión anónima.
        if (!$userId && !$sessionId) {
            return response()->json([
                'success' => false,
                'error'   => 'Se requiere user_id o session_id',
            ], 400);
        }

        $cart = $this->cartService->getCartItems($userId, $sessionId);

        return response()->json([
            'success' => true,
            'data'    => $cart,
        ]);
    }

    /**
     * POST /api/cart/add — Agrega una variante de producto al carrito.
     *
     * Valida los campos obligatorios (product_id, size_variant_id) y
     * opcionales (color_variant_id, quantity, user_id, session_id).
     * Si la variante ya existe en el carrito, incrementa la cantidad.
     * Retorna información de la variante agregada (talla, color, precio).
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id'       => 'required|integer',
            'size_variant_id'  => 'required|integer',
            'color_variant_id' => 'nullable|integer',
            'quantity'         => 'nullable|integer|min:1',
            'user_id'          => 'nullable|string',
            'session_id'       => 'nullable|string',
        ], [
            'quantity.integer' => 'La cantidad debe ser un número entero mayor o igual a 1.',
            'quantity.min' => 'La cantidad debe ser un número entero mayor o igual a 1.',
        ]);

        try {
            // CartService centraliza la validación contra catálogo y stock para no duplicar reglas en el controlador.
            $result = $this->cartService->addToCart(
                $request->input('user_id'),
                $request->input('session_id'),
                $request->input('product_id'),
                $request->input('color_variant_id'),
                $request->input('size_variant_id'),
                $request->input('quantity', 1),
            );

            return response()->json([
                'success' => true,
                ...$result,
            ]);
        } catch (\InvalidArgumentException $e) {
            // Los errores de negocio se devuelven como 422 para que el frontend pueda mostrarlos como validación.
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * PUT /api/cart/{itemId} — Actualiza la cantidad de un ítem.
     *
     * Valida que la cantidad sea un entero >= 1. Si el nuevo valor
     * supera el stock disponible, retorna error 422.
     */
    public function update(Request $request, int $itemId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ], [
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.integer' => 'La cantidad debe ser un número entero mayor o igual a 1.',
            'quantity.min' => 'La cantidad debe ser un número entero mayor o igual a 1.',
        ]);

        try {
            // La actualización se delega para reutilizar la validación de stock en tiempo real del servicio.
            $this->cartService->updateQuantity($itemId, $request->input('quantity'));

            return response()->json([
                'success' => true,
                'message' => 'Cantidad actualizada',
            ]);
        } catch (\InvalidArgumentException $e) {
            // Mantiene el mismo contrato de errores de negocio usado al agregar productos.
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * DELETE /api/cart/{itemId} — Elimina un ítem del carrito.
     *
     * Operación idempotente: si el ítem no existe, retorna éxito igualmente.
     */
    public function destroy(int $itemId): JsonResponse
    {
        // La eliminación idempotente evita errores si el frontend repite una solicitud ya procesada.
        $this->cartService->removeFromCart($itemId);

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado del carrito',
        ]);
    }

    /**
     * GET /api/cart/items — IDs de productos en el carrito.
     *
     * Método ligero para que el frontend marque visualmente los
     * productos que ya están en el carrito del usuario.
     */
    public function productIds(Request $request): JsonResponse
    {
        $userId    = $request->query('user_id');
        $sessionId = $request->query('session_id');

        // Se usa una consulta ligera para estados visuales del catálogo sin cargar todo el carrito.
        $ids = $this->cartService->getCartProductIds($userId, $sessionId);

        return response()->json([
            'success' => true,
            'items'   => $ids,
        ]);
    }

    /**
     * POST /api/admin/cart/abandoned/reminders/dispatch
     *
     * Dispara recordatorios para carritos abandonados. Endpoint interno
     * protegido por token X-Internal-Token. Consulta carritos inactivos
     * y envía notificaciones push + email vía notification-service.
     *
     * @see CartService::dispatchAbandonedCartReminders()
     */
    public function dispatchAbandonedReminders(Request $request): JsonResponse
    {
        // Este endpoint es interno porque dispara mensajes fuera del flujo normal del comprador.
        if (!$this->hasInternalAccess($request)) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        $data = $request->validate([
            'inactive_minutes' => ['nullable', 'integer', 'min:30', 'max:10080'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        // Se aplican valores por defecto conservadores para evitar cargas excesivas en ejecuciones manuales.
        $summary = $this->cartService->dispatchAbandonedCartReminders(
            (int) ($data['inactive_minutes'] ?? 180),
            (int) ($data['limit'] ?? 120),
        );

        return response()->json([
            'success' => true,
            'message' => 'Disparo de carritos abandonados ejecutado.',
            'data' => [
                'summary' => $summary,
            ],
        ]);
    }

    /**
     * Verifica que la petición tenga el token interno correcto.
     * Si no hay token configurado (vacío), permite el acceso libre
     * (entorno de desarrollo). Usa hash_equals para comparación
     * segura contra ataques de temporización.
     */
    private function hasInternalAccess(Request $request): bool
    {
        $expectedToken = trim((string) config('services.notifications.internal_token', env('AUTH_INTERNAL_TOKEN', '')));
        // En entornos locales sin token configurado se permite probar el flujo interno sin credenciales.
        if ($expectedToken === '') {
            return true;
        }

        $providedToken = trim((string) $request->header('X-Internal-Token', ''));
        // Sin encabezado no hay identidad interna verificable para ejecutar recordatorios.
        if ($providedToken === '') {
            return false;
        }

        return hash_equals($expectedToken, $providedToken);
    }
}
