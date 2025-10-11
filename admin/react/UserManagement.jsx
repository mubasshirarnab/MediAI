(function(){
  const { useState, useEffect } = React;
  
  function UserManagement() {
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [pagination, setPagination] = useState({ page: 1, limit: 10, total: 0, pages: 0 });
    const [editingUser, setEditingUser] = useState(null);
    const [showEditForm, setShowEditForm] = useState(false);
    const [deletingUserId, setDeletingUserId] = useState(null);
    const [searchTerm, setSearchTerm] = useState('');
    const [roleFilter, setRoleFilter] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [blockedFilter, setBlockedFilter] = useState('');
    const [selectedUser, setSelectedUser] = useState(null);
    const [showUserDetails, setShowUserDetails] = useState(false);

    useEffect(() => {
      loadUsers();
    }, [pagination.page, searchTerm, roleFilter, statusFilter, blockedFilter]);

    const loadUsers = async () => {
      try {
        setLoading(true);
        const params = new URLSearchParams({
          action: 'users_list',
          page: pagination.page,
          limit: pagination.limit
        });
        
        if (searchTerm) params.append('search', searchTerm);
        if (roleFilter) params.append('role', roleFilter);
        if (statusFilter) params.append('status', statusFilter);
        if (blockedFilter) params.append('blocked', blockedFilter);
        
        const response = await axios.get(`admin_api.php?${params.toString()}`);
        if (response.data.success) {
          setUsers(response.data.data);
          setPagination(response.data.pagination);
        } else {
          setError(response.data.error || 'Failed to load users');
        }
      } catch (err) {
        setError('Failed to load users');
      } finally {
        setLoading(false);
      }
    };

    const handleEditUser = (user) => {
      setEditingUser(user);
      setShowEditForm(true);
    };

    const handleUpdateUser = async (userData) => {
      try {
        setError('');
        setSuccess('');
        const response = await axios.post('admin_api.php?action=user_update', userData);
        if (response.data.success) {
          setSuccess('User updated successfully');
          setShowEditForm(false);
          setEditingUser(null);
          loadUsers();
        } else {
          setError(response.data.error || 'Failed to update user');
        }
      } catch (err) {
        setError('Failed to update user');
      }
    };

    const handleDeleteUser = async (userId) => {
      const user = users.find(u => u.id === userId);
      const userName = user ? user.username : 'this user';
      
      if (!confirm(`Are you sure you want to delete ${userName}? This action cannot be undone and will remove all associated data including appointments, bookings, posts, and comments.`)) return;
      
      try {
        setDeletingUserId(userId);
        setError('');
        setSuccess('');
        const response = await axios.post('admin_api.php?action=user_delete', { user_id: userId });
        if (response.data.success) {
          setSuccess('User deleted successfully');
          loadUsers();
        } else {
          setError(response.data.error || 'Failed to delete user');
        }
      } catch (err) {
        setError('Failed to delete user');
      } finally {
        setDeletingUserId(null);
      }
    };

    const handleBlockUser = async (userId, action) => {
      try {
        setError('');
        setSuccess('');
        const response = await axios.post('admin_api.php?action=user_block', { 
          user_id: userId, 
          action: action 
        });
        if (response.data.success) {
          setSuccess(`User ${action}ed successfully`);
          loadUsers();
        } else {
          setError(response.data.error || `Failed to ${action} user`);
        }
      } catch (err) {
        setError(`Failed to ${action} user`);
      }
    };

    const clearFilters = () => {
      setSearchTerm('');
      setRoleFilter('');
      setStatusFilter('');
      setBlockedFilter('');
      setPagination(prev => ({ ...prev, page: 1 }));
    };

    const handleViewUserDetails = (user) => {
      setSelectedUser(user);
      setShowUserDetails(true);
    };

    const UserDetailsModal = ({ user, onClose }) => {
      return React.createElement('div', { 
        key: 'modal-overlay',
        className: 'modal-overlay',
        style: {
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          backgroundColor: 'rgba(0,0,0,0.5)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          zIndex: 1000
        },
        onClick: onClose
      }, [
        React.createElement('div', {
          key: 'modal-content',
          className: 'modal-content',
          style: {
            backgroundColor: 'white',
            padding: '20px',
            borderRadius: '8px',
            maxWidth: '600px',
            width: '90%',
            maxHeight: '80vh',
            overflow: 'auto'
          },
          onClick: (e) => e.stopPropagation()
        }, [
          React.createElement('div', { key: 'modal-header', style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' } }, [
            React.createElement('h3', { key: 'title' }, 'User Details'),
            React.createElement('button', { key: 'close', onClick: onClose, style: { background: 'none', border: 'none', fontSize: '24px', cursor: 'pointer' } }, '×')
          ]),
          
          React.createElement('div', { key: 'user-info' }, [
            React.createElement('div', { key: 'row-1', className: 'row', style: { marginBottom: '15px' } }, [
              React.createElement('div', { key: 'col-1', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'User ID: '),
                React.createElement('span', { key: 'value' }, user.id)
              ]),
              React.createElement('div', { key: 'col-2', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'Username: '),
                React.createElement('span', { key: 'value' }, user.username)
              ])
            ]),
            
            React.createElement('div', { key: 'row-2', className: 'row', style: { marginBottom: '15px' } }, [
              React.createElement('div', { key: 'col-1', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'Email: '),
                React.createElement('span', { key: 'value' }, user.email)
              ]),
              React.createElement('div', { key: 'col-2', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'Role: '),
                React.createElement('span', { key: 'value', className: 'badge badge-primary' }, user.role)
              ])
            ]),
            
            React.createElement('div', { key: 'row-3', className: 'row', style: { marginBottom: '15px' } }, [
              React.createElement('div', { key: 'col-1', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'Email Verified: '),
                React.createElement('span', { 
                  key: 'value',
                  className: user.is_verified === 'authorized' ? 'status-badge status-verified' : 'status-badge status-unverified'
                }, user.is_verified === 'authorized' ? 'Yes' : 'No')
              ]),
              React.createElement('div', { key: 'col-2', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'Block Status: '),
                React.createElement('span', { 
                  key: 'value',
                  className: user.is_blocked == 1 ? 'status-badge status-blocked' : 'status-badge status-active'
                }, user.is_blocked == 1 ? 'Blocked' : 'Active')
              ])
            ]),
            
            React.createElement('div', { key: 'row-4', className: 'row', style: { marginBottom: '15px' } }, [
              React.createElement('div', { key: 'col-1', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'Created: '),
                React.createElement('span', { key: 'value' }, new Date(user.created_at).toLocaleString())
              ]),
              React.createElement('div', { key: 'col-2', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'Account Status: '),
                React.createElement('span', { 
                  key: 'value',
                  className: user.is_verified === 'authorized' ? 'status-badge status-verified' : 'status-badge status-unverified'
                }, user.is_verified === 'authorized' ? 'Active' : 'Inactive')
              ])
            ])
          ])
        ])
      ]);
    };

    const EditUserForm = ({ user, onSave, onCancel }) => {
      const [formData, setFormData] = useState({
        user_id: user.id,
        role: user.role,
        is_verified: user.is_verified
      });

      const handleSubmit = (e) => {
        e.preventDefault();
        onSave(formData);
      };

      return (
        React.createElement('div', { className: 'card' }, [
          React.createElement('h3', { key: 'title' }, 'Edit User'),
          React.createElement('form', { key: 'form', onSubmit: handleSubmit }, [
            React.createElement('div', { key: 'role', className: 'form-group' }, [
              React.createElement('label', { key: 'label' }, 'Role'),
              React.createElement('select', {
                key: 'select',
                value: formData.role,
                onChange: (e) => setFormData({...formData, role: e.target.value})
              }, [
                React.createElement('option', { key: 'patient', value: 'patient' }, 'Patient'),
                React.createElement('option', { key: 'doctor', value: 'doctor' }, 'Doctor'),
                React.createElement('option', { key: 'hospital', value: 'hospital' }, 'Hospital'),
                React.createElement('option', { key: 'admin', value: 'admin' }, 'Admin')
              ])
            ]),
            React.createElement('div', { key: 'verified', className: 'form-group' }, [
              React.createElement('label', { key: 'label' }, 'Email Verified'),
              React.createElement('select', {
                key: 'select',
                value: formData.is_verified,
                onChange: (e) => setFormData({...formData, is_verified: parseInt(e.target.value)})
              }, [
                React.createElement('option', { key: '0', value: '0' }, 'Not Verified'),
                React.createElement('option', { key: '1', value: '1' }, 'Verified')
              ])
            ]),
            React.createElement('div', { key: 'buttons', className: 'row' }, [
              React.createElement('button', { key: 'save', type: 'submit', className: 'btn btn-success' }, 'Save'),
              React.createElement('button', { key: 'cancel', type: 'button', className: 'btn', onClick: onCancel }, 'Cancel')
            ])
          ])
        ])
      );
    };

    return (
      React.createElement('div', null, [
        React.createElement('div', { key: 'header', className: 'card' }, [
          React.createElement('h3', { key: 'title' }, 'User Management'),
          React.createElement('p', { key: 'desc' }, 'Manage user accounts, roles, and verification status')
        ]),
        
        // Search and Filter Section
        React.createElement('div', { key: 'filters', className: 'card', style: { marginBottom: '20px' } }, [
          React.createElement('h4', { key: 'filter-title', style: { marginBottom: '15px' } }, 'Search & Filter Users'),
          
          // Search Row
          React.createElement('div', { key: 'search-row', className: 'row', style: { marginBottom: '15px' } }, [
            React.createElement('div', { key: 'search-col', className: 'col-md-8' }, [
              React.createElement('label', { key: 'search-label', style: { marginBottom: '5px', display: 'block', fontWeight: 'bold' } }, 'Search Users:'),
              React.createElement('input', {
                key: 'search-input',
                type: 'text',
                className: 'form-control',
                placeholder: 'Search by name, email...',
                value: searchTerm,
                onChange: (e) => setSearchTerm(e.target.value)
              })
            ]),
            React.createElement('div', { key: 'clear-col', className: 'col-md-4', style: { display: 'flex', alignItems: 'end' } }, [
              React.createElement('button', {
                key: 'clear-btn',
                className: 'btn btn-secondary',
                onClick: clearFilters,
                style: { marginLeft: '10px' }
              }, 'Clear Filters')
            ])
          ]),
          
          // Filter Row
          React.createElement('div', { key: 'filter-row', className: 'row' }, [
            React.createElement('div', { key: 'role-col', className: 'col-md-3' }, [
              React.createElement('label', { key: 'role-label', style: { marginBottom: '5px', display: 'block', fontWeight: 'bold' } }, 'Filter by Role:'),
              React.createElement('select', {
                key: 'role-select',
                className: 'form-control',
                value: roleFilter,
                onChange: (e) => setRoleFilter(e.target.value)
              }, [
                React.createElement('option', { key: 'all-roles', value: '' }, 'All Roles'),
                React.createElement('option', { key: 'patient', value: 'patient' }, 'Patient'),
                React.createElement('option', { key: 'doctor', value: 'doctor' }, 'Doctor'),
                React.createElement('option', { key: 'hospital', value: 'hospital' }, 'Hospital'),
                React.createElement('option', { key: 'admin', value: 'admin' }, 'Admin')
              ])
            ]),
            
            React.createElement('div', { key: 'status-col', className: 'col-md-3' }, [
              React.createElement('label', { key: 'status-label', style: { marginBottom: '5px', display: 'block', fontWeight: 'bold' } }, 'Filter by Status:'),
              React.createElement('select', {
                key: 'status-select',
                className: 'form-control',
                value: statusFilter,
                onChange: (e) => setStatusFilter(e.target.value)
              }, [
                React.createElement('option', { key: 'all-status', value: '' }, 'All Status'),
                React.createElement('option', { key: 'authorized', value: 'authorized' }, 'Authorized'),
                React.createElement('option', { key: 'unauthorized', value: 'unauthorized' }, 'Unauthorized')
              ])
            ]),
            
            React.createElement('div', { key: 'blocked-col', className: 'col-md-3' }, [
              React.createElement('label', { key: 'blocked-label', style: { marginBottom: '5px', display: 'block', fontWeight: 'bold' } }, 'Filter by Block Status:'),
              React.createElement('select', {
                key: 'blocked-select',
                className: 'form-control',
                value: blockedFilter,
                onChange: (e) => setBlockedFilter(e.target.value)
              }, [
                React.createElement('option', { key: 'all-blocked', value: '' }, 'All Users'),
                React.createElement('option', { key: 'unblocked', value: '0' }, 'Unblocked'),
                React.createElement('option', { key: 'blocked', value: '1' }, 'Blocked')
              ])
            ]),
            
            React.createElement('div', { key: 'results-col', className: 'col-md-3', style: { display: 'flex', alignItems: 'end' } }, [
              React.createElement('div', { key: 'results-info', style: { fontSize: '14px', color: '#666' } }, 
                `Showing ${users.length} of ${pagination.total} users`)
            ])
          ])
        ]),
        
        error ? React.createElement('div', { key: 'error', className: 'error' }, error) : null,
        success ? React.createElement('div', { key: 'success', className: 'success' }, success) : null,

        showEditForm ? React.createElement(EditUserForm, {
          key: 'edit-form',
          user: editingUser,
          onSave: handleUpdateUser,
          onCancel: () => {
            setShowEditForm(false);
            setEditingUser(null);
          }
        }) : null,
        
        showUserDetails ? React.createElement(UserDetailsModal, {
          key: 'user-details-modal',
          user: selectedUser,
          onClose: () => setShowUserDetails(false)
        }) : null,

        React.createElement('div', { key: 'table', className: 'card' }, [
          React.createElement('table', { key: 'users-table' }, [
            React.createElement('thead', { key: 'head' }, 
              React.createElement('tr', { key: 'row' }, [
                React.createElement('th', { key: 'id' }, 'ID'),
                React.createElement('th', { key: 'username' }, 'Username'),
                React.createElement('th', { key: 'email' }, 'Email'),
                React.createElement('th', { key: 'role' }, 'Role'),
                React.createElement('th', { key: 'verified' }, 'Verified'),
                React.createElement('th', { key: 'blocked' }, 'Blocked'),
                React.createElement('th', { key: 'created' }, 'Created'),
                React.createElement('th', { key: 'actions' }, 'Actions')
              ])
            ),
            React.createElement('tbody', { key: 'body' },
              loading ? 
                React.createElement('tr', { key: 'loading' }, 
                  React.createElement('td', { colSpan: 7, style: { textAlign: 'center' } }, 'Loading...')
                ) :
                users.map(user => 
                  React.createElement('tr', { key: user.id }, [
                    React.createElement('td', { key: 'id' }, user.id),
                    React.createElement('td', { key: 'username' }, user.username),
                    React.createElement('td', { key: 'email' }, user.email),
                    React.createElement('td', { key: 'role' }, user.role),
                React.createElement('td', { key: 'verified' }, user.is_verified === 'authorized' ? 'Yes' : 'No'),
                React.createElement('td', { key: 'blocked' }, 
                  React.createElement('span', { 
                    className: user.is_blocked == 1 ? 'status-badge status-blocked' : 'status-badge status-active'
                  }, user.is_blocked == 1 ? 'Blocked' : 'Active')
                ),
                React.createElement('td', { key: 'created' }, new Date(user.created_at).toLocaleDateString()),
                React.createElement('td', { key: 'actions' }, [
                  React.createElement('button', {
                    key: 'view',
                    className: 'btn btn-info',
                    style: { marginRight: '5px' },
                    onClick: () => handleViewUserDetails(user)
                  }, 'View'),
                  React.createElement('button', {
                    key: 'edit',
                    className: 'btn btn-warning',
                    style: { marginRight: '5px' },
                    onClick: () => handleEditUser(user)
                  }, 'Edit'),
                  user.is_blocked == 0 ? 
                    React.createElement('button', {
                      key: 'block',
                      className: 'btn btn-danger',
                      style: { marginRight: '5px' },
                      onClick: () => handleBlockUser(user.id, 'block')
                    }, 'Block') :
                    React.createElement('button', {
                      key: 'unblock',
                      className: 'btn btn-success',
                      style: { marginRight: '5px' },
                      onClick: () => handleBlockUser(user.id, 'unblock')
                    }, 'Unblock'),
                  React.createElement('button', {
                    key: 'delete',
                    className: 'btn btn-danger',
                    disabled: deletingUserId === user.id,
                    onClick: () => handleDeleteUser(user.id)
                  }, deletingUserId === user.id ? 'Deleting...' : 'Delete')
                ])
                  ])
                )
            )
          ])
        ]),

        pagination.pages > 1 ? React.createElement('div', { key: 'pagination', className: 'card' }, [
          React.createElement('div', { key: 'nav', className: 'row' }, [
            React.createElement('button', {
              key: 'prev',
              className: 'btn',
              disabled: pagination.page <= 1,
              onClick: () => setPagination({...pagination, page: pagination.page - 1})
            }, 'Previous'),
            React.createElement('span', { key: 'info', style: { margin: '0 10px', alignSelf: 'center' } }, 
              `Page ${pagination.page} of ${pagination.pages}`
            ),
            React.createElement('button', {
              key: 'next',
              className: 'btn',
              disabled: pagination.page >= pagination.pages,
              onClick: () => setPagination({...pagination, page: pagination.page + 1})
            }, 'Next')
          ])
        ]) : null
      ])
    );
  }
  
  window.Components = window.Components || {};
  window.Components.UserManagement = UserManagement;
})();
