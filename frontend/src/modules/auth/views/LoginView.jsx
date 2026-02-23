import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import BaseInput from '../../../components/common/BaseInput';
import BaseButton from '../../../components/common/BaseButton';
import SocialLoginButton from '../../../components/common/SocialLoginButton';
import AlertMessage from '../../../components/common/AlertMessage';
import { authService } from '../services/authService';
import './LoginView.css';

const LoginView = () => {
    const navigate = useNavigate();

    const [isSubmitting, setIsSubmitting] = useState(false);
    const [alert, setAlert] = useState({ message: '', type: 'error' });

    const [form, setForm] = useState({
        credential: '',
        password: '',
        remember: false,
    });

    const [errors, setErrors] = useState({
        credential: '',
        password: '',
    });

    const validateField = (field) => {
        let errorMsg = '';
        if (field === 'credential' && !form.credential) errorMsg = 'El correo o teléfono es obligatorio';
        if (field === 'password' && !form.password) errorMsg = 'La contraseña es obligatoria';
        setErrors((prev) => ({ ...prev, [field]: errorMsg }));
        return !errorMsg;
    };

    const handleInputChange = (field, value) => {
        setForm((prev) => ({ ...prev, [field]: value }));
        if (errors[field]) setErrors((prev) => ({ ...prev, [field]: '' }));
    };

    const handleLogin = async (e) => {
        e.preventDefault();
        const isCredValid = validateField('credential');
        const isPassValid = validateField('password');
        if (!isCredValid || !isPassValid) return;

        setIsSubmitting(true);
        setAlert({ message: '', type: 'error' });

        try {
            const result = await authService.login({
                credential: form.credential.trim(),
                password: form.password,
                remember: form.remember,
            });

            if (result.success || result.token || result.data?.token) {
                const token = result.token || result.data?.token;
                if (token) localStorage.setItem('auth_token', token);
                setAlert({ type: 'success', message: result.message || '¡Bienvenido/a!' });
                setTimeout(() => navigate('/'), 1500);
            }
        } catch (error) {
            setAlert({ type: 'error', message: error.response?.data?.message || 'Error al iniciar sesión' });
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="login-page">


            <div className="login-card">
                <div className="login-header">
                    <img src="/logo_principal.png" alt="Angelow" className="main-logo" />
                    <h1>Iniciar sesión</h1>
                    <p className="login-subtitle">Bienvenido/a de vuelta a Angelow</p>
                </div>

                <AlertMessage
                    message={alert.message}
                    type={alert.type}
                    onDismiss={() => setAlert({ message: '', type: 'error' })}
                />

                <form onSubmit={handleLogin} noValidate>
                    <BaseInput
                        id="credential"
                        label="Correo o teléfono"
                        value={form.credential}
                        onChange={(v) => handleInputChange('credential', v)}
                        onBlur={() => validateField('credential')}
                        placeholder="nombre@ejemplo.com"
                        error={errors.credential}
                        required
                    />

                    <BaseInput
                        id="password"
                        type="password"
                        label="Contraseña"
                        value={form.password}
                        onChange={(v) => handleInputChange('password', v)}
                        onBlur={() => validateField('password')}
                        placeholder="Tu contraseña"
                        error={errors.password}
                        required
                    />

                    <div className="form-extras">
                        <label className="remember-checkbox">
                            <input
                                type="checkbox"
                                checked={form.remember}
                                onChange={(e) => handleInputChange('remember', e.target.checked)}
                            />
                            <span>Recordar mi cuenta</span>
                        </label>
                        <a href="#" className="forgot-link">¿Olvidaste tu contraseña?</a>
                    </div>

                    <BaseButton
                        type="submit"
                        variant="primary"
                        block
                        loading={isSubmitting}
                        disabled={!form.credential || !form.password}
                    >
                        Ingresar
                    </BaseButton>
                </form>

                <div className="divider" />

                <SocialLoginButton disabled />

                <p className="auth-link">
                    ¿No tienes cuenta? <Link to="/register" className="auth-link__action">Regístrate gratis</Link>
                </p>
            </div>
        </div>
    );
};

export default LoginView;
