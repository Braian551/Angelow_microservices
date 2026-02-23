<?php

namespace App\Services;

use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;

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
    ) {}

    /**
     * Get a filtered, paginated list of products.
     */
    public function listProducts(array $filters, int $page = 1, int $perPage = 12, ?string $userId = null): array
    {
        $offset = ($page - 1) * $perPage;
        $result = $this->productRepository->getFiltered($filters, $perPage, $offset, $userId);

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
    public function getProductDetail(string $slug): array
    {
        $product = $this->productRepository->findBySlug($slug);

        if (!$product) {
            throw new \App\Exceptions\NotFoundException('Producto no encontrado');
        }

        $productId  = $product->id;
        $categoryId = $product->category_id;

        return [
            'product'     => (array) $product,
            'variants'    => $this->productRepository->getVariants($productId),
            'images'      => $this->productRepository->getAdditionalImages($productId),
            'reviews'     => $this->productRepository->getReviews($productId),
            'questions'   => $this->productRepository->getQuestions($productId),
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
}
