import React, { useState, useEffect, useCallback } from 'react';
import '../styles/admin.css';

// Helper functions
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  try {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch {
    return dateString;
  }
};

const formatBoolean = (value) => {
  return value ? 'Yes' : 'No';
};

const formatStatus = (status) => {
  return status === 'authorized' ? '✅ Authorized' : '⏳ Pending';
};

// Constants
const sections = [
  { key: 'users', label: 'All Users', icon: '👥' },
  { key: 'patients', label: 'Patients', icon: '🏥', filterRole: 1 },
  { key: 'doctors', label: 'Doctors', icon: '👨‍⚕️', filterRole: 2 },
  { key: 'hospitals', label: 'Hospitals', icon: '🏨', filterRole: 3 },
  { key: 'admins', label: 'Admins', icon: '👨‍💼', filterRole: 4 },
  { key: 'community', label: 'Communities', icon: '🌟' },
  { key: 'appointments', label: 'Appointments', icon: '📅' },
  { key: 'posts', label: 'Community Posts', icon: '📝' },
  { key: 'medications', label: 'Medications', icon: '💊' },
  { key: 'feedback', label: 'Feedback', icon: '💬' }
];

const tableConfigs = {
  users: {
    columns: [
      { key: 'id', label: 'ID' },
      { key: 'name', label: 'Name' },
      { key: 'email', label: 'Email' },
      { key: 'phone', label: 'Phone' },
      { key: 'status', label: 'Status', render: formatStatus },
      { key: 'created_at', label: 'Created', render: formatDate },
      { key: 'actions', label: 'Actions', isAction: true }
    ]
  },
  // Special config for filtered user tables
  users_1: {
    columns: [
      { key: 'id', label: 'Patient ID' },
      { key: 'name', label: 'Patient Name' },
      { key: 'email', label: 'Email' },
      { key: 'phone', label: 'Phone' },
      { key: 'status', label: 'Status', render: formatStatus },
      { key: 'created_at', label: 'Joined Date', render: formatDate },
      { key: 'actions', label: 'Actions', isAction: true }
    ]
  },
  users_2: {
    columns: [
      { key: 'id', label: 'Doctor ID' },
      { key: 'name', label: 'Doctor Name' },
      { key: 'email', label: 'Email' },
      { key: 'phone', label: 'Phone' },
      { key: 'status', label: 'Status', render: formatStatus },
      { key: 'created_at', label: 'Registration Date', render: formatDate },
      { key: 'actions', label: 'Actions', isAction: true }
    ]
  },
  users_3: {
    columns: [
      { key: 'id', label: 'Hospital ID' },
      { key: 'name', label: 'Hospital Name' },
      { key: 'email', label: 'Email' },
      { key: 'phone', label: 'Phone' },
      { key: 'status', label: 'Status', render: formatStatus },
      { key: 'created_at', label: 'Registration Date', render: formatDate },
      { key: 'actions', label: 'Actions', isAction: true }
    ]
  },
  users_4: {
    columns: [
      { key: 'id', label: 'Admin ID' },
      { key: 'name', label: 'Admin Name' },
      { key: 'email', label: 'Email' },
      { key: 'phone', label: 'Phone' },
      { key: 'status', label: 'Status', render: formatStatus },
      { key: 'created_at', label: 'Registration Date', render: formatDate },
      { key: 'actions', label: 'Actions', isAction: true }
    ]
  },
  patients: {
    columns: [
      { key: 'user_id', label: 'User ID' },
      { key: 'gender', label: 'Gender' },
      { key: 'date_of_birth', label: 'Date of Birth', render: formatDate },
      { key: 'address', label: 'Address' }
    ]
  },
  doctors: {
    columns: [
      { key: 'user_id', label: 'User ID' },
      { key: 'specialization', label: 'Specialization' },
      { key: 'license_number', label: 'License Number' },
      { key: 'available', label: 'Available', render: formatBoolean }
    ]
  },
  hospitals: {
    columns: [
      { key: 'user_id', label: 'User ID' },
      { key: 'hospital_name', label: 'Hospital Name' },
      { key: 'registration_number', label: 'Registration Number' },
      { key: 'location', label: 'Location' }
    ]
  },
  admins: {
    columns: [
      { key: 'user_id', label: 'User ID' },
      { key: 'role', label: 'Admin Role' },
      { key: 'department', label: 'Department' }
    ]
  },
  community: {
    columns: [
      { key: 'id', label: 'Community ID' },
      { key: 'name', label: 'Community Name' },
      { key: 'description', label: 'Status' },
      { key: 'photo', label: 'Photo' },
      { key: 'creator_name', label: 'Creator' },
      { key: 'actions', label: 'Actions', isAction: true }
    ]
  },
  appointments: {
    columns: [
      { key: 'id', label: 'ID' },
      { key: 'patient_id', label: 'Patient ID' },
      { key: 'doctor_id', label: 'Doctor ID' },
      { key: 'notes', label: 'Notes' },
      { key: 'phone', label: 'Phone' },
      { key: 'email', label: 'Email' },
      { key: 'timeslot', label: 'Appointment Time' },
      { key: 'actions', label: 'Actions', isAction: true }
    ]
  },
  posts: {
    columns: [
      { key: 'id', label: 'ID' },
      { key: 'post_creator', label: 'Creator ID' },
      { key: 'community_id', label: 'Community ID' },
      { key: 'title', label: 'Title' },
      { key: 'content', label: 'Content' },
      { key: 'created_at', label: 'Created', render: formatDate },
      { key: 'actions', label: 'Actions', isAction: true }
    ]
  },
  medications: {
    columns: [
      { key: 'id', label: 'ID' },
      { key: 'user_id', label: 'User ID' },
      { key: 'medication_name', label: 'Medication Name' },
      { key: 'dosage', label: 'Dosage' },
      { key: 'frequency', label: 'Frequency' },
      { key: 'start_date', label: 'Start Date', render: formatDate },
      { key: 'end_date', label: 'End Date', render: formatDate },
      { key: 'actions', label: 'Actions', isAction: true }
    ]
  },
  feedback: {
    columns: [
      { key: 'id', label: 'ID' },
      { key: 'patient_id', label: 'Patient ID' },
      { key: 'doctor_id', label: 'Doctor ID' },
      { key: 'rating', label: 'Rating' },
      { key: 'comment', label: 'Comment' },
      { key: 'created_at', label: 'Created', render: formatDate },
      { key: 'actions', label: 'Actions', isAction: true }
    ]
  }
};

