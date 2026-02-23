import React from 'react';
import { Link } from 'react-router-dom';
import './Header.css';

const MONOLITH_BASE = import.meta.env.VITE_MONOLITH_URL || 'http://localhost/angelow';

const Header = ({ settings }) => {
    const logoUrl = settings?.brand_logo
        ? `${MONOLITH_BASE}/${settings.brand_logo}`
        : '/images/logo.png';
    const storeName = settings?.store_name || 'Angelow';

    return (
        <header className="main-header">
            <div className="header-container">
                {/* Logo */}
                <div className="content-logo2">
                    <Link to="/">
                        <img src={logoUrl} alt={storeName} width="50" />
                    </Link>
                </div>

                {/* Search Bar */}
                <div className="search-bar">
                    <form className="search-form" onSubmit={(e) => { e.preventDefault(); }}>
                        <input
                            type="text"
                            placeholder="Buscar productos..."
                            autoComplete="off"
                        />
                        <button type="submit" aria-label="Buscar">
                            <i className="fas fa-search"></i>
                        </button>
                    </form>
                </div>

                {/* Header Icons */}
                <div className="header-icons">
                    <Link to="/login" aria-label="Mi cuenta">
                        <i className="fas fa-user"></i>
                    </Link>
                    <Link to="/wishlist" aria-label="Favoritos">
                        <i className="fas fa-heart"></i>
                    </Link>
                    <Link to="/cart" aria-label="Carrito" className="cart-link">
                        <i className="fas fa-shopping-cart"></i>
                    </Link>
                </div>
            </div>

            {/* Main Navigation */}
            <nav className="main-nav">
                <ul>
                    <li><Link to="/">Inicio</Link></li>
                    <li><Link to="/tienda?gender=niña">Niñas</Link></li>
                    <li><Link to="/tienda?gender=niño">Niños</Link></li>
                    <li><Link to="/tienda?gender=bebe">Bebés</Link></li>
                    <li><Link to="/tienda?offers=1">Ofertas</Link></li>
                    <li><Link to="/colecciones">Colecciones</Link></li>
                </ul>
            </nav>
        </header>
    );
};

export default Header;
