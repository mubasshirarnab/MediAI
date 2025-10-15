// Simplified ProfileSettings.jsx - Placeholder component
const { useState } = React;

function ProfileSettings({ settings, onSave }) {
    return (
        <div className="settings-section fade-in">
            <h2 className="section-title">
                <i className="fas fa-user"></i>
                Profile Settings
            </h2>
            
            <div className="settings-card">
                <h3 className="card-title">
                    <i className="fas fa-info-circle"></i>
                    Coming Soon
                </h3>
                <p style={{ color: 'rgba(255, 255, 255, 0.7)', textAlign: 'center', padding: '2rem' }}>
                    Profile settings features will be available in a future update.
                </p>
            </div>
        </div>
    );
}