const AdminPage = () => {
  const [databaseData, setDatabaseData] = useState({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [retryAttempt, setRetryAttempt] = useState(0);
  const [activeSection, setActiveSection] = useState('users');
  const [searchTerm, setSearchTerm] = useState('');
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [userInfo, setUserInfo] = useState(null);
  const [actionLoading, setActionLoading] = useState({});

  useEffect(() => {
    checkAuthentication();
    fetchDatabaseData();
  }, []);

  // Calculate counts for each filter role
  const getCountForRole = useCallback((roleId) => {
    if (!databaseData.users) return 0;
    return databaseData.users.filter(user => user.role_id == roleId).length;
  }, [databaseData.users]);

  // Calculate counts when data changes
  useEffect(() => {
    if (databaseData.users) {
      const patientsCount = getCountForRole(1);
      const doctorsCount = getCountForRole(2);
      const hospitalsCount = getCountForRole(3);
      const adminsCount = getCountForRole(4);
      
      // Update counts in databaseData
      setDatabaseData(prev => ({
        ...prev,
        [`users_1_count`]: patientsCount,
        [`users_2_count`]: doctorsCount,
        [`users_3_count`]: hospitalsCount,
        [`users_4_count`]: adminsCount,
      }));
    }
  }, [databaseData.users, getCountForRole]);

  const checkAuthentication = async () => {
    try {
      const response = await fetch('http://localhost/MediAI-main/check_admin_auth.php', {
        method: 'GET',
        credentials: 'include'
      });
      
      if (response.ok) {
        const authData = await response.json();
        if (authData.authenticated && authData.role_id === 4) {
          setIsAuthenticated(true);
          setUserInfo(authData.user);
        } else {
          window.location.href = 'http://localhost/MediAI-main/login.php';
        }
      } else {
        window.location.href = 'http://localhost/MediAI-main/login.php';
      }
    } catch (error) {
      console.error('Auth check failed:', error);
      window.location.href = 'http://localhost/MediAI-main/login.php';
    }
  };

  const handleLogout = () => {
    fetch('http://localhost/MediAI-main/logout.php', {
      method: 'POST',
      credentials: 'include'
    }).then(() => {
      window.location.href = 'http://localhost/MediAI-main/login.php';
    });
  };

  const fetchDatabaseData = async () => {
    try {
      setLoading(true);
      const response = await fetch('http://localhost/MediAI-main/get_admin_data.php', {
        method: 'GET',
        credentials: 'include',
        mode: 'cors',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      setDatabaseData(data);
      setError(null);
      
      // Log counts for debugging
      console.log('Users data loaded:', data.users?.length);
      if (data.users) {
        console.log('Role counts:', {
          patients: data.users.filter(u => u.role_id == 1).length,
          doctors: data.users.filter(u => u.role_id == 2).length,
          hospitals: data.users.filter(u => u.role_id == 3).length,
          admins: data.users.filter(u => u.role_id == 4).length
        });
      }
    } catch (err) {
      if (err.message.includes('401')) {
        window.location.href = 'http://localhost/MediAI-main/login.php';
        return;
      }
      setError('Failed to fetch database data: ' + err.message);
      console.error('Error fetching data:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleRetry = () => {
    setRetryAttempt(prev => prev + 1);
    fetchDatabaseData();
  };

  const handleDelete = async (id, table, recordName) => {
    if (!window.confirm(`Are you sure you want to PERMANENTLY DELETE this ${recordName}? This action cannot be undone!`)) {
      return;
    }

    const actionKey = `delete_${table}_${id}`;
    setActionLoading(prev => ({ ...prev, [actionKey]: true }));

    try {
      const response = await fetch('http://localhost/MediAI-main/admin_delete.php', {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          id: id,
          table: table
        })
      });

      if (response.ok) {
        const result = await response.json();
        if (result.success) {
          alert(`✅ ${recordName} permanently deleted successfully!`);
          fetchDatabaseData(); // Refresh data
        } else {
          alert(`❌ Error: ${result.message}`);
        }
      } else {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
    } catch (error) {
      console.error('Delete error:', error);
      alert(`❌ Failed to delete ${recordName}: ${error.message}`);
    } finally {
      setActionLoading(prev => ({ ...prev, [actionKey]: false }));
    }
  };

  const handleBlock = async (id, table, recordName, currentStatus) => {
    const action = currentStatus === 'authorized' ? 'block' : 'unblock';
    const actionText = action === 'block' ? 'BLOCK' : 'UNBLOCK';
    
    if (!window.confirm(`Are you sure you want to ${actionText} this ${recordName}?`)) {
      return;
    }

    const actionKey = `block_${table}_${id}`;
    setActionLoading(prev => ({ ...prev, [actionKey]: true }));

    try {
      const response = await fetch('http://localhost/MediAI-main/admin_block.php', {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          id: id,
          table: table,
          action: action
        })
      });

      if (response.ok) {
        const result = await response.json();
        if (result.success) {
          alert(`✅ ${recordName} ${action}ed successfully!`);
          fetchDatabaseData(); // Refresh data
        } else {
          alert(`❌ Error: ${result.message}`);
        }
      } else {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
    } catch (error) {
      console.error('Block error:', error);
      alert(`❌ Failed to ${action} ${recordName}: ${error.message}`);
    } finally {
      setActionLoading(prev => ({ ...prev, [actionKey]: false }));
    }
  };

  const handleSectionClick = async (section) => {
    setActiveSection(section.key);
    
    // If it has filterRole, fetch filtered data
    if (section.filterRole) {
      try {
        const response = await fetch('http://localhost/MediAI-main/get_admin_data.php', {
          method: 'GET',
          credentials: 'include',
          mode: 'cors',
          headers: {
            'Content-Type': 'application/json',
          }
        });
        
        if (response.ok) {
          const allData = await response.json();
          // Filter users by role_id on client side
          const filteredUsers = allData.users.filter(user => user.role_id == section.filterRole);
          setDatabaseData(prev => ({
            ...prev,
            [`users_${section.filterRole}`]: filteredUsers
          }));
        }
      } catch (error) {
        console.error('Error fetching filtered data:', error);
      }
    }
  };

  const renderTable = (data, title, columns) => {
    if (!data || data.length === 0) {
      return (
        <div className="table-container">
          <h3>{title}</h3>
          <p>No data available</p>
        </div>
      );
    }

    const filteredData = data.filter(row => {
      return columns.some(col => {
        const value = row[col.key];
        return value && value.toString().toLowerCase().includes(searchTerm.toLowerCase());
      });
    });

    return (
      <div className="table-container">
        <h3>{title} ({filteredData.length} records)</h3>
        <div className="table-wrapper">
          <table className="data-table">
            <thead>
              <tr>
                {columns.map((col, index) => (
                  <th key={index}>{col.label}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {filteredData.map((row, rowIndex) => (
                <tr key={rowIndex}>
                  {columns.map((col, colIndex) => (
                    <td key={colIndex}>
                      {col.isAction ? (
                        <div className="action-buttons">
                          <button
                            className={`action-btn block-btn ${row.status === 'authorized' ? 'block' : 'unblock'}`}
                            onClick={() => handleBlock(row.id, activeSection, row.name || `Record ${row.id}`, row.status)}
                            disabled={actionLoading[`block_${activeSection}_${row.id}`]}
                          >
                            {actionLoading[`block_${activeSection}_${row.id}`] ? '⏳' : (row.status === 'authorized' ? '🚫 Block' : '✅ Unblock')}
                          </button>
                          <button
                            className="action-btn delete-btn"
                            onClick={() => handleDelete(row.id, activeSection, row.name || `Record ${row.id}`)}
                            disabled={actionLoading[`delete_${activeSection}_${row.id}`]}
                          >
                            {actionLoading[`delete_${activeSection}_${row.id}`] ? '⏳' : '🗑️ Delete'}
                          </button>
                        </div>
                      ) : (
                        col.render ? col.render(row[col.key]) : row[col.key]
                      )}
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    );
  };

  if (loading) {
    return (
      <div className="admin-page">
        <div className="loading">
          <div className="spinner"></div>
          <p>Loading database data...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="admin-page">
        <div className="admin-container">
          <div className="error">
            <h2>❌ Error</h2>
            <p>{error}</p>
            <button onClick={handleRetry} className="retry-btn">
              Try Again
            </button>
            <p style={{marginTop: '20px', fontSize: '0.9rem', opacity: 0.8}}>
              Attempt: {retryAttempt + 1}
            </p>
          </div>
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return (
      <div className="admin-page">
        <div className="loading">
          <div className="spinner"></div>
          <p>Verifying admin access...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="admin-page">
      <div className="admin-container">
        <div className="admin-header">
          <div className="header-content">
            <div>
              <h1>🔐 MediAI Database Admin Panel</h1>
              <p>Complete database overview and management</p>
            </div>
            <div className="user-info">
              <span className="welcome-text">Welcome, {userInfo?.name}</span>
              <button onClick={handleLogout} className="logout-btn">
                🚪 Logout
              </button>
            </div>
          </div>
        </div>

      <div className="admin-controls">
        <div className="search-container">
          <input
            type="text"
            placeholder="Search across all tables..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="search-input"
          />
        </div>
        
        <button onClick={handleRetry} className="refresh-btn">
          🔄 Refresh Data
        </button>
      </div>

      <div className="admin-content">
        <div className="sidebar">
          <h3>📊 Database Sections</h3>
          <nav className="nav-menu">
            {sections.map(section => {
              const count = section.filterRole ? 
                (databaseData.users ? databaseData.users.filter(user => user.role_id == section.filterRole).length : 0) :
                (databaseData[section.key] ? databaseData[section.key].length : 0);
                
              return (
                <button
                  key={section.key}
                  className={`nav-button ${activeSection === section.key ? 'active' : ''}`}
                  onClick={() => handleSectionClick(section)}
                >
                  <span className="nav-icon">{section.icon}</span>
                  <span className="nav-label">{section.label}</span>
                  <span className="nav-count">{count}</span>
                </button>
              );
            })}
          </nav>
        </div>

        <div className="main-content">
          {activeSection && (
            (() => {
              const section = sections.find(s => s.key === activeSection);
              let dataToShow = databaseData[activeSection];
              
              // For filtered user data
              if (section?.filterRole) {
                dataToShow = databaseData[`users_${section.filterRole}`];
              }
              
              if (dataToShow) {
                // Determine which table config to use
                let configKey = section?.key || 'users';
                if (section?.filterRole) {
                  configKey = `users_${section.filterRole}`;
                }
                
                return renderTable(
                  dataToShow,
                  section?.label || activeSection,
                  tableConfigs[configKey]?.columns || tableConfigs.users.columns
                );
              }
              
              return (
                <div className="table-container">
                  <div className="loading">
                    <h3>Select a table to view data</h3>
                    <p>Click on any section from the sidebar to load the data</p>
                  </div>
                </div>
              );
            })()
          )}
        </div>
      </div>
      </div>
    </div>
  );
};

export default AdminPage;