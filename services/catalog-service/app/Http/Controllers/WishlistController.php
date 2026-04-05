<?php

namespace App\Http\Controllers;

use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Wishlist Controller
 *
 * Handles API requests for the user's wishlist/favorites.
 */
class WishlistController extends Controller
{
    private const LEGACY_CONNECTION = 'legacy_mysql';

    public function __construct(
        private readonly WishlistService $wishlistService,
    ) {}

    /**
     * GET /api/wishlist?user_id=xxx&user_email=yyy
     *
     * Get all wishlist items for a user.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'string', 'max:40'],
            'user_email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        $candidateUserIds = $this->buildCandidateUserIds(
            $this->nullableString($data['user_id'] ?? null),
            $this->nullableString($data['user_email'] ?? null),
        );

        if ($candidateUserIds === []) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $items = collect();

        foreach ($candidateUserIds as $candidateUserId) {
            $items = $items->concat($this->wishlistService->getUserWishlist($candidateUserId));
        }

        $normalizedItems = $items
            ->sortByDesc(function (array $item): int {
                $raw = (string) ($item['added_at'] ?? $item['created_at'] ?? '');
                $timestamp = strtotime($raw);
                return $timestamp ?: 0;
            })
            ->unique(function (array $item): string {
                $productId = (string) ($item['id'] ?? $item['product_id'] ?? '');
                return $productId;
            })
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => $normalizedItems,
        ]);
    }

    /**
     * POST /api/wishlist/toggle
     *
     * Toggle a product in the user's wishlist.
     */
    public function toggle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'string', 'max:40'],
            'user_email' => ['nullable', 'string', 'email', 'max:255'],
            'product_id' => ['required', 'integer'],
        ]);

        $candidateUserIds = $this->buildCandidateUserIds(
            $this->nullableString($data['user_id'] ?? null),
            $this->nullableString($data['user_email'] ?? null),
        );

        if ($candidateUserIds === []) {
            return response()->json([
                'success' => false,
                'error' => 'Debes enviar user_id o user_email',
            ], 422);
        }

        $productId = (int) $data['product_id'];

        foreach ($candidateUserIds as $candidateUserId) {
            if ($this->wishlistService->hasProduct($candidateUserId, $productId)) {
                $this->wishlistService->removeProduct($candidateUserId, $productId);

                return response()->json([
                    'success' => true,
                    'action' => 'removed',
                    'message' => 'Producto eliminado de tu lista de deseos',
                ]);
            }
        }

        try {
            $targetUserId = $candidateUserIds[0];
            $this->wishlistService->addProduct($targetUserId, $productId);

            return response()->json([
                'success' => true,
                'action' => 'added',
                'message' => 'Producto añadido a tu lista de deseos',
            ]);
        } catch (\App\Exceptions\NotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Construye candidatos de user_id priorizando el usuario legacy por correo.
     * Esto evita perder favoritos durante la migración de identificadores.
     *
     * @return array<int, string>
     */
    private function buildCandidateUserIds(?string $userId, ?string $userEmail): array
    {
        $candidateUserIds = [];

        $legacyUserId = $this->resolveLegacyUserIdByEmail($userEmail);
        if ($legacyUserId !== null) {
            $candidateUserIds[] = $legacyUserId;
        }

        if ($userId !== null && $userId !== '' && !in_array($userId, $candidateUserIds, true)) {
            $candidateUserIds[] = $userId;
        }

        return $candidateUserIds;
    }

    /**
     * Resuelve el user_id del sistema legacy por correo.
     */
    private function resolveLegacyUserIdByEmail(?string $userEmail): ?string
    {
        if ($userEmail === null || $userEmail === '') {
            return null;
        }

        try {
            $userId = DB::connection(self::LEGACY_CONNECTION)
                ->table('users')
                ->whereRaw('LOWER(email) = ?', [Str::lower($userEmail)])
                ->value('id');

            if ($userId === null || $userId === '') {
                return null;
            }

            return (string) $userId;
        } catch (Throwable) {
            return null;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        return $normalized === '' ? null : $normalized;
    }
}
