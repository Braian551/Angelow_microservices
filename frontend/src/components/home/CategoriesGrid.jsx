import React from 'react';
import { Link } from 'react-router-dom';
import './CategoriesGrid.css';

const MONOLITH_BASE = import.meta.env.VITE_MONOLITH_URL || 'http://localhost/angelow';

const CategoriesGrid = ({ categories = [] }) => {
    return (
        <section className="featured-categories">
            <h2 className="section-title">Explora nuestras categorías</h2>
            <div className="categories-grid">
                {categories.length > 0 ? (
                    categories.map((cat) => (
                        <Link
                            key={cat.id}
                            to={`/tienda?category=${cat.id}`}
                            className="category-card"
                        >
                            <img
                                src={cat.image ? `${MONOLITH_BASE}/${cat.image}` : '/images/default-category.jpg'}
                                alt={cat.name}
                            />
                            <h3>{cat.name}</h3>
                        </Link>
                    ))
                ) : (
                    <>
                        <Link to="/tienda" className="category-card">
                            <img src="/images/default-product.jpg" alt="Vestidos para niñas" />
                            <h3>Vestidos</h3>
                        </Link>
                        <Link to="/tienda" className="category-card">
                            <img src="/images/default-product.jpg" alt="Conjuntos para niños" />
                            <h3>Conjuntos</h3>
                        </Link>
                    </>
                )}
            </div>
        </section>
    );
};

export default CategoriesGrid;
