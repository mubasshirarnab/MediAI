// SettingsSidebar.jsx - Navigation Sidebar Component
const { useState } = React;

function SettingsSidebar({ activeTab, onTabChange, theme, onThemeToggle }) {
    const menuItems = [
        { id: 'general', label: 'General', icon: 'fas fa-cog' },
        { id: 'users', label: 'User Management', icon: 'fas fa-users' },
        { id: 'billing', label: 'Billing & Finance', icon: 'fas fa-credit-card' },
        { id: 'notifications', label: 'Notifications', icon: 'fas fa-bell' },
        { id: 'departments', label: 'Departments', icon: 'fas fa-building' },
        { id: 'system', label: 'System & Data', icon: 'fas fa-server' },
        { id: 'integrations', label: 'Integrations', icon: 'fas fa-plug' },
        { id: 'profile', label: 'Profile', icon: 'fas fa-user' }
    ];

    return (
        <div className="settings-sidebar">
            <div className="sidebar-header">
                <h2 className="sidebar-title">Settings</h2>
                <p className="sidebar-subtitle">Manage your hospital configuration</p>
            </div>
            
            <nav className="sidebar-nav">
                {menuItems.map(item => (
                    <div key={item.id} className="sidebar-nav-item">
                        <a
                            href="#"
                            className={`sidebar-nav-link ${activeTab === item.id ? 'active' : ''}`}
                            onClick={(e) => {
                                e.preventDefault();
                                onTabChange(item.id);
                            }}
                        >
                            <i className={`sidebar-nav-icon ${item.icon}`}></i>
                            {item.label}
                        </a>
                    </div>
                ))}
            </nav>
        </div>
    );
}
