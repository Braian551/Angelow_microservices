import React from 'react';
import './BaseButton.css';

const BaseButton = ({
    children,
    type = 'button',
    variant = 'primary',
    size = 'md',
    block = false,
    loading = false,
    disabled = false,
    onClick,
    ...rest
}) => {
    const baseClass = 'btn';
    const classes = [
        baseClass,
        `${baseClass}--${variant}`,
        `${baseClass}--${size}`,
        block ? `${baseClass}--block` : '',
        loading ? `${baseClass}--loading` : '',
    ].filter(Boolean).join(' ');

    return (
        <button
            type={type}
            className={classes}
            disabled={disabled || loading}
            onClick={onClick}
            {...rest}
        >
            {loading && <span className="btn-spinner"></span>}
            <span className={loading ? 'btn-text-hidden' : ''}>{children}</span>
        </button>
    );
};

export default BaseButton;
