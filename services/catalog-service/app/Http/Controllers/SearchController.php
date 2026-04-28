<?php

namespace App\Http\Controllers;

use App\Models\PopularSearch;
use App\Models\SearchHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDO;
use Throwable;

/**
 * Search Controller
 *
 * Endpoint de sugerencias de búsqueda para el header.
 * Retorna producto sugerido con imagen + términos de búsqueda relevantes.
 * Réplica del comportamiento de angelow/ajax/busqueda/search.php
 */
class SearchController extends Controller
{
    public function history(Request $request): JsonResponse
    {
        $userId = trim((string) $request->query('user_id', ''));

        if ($userId === '') {
            return response()->json([
                'success' => true,
                'data' => ['terms' => []],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'terms' => $this->getKnownSearchHistory($userId),
            ],
        ]);
    }

    public function storeHistory(Request $request): JsonResponse
    {
        $userId = trim((string) $request->input('user_id', ''));
        $term = trim((string) $request->input('term', ''));

        if ($userId === '' || mb_strlen($term) < 2) {
            return response()->json([
                'success' => true,
            ]);
        }

        $normalizedTerm = Str::squish($term);

        $existingHistory = SearchHistory::query()
            ->where('user_id', $userId)
            ->whereRaw('LOWER(TRIM(search_term)) = ?', [mb_strtolower($normalizedTerm)])
            ->first();

        if ($existingHistory) {
            $existingHistory->forceFill([
                'search_term' => $normalizedTerm,
                'created_at' => now(),
            ])->save();
        } else {
            SearchHistory::query()->create([
                'user_id' => $userId,
                'search_term' => $normalizedTerm,
                'created_at' => now(),
            ]);
        }

        $popularSearch = PopularSearch::query()
            ->whereRaw('LOWER(TRIM(search_term)) = ?', [mb_strtolower($normalizedTerm)])
            ->first();

        if ($popularSearch) {
            $popularSearch->forceFill([
                'search_term' => $normalizedTerm,
                'search_count' => (int) $popularSearch->search_count + 1,
                'last_searched' => now(),
            ])->save();
        } else {
            PopularSearch::query()->create([
                'search_term' => $normalizedTerm,
                'search_count' => 1,
                'last_searched' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * GET /api/search/suggestions?term=xxx&user_id=xxx
     *
     * Devuelve sugerencias de producto y términos de búsqueda.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('term', ''));
        $userId = trim((string) $request->query('user_id', ''));

        if (mb_strlen($term) < 2) {
            return response()->json([
                'success' => true,
                'data' => ['suggestions' => [], 'terms' => []],
            ]);
        }

        try {
            $legacyPayload = $this->searchWithLegacyProcedure($term, $userId);

            if (!empty($legacyPayload['suggestions']) || !empty($legacyPayload['terms'])) {
                return response()->json([
                    'success' => true,
                    'data' => $legacyPayload,
                ]);
            }
        } catch (Throwable) {
            // Fallback silencioso a consultas equivalentes si el procedimiento no existe o falla.
        }

        $suggestions = $this->findProductSuggestionsFallback($term);
        $terms = $this->findSearchTermsFallback($term, $userId);

        return response()->json([
            'success' => true,
            'data' => [
                'suggestions' => $suggestions,
                'terms' => $terms,
            ],
        ]);
    }

    /**
     * Busca el primer producto activo que coincida con el término.
     * Primero intenta en BD principal, fallback a legacy.
     *
     * @return array<int, array{name: string, slug: string, image_path: string}>
     */
    private function searchWithLegacyProcedure(string $term, string $userId): array
    {
        $pdo = DB::connection('legacy_mysql')->getPdo();
        $stmt = $pdo->prepare('CALL SearchProductsAndTerms(:term, :user_id)');
        $stmt->bindValue(':term', $term);
        $stmt->bindValue(':user_id', $userId);
        $stmt->execute();

        $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $terms = [];

        if ($stmt->nextRowset()) {
            $terms = $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
        }

        $stmt->closeCursor();

        return [
            'suggestions' => array_values(array_slice(array_filter(array_map(
                fn (array $item) => $this->normalizeSuggestion($item),
                $suggestions
            ), fn (array $item) => !empty($item['name']) && !empty($item['slug'])), 0, 5)),
            'terms' => $this->normalizeTerms($terms, 6),
        ];
    }

    private function findProductSuggestionsFallback(string $term): array
    {
        // Intentar en BD principal (PostgreSQL)
        $products = $this->queryProductSuggestions(null, $term);

        // Fallback a legacy si no se encuentra
        if ($products === []) {
            $products = $this->queryProductSuggestions('legacy_mysql', $term);
        }

        return $products;
    }

    /**
     * Ejecuta la consulta de productos sugeridos en la conexión indicada.
     */
    private function queryProductSuggestions(?string $connection, string $term): array
    {
        try {
            $db = $connection ? DB::connection($connection) : DB::connection();
            $operator = $db->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            $results = $db->table('products as p')
                ->leftJoin('product_images as pi', function ($join) {
                    $join->on('p.id', '=', 'pi.product_id')
                        ->where('pi.is_primary', '=', true);
                })
                ->where('p.is_active', true)
                ->where(function ($q) use ($term, $operator) {
                    $q->where('p.name', $operator, "%{$term}%")
                        ->orWhere('p.description', $operator, "%{$term}%")
                        ->orWhere('p.brand', $operator, "%{$term}%");
                })
                ->orderByRaw(
                    $db->getDriverName() === 'pgsql'
                        ? "CASE WHEN p.name ILIKE ? THEN 1 WHEN p.name ILIKE ? THEN 2 ELSE 3 END"
                        : "CASE WHEN p.name LIKE ? THEN 1 WHEN p.name LIKE ? THEN 2 ELSE 3 END",
                    ["{$term}%", "%{$term}%"]
                )
                ->select([
                    'p.name',
                    'p.slug',
                    $db->raw("COALESCE(pi.image_path, 'uploads/products/default-product.jpg') as image_path"),
                ])
                ->limit(5)
                ->get();

            return array_values(array_map(
                fn ($row) => $this->normalizeSuggestion((array) $row),
                $results->map(fn ($row) => (array) $row)->all()
            ));
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Busca términos de búsqueda relevantes combinando:
     * 1. Historial del usuario (search_history - legacy)
     * 2. Búsquedas populares (popular_searches - legacy, tiene más datos)
     * 3. Nombres de categorías que coincidan (BD principal)
     *
     * @return array<int, string>
     */
    private function findSearchTermsFallback(string $term, string $userId): array
    {
        $allTerms = [];

        // 1. Historial del usuario (si está logueado) — legacy_mysql
        if ($userId !== '') {
            $historyTerms = $this->queryUserSearchHistory($userId, $term);
            $allTerms = array_merge($allTerms, $historyTerms);
        }

        // 2. Búsquedas populares — legacy_mysql (37 registros vs 9 en principal)
        $popularTerms = $this->queryPopularSearches($term);
        $allTerms = array_merge($allTerms, $popularTerms);

        // 3. Nombres de categorías que coincidan — BD principal
        $categoryTerms = $this->queryCategoryNames($term);
        $allTerms = array_merge($allTerms, $categoryTerms);

        // Eliminar duplicados, filtrar vacíos, limitar a 5
        $unique = [];
        foreach ($allTerms as $t) {
            $clean = trim((string) $t);
            $lower = mb_strtolower($clean);
            if ($clean !== '' && !isset($unique[$lower])) {
                $unique[$lower] = $clean;
            }
        }

        return array_values(array_slice($unique, 0, 6));
    }

    /**
     * Normaliza una sugerencia al formato esperado por el header SPA.
     * Mantiene compatibilidad con rutas legacy de uploads/productos.
     *
     * @param array<string, mixed> $item
     * @return array{name: string, slug: string, image_path: string}
     */
    private function normalizeSuggestion(array $item): array
    {
        $imagePath = trim((string) ($item['image_path'] ?? 'uploads/productos/default-product.jpg'));
        $imagePath = str_replace('uploads/products/', 'uploads/productos/', $imagePath);

        return [
            'name' => trim((string) ($item['name'] ?? '')),
            'slug' => trim((string) ($item['slug'] ?? '')),
            'image_path' => $imagePath !== '' ? $imagePath : 'uploads/productos/default-product.jpg',
        ];
    }

    /**
     * Filtra términos vacíos, remueve duplicados y limita la lista.
     *
     * @param array<int, mixed> $terms
     * @return array<int, string>
     */
    private function normalizeTerms(array $terms, int $limit = 6): array
    {
        $unique = [];

        foreach ($terms as $term) {
            $clean = trim((string) $term);
            $lower = mb_strtolower($clean);

            if ($clean !== '' && !isset($unique[$lower])) {
                $unique[$lower] = $clean;
            }
        }

        return array_values(array_slice($unique, 0, $limit));
    }

    /**
     * Historial de búsqueda del usuario desde legacy.
     */
    private function queryUserSearchHistory(string $userId, string $term): array
    {
        $mainTerms = $this->querySearchHistoryConnection(null, $userId, $term, 3);
        $legacyTerms = $this->querySearchHistoryConnection('legacy_mysql', $userId, $term, 3);

        return $this->normalizeTerms(array_merge($mainTerms, $legacyTerms), 3);
    }

    /**
     * Historial completo del usuario para marcar términos ya usados.
     *
     * @return array<int, string>
     */
    private function getKnownSearchHistory(string $userId): array
    {
        $mainTerms = $this->querySearchHistoryConnection(null, $userId, null, 50);
        $legacyTerms = $this->querySearchHistoryConnection('legacy_mysql', $userId, null, 50);

        return array_values(array_map(
            static fn (string $historyTerm) => mb_strtolower(trim($historyTerm)),
            $this->normalizeTerms(array_merge($mainTerms, $legacyTerms), 50)
        ));
    }

    /**
     * Consulta historial de búsqueda en una conexión específica.
     *
     * @return array<int, string>
     */
    private function querySearchHistoryConnection(?string $connection, string $userId, ?string $term, int $limit): array
    {
        try {
            $db = $connection ? DB::connection($connection) : DB::connection();
            $operator = $db->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            $query = $db
                ->table('search_history')
                ->where('user_id', $userId)
                ->whereNotNull('search_term')
                ->where('search_term', '!=', '')
                ->select('search_term')
                ->orderByDesc('created_at')
                ->limit($limit);

            if ($term !== null && $term !== '') {
                $query->where('search_term', $operator, "{$term}%");
            }

            return $query->pluck('search_term')->toArray();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Búsquedas populares desde legacy (tiene más registros).
     */
    private function queryPopularSearches(string $term): array
    {
        try {
            return DB::connection('legacy_mysql')
                ->table('popular_searches')
                ->where('search_term', 'like', "%{$term}%")
                ->whereNotNull('search_term')
                ->where('search_term', '!=', '')
                ->orderByDesc('search_count')
                ->limit(3)
                ->pluck('search_term')
                ->toArray();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Nombres de categorías que coincidan con el término.
     * Usa BD principal (PostgreSQL).
     */
    private function queryCategoryNames(string $term): array
    {
        try {
            $db = DB::connection();
            $operator = $db->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            return $db->table('categories')
                ->where('is_active', true)
                ->where('name', $operator, "%{$term}%")
                ->orderBy('name')
                ->limit(3)
                ->pluck('name')
                ->toArray();
        } catch (Throwable) {
            return [];
        }
    }
}
