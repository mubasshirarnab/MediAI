(function(){
  const { useState, useEffect } = React;
  
  function HospitalManagement() {
    const [hospitals, setHospitals] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    useEffect(() => {
      loadHospitals();
    }, []);

    const loadHospitals = async () => {
      try {
        setLoading(true);
        const response = await axios.get('admin_api.php?action=hospitals_list');
        if (response.data.success) {
          setHospitals(response.data.data);
        } else {
          setError(response.data.error || 'Failed to load hospitals');
        }
      } catch (err) {
        setError('Failed to load hospitals');
      } finally {
        setLoading(false);
      }
    };

    return (
      React.createElement('div', null, [
        React.createElement('div', { key: 'header', className: 'card' }, [
          React.createElement('h3', { key: 'title' }, 'Hospital Management'),
          React.createElement('p', { key: 'desc' }, 'View and manage registered hospitals')
        ]),
        
        error ? React.createElement('div', { key: 'error', className: 'error' }, error) : null,
        success ? React.createElement('div', { key: 'success', className: 'success' }, success) : null,

        React.createElement('div', { key: 'table', className: 'card' }, [
          React.createElement('table', { key: 'hospitals-table' }, [
            React.createElement('thead', { key: 'head' }, 
              React.createElement('tr', { key: 'row' }, [
                React.createElement('th', { key: 'id' }, 'ID'),
                React.createElement('th', { key: 'name' }, 'Hospital Name'),
                React.createElement('th', { key: 'username' }, 'Username'),
                React.createElement('th', { key: 'email' }, 'Email'),
                React.createElement('th', { key: 'address' }, 'Address'),
                React.createElement('th', { key: 'phone' }, 'Phone'),
                React.createElement('th', { key: 'verified' }, 'Verified'),
                React.createElement('th', { key: 'created' }, 'Created')
              ])
            ),
            React.createElement('tbody', { key: 'body' },
              loading ? 
                React.createElement('tr', { key: 'loading' }, 
                  React.createElement('td', { colSpan: 8, style: { textAlign: 'center' } }, 'Loading...')
                ) :
                hospitals.map(hospital => 
                  React.createElement('tr', { key: hospital.hospital_id }, [
                    React.createElement('td', { key: 'id' }, hospital.hospital_id),
                    React.createElement('td', { key: 'name' }, hospital.hospital_name),
                    React.createElement('td', { key: 'username' }, hospital.username),
                    React.createElement('td', { key: 'email' }, hospital.email),
                    React.createElement('td', { key: 'address' }, hospital.address || 'N/A'),
                    React.createElement('td', { key: 'phone' }, hospital.phone || 'N/A'),
                    React.createElement('td', { key: 'verified' }, hospital.is_verified ? 'Yes' : 'No'),
                    React.createElement('td', { key: 'created' }, new Date(hospital.created_at).toLocaleDateString())
                  ])
                )
            )
          ])
        ])
      ])
    );
  }
  
  window.Components = window.Components || {};
  window.Components.HospitalManagement = HospitalManagement;
})();
