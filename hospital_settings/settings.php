<?php
session_start();
require_once '../dbConnect.php';

// Check if user is logged in and has hospital privileges
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'hospital' && $_SESSION['user_role'] !== 'admin')) {
    header('Location: ../login.php');
    exit();
}

$hospital_id = $_SESSION['hospital_id'] ?? 1;

// Get hospital information
$user_id = $_SESSION['user_id'];
$query = "SELECT h.user_id as hospital_id FROM hospitals h WHERE h.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$hospital_data = $result->fetch_assoc();

if ($hospital_data) {
    $hospital_id = $hospital_data['hospital_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Settings - MediAI</title>
    <link rel="stylesheet" href="css/settings.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/react@18/umd/react.development.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
</head>
<body>
    <div id="settings-app"></div>

    <!-- Include React Components -->
    <script type="text/babel" src="react/AppSettings.jsx"></script>
    <script type="text/babel" src="react/GeneralSettings.jsx"></script>
    <script type="text/babel" src="react/UserManagement.jsx"></script>
    <script type="text/babel" src="react/BillingFinance.jsx"></script>
    <script type="text/babel" src="react/Notifications.jsx"></script>
    <script type="text/babel" src="react/DepartmentService.jsx"></script>
    <script type="text/babel" src="react/SystemData.jsx"></script>
    <script type="text/babel" src="react/Integrations.jsx"></script>
    <script type="text/babel" src="react/ProfileSettings.jsx"></script>
    <script type="text/babel" src="react/SettingsSidebar.jsx"></script>

    <script type="text/babel">
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

        ReactDOM.render(<AppSettings />, document.getElementById('settings-app'));
    </script>
</body>
</html>
