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
        // Welcome Section
        React.createElement('div', { 
          key: 'welcome-section', 
          style: {
            background: 'linear-gradient(135deg, #a259ff 0%, #8a4ae6 100%)',
            borderRadius: '20px',
            padding: '30px',
            marginBottom: '30px',
            color: '#fff',
            textAlign: 'center'
          }
        }, [
          React.createElement('h1', { 
            key: 'welcome-title', 
            style: { 
              margin: '0 0 10px 0', 
              fontSize: '2.2rem', 
              fontWeight: '700' 
            } 
          }, 'Welcome to Admin Dashboard'),
          React.createElement('p', { 
            key: 'welcome-subtitle', 
            style: { 
              margin: 0, 
              fontSize: '1.1rem', 
              opacity: 0.9 
            } 
          }, 'Manage your system efficiently and effectively')
        ]),

        // Statistics Grid
        React.createElement('div', { 
          key: 'stats-grid', 
          style: { 
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))',
            gap: '20px',
            marginBottom: '30px'
          } 
        }, 
          stats ? [
            // Total Users Card
            React.createElement('div', { 
              key: 'users-card', 
              style: {
                background: 'linear-gradient(135deg, #a259ff 0%, #8a4ae6 100%)',
                borderRadius: '15px',
                padding: '25px',
                border: '1px solid #8a4ae6',
                boxShadow: '0 4px 15px rgba(162, 89, 255, 0.3)',
                transition: 'all 0.3s ease',
                position: 'relative',
                overflow: 'hidden',
                color: '#fff'
              },
              onMouseOver: (e) => {
                e.target.style.transform = 'translateY(-5px)';
                e.target.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
              },
              onMouseOut: (e) => {
                e.target.style.transform = 'translateY(0)';
                e.target.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
              }
            }, [
              React.createElement('div', { 
                key: 'users-icon', 
                style: { 
                  position: 'absolute',
                  top: '15px',
                  right: '20px',
                  fontSize: '2.5rem',
                  opacity: 0.1
                } 
              }, '👥'),
              React.createElement('div', { 
                key: 'users-number', 
                style: { 
                  fontSize: '2.5rem', 
                  fontWeight: '700', 
                  color: '#fff',
                  marginBottom: '8px'
                } 
              }, stats.total_users),
              React.createElement('div', { 
                key: 'users-label', 
                style: { 
                  fontSize: '1rem', 
                  color: '#fff',
                  fontWeight: '600'
                } 
              }, 'Total Users'),
              React.createElement('div', { 
                key: 'users-subtitle', 
                style: { 
                  fontSize: '0.85rem', 
                  color: 'rgba(255,255,255,0.8)',
                  marginTop: '5px'
                } 
              }, 'Registered patients & staff')
            ]),

            // Doctors Card
            React.createElement('div', { 
              key: 'doctors-card', 
              style: {
                background: 'linear-gradient(135deg, #a259ff 0%, #8a4ae6 100%)',
                borderRadius: '15px',
                padding: '25px',
                border: '1px solid #8a4ae6',
                boxShadow: '0 4px 15px rgba(162, 89, 255, 0.3)',
                transition: 'all 0.3s ease',
                position: 'relative',
                overflow: 'hidden',
                color: '#fff'
              },
              onMouseOver: (e) => {
                e.target.style.transform = 'translateY(-5px)';
                e.target.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
              },
              onMouseOut: (e) => {
                e.target.style.transform = 'translateY(0)';
                e.target.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
              }
            }, [
              React.createElement('div', { 
                key: 'doctors-icon', 
                style: { 
                  position: 'absolute',
                  top: '15px',
                  right: '20px',
                  fontSize: '2.5rem',
                  opacity: 0.1
                } 
              }, '👨‍⚕️'),
              React.createElement('div', { 
                key: 'doctors-number', 
                style: { 
                  fontSize: '2.5rem', 
                  fontWeight: '700', 
                  color: '#fff',
                  marginBottom: '8px'
                } 
              }, stats.total_doctors),
              React.createElement('div', { 
                key: 'doctors-label', 
                style: { 
                  fontSize: '1rem', 
                  color: '#fff',
                  fontWeight: '600'
                } 
              }, 'Active Doctors'),
              React.createElement('div', { 
                key: 'doctors-subtitle', 
                style: { 
                  fontSize: '0.85rem', 
                  color: 'rgba(255,255,255,0.8)',
                  marginTop: '5px'
                } 
              }, 'Medical professionals')
            ]),

            // Appointments Card
            React.createElement('div', { 
              key: 'appointments-card', 
              style: {
                background: 'linear-gradient(135deg, #a259ff 0%, #8a4ae6 100%)',
                borderRadius: '15px',
                padding: '25px',
                border: '1px solid #8a4ae6',
                boxShadow: '0 4px 15px rgba(162, 89, 255, 0.3)',
                transition: 'all 0.3s ease',
                position: 'relative',
                overflow: 'hidden',
                color: '#fff'
              },
              onMouseOver: (e) => {
                e.target.style.transform = 'translateY(-5px)';
                e.target.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
              },
              onMouseOut: (e) => {
                e.target.style.transform = 'translateY(0)';
                e.target.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
              }
            }, [
              React.createElement('div', { 
                key: 'appointments-icon', 
                style: { 
                  position: 'absolute',
                  top: '15px',
                  right: '20px',
                  fontSize: '2.5rem',
                  opacity: 0.1
                } 
              }, '📅'),
              React.createElement('div', { 
                key: 'appointments-number', 
                style: { 
                  fontSize: '2.5rem', 
                  fontWeight: '700', 
                  color: '#fff',
                  marginBottom: '8px'
                } 
              }, stats.pending_appointments),
              React.createElement('div', { 
                key: 'appointments-label', 
                style: { 
                  fontSize: '1rem', 
                  color: '#fff',
                  fontWeight: '600'
                } 
              }, 'Pending Appointments'),
              React.createElement('div', { 
                key: 'appointments-subtitle', 
                style: { 
                  fontSize: '0.85rem', 
                  color: 'rgba(255,255,255,0.8)',
                  marginTop: '5px'
                } 
              }, 'Awaiting confirmation')
            ]),

            // Cabin Bookings Card
            React.createElement('div', { 
              key: 'cabins-card', 
              style: {
                background: 'linear-gradient(135deg, #a259ff 0%, #8a4ae6 100%)',
                borderRadius: '15px',
                padding: '25px',
                border: '1px solid #8a4ae6',
                boxShadow: '0 4px 15px rgba(162, 89, 255, 0.3)',
                transition: 'all 0.3s ease',
                position: 'relative',
                overflow: 'hidden',
                color: '#fff'
              },
              onMouseOver: (e) => {
                e.target.style.transform = 'translateY(-5px)';
                e.target.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
              },
              onMouseOut: (e) => {
                e.target.style.transform = 'translateY(0)';
                e.target.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
              }
            }, [
              React.createElement('div', { 
                key: 'cabins-icon', 
                style: { 
                  position: 'absolute',
                  top: '15px',
                  right: '20px',
                  fontSize: '2.5rem',
                  opacity: 0.1
                } 
              }, '🏥'),
              React.createElement('div', { 
                key: 'cabins-number', 
                style: { 
                  fontSize: '2.5rem', 
                  fontWeight: '700', 
                  color: '#fff',
                  marginBottom: '8px'
                } 
              }, stats.occupied_cabins),
              React.createElement('div', { 
                key: 'cabins-label', 
                style: { 
                  fontSize: '1rem', 
                  color: '#fff',
                  fontWeight: '600'
                } 
              }, 'Occupied Cabins'),
              React.createElement('div', { 
                key: 'cabins-subtitle', 
                style: { 
                  fontSize: '0.85rem', 
                  color: 'rgba(255,255,255,0.8)',
                  marginTop: '5px'
                } 
              }, 'Currently in use')
            ]),

            // Community Posts Card
            React.createElement('div', { 
              key: 'posts-card', 
              style: {
                background: 'linear-gradient(135deg, #a259ff 0%, #8a4ae6 100%)',
                borderRadius: '15px',
                padding: '25px',
                border: '1px solid #8a4ae6',
                boxShadow: '0 4px 15px rgba(162, 89, 255, 0.3)',
                transition: 'all 0.3s ease',
                position: 'relative',
                overflow: 'hidden',
                color: '#fff'
              },
              onMouseOver: (e) => {
                e.target.style.transform = 'translateY(-5px)';
                e.target.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
              },
              onMouseOut: (e) => {
                e.target.style.transform = 'translateY(0)';
                e.target.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
              }
            }, [
              React.createElement('div', { 
                key: 'posts-icon', 
                style: { 
                  position: 'absolute',
                  top: '15px',
                  right: '20px',
                  fontSize: '2.5rem',
                  opacity: 0.1
                } 
              }, '💬'),
              React.createElement('div', { 
                key: 'posts-number', 
                style: { 
                  fontSize: '2.5rem', 
                  fontWeight: '700', 
                  color: '#fff',
                  marginBottom: '8px'
                } 
              }, stats.total_posts),
              React.createElement('div', { 
                key: 'posts-label', 
                style: { 
                  fontSize: '1rem', 
                  color: '#fff',
                  fontWeight: '600'
                } 
              }, 'Community Posts'),
              React.createElement('div', { 
                key: 'posts-subtitle', 
                style: { 
                  fontSize: '0.85rem', 
                  color: 'rgba(255,255,255,0.8)',
                  marginTop: '5px'
                } 
              }, 'User discussions')
            ]),

            // Blocked Users Card
            React.createElement('div', { 
              key: 'blocked-card', 
              style: {
                background: 'linear-gradient(135deg, #a259ff 0%, #8a4ae6 100%)',
                borderRadius: '15px',
                padding: '25px',
                border: '1px solid #8a4ae6',
                boxShadow: '0 4px 15px rgba(162, 89, 255, 0.3)',
                transition: 'all 0.3s ease',
                position: 'relative',
                overflow: 'hidden',
                color: '#fff'
              },
              onMouseOver: (e) => {
                e.target.style.transform = 'translateY(-5px)';
                e.target.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
              },
              onMouseOut: (e) => {
                e.target.style.transform = 'translateY(0)';
                e.target.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
              }
            }, [
              React.createElement('div', { 
                key: 'blocked-icon', 
                style: { 
                  position: 'absolute',
                  top: '15px',
                  right: '20px',
                  fontSize: '2.5rem',
                  opacity: 0.1
                } 
              }, '🚫'),
              React.createElement('div', { 
                key: 'blocked-number', 
                style: { 
                  fontSize: '2.5rem', 
                  fontWeight: '700', 
                  color: '#fff',
                  marginBottom: '8px'
                } 
              }, stats.blocked_users),
              React.createElement('div', { 
                key: 'blocked-label', 
                style: { 
                  fontSize: '1rem', 
                  color: '#fff',
                  fontWeight: '600'
                } 
              }, 'Blocked Users'),
              React.createElement('div', { 
                key: 'blocked-subtitle', 
                style: { 
                  fontSize: '0.85rem', 
                  color: 'rgba(255,255,255,0.8)',
                  marginTop: '5px'
                } 
              }, 'Restricted access')
            ])
          ] : 
          React.createElement('div', { 
            key: 'loading', 
            style: { 
              gridColumn: '1 / -1',
              textAlign: 'center',
              padding: '40px',
              color: '#6c757d'
            } 
          }, 'Loading statistics...')
        ),

        // Recent Activity Section
        React.createElement('div', { 
          key: 'activity-section', 
          style: {
            background: '#fff',
            borderRadius: '15px',
            padding: '25px',
            border: '1px solid #e9ecef',
            boxShadow: '0 4px 15px rgba(0,0,0,0.1)'
          }
        }, [
          React.createElement('h3', { 
            key: 'activity-title', 
            style: { 
              margin: '0 0 20px 0', 
              color: '#495057',
              fontSize: '1.3rem',
              fontWeight: '600',
              display: 'flex',
              alignItems: 'center'
            } 
          }, [
            React.createElement('span', { key: 'icon', style: { marginRight: '10px' } }, '📊'),
            'Recent Activity'
          ]),
          
            recentActivity.length > 0 ? 
            React.createElement('div', { 
              key: 'activity-list', 
              style: { maxHeight: '400px', overflowY: 'auto' } 
            }, 
              recentActivity.map((activity, index) => 
                React.createElement('div', { 
                  key: index, 
                  style: {
                    display: 'flex',
                    alignItems: 'center',
                    padding: '15px',
                    marginBottom: '12px',
                    backgroundColor: '#f8f9fa',
                    borderRadius: '12px',
                    border: '1px solid #e9ecef',
                    transition: 'all 0.3s ease',
                    cursor: 'pointer'
                  },
                  onMouseOver: (e) => {
                    e.target.style.backgroundColor = '#e9ecef';
                    e.target.style.transform = 'translateX(5px)';
                    e.target.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                  },
                  onMouseOut: (e) => {
                    e.target.style.backgroundColor = '#f8f9fa';
                    e.target.style.transform = 'translateX(0)';
                    e.target.style.boxShadow = 'none';
                  }
                }, [
                  React.createElement('div', { 
                    key: 'activity-icon', 
                    style: { 
                      width: '40px',
                      height: '40px',
                      borderRadius: '50%',
                      backgroundColor: '#a259ff',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      marginRight: '15px',
                      fontSize: '1.2rem',
                      color: '#fff',
                      fontWeight: 'bold'
                    } 
                  }, activity.type === 'user_registration' ? '👤' : activity.type === 'booking' ? '🏥' : '📝'),
                  
                  React.createElement('div', { 
                    key: 'activity-content', 
                    style: { flex: 1 } 
                  }, [
                    React.createElement('div', { 
                      key: 'activity-text', 
                      style: { 
                        fontSize: '0.95rem', 
                        color: '#495057',
                        fontWeight: '600',
                        marginBottom: '4px'
                      } 
                    }, `${activity.type.replace('_', ' ').toUpperCase()}`),
                    React.createElement('div', { 
                      key: 'activity-user', 
                      style: { 
                        fontSize: '0.9rem', 
                        color: '#6c757d',
                        marginBottom: '4px'
                      } 
                    }, `User: ${activity.username}`),
                    React.createElement('div', { 
                      key: 'activity-date', 
                      style: { 
                        fontSize: '0.8rem', 
                        color: '#adb5bd',
                        display: 'flex',
                        alignItems: 'center'
                      } 
                    }, [
                      React.createElement('span', { key: 'time-icon', style: { marginRight: '5px' } }, '🕒'),
                    new Date(activity.created_at).toLocaleString()
                    ])
                  ]),
                  
                  React.createElement('div', { 
                    key: 'activity-status', 
                    style: { 
                      padding: '4px 8px',
                      borderRadius: '12px',
                      fontSize: '0.75rem',
                      fontWeight: '600',
                      backgroundColor: activity.type === 'user_registration' ? '#e8f5e8' : '#fff3cd',
                      color: activity.type === 'user_registration' ? '#2ed573' : '#ffa502',
                      border: `1px solid ${activity.type === 'user_registration' ? '#2ed573' : '#ffa502'}`
                    } 
                  }, activity.type === 'user_registration' ? 'NEW' : 'UPDATE')
                ])
              )
            ) :
            React.createElement('div', { 
              key: 'empty-activity', 
              style: { 
                textAlign: 'center', 
                padding: '60px 20px',
                backgroundColor: '#f8f9fa',
                borderRadius: '12px',
                border: '2px dashed #dee2e6'
              } 
            }, [
              React.createElement('div', { 
                key: 'empty-icon', 
                style: { 
                  fontSize: '4rem', 
                  marginBottom: '20px',
                  color: '#adb5bd'
                } 
              }, '📊'),
              React.createElement('h5', { 
                key: 'empty-title', 
                style: { 
                  margin: '0 0 10px 0', 
                  color: '#6c757d',
                  fontSize: '1.2rem',
                  fontWeight: '600'
                } 
              }, 'No Recent Activity'),
              React.createElement('p', { 
                key: 'empty-desc', 
                style: { 
                  margin: 0, 
                  color: '#adb5bd',
                  fontSize: '0.95rem',
                  lineHeight: '1.5'
                } 
              }, 'Activity will appear here as users interact with the system')
            ])
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
