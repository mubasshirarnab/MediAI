(function(){
  const { useState, useEffect } = React;
  function CabinForm({ editing, onSaved, onCancel }) {
    const [cabin_number, setCabinNumber] = useState(editing ? editing.cabin_number : '');
    const [type, setType] = useState(editing ? editing.type : 'general');
    const [price, setPrice] = useState(editing ? editing.price : 0);
    const [availability, setAvailability] = useState(editing ? editing.availability : 1);
    const [saving, setSaving] = useState(false);
    const [err, setErr] = useState('');
    const api = axios.create({ baseURL: 'cabin_api.php' });

    const save = () => {
      setErr(''); setSaving(true);
      const payload = { cabin_number, type, price: Number(price), availability: Number(availability) };
      const req = editing ? api.post('?action=cabins_update', { ...payload, cabin_id: editing.cabin_id }) : api.post('?action=cabins_add', payload);
      req.then(res => { if (res.data.success) onSaved(); else setErr(res.data.error || 'Save failed'); })
         .catch(() => setErr('Save failed'))
         .finally(() => setSaving(false));
    };

    return (
      React.createElement('div', { className:'card' }, [
        React.createElement('div', { key:'t', className:'title' }, editing ? 'Edit Cabin' : 'Add Cabin'),
        React.createElement('div', { key:'f', className:'row' }, [
          React.createElement('div', { key:'n' }, [React.createElement('label', { key:'l' }, 'Cabin Number'), React.createElement('br'), React.createElement('input', { key:'i', value:cabin_number, onChange:e=>setCabinNumber(e.target.value) })]),
          React.createElement('div', { key:'ty' }, [React.createElement('label', { key:'l' }, 'Type'), React.createElement('br'), React.createElement('select', { key:'s', value:type, onChange:e=>setType(e.target.value) }, [
            React.createElement('option', { key:'g', value:'general' }, 'General'),
            React.createElement('option', { key:'d', value:'deluxe' }, 'Deluxe'),
            React.createElement('option', { key:'i', value:'ICU' }, 'ICU')
          ])]),
          React.createElement('div', { key:'p' }, [React.createElement('label', { key:'l' }, 'Price'), React.createElement('br'), React.createElement('input', { key:'i', type:'number', step:'0.01', value:price, onChange:e=>setPrice(e.target.value) })]),
          React.createElement('div', { key:'a' }, [React.createElement('label', { key:'l' }, 'Available'), React.createElement('br'), React.createElement('select', { key:'s', value:availability, onChange:e=>setAvailability(e.target.value) }, [
            React.createElement('option', { key:'y', value:1 }, 'Yes'),
            React.createElement('option', { key:'n', value:0 }, 'No')
          ])])
        ]),
        err ? React.createElement('div', { key:'e', className:'error' }, err) : null,
        React.createElement('div', { key:'b', className:'row' }, [
          React.createElement('button', { key:'s', className:'btn primary', onClick: save, disabled: saving }, saving ? 'Saving…' : 'Save'),
          React.createElement('button', { key:'c', className:'btn', onClick: onCancel }, 'Cancel')
        ])
      ])
    );
  }

  function AdminCabinManagement() {
    const [rows, setRows] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [showForm, setShowForm] = useState(false);
    const [editing, setEditing] = useState(null);
    const api = axios.create({ baseURL: 'cabin_api.php' });

    const load = () => {
      setLoading(true); setError('');
      api.get('', { params: { action: 'cabins_list' }})
        .then(res => { if (res.data.success) setRows(res.data.data); else setError(res.data.error || 'Failed to load'); })
        .catch(() => setError('Failed to load'))
        .finally(() => setLoading(false));
    };

    useEffect(() => { load(); }, []);

    const onAdd = () => { setEditing(null); setShowForm(true); };
    const onEdit = (row) => { setEditing(row); setShowForm(true); };
    const onSaved = () => { setShowForm(false); setEditing(null); load(); };
    const onCancel = () => { setShowForm(false); setEditing(null); };

    const onDelete = (row) => {
      if (!confirm('Delete this cabin?')) return;
      api.post('?action=cabins_delete', { cabin_id: row.cabin_id })
        .then(res => { if (res.data.success) load(); else alert(res.data.error || 'Delete failed'); })
        .catch(() => alert('Delete failed'));
    };

    return (
      React.createElement('div', null, [
        React.createElement('div', { key:'hdr', className:'header' }, [
          React.createElement('div', { key:'t', className:'title' }, 'Cabin Management'),
          React.createElement('div', { key:'actions' }, [
            React.createElement('button', { key:'add', className:'btn primary', onClick:onAdd }, 'Add Cabin'),
            React.createElement('button', { key:'r', className:'btn', onClick: load, disabled: loading }, loading ? 'Loading…' : 'Refresh')
          ])
        ]),
        showForm ? React.createElement(CabinForm, { key:'form', editing, onSaved, onCancel }) : null,
        error ? React.createElement('div', { key:'err', className:'error' }, error) : null,
        React.createElement('div', { key:'tbl', className:'card' }, [
          React.createElement('table', { key:'t' }, [
            React.createElement('thead', { key:'h' }, React.createElement('tr', null, [
              React.createElement('th', { key:'id' }, '#'),
              React.createElement('th', { key:'num' }, 'Cabin No.'),
              React.createElement('th', { key:'type' }, 'Type'),
              React.createElement('th', { key:'price' }, 'Price'),
              React.createElement('th', { key:'av' }, 'Available'),
              React.createElement('th', { key:'act' }, 'Actions')
            ])),
            React.createElement('tbody', { key:'b' }, rows.map(r => React.createElement('tr', { key:r.cabin_id }, [
              React.createElement('td', { key:'id' }, r.cabin_id),
              React.createElement('td', { key:'num' }, r.cabin_number),
              React.createElement('td', { key:'type' }, r.type),
              React.createElement('td', { key:'price' }, `৳ ${Number(r.price).toFixed(2)}`),
              React.createElement('td', { key:'av' }, r.availability ? 'Yes' : 'No'),
              React.createElement('td', { key:'act' }, [
                React.createElement('button', { key:'e', className:'btn', onClick: ()=> onEdit(r) }, 'Edit'),
                React.createElement('span', { key:'sp', style:{ margin:'0 6px' } }, ''),
                React.createElement('button', { key:'d', className:'btn', onClick: ()=> onDelete(r) }, 'Delete')
              ])
            ])))
          ])
        ])
      ])
    );
  }

  window.Components = window.Components || {};
  window.Components.AdminCabinManagement = AdminCabinManagement;
})();
