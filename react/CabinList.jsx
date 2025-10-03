(function(){
  const { useState, useEffect } = React;
  function CabinList({ checkIn, checkOut, onSelect }) {
    const [loading, setLoading] = useState(false);
    const [cabins, setCabins] = useState([]);
    const [error, setError] = useState('');
    const api = axios.create({ baseURL: 'cabin_api.php' });

    const load = () => {
      setLoading(true); setError('');
      const params = {};
      if (checkIn && checkOut) { params.check_in = checkIn; params.check_out = checkOut; }
      api.get('', { params: { action: 'available_cabins', ...params }})
        .then(res => { if (res.data.success) setCabins(res.data.data); else setError(res.data.error || 'Failed to load'); })
        .catch(() => setError('Failed to load'))
        .finally(() => setLoading(false));
    };

    useEffect(() => { load(); }, [checkIn, checkOut]);

    return (
      React.createElement('div', { className:'card' }, [
        React.createElement('div', { key:'hdr', className:'header' }, [
          React.createElement('div', { key:'t', className:'title' }, 'Available Cabins'),
          React.createElement('button', { key:'r', className:'btn', onClick: load, disabled: loading }, loading ? 'Loading…' : 'Refresh')
        ]),
        error ? React.createElement('div', { key:'err', className:'error' }, error) : null,
        React.createElement('div', { key:'tbl' }, [
          React.createElement('table', { key:'t' }, [
            React.createElement('thead', { key:'h' }, React.createElement('tr', null, [
              React.createElement('th', { key:'num' }, 'Cabin No.'),
              React.createElement('th', { key:'type' }, 'Type'),
              React.createElement('th', { key:'price' }, 'Price'),
              React.createElement('th', { key:'act' }, 'Action')
            ])),
            React.createElement('tbody', { key:'b' }, cabins.map(c => React.createElement('tr', { key:c.cabin_id }, [
              React.createElement('td', { key:'n' }, c.cabin_number),
              React.createElement('td', { key:'t' }, c.type),
              React.createElement('td', { key:'p' }, `৳ ${Number(c.price).toFixed(2)}`),
              React.createElement('td', { key:'a' }, React.createElement('button', { className:'btn primary', onClick: () => onSelect(c) }, 'Book'))
            ])))
          ])
        ])
      ])
    );
  }
  window.Components = window.Components || {};
  window.Components.CabinList = CabinList;
})();
