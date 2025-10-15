// Simplified Notifications.jsx - Only essential notification settings
const { useState, useEffect } = React;

function Notifications({ settings, onSave }) {
    const [notificationSettings, setNotificationSettings] = useState({
        email_enabled: true,
        sms_enabled: true,
        push_enabled: true
    });

    useEffect(() => {
        if (settings.notifications) {
            setNotificationSettings(settings.notifications);
        }
    }, [settings]);

    const handleInputChange = (key, value) => {
        setNotificationSettings(prev => ({
            ...prev,
            [key]: value
        }));
    };

    const handleSave = async () => {
        await onSave('notifications', notificationSettings);
        alert('Notification settings saved successfully!');
    };

    return (
        <div className="settings-section fade-in">
            <h2 className="section-title">
                <i className="fas fa-bell"></i>
                Notification Settings
            </h2>

            <div className="settings-grid">
                {/* General Notification Settings */}
                <div className="settings-card">
                    <h3 className="card-title">
                        <i className="fas fa-cog"></i>
                        General Settings
                    </h3>
                    
                    <div className="form-group">
                        <label className="form-label">Email Notifications</label>
                        <label className="toggle-switch">
                            <input
                                type="checkbox"
                                checked={notificationSettings.email_enabled}
                                onChange={(e) => handleInputChange('email_enabled', e.target.checked)}
                            />
                            <span className="toggle-slider"></span>
                        </label>
                    </div>

                    <div className="form-group">
                        <label className="form-label">SMS Notifications</label>
                        <label className="toggle-switch">
                            <input
                                type="checkbox"
                                checked={notificationSettings.sms_enabled}
                                onChange={(e) => handleInputChange('sms_enabled', e.target.checked)}
                            />
                            <span className="toggle-slider"></span>
                        </label>
                    </div>

                    <div className="form-group">
                        <label className="form-label">Push Notifications</label>
                        <label className="toggle-switch">
                            <input
                                type="checkbox"
                                checked={notificationSettings.push_enabled}
                                onChange={(e) => handleInputChange('push_enabled', e.target.checked)}
                            />
                            <span className="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div style={{ marginTop: '2rem', textAlign: 'right' }}>
                <button className="btn btn-primary btn-lg" onClick={handleSave}>
                    <i className="fas fa-save"></i>
                    Save Notification Settings
                </button>
            </div>
        </div>
    );
}