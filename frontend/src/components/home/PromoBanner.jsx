import React from 'react';
import './PromoBanner.css';

const MONOLITH_BASE = import.meta.env.VITE_MONOLITH_URL || 'http://localhost/angelow';

const PromoBanner = ({ banner }) => {
    if (!banner) return null;

    const hasImage = !!banner.image;
    const bgStyle = hasImage
        ? { backgroundImage: `url('${MONOLITH_BASE}/${banner.image}')` }
        : {};

    return (
        <section className={`promo-banner ${hasImage ? 'has-image' : ''}`} style={bgStyle}>
            {hasImage && <div className="promo-image-overlay" />}
            <div className="promo-content">
                {banner.icon && <i className={`fas ${banner.icon} fa-3x`} />}
                <h2>{banner.title}</h2>
                {banner.subtitle && <p>{banner.subtitle}</p>}
                {banner.button_text && banner.button_link && (
                    <a href={banner.button_link} className="btn">{banner.button_text}</a>
                )}
            </div>
        </section>
    );
};

export default PromoBanner;
