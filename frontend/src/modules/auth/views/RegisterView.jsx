import React, { useState, useMemo } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import BaseInput from '../../../components/common/BaseInput';
import BaseButton from '../../../components/common/BaseButton';
import ProgressSteps from '../../../components/common/ProgressSteps';
import SocialLoginButton from '../../../components/common/SocialLoginButton';
import AlertMessage from '../../../components/common/AlertMessage';
import { authService } from '../services/authService';
import './RegisterView.css';

const STEPS = ['Nombre', 'Correo', 'Teléfono', 'Contraseña'];

const RegisterView = () => {
    const navigate = useNavigate();

    const [currentStep, setCurrentStep] = useState(0);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [alert, setAlert] = useState({ message: '', type: 'error' });

    const [form, setForm] = useState({
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
        terms: false,
    });

    const [errors, setErrors] = useState({
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
        terms: '',
    });

    const validateField = (field, value) => {
        let errorMsg = '';
        const val = value !== undefined ? value : form[field];

        switch (field) {
            case 'name':
                if (!val.trim()) errorMsg = 'El nombre es obligatorio';
                else if (val.trim().length < 2) errorMsg = 'Mínimo 2 caracteres';
                break;
            case 'email':
                if (!val.trim()) {
                    errorMsg = 'El correo es obligatorio';
                } else {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(val)) errorMsg = 'Correo no válido';
                }
                break;
            case 'phone':
                if (val && !/^[0-9]{10,15}$/.test(val)) {
                    errorMsg = 'Teléfono debe tener entre 10 y 15 dígitos';
                }
                break;
            case 'password':
                if (!val) errorMsg = 'La contraseña es obligatoria';
                else if (val.length < 6) errorMsg = 'Mínimo 6 caracteres';
                else if (val.length > 20) errorMsg = 'Máximo 20 caracteres';
                break;
            case 'password_confirmation':
                if (!val) errorMsg = 'Confirma tu contraseña';
                else if (form.password !== val) errorMsg = 'Las contraseñas no coinciden';
                break;
            default:
                break;
        }

        setErrors((prev) => ({ ...prev, [field]: errorMsg }));
        return !errorMsg;
    };

    const validateCurrentStep = () => {
        const stepFields = [['name'], ['email'], ['phone'], ['password', 'password_confirmation']];
        let isValid = true;
        stepFields[currentStep].forEach((field) => {
            if (!validateField(field)) isValid = false;
        });
        return isValid;
    };

    const handleInputChange = (field, value) => {
        setForm((prev) => ({ ...prev, [field]: value }));
        if (errors[field]) setErrors((prev) => ({ ...prev, [field]: '' }));
    };

    const nextStep = () => {
        if (!validateCurrentStep()) return;
        if (currentStep < 3) setCurrentStep((prev) => prev + 1);
    };

    const prevStep = () => {
        if (currentStep > 0) setCurrentStep((prev) => prev - 1);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!validateCurrentStep()) return;

        if (!form.terms) {
            setErrors((prev) => ({ ...prev, terms: 'Debes aceptar los términos y condiciones' }));
            return;
        }

        setIsSubmitting(true);
        setAlert({ message: '', type: 'error' });

        try {
            const result = await authService.register({
                name: form.name.trim(),
                email: form.email.trim(),
                phone: form.phone || null,
                password: form.password,
                password_confirmation: form.password_confirmation,
                terms: form.terms,
            });

            if (result.success || result.token || result.data?.token) {
                const token = result.token || result.data?.token;
                if (token) localStorage.setItem('auth_token', token);
                setAlert({ type: 'success', message: result.message || '¡Registro exitoso!' });
                setTimeout(() => navigate('/login'), 2000);
            }
        } catch (error) {
            const response = error.response?.data;
            if (response?.errors) {
                setErrors((prev) => {
                    const newErrors = { ...prev };
                    Object.keys(response.errors).forEach((key) => {
                        if (newErrors[key] !== undefined) newErrors[key] = response.errors[key][0];
                    });
                    return newErrors;
                });
                setAlert({ type: 'error', message: 'Por favor corrige los errores del formulario' });
            } else {
                setAlert({ type: 'error', message: response?.message || 'Error al registrar. Intenta de nuevo.' });
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    const passwordStrength = useMemo(() => {
        const pwd = form.password;
        if (!pwd) return { percent: 0, label: '', className: '' };
        let score = 0;
        if (pwd.length >= 6) score++;
        if (pwd.length >= 10) score++;
        if (/[A-Z]/.test(pwd)) score++;
        if (/[0-9]/.test(pwd)) score++;
        if (/[^A-Za-z0-9]/.test(pwd)) score++;
        const levels = [
            { percent: 20, label: 'Muy débil', className: 'strength--weak' },
            { percent: 40, label: 'Débil', className: 'strength--weak' },
            { percent: 60, label: 'Aceptable', className: 'strength--medium' },
            { percent: 80, label: 'Fuerte', className: 'strength--strong' },
            { percent: 100, label: 'Muy fuerte', className: 'strength--strong' },
        ];
        return levels[Math.min(score, levels.length - 1)];
    }, [form.password]);

    const canSubmit = useMemo(() => {
        return form.name.trim() && form.email.trim() && form.password && form.password_confirmation && form.terms && !isSubmitting;
    }, [form, isSubmitting]);

    return (
        <div className="register-page">


            <div className="register-card">
                <div className="register-header">
                    <img src="/logo_principal.png" alt="Angelow" className="main-logo" />
                    <h1>Crea tu cuenta</h1>
                    <p className="register-subtitle">Únete a Angelow y descubre moda infantil única</p>
                </div>

                <AlertMessage
                    message={alert.message}
                    type={alert.type}
                    onDismiss={() => setAlert({ message: '', type: 'error' })}
                />

                <ProgressSteps steps={STEPS} current={currentStep} />

                <form onSubmit={handleSubmit} noValidate>
                    {/* Step 1: Nombre */}
                    {currentStep === 0 && (
                        <div className="step-content">
                            <BaseInput
                                id="name"
                                label="Nombre completo"
                                value={form.name}
                                onChange={(v) => handleInputChange('name', v)}
                                onBlur={() => validateField('name')}
                                placeholder="Ej. Juan Pérez"
                                hint="Así aparecerás en Angelow"
                                error={errors.name}
                                maxLength={100}
                                required
                            />
                            <BaseButton variant="primary" block onClick={nextStep} disabled={!form.name.trim()}>
                                Continuar
                            </BaseButton>
                        </div>
                    )}

                    {/* Step 2: Correo */}
                    {currentStep === 1 && (
                        <div className="step-content">
                            <BaseInput
                                id="email"
                                type="email"
                                label="Correo electrónico"
                                value={form.email}
                                onChange={(v) => handleInputChange('email', v)}
                                onBlur={() => validateField('email')}
                                placeholder="nombre@ejemplo.com"
                                error={errors.email}
                                maxLength={100}
                                required
                            />
                            <div className="step-actions">
                                <BaseButton variant="outline" onClick={prevStep}>Atrás</BaseButton>
                                <BaseButton variant="primary" onClick={nextStep} disabled={!form.email.trim()}>
                                    Continuar
                                </BaseButton>
                            </div>
                        </div>
                    )}

                    {/* Step 3: Teléfono */}
                    {currentStep === 2 && (
                        <div className="step-content">
                            <BaseInput
                                id="phone"
                                type="tel"
                                label="Número de teléfono"
                                value={form.phone}
                                onChange={(v) => handleInputChange('phone', v)}
                                onBlur={() => validateField('phone')}
                                placeholder="3001234567"
                                hint="Opcional: para notificaciones de pedidos"
                                error={errors.phone}
                                maxLength={15}
                            />
                            <div className="step-actions">
                                <BaseButton variant="outline" onClick={prevStep}>Atrás</BaseButton>
                                <BaseButton variant="primary" onClick={nextStep}>Continuar</BaseButton>
                            </div>
                        </div>
                    )}

                    {/* Step 4: Contraseña */}
                    {currentStep === 3 && (
                        <div className="step-content">
                            <BaseInput
                                id="password"
                                type="password"
                                label="Contraseña"
                                value={form.password}
                                onChange={(v) => handleInputChange('password', v)}
                                onBlur={() => validateField('password')}
                                placeholder="Mínimo 6 caracteres"
                                error={errors.password}
                                maxLength={20}
                                required
                            />
                            <BaseInput
                                id="password_confirmation"
                                type="password"
                                label="Confirmar contraseña"
                                value={form.password_confirmation}
                                onChange={(v) => handleInputChange('password_confirmation', v)}
                                onBlur={() => validateField('password_confirmation')}
                                placeholder="Repetir contraseña"
                                error={errors.password_confirmation}
                                maxLength={20}
                                required
                            />

                            <label className="terms-checkbox">
                                <input
                                    type="checkbox"
                                    checked={form.terms}
                                    onChange={(e) => handleInputChange('terms', e.target.checked)}
                                />
                                <span>Acepto los <a href="#" className="terms-link">términos y condiciones</a></span>
                            </label>
                            {errors.terms && <p className="field-error">{errors.terms}</p>}

                            {form.password && (
                                <div className="password-strength">
                                    <div className="strength-bar">
                                        <div
                                            className={`strength-fill ${passwordStrength.className}`}
                                            style={{ width: `${passwordStrength.percent}%` }}
                                        ></div>
                                    </div>
                                    <span className={`strength-label ${passwordStrength.className}`}>
                                        {passwordStrength.label}
                                    </span>
                                </div>
                            )}

                            <div className="step-actions">
                                <BaseButton variant="outline" onClick={prevStep}>Atrás</BaseButton>
                                <BaseButton type="submit" variant="primary" loading={isSubmitting} disabled={!canSubmit}>
                                    Registrarse
                                </BaseButton>
                            </div>
                        </div>
                    )}
                </form>

                <div className="divider" />

                <SocialLoginButton disabled />

                <p className="auth-link">
                    ¿Ya tienes una cuenta? <Link to="/login" className="auth-link__action">Inicia sesión</Link>
                </p>
            </div>
        </div>
    );
};

export default RegisterView;
