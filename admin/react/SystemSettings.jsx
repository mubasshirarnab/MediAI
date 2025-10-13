(function(){
  const { useState, useEffect } = React;
  
  function SystemSettings() {
    const [settings, setSettings] = useState({});
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    useEffect(() => {
      loadSettings();
    }, []);

    const loadSettings = async () => {
      try {
        setLoading(true);
        const response = await axios.get('admin_api.php?action=system_settings');
        if (response.data.success) {
          setSettings(response.data.data);
        } else {
          setError(response.data.error || 'Failed to load settings');
        }
      } catch (err) {
        setError('Failed to load settings');
      } finally {
        setLoading(false);
      }
    };

    const handleSaveSettings = async () => {
      try {
        setError('');
        setSuccess('');
        const response = await axios.post('admin_api.php?action=update_settings', settings);
        if (response.data.success) {
          setSuccess('Settings saved successfully');
        } else {
          setError(response.data.error || 'Failed to save settings');
        }
      } catch (err) {
        setError('Failed to save settings');
      }
    };

    const handleSettingChange = (key, value) => {
      setSettings(prev => ({
        ...prev,
        [key]: value
      }));
    };

    return (
      React.createElement('div', null, [
        React.createElement('div', { key: 'header', className: 'card' }, [
          React.createElement('h3', { key: 'title' }, 'System Settings'),
          React.createElement('p', { key: 'desc' }, 'Configure system-wide settings and preferences')
        ]),
        
        error ? React.createElement('div', { key: 'error', className: 'error' }, error) : null,
        success ? React.createElement('div', { key: 'success', className: 'success' }, success) : null,

        loading ? 
          React.createElement('div', { key: 'loading', className: 'card' }, 'Loading settings...') :
          React.createElement('div', { key: 'form', className: 'card' }, [
            React.createElement('h4', { key: 'title' }, 'General Settings'),
            
            React.createElement('div', { key: 'site-name', className: 'form-group' }, [
              React.createElement('label', { key: 'label' }, 'Site Name'),
              React.createElement('input', {
                key: 'input',
                type: 'text',
                value: settings.site_name || '',
                onChange: (e) => handleSettingChange('site_name', e.target.value)
              })
            ]),

            React.createElement('div', { key: 'maintenance', className: 'form-group' }, [
              React.createElement('label', { key: 'label' }, 'Maintenance Mode'),
              React.createElement('select', {
                key: 'select',
                value: settings.maintenance_mode ? '1' : '0',
                onChange: (e) => handleSettingChange('maintenance_mode', e.target.value === '1')
              }, [
                React.createElement('option', { key: '0', value: '0' }, 'Disabled'),
                React.createElement('option', { key: '1', value: '1' }, 'Enabled')
              ])
            ]),

            React.createElement('div', { key: 'registration', className: 'form-group' }, [
              React.createElement('label', { key: 'label' }, 'User Registration'),
              React.createElement('select', {
                key: 'select',
                value: settings.registration_enabled ? '1' : '0',
                onChange: (e) => handleSettingChange('registration_enabled', e.target.value === '1')
              }, [
                React.createElement('option', { key: '1', value: '1' }, 'Enabled'),
                React.createElement('option', { key: '0', value: '0' }, 'Disabled')
              ])
            ]),

            React.createElement('div', { key: 'email-verification', className: 'form-group' }, [
              React.createElement('label', { key: 'label' }, 'Email Verification Required'),
              React.createElement('select', {
                key: 'select',
                value: settings.email_verification_required ? '1' : '0',
                onChange: (e) => handleSettingChange('email_verification_required', e.target.value === '1')
              }, [
                React.createElement('option', { key: '1', value: '1' }, 'Required'),
                React.createElement('option', { key: '0', value: '0' }, 'Not Required')
              ])
            ]),

            React.createElement('div', { key: 'buttons', className: 'row' }, [
              React.createElement('button', {
                key: 'save',
                className: 'btn btn-success',
                onClick: handleSaveSettings
              }, 'Save Settings'),
              React.createElement('button', {
                key: 'reset',
                className: 'btn',
                onClick: loadSettings
              }, 'Reset')
            ])
          ])
      ])
    );
  }
  
  window.Components = window.Components || {};
  window.Components.SystemSettings = SystemSettings;
})();
