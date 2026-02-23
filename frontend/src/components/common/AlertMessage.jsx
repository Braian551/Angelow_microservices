import React from 'react';
import './AlertMessage.css';

const AlertMessage = ({ message, type = 'error', dismissible = true, onDismiss }) => {
    if (!message) return null;

    return (
        <div className={`alert alert--${type}`}>
            <div className="alert-content">
                <span className="alert-icon">
                    {type === 'success' ? '✓' : '⚠'}
                </span>
                <span className="alert-text">{message}</span>
            </div>
            {dismissible && (
                <button className="alert-close" onClick={onDismiss} aria-label="Cerrar">
                    &times;
                </button>
            )}
        </div>
    );
};

export default AlertMessage;
