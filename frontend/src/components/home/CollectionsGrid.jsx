import React from 'react';
import { Link } from 'react-router-dom';
import './CollectionsGrid.css';

const MONOLITH_BASE = import.meta.env.VITE_MONOLITH_URL || 'http://localhost/angelow';

const CollectionsGrid = ({ collections = [] }) => {
    return (
        <section className="featured-collections">
            <h2 className="section-title">Nuestras colecciones</h2>
            <div className="collections-grid">
                {collections.length > 0 ? (
                    collections.map((col) => (
                        <Link
                            key={col.id}
                            to={`/tienda?collection=${col.id}`}
                            className="collection-card"
                        >
                            <img
                                src={col.image ? `${MONOLITH_BASE}/${col.image}` : '/images/default-collection.jpg'}
                                alt={col.name}
                            />
                            <div className="collection-overlay">
                                <h3>{col.name}</h3>
                                {col.description && <p>{col.description}</p>}
                            </div>
                        </Link>
                    ))
                ) : (
                    <Link to="/tienda" className="collection-card">
                        <img src="/images/default-collection.jpg" alt="Colección" />
                        <div className="collection-overlay">
                            <h3>Colección destacada</h3>
                        </div>
                    </Link>
                )}
            </div>
        </section>
    );
};

export default CollectionsGrid;
