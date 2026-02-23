import React from 'react';
import './Footer.css';

const Footer = () => {
    return (
        <footer className="main-footer">
            <div className="footer-container">
                <div className="footer-column">
                    <h3>Tienda</h3>
                    <ul>
                        <li><a href="/tienda?gender=niña">Niñas</a></li>
                        <li><a href="/tienda?gender=niño">Niños</a></li>
                        <li><a href="/tienda?gender=bebe">Bebés</a></li>
                        <li><a href="/tienda">Novedades</a></li>
                        <li><a href="/tienda?offers=1">Ofertas</a></li>
                    </ul>
                </div>

                <div className="footer-column">
                    <h3>Información</h3>
                    <ul>
                        <li><a href="#">Sobre nosotros</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Contacto</a></li>
                        <li><a href="#">Preguntas frecuentes</a></li>
                        <li><a href="#">Sostenibilidad</a></li>
                    </ul>
                </div>

                <div className="footer-column">
                    <h3>Ayuda</h3>
                    <ul>
                        <li><a href="#">Guía de tallas</a></li>
                        <li><a href="#">Envíos y entregas</a></li>
                        <li><a href="#">Devoluciones</a></li>
                        <li><a href="#">Términos y condiciones</a></li>
                        <li><a href="#">Política de privacidad</a></li>
                    </ul>
                </div>

                <div className="footer-column">
                    <h3>Contacto</h3>
                    <address>
                        <p><i className="fas fa-map-marker-alt"></i> Medellín, Colombia</p>
                        <p><i className="fas fa-phone"></i> +57 300 000 0000</p>
                        <p><i className="fas fa-envelope"></i> soporte@angelow.com</p>
                    </address>
                    <div className="social-links">
                        <a href="#" aria-label="Facebook"><i className="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i className="fab fa-instagram"></i></a>
                        <a href="#" aria-label="TikTok"><i className="fab fa-tiktok"></i></a>
                        <a href="#" aria-label="WhatsApp"><i className="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>

            <div className="footer-bottom">
                <p className="copyright">
                    &copy; {new Date().getFullYear()} Angelow. Todos los derechos reservados.
                </p>
            </div>
        </footer>
    );
};

export default Footer;
