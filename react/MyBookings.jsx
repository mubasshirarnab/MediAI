(function(){
  const { useState, useEffect } = React;
  function MyBookings() {
    const [rows, setRows] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const api = axios.create({ baseURL: 'cabin_api.php' });

    const load = () => {
      setLoading(true); setError('');
      api.get('', { params: { action: 'my_bookings' }})
        .then(res => { if (res.data.success) setRows(res.data.data); else setError(res.data.error || 'Failed to load'); })
        .catch(() => setError('Failed to load'))
        .finally(() => setLoading(false));
    };

    useEffect(() => { load(); }, []);

    return (
      React.createElement('div', { className:'card' }, [
        React.createElement('div', { key:'hdr', className:'header' }, [
          React.createElement('div', { key:'t', className:'title' }, 'My Bookings'),
          React.createElement('button', { key:'r', className:'btn', onClick: load, disabled: loading }, loading ? 'Loading…' : 'Refresh')
        ]),
        error ? React.createElement('div', { key:'err', className:'error' }, error) : null,
        React.createElement('table', { key:'tbl' }, [
          React.createElement('thead', { key:'h' }, React.createElement('tr', null, [
            React.createElement('th', { key:'id' }, '#'),
            React.createElement('th', { key:'cab' }, 'Cabin'),
            React.createElement('th', { key:'type' }, 'Type'),
            React.createElement('th', { key:'price' }, 'Price'),
            React.createElement('th', { key:'ci' }, 'Check-in'),
            React.createElement('th', { key:'co' }, 'Check-out'),
            React.createElement('th', { key:'st' }, 'Status'),
            React.createElement('th', { key:'bd' }, 'Booked At')
          ])),
          React.createElement('tbody', { key:'b' }, rows.map(r => React.createElement('tr', { key:r.booking_id }, [
            React.createElement('td', { key:'id' }, r.booking_id),
            React.createElement('td', { key:'cab' }, r.cabin_number),
            React.createElement('td', { key:'type' }, r.type),
            React.createElement('td', { key:'price' }, `৳ ${Number(r.price).toFixed(2)}`),
            React.createElement('td', { key:'ci' }, r.check_in),
            React.createElement('td', { key:'co' }, r.check_out),
            React.createElement('td', { key:'st' }, r.status),
            React.createElement('td', { key:'bd' }, r.booking_date)
          ])))
        ])
      ])
    );
  }
  window.Components = window.Components || {};
  window.Components.MyBookings = MyBookings;
})();
