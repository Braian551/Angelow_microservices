# catalog-service - Diagrama de clases en PlantUML

<!-- indice:auto:start -->
## Índice rápido

- [Alcance](#alcance)
- [Diagrama](#diagrama)
- [Fuentes revisadas](#fuentes-revisadas)
- [Documentos relacionados](#documentos-relacionados)
<!-- indice:auto:end -->

## Alcance

Diagrama del microservicio de catálogo, home, búsquedas, favoritos, administración de productos, inventario, reseñas, preguntas, sliders y configuración del sitio. Incluye atributos de modelos/tablas y relaciones UML con multiplicidad, composición y agregación.

## Diagrama

```plantuml
@startuml
title catalog-service - Catálogo, home, favoritos e inventario
left to right direction
skinparam classAttributeIconSize 0
skinparam packageStyle rectangle

package "Controladores públicos e internos" {
  class "ProductController" as CatalogProductController <<Controller>> {
    -catalogService: CatalogService
    +__construct(catalogService)
    +index(request)
    +show(request, slug)
  }
  class "CategoryController" as CatalogCategoryController <<Controller>> {
    -catalogService: CatalogService
    +__construct(catalogService)
    +index()
    +collections()
  }
  class "SiteController" as CatalogSiteController <<Controller>> {
    -siteService: SiteService
    +__construct(siteService)
    +home()
    +settings()
    +sliders()
  }
  class "WishlistController" as CatalogWishlistController <<Controller>> {
    -wishlistService: WishlistService
    +__construct(wishlistService)
    +index(request)
    +toggle(request)
  }
  class "SearchController" as CatalogSearchController <<Controller>> {
    +history(request)
    +storeHistory(request)
    +suggestions(request)
  }
  class "InternalCatalogController" as CatalogInternalController <<Controller>> {
    -realtimePublisher: StockRealtimePublisher
    +product(id)
    +variant(id)
    +commitInventory(request)
  }
  class "HealthController" as CatalogHealthController <<Controller>> {
    +__invoke()
  }
}

package "Control administrativo" {
  class "AdminCatalogController" as CatalogAdminController <<Controller>> {
    -inventoryAlertService: InventoryAlertService
    -realtimePublisher: StockRealtimePublisher
    +products(request)
    +showProduct(id)
    +storeProduct(request)
    +updateProduct(request, id)
    +destroyProduct(id)
    +toggleProductStatus(id)
    +categories()
    +storeCategory(request)
    +updateCategory(request, id)
    +destroyCategory(id)
    +collections()
    +colors()
    +storeCollection(request)
    +updateCollection(request, id)
    +destroyCollection(id)
    +sizes()
    +storeSize(request)
    +updateSize(request, id)
    +destroySize(id)
    +inventory(request)
    +inventoryHistory(request)
    +adjustStock(request)
    +transferStock(request)
    +reviews(request)
    +updateReviewStatus(request, id)
    +deleteReview(id)
    +questions(request)
    +answerQuestion(request, id)
    +deleteQuestion(id)
    +sliders()
    +storeSlider(request)
    +updateSlider(request, id)
    +destroySlider(id)
    +toggleSliderStatus(id)
    +reorderSliders(request)
    +settings()
    +updateSettings(request)
    +exportProductsCsv(request)
    +exportProductsPdf(request)
    +reportProducts(request)
  }
}

package "Servicios" {
  class "CatalogService" as CatalogService <<Service>> {
    -products: ProductRepositoryInterface
    -categories: CategoryRepositoryInterface
    -authProfiles: AuthProfileService
    +__construct(products, categories, authProfiles)
    +listProducts(filters, limit, offset, userId)
    +getProductDetail(slug, userId)
    +getCategories()
    +getCollections()
  }
  class "SiteService" as CatalogSiteService <<Service>> {
    -siteRepository: SiteRepositoryInterface
    +__construct(siteRepository)
    +getHomeData()
    +getSettings()
    +getSliders()
  }
  class "WishlistService" as CatalogWishlistService <<Service>> {
    -wishlistRepository: WishlistRepositoryInterface
    +__construct(wishlistRepository)
    +toggle(userId, productId)
    +hasProduct(userId, productId)
    +addProduct(userId, productId)
    +removeProduct(userId, productId)
    +getUserWishlist(userId)
  }
  class "InventoryAlertService" as CatalogInventoryAlertService <<Service>> {
    -alertModel: InventoryAlert
    +syncVariantState(variantId, availableStock)
    +reconcileCurrentInventory()
    +dispatchReminderEmails()
  }
  class "AuthProfileService" as CatalogAuthProfileService <<Service>> {
    +getProfilesByIds(userIds)
  }
  class "StockRealtimePublisher" as CatalogStockRealtimePublisher <<Service>> {
    -redis: Redis
    +publish(event, payload)
  }
}

package "Repositorios" {
  interface "ProductRepositoryInterface" as CatalogProductRepositoryInterface <<Interface>> {
    +getFiltered(filters, limit, offset)
    +findBySlug(slug)
    +getVariants(productId)
    +getAdditionalImages(productId)
    +getRelated(productId, categoryId)
    +getReviews(productId)
    +getQuestions(productId)
  }
  interface "CategoryRepositoryInterface" as CatalogCategoryRepositoryInterface <<Interface>> {
    +getAllActive()
    +findById(id)
    +getAllCollections()
  }
  interface "SiteRepositoryInterface" as CatalogSiteRepositoryInterface <<Interface>> {
    +getSettings()
    +getSliders()
    +getTopBarAnnouncement()
    +getPromoBanner()
  }
  interface "WishlistRepositoryInterface" as CatalogWishlistRepositoryInterface <<Interface>> {
    +add(userId, productId)
    +remove(userId, productId)
    +getByUser(userId)
    +exists(userId, productId)
  }
  class "QueryBuilderProductRepository" as CatalogProductRepository <<Repository>> {
    -productsTable: products
    -variantsTable: product_size_variants
  }
  class "QueryBuilderCategoryRepository" as CatalogCategoryRepository <<Repository>> {
    -categoriesTable: categories
    -collectionsTable: collections
  }
  class "QueryBuilderSiteRepository" as CatalogSiteRepository <<Repository>> {
    -settingsTable: site_settings
    -slidersTable: sliders
    -announcementsTable: announcements
  }
  class "QueryBuilderWishlistRepository" as CatalogWishlistRepository <<Repository>> {
    -wishlistTable: wishlist
  }
}

package "Modelos y soporte" {
  class "User" as CatalogUser <<Model>> {
    +id: Integer
    +name: String
    +email: String
    -password: String
    +email_verified_at: DateTime
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "InventoryAlert (inventory_alerts)" as CatalogInventoryAlert <<Model>> {
    +id: Integer
    +variant_id: Integer
    +product_id: Integer
    +product_name: String
    +color_name: String
    +size_label: String
    +sku: String
    +stock: Integer
    +status: String
    +out_of_stock_since: DateTime
    +last_initial_notification_at: DateTime
    +last_reminder_at: DateTime
    +resolved_at: DateTime
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "PopularSearch (popular_searches)" as CatalogPopularSearch <<Model>> {
    +id: Integer
    +search_term: String
    +search_count: Integer
    +last_searched: DateTime
  }
  class "SearchHistory (search_history)" as CatalogSearchHistory <<Model>> {
    +id: Integer
    +user_id: String
    +search_term: String
    +created_at: DateTime
  }
  class "SiteSetting (site_settings)" as CatalogSiteSetting <<Model>> {
    +id: Integer
    +setting_key: String
    +setting_value: Text
    +category: String
    +updated_by: String
    +updated_at: DateTime
  }
  class "Slider (sliders)" as CatalogSlider <<Model>> {
    +id: Integer
    +title: String
    +subtitle: String
    +image: String
    +link: String
    +order_position: Integer
    +is_active: Boolean
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "SiteSettingsCatalog" as CatalogSiteSettingsCatalog <<Support>> {
    +definitions()
  }
  class "NotFoundException" as CatalogNotFoundException <<Exception>> {
    +message: String
    +__construct(message)
  }
}

package "Tablas Query Builder" {
  class "products" as CatalogProductsTable <<Tabla>> {
    +id: Integer
    +name: String
    +slug: String
    +description: Text
    +brand: String
    +gender: String
    +collection: String
    +material: String
    +care_instructions: Text
    +compare_price: Decimal
    +price: Decimal
    +category_id: Integer
    +collection_id: Integer
    +is_featured: Boolean
    +is_active: Boolean
    +is_refundable: Boolean
    +refund_days: Integer
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "categories" as CatalogCategoriesTable <<Tabla>> {
    +id: Integer
    +name: String
    +slug: String
    +description: Text
    +image: String
    +parent_id: Integer
    +is_active: Boolean
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "collections" as CatalogCollectionsTable <<Tabla>> {
    +id: Integer
    +name: String
    +slug: String
    +description: Text
    +image: String
    +launch_date: Date
    +is_active: Boolean
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "product_collections" as CatalogProductCollectionsTable <<Tabla>> {
    +id: Integer
    +product_id: Integer
    +collection_id: Integer
    +display_order: Integer
    +created_at: DateTime
  }
  class "colors" as CatalogColorsTable <<Tabla>> {
    +id: Integer
    +name: String
    +hex_code: String
    +is_active: Boolean
    +created_at: DateTime
  }
  class "sizes" as CatalogSizesTable <<Tabla>> {
    +id: Integer
    +name: String
    +description: String
    +is_active: Boolean
    +created_at: DateTime
  }
  class "product_color_variants" as CatalogColorVariantsTable <<Tabla>> {
    +id: Integer
    +product_id: Integer
    +color_id: Integer
    +is_default: Boolean
  }
  class "product_size_variants" as CatalogSizeVariantsTable <<Tabla>> {
    +id: Integer
    +color_variant_id: Integer
    +size_id: Integer
    +sku: String
    +barcode: String
    +price: Decimal
    +compare_price: Decimal
    +quantity: Integer
    +is_active: Boolean
  }
  class "product_images" as CatalogProductImagesTable <<Tabla>> {
    +id: Integer
    +product_id: Integer
    +color_variant_id: Integer
    +image_path: String
    +alt_text: String
    +order: Integer
    +is_primary: Boolean
    +created_at: DateTime
  }
  class "variant_images" as CatalogVariantImagesTable <<Tabla>> {
    +id: Integer
    +color_variant_id: Integer
    +product_id: Integer
    +image_id: Integer
    +image_path: String
    +alt_text: String
    +order: Integer
    +is_primary: Boolean
    +created_at: DateTime
  }
  class "wishlist" as CatalogWishlistTable <<Tabla>> {
    +id: Integer
    +user_id: String
    +product_id: Integer
    +created_at: DateTime
  }
  class "product_reviews" as CatalogReviewsTable <<Tabla>> {
    +id: Integer
    +product_id: Integer
    +user_id: String
    +order_id: Integer
    +rating: Integer
    +title: String
    +comment: Text
    +images: Text
    +is_verified: Boolean
    +is_approved: Boolean
    +created_at: DateTime
    +updated_at: DateTime
  }
  class "review_votes" as CatalogReviewVotesTable <<Tabla>> {
    +id: Integer
    +review_id: Integer
    +user_id: String
    +is_helpful: Boolean
    +created_at: DateTime
  }
  class "product_questions" as CatalogQuestionsTable <<Tabla>> {
    +id: Integer
    +product_id: Integer
    +user_id: String
    +question: Text
    +created_at: DateTime
  }
  class "question_answers" as CatalogAnswersTable <<Tabla>> {
    +id: Integer
    +question_id: Integer
    +user_id: String
    +answer: Text
    +is_seller: Boolean
    +created_at: DateTime
  }
  class "stock_history" as CatalogStockHistoryTable <<Tabla>> {
    +id: Integer
    +variant_id: Integer
    +user_id: String
    +previous_qty: Integer
    +new_qty: Integer
    +operation: String
    +notes: Text
    +created_at: DateTime
  }
  class "announcements" as CatalogAnnouncementsTable <<Tabla>> {
    +id: Integer
    +type: String
    +title: String
    +message: Text
    +subtitle: String
    +button_text: String
    +button_link: String
    +image: String
    +background_color: String
    +text_color: String
    +icon: String
    +priority: Integer
    +is_active: Boolean
    +start_date: DateTime
    +end_date: DateTime
    +created_at: DateTime
    +updated_at: DateTime
  }
}

package "Soporte Laravel" {
  class "EnsureAdmin" as CatalogEnsureAdmin <<Middleware>> {
    +handle(request, next)
  }
  class "AppServiceProvider" as CatalogAppServiceProvider <<Provider>> {
    +register()
    +boot()
  }
  class "RepositoryServiceProvider" as CatalogRepositoryServiceProvider <<Provider>> {
    +register()
    +boot()
  }
}

package "Servicios externos" {
  class "auth-service" as CatalogAuthExternal <<External>>
  class "notification-service" as CatalogNotificationExternal <<External>>
  class "Redis stock websocket" as CatalogRedisExternal <<External>>
  class "/uploads/catalog" as CatalogUploads <<Storage>>
}

CatalogProductController --> CatalogService
CatalogCategoryController --> CatalogService
CatalogSiteController --> CatalogSiteService
CatalogWishlistController --> CatalogWishlistService
CatalogSearchController --> CatalogSearchHistory
CatalogSearchController --> CatalogPopularSearch
CatalogInternalController --> CatalogProductsTable
CatalogInternalController --> CatalogSizeVariantsTable
CatalogInternalController --> CatalogStockHistoryTable
CatalogInternalController --> CatalogStockRealtimePublisher
CatalogAdminController --> CatalogSiteSettingsCatalog
CatalogAdminController --> CatalogInventoryAlertService
CatalogAdminController --> CatalogStockRealtimePublisher
CatalogAdminController --> CatalogProductsTable
CatalogAdminController --> CatalogCategoriesTable
CatalogAdminController --> CatalogCollectionsTable
CatalogAdminController --> CatalogColorsTable
CatalogAdminController --> CatalogSizesTable
CatalogAdminController --> CatalogReviewsTable
CatalogAdminController --> CatalogQuestionsTable
CatalogAdminController --> CatalogStockHistoryTable
CatalogAdminController --> CatalogProductImagesTable
CatalogAdminController --> CatalogVariantImagesTable
CatalogAdminController --> CatalogUploads

CatalogService --> CatalogProductRepositoryInterface
CatalogService --> CatalogCategoryRepositoryInterface
CatalogService --> CatalogAuthProfileService
CatalogSiteService --> CatalogSiteRepositoryInterface
CatalogWishlistService --> CatalogWishlistRepositoryInterface
CatalogInventoryAlertService --> CatalogInventoryAlert
CatalogInventoryAlertService --> CatalogSizeVariantsTable
CatalogInventoryAlertService ..> CatalogNotificationExternal : correos de reposición
CatalogAuthProfileService ..> CatalogAuthExternal : perfiles internos
CatalogStockRealtimePublisher ..> CatalogRedisExternal : eventos de stock

CatalogProductRepositoryInterface <|.. CatalogProductRepository
CatalogCategoryRepositoryInterface <|.. CatalogCategoryRepository
CatalogSiteRepositoryInterface <|.. CatalogSiteRepository
CatalogWishlistRepositoryInterface <|.. CatalogWishlistRepository
CatalogProductRepository --> CatalogProductsTable
CatalogProductRepository --> CatalogColorVariantsTable
CatalogProductRepository --> CatalogSizeVariantsTable
CatalogProductRepository --> CatalogProductImagesTable
CatalogProductRepository --> CatalogVariantImagesTable
CatalogProductRepository --> CatalogReviewsTable
CatalogProductRepository --> CatalogQuestionsTable
CatalogProductRepository --> CatalogAnswersTable
CatalogCategoryRepository --> CatalogCategoriesTable
CatalogCategoryRepository --> CatalogCollectionsTable
CatalogSiteRepository --> CatalogSiteSetting
CatalogSiteRepository --> CatalogSlider
CatalogSiteRepository --> CatalogAnnouncementsTable
CatalogWishlistRepository --> CatalogWishlistTable
CatalogWishlistRepository --> CatalogProductsTable
CatalogRepositoryServiceProvider --> CatalogProductRepositoryInterface : binding
CatalogRepositoryServiceProvider --> CatalogCategoryRepositoryInterface : binding
CatalogRepositoryServiceProvider --> CatalogSiteRepositoryInterface : binding
CatalogRepositoryServiceProvider --> CatalogWishlistRepositoryInterface : binding
CatalogEnsureAdmin ..> CatalogAuthExternal : valida token admin

CatalogCategoriesTable "0..1" o-- "0..*" CatalogCategoriesTable : subcategorías
CatalogCategoriesTable "1" o-- "0..*" CatalogProductsTable : productos
CatalogCollectionsTable "1" o-- "0..*" CatalogProductCollectionsTable : vínculo
CatalogProductsTable "1" o-- "0..*" CatalogProductCollectionsTable : vínculo
CatalogProductsTable "1" *-- "0..*" CatalogColorVariantsTable : colores
CatalogColorsTable "1" o-- "0..*" CatalogColorVariantsTable : color
CatalogColorVariantsTable "1" *-- "0..*" CatalogSizeVariantsTable : tallas
CatalogSizesTable "1" o-- "0..*" CatalogSizeVariantsTable : talla
CatalogProductsTable "1" *-- "0..*" CatalogProductImagesTable : imágenes
CatalogColorVariantsTable "1" *-- "0..*" CatalogVariantImagesTable : imágenes variante
CatalogProductImagesTable "1" o-- "0..*" CatalogVariantImagesTable : origen
CatalogUser "1" o-- "0..*" CatalogWishlistTable : favoritos
CatalogProductsTable "1" o-- "0..*" CatalogWishlistTable : favorito
CatalogProductsTable "1" o-- "0..*" CatalogReviewsTable : reseñas
CatalogReviewsTable "1" *-- "0..*" CatalogReviewVotesTable : votos
CatalogUser "1" o-- "0..*" CatalogReviewsTable : escribe
CatalogUser "1" o-- "0..*" CatalogReviewVotesTable : vota
CatalogProductsTable "1" o-- "0..*" CatalogQuestionsTable : preguntas
CatalogQuestionsTable "1" *-- "0..*" CatalogAnswersTable : respuestas
CatalogUser "1" o-- "0..*" CatalogQuestionsTable : pregunta
CatalogUser "1" o-- "0..*" CatalogAnswersTable : responde
CatalogSizeVariantsTable "1" o-- "0..*" CatalogStockHistoryTable : movimientos
CatalogSizeVariantsTable "1" o-- "0..1" CatalogInventoryAlert : alerta
CatalogUser "1" o-- "0..*" CatalogSearchHistory : búsquedas
CatalogSlider "0..*" o-- "0..1" CatalogUploads : imagen
CatalogAnnouncementsTable "0..*" o-- "0..1" CatalogUploads : imagen
@enduml
```

## Fuentes revisadas

- `services/catalog-service/app/**/*.php`
- `services/catalog-service/routes/api.php`
- `services/catalog-service/database/migrations/*.php`

## Documentos relacionados

- [Índice de diagramas](../diagramas-clases-microservicios-plantuml.md)
- [Documentación por microservicio](../../microservicios/README.md)
