import React, { useState, useEffect, useCallback } from 'react';
import './HeroSlider.css';

const MONOLITH_BASE = import.meta.env.VITE_MONOLITH_URL || 'http://localhost/angelow';

const defaultSlides = [
    {
        title: 'Bienvenido a Angelow',
        subtitle: 'Moda infantil de calidad',
        image: '/images/default-slider.jpg',
        link: '/tienda',
    },
];

const HeroSlider = ({ slides = [] }) => {
    const data = slides.length > 0 ? slides : defaultSlides;
    const [current, setCurrent] = useState(0);

    const resolveImage = (img) => {
        if (!img) return '/images/default-slider.jpg';
        if (img.startsWith('http')) return img;
        return `${MONOLITH_BASE}/${img}`;
    };

    const next = useCallback(() => {
        setCurrent((prev) => (prev + 1) % data.length);
    }, [data.length]);

    const prev = useCallback(() => {
        setCurrent((prev) => (prev - 1 + data.length) % data.length);
    }, [data.length]);

    useEffect(() => {
        if (data.length <= 1) return;
        const interval = setInterval(next, 5000);
        return () => clearInterval(interval);
    }, [next, data.length]);

    return (
        <section className="hero-banner">
            <div className="hero-slider">
                {data.map((slide, i) => (
                    <div key={i} className={`hero-slide ${i === current ? 'active' : ''}`}>
                        <img
                            src={resolveImage(slide.image)}
                            alt={slide.title}
                        />
                        <div className="hero-content">
                            <h1>{slide.title}</h1>
                            <p>{slide.subtitle}</p>
                            {slide.link && (
                                <a href={slide.link} className="btn">Ver más</a>
                            )}
                        </div>
                    </div>
                ))}
            </div>

            {data.length > 1 && (
                <>
                    <div className="hero-dots">
                        {data.map((_, i) => (
                            <span
                                key={i}
                                className={`dot ${i === current ? 'active' : ''}`}
                                onClick={() => setCurrent(i)}
                            />
                        ))}
                    </div>
                    <button className="hero-prev" onClick={prev} aria-label="Anterior">❮</button>
                    <button className="hero-next" onClick={next} aria-label="Siguiente">❯</button>
                </>
            )}
        </section>
    );
};

export default HeroSlider;
