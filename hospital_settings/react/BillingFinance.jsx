// Simplified BillingFinance.jsx - Only essential billing settings
const { useState, useEffect } = React;

function BillingFinance({ settings, onSave }) {
    const [billingSettings, setBillingSettings] = useState({
        tax_rate: 0.00,
        service_charge: 0.00,
        currency: 'BDT',
        auto_billing_enabled: false
    });

    useEffect(() => {
        if (settings.billing) {
            setBillingSettings(settings.billing);
        }
    }, [settings]);

    const handleInputChange = (key, value) => {
        setBillingSettings(prev => ({
            ...prev,
            [key]: value
        }));
    };

    const handleSave = async () => {
        await onSave('billing', billingSettings);
        alert('Billing settings saved successfully!');
    };

    return (
        <div className="settings-section fade-in">
            <h2 className="section-title">
                <i className="fas fa-credit-card"></i>
                Billing & Finance Settings
            </h2>

            <div className="settings-grid">
                {/* Basic Billing Settings */}
                <div className="settings-card">
                    <h3 className="card-title">
                        <i className="fas fa-calculator"></i>
                        Basic Settings
                    </h3>
                    
                    <div className="form-group">
                        <label className="form-label">Tax Rate (%)</label>
                        <input
                            type="number"
                            value={billingSettings.tax_rate}
                            onChange={(e) => handleInputChange('tax_rate', parseFloat(e.target.value))}
                            className="form-input"
                            min="0"
                            max="100"
                            step="0.01"
                        />
                    </div>

                    <div className="form-group">
                        <label className="form-label">Service Charge (%)</label>
                        <input
                            type="number"
                            value={billingSettings.service_charge}
                            onChange={(e) => handleInputChange('service_charge', parseFloat(e.target.value))}
                            className="form-input"
                            min="0"
                            max="100"
                            step="0.01"
                        />
                    </div>

                    <div className="form-group">
                        <label className="form-label">Default Currency</label>
                        <select
                            value={billingSettings.currency}
                            onChange={(e) => handleInputChange('currency', e.target.value)}
                            className="form-input form-select"
                        >
                            <option value="BDT">BDT (৳)</option>
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                        </select>
                    </div>

                    <div className="form-group">
                        <label className="form-label">Auto Billing</label>
                        <label className="toggle-switch">
                            <input
                                type="checkbox"
                                checked={billingSettings.auto_billing_enabled}
                                onChange={(e) => handleInputChange('auto_billing_enabled', e.target.checked)}
                            />
                            <span className="toggle-slider"></span>
                        </label>
                        <small style={{ color: 'rgba(255, 255, 255, 0.6)', fontSize: '0.75rem' }}>
                            Automatically generate bills for completed services
                        </small>
                    </div>
                </div>
            </div>

            <div style={{ marginTop: '2rem', textAlign: 'right' }}>
                <button className="btn btn-primary btn-lg" onClick={handleSave}>
                    <i className="fas fa-save"></i>
                    Save Billing Settings
                </button>
            </div>
        </div>
    );
}