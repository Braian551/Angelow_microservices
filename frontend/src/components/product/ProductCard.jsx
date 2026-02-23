import React from 'react';
import { Link } from 'react-router-dom';
import './ProductCard.css';

const MONOLITH_BASE = import.meta.env.VITE_MONOLITH_URL || 'http://localhost/angelow';

/**
 * Reusable ProductCard component.
 * Matches the original monolith product-card layout.
 */
const ProductCard = ({ product, onWishlistToggle }) => {
    const avgRating = parseFloat(product.avg_rating) || 0;
    const reviewCount = parseInt(product.review_count) || 0;
    const hasDiscount = product.compare_price && product.price < product.compare_price;
    const discountPercentage = hasDiscount
        ? Math.round((1 - product.price / product.compare_price) * 100)
        : 0;

    const formatPrice = (price) =>
        new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 })
            .format(price);

    const renderStars = () => {
        const stars = [];
        const full = Math.floor(avgRating);
        const half = avgRating - full >= 0.5;
        const empty = 5 - full - (half ? 1 : 0);

        for (let i = 0; i < full; i++) stars.push(<i key={`f${i}`} className="fas fa-star" />);
        if (half) stars.push(<i key="h" className="fas fa-star-half-alt" />);
        for (let i = 0; i < empty; i++) stars.push(<i key={`e${i}`} className="far fa-star" />);
        return stars;
    };

    const imageUrl = product.primary_image || product.main_image;

    return (
        <div className="product-card" data-product-id={product.id}>
            {product.is_featured ? <div className="product-badge">Destacado</div> : null}

            {/* Wishlist button */}
            <button
                className="wishlist-btn"
                aria-label="Añadir a favoritos"
                onClick={() => onWishlistToggle?.(product.id)}
            >
                <i className={product.is_favorite ? 'fas fa-heart' : 'far fa-heart'} />
            </button>

            {/* Product image */}
            <Link
                to={`/producto/${product.slug}`}
                className="product-image"
            >
                <img
                    src={imageUrl ? `${MONOLITH_BASE}/${imageUrl}` : '/images/default-product.jpg'}
                    alt={product.name}
                    onError={(e) => { e.target.src = '/images/default-product.jpg'; }}
                />
                {hasDiscount && (
                    <div className="product-badge sale">{discountPercentage}% OFF</div>
                )}
            </Link>

            {/* Product info */}
            <div className="product-info">
                <span className="product-category">
                    {product.category_name || 'Sin categoría'}
                </span>
                <h3 className="product-title">
                    <Link to={`/producto/${product.slug}`}>{product.name}</Link>
                </h3>

                {/* Rating */}
                <div className="product-rating">
                    <div className="stars">{renderStars()}</div>
                    <span className="rating-count">({reviewCount})</span>
                </div>

                {/* Price */}
                <div className="product-price">
                    <span className="current-price">{formatPrice(product.price)}</span>
                    {hasDiscount && (
                        <span className="original-price">{formatPrice(product.compare_price)}</span>
                    )}
                </div>

                {/* View product button */}
                <Link to={`/producto/${product.slug}`} className="view-product-btn">
                    <i className="fas fa-eye" /> Ver producto
                </Link>
            </div>
        </div>
    );
};

export default ProductCard;
