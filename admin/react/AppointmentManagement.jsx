(function(){
  const { useState, useEffect } = React;
  
  function AppointmentManagement() {
    const [appointments, setAppointments] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [pagination, setPagination] = useState({ page: 1, limit: 10, total: 0, pages: 0 });

    useEffect(() => {
      loadAppointments();
    }, [pagination.page]);

    const loadAppointments = async () => {
      try {
        setLoading(true);
        const response = await axios.get(`admin_api.php?action=appointments_list&page=${pagination.page}&limit=${pagination.limit}`);
        if (response.data.success) {
          setAppointments(response.data.data);
          setPagination(response.data.pagination);
        } else {
          setError(response.data.error || 'Failed to load appointments');
        }
      } catch (err) {
        setError('Failed to load appointments');
      } finally {
        setLoading(false);
      }
    };

    return (
      React.createElement('div', null, [
        React.createElement('div', { key: 'header', className: 'card' }, [
          React.createElement('h3', { key: 'title' }, 'Appointment Management'),
          React.createElement('p', { key: 'desc' }, 'View and manage all patient appointments')
        ]),
        
        error ? React.createElement('div', { key: 'error', className: 'error' }, error) : null,
        success ? React.createElement('div', { key: 'success', className: 'success' }, success) : null,

        React.createElement('div', { key: 'table', className: 'card' }, [
          React.createElement('table', { key: 'appointments-table' }, [
            React.createElement('thead', { key: 'head' }, 
              React.createElement('tr', { key: 'row' }, [
                React.createElement('th', { key: 'id' }, 'ID'),
                React.createElement('th', { key: 'patient' }, 'Patient'),
                React.createElement('th', { key: 'doctor' }, 'Doctor'),
                React.createElement('th', { key: 'hospital' }, 'Hospital'),
                React.createElement('th', { key: 'timeslot' }, 'Appointment Time'),
                React.createElement('th', { key: 'phone' }, 'Phone'),
                React.createElement('th', { key: 'email' }, 'Email'),
                React.createElement('th', { key: 'notes' }, 'Notes')
              ])
            ),
            React.createElement('tbody', { key: 'body' },
              loading ? 
                React.createElement('tr', { key: 'loading' }, 
                  React.createElement('td', { colSpan: 8, style: { textAlign: 'center' } }, 'Loading...')
                ) :
                appointments.map(appointment => 
                  React.createElement('tr', { key: appointment.id }, [
                    React.createElement('td', { key: 'id' }, appointment.id),
                    React.createElement('td', { key: 'patient' }, appointment.patient_name || 'N/A'),
                    React.createElement('td', { key: 'doctor' }, appointment.doctor_name || 'N/A'),
                    React.createElement('td', { key: 'hospital' }, appointment.hospital_name || 'N/A'),
                    React.createElement('td', { key: 'timeslot' }, appointment.timeslot ? new Date(appointment.timeslot).toLocaleString() : 'N/A'),
                    React.createElement('td', { key: 'phone' }, appointment.phone || 'N/A'),
                    React.createElement('td', { key: 'email' }, appointment.email || 'N/A'),
                    React.createElement('td', { key: 'notes' }, appointment.notes || 'N/A')
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
  window.Components.AppointmentManagement = AppointmentManagement;
})();
