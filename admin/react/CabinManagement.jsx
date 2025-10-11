(function(){
  const { useState, useEffect } = React;
  
  function CabinManagement() {
    const [bookings, setBookings] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [pagination, setPagination] = useState({ page: 1, limit: 10, total: 0, pages: 0 });

    useEffect(() => {
      loadBookings();
    }, [pagination.page]);

    const loadBookings = async () => {
      try {
        setLoading(true);
        const response = await axios.get(`admin_api.php?action=cabin_bookings_list&page=${pagination.page}&limit=${pagination.limit}`);
        if (response.data.success) {
          setBookings(response.data.data);
          setPagination(response.data.pagination);
        } else {
          setError(response.data.error || 'Failed to load cabin bookings');
        }
      } catch (err) {
        setError('Failed to load cabin bookings');
      } finally {
        setLoading(false);
      }
    };

    const handleStatusChange = async (bookingId, newStatus) => {
      try {
        setError('');
        setSuccess('');
        const response = await axios.post('admin_api.php?action=update_booking_status', {
          booking_id: bookingId,
          status: newStatus
        });
        if (response.data.success) {
          setSuccess('Booking status updated successfully');
          loadBookings();
        } else {
          setError(response.data.error || 'Failed to update booking status');
        }
      } catch (err) {
        setError('Failed to update booking status');
      }
    };

    const getStatusBadge = (status) => {
      const statusClasses = {
        'booked': 'status-badge status-pending',
        'completed': 'status-badge status-verified',
        'cancelled': 'status-badge status-unverified'
      };
      return React.createElement('span', { 
        className: statusClasses[status] || 'status-badge'
      }, status.charAt(0).toUpperCase() + status.slice(1));
    };

    return (
      React.createElement('div', null, [
        React.createElement('div', { key: 'header', className: 'card' }, [
          React.createElement('h3', { key: 'title' }, 'Cabin Booking Management'),
          React.createElement('p', { key: 'desc' }, 'View and manage all cabin bookings')
        ]),
        
        error ? React.createElement('div', { key: 'error', className: 'error' }, error) : null,
        success ? React.createElement('div', { key: 'success', className: 'success' }, success) : null,

        React.createElement('div', { key: 'table', className: 'card' }, [
          React.createElement('table', { key: 'bookings-table' }, [
            React.createElement('thead', { key: 'head' }, 
              React.createElement('tr', { key: 'row' }, [
                React.createElement('th', { key: 'id' }, 'Booking ID'),
                React.createElement('th', { key: 'user' }, 'User'),
                React.createElement('th', { key: 'cabin' }, 'Cabin'),
                React.createElement('th', { key: 'type' }, 'Type'),
                React.createElement('th', { key: 'price' }, 'Price'),
                React.createElement('th', { key: 'checkin' }, 'Check-in'),
                React.createElement('th', { key: 'checkout' }, 'Check-out'),
                React.createElement('th', { key: 'status' }, 'Status'),
                React.createElement('th', { key: 'actions' }, 'Actions')
              ])
            ),
            React.createElement('tbody', { key: 'body' },
              loading ? 
                React.createElement('tr', { key: 'loading' }, 
                  React.createElement('td', { colSpan: 9, style: { textAlign: 'center' } }, 'Loading...')
                ) :
                bookings.map(booking => 
                  React.createElement('tr', { key: booking.booking_id }, [
                    React.createElement('td', { key: 'id' }, booking.booking_id),
                    React.createElement('td', { key: 'user' }, booking.user_name),
                    React.createElement('td', { key: 'cabin' }, booking.cabin_number),
                    React.createElement('td', { key: 'type' }, booking.type),
                    React.createElement('td', { key: 'price' }, `৳${Number(booking.price).toFixed(2)}`),
                    React.createElement('td', { key: 'checkin' }, new Date(booking.check_in).toLocaleDateString()),
                    React.createElement('td', { key: 'checkout' }, new Date(booking.check_out).toLocaleDateString()),
                    React.createElement('td', { key: 'status' }, getStatusBadge(booking.status)),
                    React.createElement('td', { key: 'actions' }, [
                      booking.status === 'booked' ? 
                        React.createElement('button', {
                          key: 'complete',
                          className: 'btn btn-success',
                          style: { marginRight: '5px' },
                          onClick: () => handleStatusChange(booking.booking_id, 'completed')
                        }, 'Complete') : null,
                      booking.status === 'booked' ? 
                        React.createElement('button', {
                          key: 'cancel',
                          className: 'btn btn-danger',
                          onClick: () => handleStatusChange(booking.booking_id, 'cancelled')
                        }, 'Cancel') : null
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
  window.Components.CabinManagement = CabinManagement;
})();
