<?php

namespace App\Repositories\Contracts;

/**
 * Contract for Product repository.
 *
 * Defines the data access interface for product operations.
 */
interface ProductRepositoryInterface
{
    /**
     * Get a paginated, filtered list of products.
     */
    public function getFiltered(array $filters, int $limit, int $offset, ?string $userId = null): array;

    /**
     * Get a single product by its slug.
     */
    public function findBySlug(string $slug): ?object;

    /**
     * Get product color variants with size variants and images.
     */
    public function getVariants(int $productId): array;

    /**
     * Get additional (non-primary) images for a product.
     */
    public function getAdditionalImages(int $productId): array;

    /**
     * Get related products (same category).
     */
    public function getRelated(int $productId, int $categoryId, int $limit = 4): array;

    /**
     * Get reviews for a product.
     */
    public function getReviews(int $productId, ?string $userId = null): array;

    /**
     * Get questions and answers for a product.
     */
    public function getQuestions(int $productId, int $limit = 5, ?string $userId = null): array;
}
