import React from 'react';
import './ProgressSteps.css';

const ProgressSteps = ({ steps, current }) => {
    return (
        <div className="progress-container">


            <div className="steps-wrapper">
                {steps.map((step, index) => {
                    let stepClass = 'step';
                    if (index < current) stepClass += ' step--completed';
                    if (index === current) stepClass += ' step--active';

                    return (
                        <div key={index} className="step-item">
                            <div className={stepClass}>
                                <div className="step-number">{index + 1}</div>
                            </div>
                            <div className="step-label">{step}</div>
                        </div>
                    );
                })}

                <div className="steps-line">
                    <div
                        className="steps-line-fill"
                        style={{ width: `${(current / (steps.length - 1)) * 100}%` }}
                    ></div>
                </div>
            </div>
        </div>
    );
};

export default ProgressSteps;
