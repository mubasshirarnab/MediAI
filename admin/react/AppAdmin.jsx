(function(){
  const { useState } = React;
  const { UserManagement, HospitalManagement, DoctorManagement, SystemSettings, AppointmentManagement, CabinManagement, CommunityManagement } = window.Components;
  
  function AppAdmin() {
    const [activeTab, setActiveTab] = useState('dashboard');
    const [stats, setStats] = useState(null);
    const [recentActivity, setRecentActivity] = useState([]);

    React.useEffect(() => {
      // Load dashboard data
      loadStats();
      loadRecentActivity();
    }, []);

    const loadStats = async () => {
      try {
        const response = await axios.get('admin_api.php?action=system_stats');
        if (response.data.success) {
          setStats(response.data.data);
        }
      } catch (error) {
        console.error('Failed to load stats:', error);
      }
    };

    const loadRecentActivity = async () => {
      try {
        const response = await axios.get('admin_api.php?action=recent_activity');
        if (response.data.success) {
          setRecentActivity(response.data.data);
        }
      } catch (error) {
        console.error('Failed to load recent activity:', error);
      }
    };

    const Dashboard = () => (
      React.createElement('div', null, [
            React.createElement('div', { key: 'stats', className: 'card' }, [
              React.createElement('h3', { key: 'title' }, 'System Statistics'),
              stats ? React.createElement('div', { key: 'grid', className: 'row' }, [
                React.createElement('div', { key: 'users', className: 'stat-card' }, [
                  React.createElement('div', { key: 'number' }, stats.total_users),
                  React.createElement('div', { key: 'label' }, 'Total Users')
                ]),
                React.createElement('div', { key: 'doctors', className: 'stat-card' }, [
                  React.createElement('div', { key: 'number' }, stats.total_doctors),
                  React.createElement('div', { key: 'label' }, 'Doctors')
                ]),
                React.createElement('div', { key: 'appointments', className: 'stat-card' }, [
                  React.createElement('div', { key: 'number' }, stats.pending_appointments),
                  React.createElement('div', { key: 'label' }, 'Pending Appointments')
                ]),
                React.createElement('div', { key: 'cabins', className: 'stat-card' }, [
                  React.createElement('div', { key: 'number' }, stats.occupied_cabins),
                  React.createElement('div', { key: 'label' }, 'Occupied Cabins')
                ]),
                React.createElement('div', { key: 'bookings', className: 'stat-card' }, [
                  React.createElement('div', { key: 'number' }, stats.active_bookings),
                  React.createElement('div', { key: 'label' }, 'Active Bookings')
                ]),
                React.createElement('div', { key: 'posts', className: 'stat-card' }, [
                  React.createElement('div', { key: 'number' }, stats.total_posts),
                  React.createElement('div', { key: 'label' }, 'Community Posts')
                ]),
                React.createElement('div', { key: 'blocked', className: 'stat-card' }, [
                  React.createElement('div', { key: 'number' }, stats.blocked_users),
                  React.createElement('div', { key: 'label' }, 'Blocked Users')
                ])
              ]) : React.createElement('div', { key: 'loading' }, 'Loading...')
            ]),
        React.createElement('div', { key: 'activity', className: 'card' }, [
          React.createElement('h3', { key: 'title' }, 'Recent Activity'),
          React.createElement('div', { key: 'list' }, 
            recentActivity.length > 0 ? 
              recentActivity.map((activity, index) => 
                React.createElement('div', { key: index, className: 'activity-item' }, [
                  React.createElement('span', { key: 'type', className: 'activity-type' }, activity.type),
                  React.createElement('span', { key: 'user' }, activity.username),
                  React.createElement('span', { key: 'date', className: 'activity-date' }, 
                    new Date(activity.created_at).toLocaleString()
                  )
                ])
              ) : 
              React.createElement('div', { key: 'empty' }, 'No recent activity')
          )
        ])
      ])
    );

    return (
      React.createElement('div', null, [
        React.createElement('div', { key: 'header', className: 'header' }, 
          React.createElement('div', { className: 'title' }, 'Admin Dashboard')
        ),
        React.createElement('div', { key: 'tabs', className: 'tabs' }, [
          React.createElement('button', { 
            key: 'dashboard', 
            className: 'btn' + (activeTab === 'dashboard' ? ' primary' : ''), 
            onClick: () => setActiveTab('dashboard') 
          }, 'Dashboard'),
          React.createElement('button', { 
            key: 'users', 
            className: 'btn' + (activeTab === 'users' ? ' primary' : ''), 
            onClick: () => setActiveTab('users') 
          }, 'User Management'),
          React.createElement('button', { 
            key: 'doctors', 
            className: 'btn' + (activeTab === 'doctors' ? ' primary' : ''), 
            onClick: () => setActiveTab('doctors') 
          }, 'Doctor Management'),
          React.createElement('button', { 
            key: 'hospitals', 
            className: 'btn' + (activeTab === 'hospitals' ? ' primary' : ''), 
            onClick: () => setActiveTab('hospitals') 
          }, 'Hospital Management'),
          React.createElement('button', { 
            key: 'appointments', 
            className: 'btn' + (activeTab === 'appointments' ? ' primary' : ''), 
            onClick: () => setActiveTab('appointments') 
          }, 'Appointments'),
          React.createElement('button', { 
            key: 'cabins', 
            className: 'btn' + (activeTab === 'cabins' ? ' primary' : ''), 
            onClick: () => setActiveTab('cabins') 
          }, 'Cabin Bookings'),
          React.createElement('button', { 
            key: 'community', 
            className: 'btn' + (activeTab === 'community' ? ' primary' : ''), 
            onClick: () => setActiveTab('community') 
          }, 'Community'),
          React.createElement('button', { 
            key: 'settings', 
            className: 'btn' + (activeTab === 'settings' ? ' primary' : ''), 
            onClick: () => setActiveTab('settings') 
          }, 'System Settings')
        ]),
        activeTab === 'dashboard' ? React.createElement(Dashboard, { key: 'dashboard' }) :
        activeTab === 'users' ? React.createElement(UserManagement, { key: 'users' }) :
        activeTab === 'doctors' ? React.createElement(DoctorManagement, { key: 'doctors' }) :
        activeTab === 'hospitals' ? React.createElement(HospitalManagement, { key: 'hospitals' }) :
        activeTab === 'appointments' ? React.createElement(AppointmentManagement, { key: 'appointments' }) :
        activeTab === 'cabins' ? React.createElement(CabinManagement, { key: 'cabins' }) :
        activeTab === 'community' ? React.createElement(CommunityManagement, { key: 'community' }) :
        activeTab === 'settings' ? React.createElement(SystemSettings, { key: 'settings' }) :
        null
      ])
    );
  }
  
  window.Apps = window.Apps || {};
  window.Apps.AppAdmin = AppAdmin;
})();
