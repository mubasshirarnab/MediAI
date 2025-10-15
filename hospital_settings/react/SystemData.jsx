// Simplified SystemData.jsx - Only essential system settings
const { useState, useEffect } = React;

function SystemData({ settings, onSave }) {
    const [systemSettings, setSystemSettings] = useState({
        maintenance_mode: false,
        backup_frequency: 'daily',
        audit_log_enabled: true
    });

    useEffect(() => {
        if (settings.system) {
            setSystemSettings(settings.system);
        }
    }, [settings]);

    const handleInputChange = (key, value) => {
        setSystemSettings(prev => ({
            ...prev,
            [key]: value
        }));
    };

    const handleSave = async () => {
        await onSave('system', systemSettings);
        alert('System settings saved successfully!');
    };

    return (
        <div className="settings-section fade-in">
            <h2 className="section-title">
                <i className="fas fa-server"></i>
                System Settings
            </h2>

            <div className="settings-grid">
                {/* System Settings */}
                <div className="settings-card">
                    <h3 className="card-title">
                        <i className="fas fa-cog"></i>
                        System Settings
                    </h3>
                    
                    <div className="form-group">
                        <label className="form-label">Maintenance Mode</label>
                        <label className="toggle-switch">
                            <input
                                type="checkbox"
                                checked={systemSettings.maintenance_mode}
                                onChange={(e) => handleInputChange('maintenance_mode', e.target.checked)}
                            />
                            <span className="toggle-slider"></span>
                        </label>
                        <small style={{ color: 'rgba(255, 255, 255, 0.6)', fontSize: '0.75rem' }}>
                            Enable maintenance mode to restrict access
                        </small>
                    </div>

                    <div className="form-group">
                        <label className="form-label">Backup Frequency</label>
                        <select
                            value={systemSettings.backup_frequency}
                            onChange={(e) => handleInputChange('backup_frequency', e.target.value)}
                            className="form-input form-select"
                        >
                            <option value="hourly">Hourly</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>

                    <div className="form-group">
                        <label className="form-label">Audit Log</label>
                        <label className="toggle-switch">
                            <input
                                type="checkbox"
                                checked={systemSettings.audit_log_enabled}
                                onChange={(e) => handleInputChange('audit_log_enabled', e.target.checked)}
                            />
                            <span className="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div style={{ marginTop: '2rem', textAlign: 'right' }}>
                <button className="btn btn-primary btn-lg" onClick={handleSave}>
                    <i className="fas fa-save"></i>
                    Save System Settings
                </button>
            </div>
        </div>
    );
}