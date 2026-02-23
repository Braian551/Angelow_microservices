import React from 'react';
import './BaseInput.css';

const BaseInput = ({
    id,
    type = 'text',
    value,
    onChange,
    onBlur,
    error = '',
    label,
    hint,
    placeholder,
    required = false,
    maxLength,
    ...rest
}) => {
    return (
        <div className={`form-group ${error ? 'has-error' : ''}`}>
            {label && (
                <label htmlFor={id} className="form-label">
                    {label}
                </label>
            )}
            <input
                id={id}
                type={type}
                className="form-input"
                value={value}
                onChange={(e) => onChange(e.target.value)}
                onBlur={onBlur}
                placeholder={placeholder}
                required={required}
                maxLength={maxLength}
                {...rest}
            />
            {hint && !error && <span className="form-hint">{hint}</span>}
            {error && <span className="form-error">{error}</span>}
        </div>
    );
};

export default BaseInput;
