import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import Header from '../../../components/layout/Header';
import Footer from '../../../components/layout/Footer';
import AnnouncementBar from '../../../components/home/AnnouncementBar';
import HeroSlider from '../../../components/home/HeroSlider';
import CategoriesGrid from '../../../components/home/CategoriesGrid';
import ProductCard from '../../../components/product/ProductCard';
import PromoBanner from '../../../components/home/PromoBanner';
import CollectionsGrid from '../../../components/home/CollectionsGrid';
import { getHomeData, getProducts, getCategories, getCollections } from '../../../services/catalogApi';
import './HomeView.css';

const HomeView = () => {
    const [siteData, setSiteData] = useState(null);
    const [products, setProducts] = useState([]);
    const [categories, setCategories] = useState([]);
    const [collections, setCollectionsList] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const [homeRes, productsRes, categoriesRes, collectionsRes] = await Promise.all([
                    getHomeData().catch(() => null),
                    getProducts({ per_page: 6 }).catch(() => null),
                    getCategories().catch(() => null),
                    getCollections().catch(() => null),
                ]);

                if (homeRes?.data) setSiteData(homeRes.data);
                setProducts(productsRes?.data?.products || []);
                setCategories(categoriesRes?.data || []);
                setCollectionsList(collectionsRes?.data || []);
            } catch (err) {
                console.error('Error loading home data:', err);
            } finally {
                setLoading(false);
            }
        };

        fetchData();
    }, []);

    return (
        <>
            {/* Top bar announcement */}
            <AnnouncementBar announcement={siteData?.top_bar} />

            <Header settings={siteData?.settings} />

            {/* Hero Banner with real sliders */}
            <HeroSlider slides={siteData?.sliders || []} />

            {/* Categories */}
            <CategoriesGrid categories={categories.slice(0, 4)} />

            {/* Featured Products */}
            <section className="featured-products">
                <div className="section-header">
                    <h2 className="section-title">Productos destacados</h2>
                    <Link to="/tienda" className="view-all">Ver todos</Link>
                </div>
                <div className="products-grid">
                    {loading ? (
                        Array.from({ length: 6 }).map((_, i) => (
                            <div key={i} className="product-card shimmer">
                                <div className="shimmer-image" />
                                <div className="shimmer-info">
                                    <div className="shimmer-category" />
                                    <div className="shimmer-title" />
                                    <div className="shimmer-title" />
                                    <div className="shimmer-rating" />
                                    <div className="shimmer-price" />
                                    <div className="shimmer-button" />
                                </div>
                            </div>
                        ))
                    ) : products.length > 0 ? (
                        products.map((product) => (
                            <ProductCard key={product.id} product={product} />
                        ))
                    ) : (
                        <p className="no-products">No hay productos disponibles.</p>
                    )}
                </div>
            </section>

            {/* Promo Banner */}
            <PromoBanner banner={siteData?.promo_banner} />

            {/* Collections */}
            <CollectionsGrid collections={collections.slice(0, 3)} />

            <Footer />
        </>
    );
};

export default HomeView;
