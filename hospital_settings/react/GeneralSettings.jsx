// Simplified GeneralSettings.jsx - Only essential hospital settings
const { useState, useEffect } = React;

function GeneralSettings({ settings, onSave }) {
    const [formData, setFormData] = useState({
        hospital_name: '',
        hospital_address: '',
        hospital_phone: '',
        hospital_email: '',
        hospital_website: '',
        operating_hours: {},
        timezone: 'Asia/Dhaka',
        currency: 'BDT',
        language: 'en',
        theme: 'light',
        date_format: 'Y-m-d',
        time_format: 'H:i:s'
    });

    const [logoFile, setLogoFile] = useState(null);
    const [logoPreview, setLogoPreview] = useState('');

    useEffect(() => {
        if (settings.general) {
            setFormData(prev => ({
                ...prev,
                ...settings.general
            }));
        }
    }, [settings]);

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    };

    const handleOperatingHoursChange = (day, value) => {
        setFormData(prev => ({
            ...prev,
            operating_hours: {
                ...prev.operating_hours,
                [day]: value
            }
        }));
    };

    const handleLogoUpload = (e) => {
        const file = e.target.files[0];
        if (file) {
            setLogoFile(file);
            const reader = new FileReader();
            reader.onload = (e) => {
                setLogoPreview(e.target.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const handleSave = async () => {
        const success = await onSave('general', formData);
        if (success && logoFile) {
            // Upload logo separately
            const formData = new FormData();
            formData.append('logo', logoFile);
            
            try {
                const response = await fetch('../hospital_settings/settings_api.php?action=upload_logo', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    alert('Settings and logo saved successfully!');
                }
            } catch (error) {
                console.error('Error uploading logo:', error);
            }
        } else if (success) {
            alert('Settings saved successfully!');
        }
    };

    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    return (
        <div className="settings-section fade-in">
            <h2 className="section-title">
                <i className="fas fa-cog"></i>
                General Settings
            </h2>

            <div className="settings-grid">
                {/* Hospital Information */}
                <div className="settings-card">
                    <h3 className="card-title">
                        <i className="fas fa-hospital"></i>
                        Hospital Information
                    </h3>
                    
                    <div className="form-group">
                        <label className="form-label">Hospital Name</label>
                        <input
                            type="text"
                            name="hospital_name"
                            value={formData.hospital_name}
                            onChange={handleInputChange}
                            className="form-input"
                            placeholder="Enter hospital name"
                        />
                    </div>

                    <div className="form-group">
                        <label className="form-label">Address</label>
                        <textarea
                            name="hospital_address"
                            value={formData.hospital_address}
                            onChange={handleInputChange}
                            className="form-input form-textarea"
                            placeholder="Enter hospital address"
                            rows="3"
                        />
                    </div>

                    <div className="form-group">
                        <label className="form-label">Phone Number</label>
                        <input
                            type="tel"
                            name="hospital_phone"
                            value={formData.hospital_phone}
                            onChange={handleInputChange}
                            className="form-input"
                            placeholder="+880-123-456-789"
                        />
                    </div>

                    <div className="form-group">
                        <label className="form-label">Email</label>
                        <input
                            type="email"
                            name="hospital_email"
                            value={formData.hospital_email}
                            onChange={handleInputChange}
                            className="form-input"
                            placeholder="info@hospital.com"
                        />
                    </div>

                    <div className="form-group">
                        <label className="form-label">Website</label>
                        <input
                            type="url"
                            name="hospital_website"
                            value={formData.hospital_website}
                            onChange={handleInputChange}
                            className="form-input"
                            placeholder="https://hospital.com"
                        />
                    </div>
                </div>

                {/* Logo Upload */}
                <div className="settings-card">
                    <h3 className="card-title">
                        <i className="fas fa-image"></i>
                        Hospital Logo
                    </h3>
                    
                    <div className="form-group">
                        <div className="file-upload">
                            <input
                                type="file"
                                id="logo-upload"
                                accept="image/*"
                                onChange={handleLogoUpload}
                            />
                            <label htmlFor="logo-upload" className="file-upload-label">
                                <i className="fas fa-cloud-upload-alt file-upload-icon"></i>
                                Choose Logo File
                            </label>
                        </div>
                        
                        {logoPreview && (
                            <div style={{ marginTop: '1rem', textAlign: 'center' }}>
                                <img 
                                    src={logoPreview} 
                                    alt="Logo Preview" 
                                    style={{ 
                                        maxWidth: '200px', 
                                        maxHeight: '100px', 
                                        borderRadius: '8px',
                                        border: '1px solid rgba(255, 255, 255, 0.2)'
                                    }} 
                                />
                            </div>
                        )}
                    </div>
                </div>

                {/* Operating Hours */}
                <div className="settings-card">
                    <h3 className="card-title">
                        <i className="fas fa-clock"></i>
                        Operating Hours
                    </h3>
                    
                    {days.map(day => (
                        <div key={day} className="form-group">
                            <label className="form-label" style={{ textTransform: 'capitalize' }}>
                                {day}
                            </label>
                            <input
                                type="text"
                                value={formData.operating_hours[day] || ''}
                                onChange={(e) => handleOperatingHoursChange(day, e.target.value)}
                                className="form-input"
                                placeholder="e.g., 9:00 AM - 5:00 PM or 24/7"
                            />
                        </div>
                    ))}
                </div>

                {/* Regional Settings */}
                <div className="settings-card">
                    <h3 className="card-title">
                        <i className="fas fa-globe"></i>
                        Regional Settings
                    </h3>
                    
                    <div className="form-group">
                        <label className="form-label">Timezone</label>
                        <select
                            name="timezone"
                            value={formData.timezone}
                            onChange={handleInputChange}
                            className="form-input form-select"
                        >
                            <option value="Asia/Dhaka">Asia/Dhaka</option>
                            <option value="UTC">UTC</option>
                            <option value="America/New_York">America/New_York</option>
                            <option value="Europe/London">Europe/London</option>
                        </select>
                    </div>

                    <div className="form-group">
                        <label className="form-label">Currency</label>
                        <select
                            name="currency"
                            value={formData.currency}
                            onChange={handleInputChange}
                            className="form-input form-select"
                        >
                            <option value="BDT">BDT (৳)</option>
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                        </select>
                    </div>

                    <div className="form-group">
                        <label className="form-label">Language</label>
                        <select
                            name="language"
                            value={formData.language}
                            onChange={handleInputChange}
                            className="form-input form-select"
                        >
                            <option value="en">English</option>
                            <option value="bn">বাংলা</option>
                            <option value="hi">हिन्दी</option>
                            <option value="ar">العربية</option>
                        </select>
                    </div>
                </div>
            </div>

            <div style={{ marginTop: '2rem', textAlign: 'right' }}>
                <button className="btn btn-primary btn-lg" onClick={handleSave}>
                    <i className="fas fa-save"></i>
                    Save General Settings
                </button>
            </div>
        </div>
    );
}