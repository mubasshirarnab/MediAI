(function(){
  const { useState } = React;
  function CabinBookingForm({ selected, checkIn, setCheckIn, checkOut, setCheckOut, onBooked }) {
    const [submitting, setSubmitting] = useState(false);
    const [msg, setMsg] = useState('');
    const [err, setErr] = useState('');
    const api = axios.create({ baseURL: 'cabin_api.php' });

    const submit = () => {
      setErr(''); setMsg('');
      if (!selected) { setErr('Please select a cabin'); return; }
      if (!checkIn || !checkOut) { setErr('Select check-in and check-out'); return; }
      setSubmitting(true);
      api.post('?action=book', { cabin_id: selected.cabin_id, check_in: checkIn, check_out: checkOut })
        .then(res => {
          if (res.data.success) { setMsg('Booked successfully'); onBooked(); }
          else setErr(res.data.error || 'Booking failed');
        })
        .catch(() => setErr('Booking failed'))
        .finally(() => setSubmitting(false));
    };

    return (
      React.createElement('div', { className:'card' }, [
        React.createElement('div', { key:'h', className:'title' }, 'Book a Cabin'),
        selected ? React.createElement('div', { key:'sel', style:{ margin:'8px 0', color:'#b3b3b3' } }, `Selected: ${selected.cabin_number} (${selected.type}), ৳ ${Number(selected.price).toFixed(2)}`) :
          React.createElement('div', { key:'ns', style:{ margin:'8px 0', color:'#b3b3b3' } }, 'No cabin selected'),
        React.createElement('div', { key:'frm', className:'row' }, [
          React.createElement('div', { key:'ci' }, [React.createElement('label', { key:'l' }, 'Check-in'), React.createElement('br'), React.createElement('input', { key:'i', type:'date', value:checkIn, onChange:e=>setCheckIn(e.target.value) })]),
          React.createElement('div', { key:'co' }, [React.createElement('label', { key:'l' }, 'Check-out'), React.createElement('br'), React.createElement('input', { key:'i', type:'date', value:checkOut, onChange:e=>setCheckOut(e.target.value) })])
        ]),
        err ? React.createElement('div', { key:'e', className:'error' }, err) : null,
        msg ? React.createElement('div', { key:'m', className:'success' }, msg) : null,
        React.createElement('button', { key:'s', className:'btn primary', onClick: submit, disabled: submitting }, submitting ? 'Booking…' : 'Book')
      ])
    );
  }
  window.Components = window.Components || {};
  window.Components.CabinBookingForm = CabinBookingForm;
})();
