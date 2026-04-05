<?php

namespace App\Services;

use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Catalog Service
 *
 * Contains all business logic for browsing and searching the product catalog.
 * Delegates data access to repositories.
 */
class CatalogService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly AuthProfileService $authProfileService,
    ) {}

    /**
     * Get a filtered, paginated list of products.
     */
    public function listProducts(
        array $filters,
        int $page = 1,
        int $perPage = 12,
        ?string $userId = null,
        ?string $userEmail = null,
    ): array
    {
        $offset = ($page - 1) * $perPage;
        $resolvedUserId = $this->resolveCatalogUserId($userId, $userEmail);
        $result = $this->productRepository->getFiltered($filters, $perPage, $offset, $resolvedUserId);

        return [
            'products'    => $result['products'],
            'total'       => $result['total'],
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => max(1, (int) ceil($result['total'] / $perPage)),
        ];
    }

    /**
     * Get detailed product information.
     *
     * @throws \App\Exceptions\NotFoundException
     */
    public function getProductDetail(
        string $slug,
        ?string $userId = null,
        ?string $userEmail = null,
    ): array
    {
        $resolvedUserId = $this->resolveCatalogUserId($userId, $userEmail);
        $product = $this->productRepository->findBySlug($slug);

        if (!$product) {
            throw new \App\Exceptions\NotFoundException('Producto no encontrado');
        }

        $productId  = $product->id;
        $categoryId = $product->category_id;
        $reviewsData = $this->productRepository->getReviews($productId, $resolvedUserId);
        $questionsData = $this->productRepository->getQuestions($productId, 5, $resolvedUserId);

        $userIds = $this->collectUserIdsForProfiles($reviewsData, $questionsData);
        $profilesByUserId = $this->authProfileService->getProfilesByIds($userIds);

        $reviewsData = $this->enrichReviewsWithProfiles($reviewsData, $profilesByUserId);
        $questionsData = $this->enrichQuestionsWithProfiles($questionsData, $profilesByUserId);

        return [
            'product'     => (array) $product,
            'variants'    => $this->productRepository->getVariants($productId),
            'images'      => $this->productRepository->getAdditionalImages($productId),
            'reviews'     => $reviewsData,
            'questions'   => $questionsData,
            'related'     => $this->productRepository->getRelated($productId, $categoryId),
        ];
    }

    /**
     * Get all active categories.
     */
    public function getCategories(): array
    {
        return $this->categoryRepository->getAllActive();
    }

    /**
     * Get all active collections.
     */
    public function getCollections(): array
    {
        return $this->categoryRepository->getAllCollections();
    }

    /**
     * Recolecta ids de usuario presentes en reseñas y preguntas/respuestas.
     *
     * @return array<int, string>
     */
    private function collectUserIdsForProfiles(array $reviewsData, array $questionsData): array
    {
        $userIds = [];

        $reviews = $reviewsData['reviews'] ?? [];
        if (is_array($reviews)) {
            foreach ($reviews as $review) {
                if (!is_array($review)) {
                    continue;
                }
                $userId = trim((string) ($review['user_id'] ?? ''));
                if ($userId !== '') {
                    $userIds[$userId] = true;
                }
            }
        }

        if (is_array($questionsData)) {
            foreach ($questionsData as $question) {
                if (!is_array($question)) {
                    continue;
                }

                $questionUserId = trim((string) ($question['user_id'] ?? ''));
                if ($questionUserId !== '') {
                    $userIds[$questionUserId] = true;
                }

                $answers = $question['answers'] ?? [];
                if (!is_array($answers)) {
                    continue;
                }

                foreach ($answers as $answer) {
                    if (!is_array($answer)) {
                        continue;
                    }
                    $answerUserId = trim((string) ($answer['user_id'] ?? ''));
                    if ($answerUserId !== '') {
                        $userIds[$answerUserId] = true;
                    }
                }
            }
        }

        return array_keys($userIds);
    }

    /**
     * Aplica nombre/avatar del auth-service sobre las reseñas.
     */
    private function enrichReviewsWithProfiles(array $reviewsData, array $profilesByUserId): array
    {
        $reviews = $reviewsData['reviews'] ?? [];
        if (!is_array($reviews)) {
            return $reviewsData;
        }

        $reviewsData['reviews'] = array_map(function ($review) use ($profilesByUserId) {
            if (!is_array($review)) {
                return $review;
            }

            $userId = trim((string) ($review['user_id'] ?? ''));
            if ($userId === '' || !isset($profilesByUserId[$userId])) {
                return $review;
            }

            $profile = $profilesByUserId[$userId];
            $review['user_name'] = $profile['name'];
            $review['user_image'] = $profile['image'];

            return $review;
        }, $reviews);

        return $reviewsData;
    }

    /**
     * Aplica nombre/avatar del auth-service sobre preguntas y respuestas.
     */
    private function enrichQuestionsWithProfiles(array $questionsData, array $profilesByUserId): array
    {
        return array_map(function ($question) use ($profilesByUserId) {
            if (!is_array($question)) {
                return $question;
            }

            $questionUserId = trim((string) ($question['user_id'] ?? ''));
            if ($questionUserId !== '' && isset($profilesByUserId[$questionUserId])) {
                $questionProfile = $profilesByUserId[$questionUserId];
                $question['user_name'] = $questionProfile['name'];
                $question['user_image'] = $questionProfile['image'];
            }

            $answers = $question['answers'] ?? [];
            if (is_array($answers)) {
                $question['answers'] = array_map(function ($answer) use ($profilesByUserId) {
                    if (!is_array($answer)) {
                        return $answer;
                    }

                    $answerUserId = trim((string) ($answer['user_id'] ?? ''));
                    if ($answerUserId !== '' && isset($profilesByUserId[$answerUserId])) {
                        $answerProfile = $profilesByUserId[$answerUserId];
                        $answer['user_name'] = $answerProfile['name'];
                        $answer['user_image'] = $answerProfile['image'];
                    }

                    return $answer;
                }, $answers);
            }

            return $question;
        }, $questionsData);
    }

    /**
     * Resuelve el user_id para favoritos/reseñas usando fallback por correo a legacy.
     */
    private function resolveCatalogUserId(?string $userId, ?string $userEmail): ?string
    {
        $normalizedUserId = $this->nullableString($userId);
        $normalizedUserEmail = $this->nullableString($userEmail);

        if ($normalizedUserEmail !== null) {
            try {
                $legacyUserId = DB::connection('legacy_mysql')
                    ->table('users')
                    ->whereRaw('LOWER(email) = ?', [Str::lower($normalizedUserEmail)])
                    ->value('id');

                if ($legacyUserId !== null && $legacyUserId !== '') {
                    return (string) $legacyUserId;
                }
            } catch (Throwable) {
                // Si falla legacy, usa el user_id recibido para no romper el flujo.
            }
        }

        return $normalizedUserId;
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        return $normalized === '' ? null : $normalized;
    }
}

