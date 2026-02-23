import React from 'react';
import './AnnouncementBar.css';

const AnnouncementBar = ({ announcement }) => {
    if (!announcement) return null;

    return (
        <div className="announcement-bar">
            <p>
                {announcement.icon && <i className={`fas ${announcement.icon}`} />}
                {' '}{announcement.message}
            </p>
        </div>
    );
};

export default AnnouncementBar;
