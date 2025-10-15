// Simplified AppSettings.jsx - Main Settings Application Component
const { useState, useEffect } = React;

function AppSettings() {
    const [activeTab, setActiveTab] = useState('general');
    const [settings, setSettings] = useState({});
    const [loading, setLoading] = useState(true);
    const [theme, setTheme] = useState('light');

    useEffect(() => {
        loadSettings();
        loadTheme();
    }, []);

    const loadSettings = async () => {
        try {
            const response = await fetch('../hospital_settings/settings_api.php?action=get_settings');
            const data = await response.json();
            if (data.success) {
                setSettings(data.settings);
            }
        } catch (error) {
            console.error('Error loading settings:', error);
        } finally {
            setLoading(false);
        }
    };

    const loadTheme = () => {
        const savedTheme = localStorage.getItem('hospital-theme') || 'light';
        setTheme(savedTheme);
        document.body.className = savedTheme;
    };

    const toggleTheme = () => {
        const newTheme = theme === 'light' ? 'dark' : 'light';
        setTheme(newTheme);
        document.body.className = newTheme;
        localStorage.setItem('hospital-theme', newTheme);
    };

    const saveSettings = async (section, data) => {
        try {
            const response = await fetch('../hospital_settings/settings_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'save_settings',
                    section: section,
                    data: data
                })
            });
            const result = await response.json();
            return result.success;
        } catch (error) {
            console.error('Error saving settings:', error);
            return false;
        }
    };

    const renderActiveComponent = () => {
        const commonProps = {
            settings: settings,
            onSave: saveSettings,
            loading: loading
        };

        switch (activeTab) {
            case 'general':
                return <GeneralSettings {...commonProps} />;
            case 'users':
                return <UserManagement {...commonProps} />;
            case 'billing':
                return <BillingFinance {...commonProps} />;
            case 'notifications':
                return <Notifications {...commonProps} />;
            case 'departments':
                return <DepartmentService {...commonProps} />;
            case 'system':
                return <SystemData {...commonProps} />;
            case 'integrations':
                return <Integrations {...commonProps} />;
            case 'profile':
                return <ProfileSettings {...commonProps} />;
            default:
                return <GeneralSettings {...commonProps} />;
        }
    };

    if (loading) {
        return (
            <div className="settings-loading">
                <div className="loading-spinner"></div>
                <p>Loading settings...</p>
            </div>
        );
    }

    return (
        <div className="settings-container">
            <SettingsSidebar 
                activeTab={activeTab} 
                onTabChange={setActiveTab}
                theme={theme}
                onThemeToggle={toggleTheme}
            />
            <div className="settings-content">
                <div className="settings-header">
                    <h1>Hospital Settings</h1>
                    <div className="header-actions">
                        <button 
                            className="theme-toggle"
                            onClick={toggleTheme}
                            title={`Switch to ${theme === 'light' ? 'dark' : 'light'} mode`}
                        >
                            <i className={`fas fa-${theme === 'light' ? 'moon' : 'sun'}`}></i>
                        </button>
                    </div>
                </div>
                <div className="settings-main">
                    {renderActiveComponent()}
                </div>
            </div>
        </div>
    );
}