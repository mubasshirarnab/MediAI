(function(){
  const { useState } = React;
  const { CabinList, CabinBookingForm, MyBookings } = window.Components;
  function AppCabinBooking() {
    const [active, setActive] = useState('browse');
    const [selected, setSelected] = useState(null);
    const [checkIn, setCheckIn] = useState('');
    const [checkOut, setCheckOut] = useState('');

    const onBooked = () => { setSelected(null); };

    return (
      React.createElement('div', null, [
        React.createElement('div', { key:'hdr', className:'header' }, React.createElement('div', { className:'title' }, 'Cabin Booking')),
        React.createElement('div', { key:'tabs', className:'tabs' }, [
          React.createElement('button', { key:'b', className:'btn' + (active==='browse'?' primary':''), onClick: ()=> setActive('browse') }, 'Browse & Book'),
          React.createElement('button', { key:'m', className:'btn' + (active==='my'?' primary':''), onClick: ()=> setActive('my') }, 'My Bookings')
        ]),
        active === 'browse' ? React.createElement('div', { key:'bb' }, [
          React.createElement(CabinList, { key:'list', checkIn, checkOut, onSelect:setSelected }),
          React.createElement(CabinBookingForm, { key:'form', selected, checkIn, setCheckIn, checkOut, setCheckOut, onBooked })
        ]) : React.createElement(MyBookings, { key:'my' })
      ])
    );
  }
  window.Apps = window.Apps || {};
  window.Apps.AppCabinBooking = AppCabinBooking;
})();
