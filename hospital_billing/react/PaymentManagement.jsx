(function(){
  const { useState, useEffect } = React;
  
  function PaymentManagement({ onError, onSuccess }) {
    const [payments, setPayments] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showPaymentForm, setShowPaymentForm] = useState(false);
    const [selectedBill, setSelectedBill] = useState(null);
    const [bills, setBills] = useState([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [methodFilter, setMethodFilter] = useState('');

    useEffect(() => {
      loadPayments();
      loadBills();
    }, [searchTerm, methodFilter]);

    const loadPayments = async () => {
      try {
        setLoading(true);
        // This would be implemented in the API
        // For now, we'll show a placeholder
        setPayments([]);
      } catch (err) {
        onError('Failed to load payments');
      } finally {
        setLoading(false);
      }
    };

    const loadBills = async () => {
      try {
        const response = await axios.get('billing_api.php?action=bills_list&limit=100');
        if (response.data.success) {
          setBills(response.data.data.filter(bill => bill.balance_amount > 0));
        }
      } catch (err) {
        console.error('Failed to load bills:', err);
      }
    };

    const handleMakePayment = async (paymentData) => {
      try {
        const response = await axios.post('billing_api.php?action=make_payment', paymentData);
        if (response.data.success) {
          onSuccess('Payment recorded successfully');
          setShowPaymentForm(false);
          setSelectedBill(null);
          loadPayments();
          loadBills();
          // Trigger dashboard and reports refresh by updating a timestamp in sessionStorage (read by App)
          try { window.sessionStorage.setItem('last_payment_ts', Date.now().toString()); } catch (e) {}
        } else {
          onError(response.data.error || 'Failed to record payment');
        }
      } catch (err) {
        onError('Failed to record payment');
      }
    };

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('en-BD', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 2
      }).format(amount);
    };

    const PaymentForm = ({ bill, onSave, onCancel }) => {
      const [formData, setFormData] = useState({
        bill_id: bill?.id || '',
        payment_method: 'cash',
        payment_amount: bill?.balance_amount || 0,
        payment_reference: '',
        transaction_id: '',
        bank_name: '',
        mobile_banking_provider: '',
        mobile_number: ''
      });

      const handleSubmit = async () => {
        if (formData.payment_amount <= 0) {
          onError('Payment amount must be greater than 0');
          return;
        }
        
        await handleMakePayment(formData);
        onSave();
      };

      return React.createElement('div', { className: 'modal-overlay', onClick: onCancel }, [
        React.createElement('div', { 
          className: 'modal-content', 
          onClick: (e) => e.stopPropagation() 
        }, [
          React.createElement('div', { key: 'header', className: 'modal-header' }, [
            React.createElement('h3', { key: 'title', className: 'modal-title' }, 'Record Payment'),
            React.createElement('button', { key: 'close', className: 'close-btn', onClick: onCancel }, '×')
          ]),
          
          React.createElement('div', { key: 'bill-info', style: { marginBottom: '20px', padding: '15px', backgroundColor: '#181d36', borderRadius: '8px' } }, [
            React.createElement('h4', { key: 'title', style: { marginBottom: '10px', color: '#a259ff' } }, 'Bill Information'),
            React.createElement('div', { key: 'row1', className: 'row' }, [
              React.createElement('div', { key: 'col1', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'Bill ID: '),
                React.createElement('span', { key: 'value' }, `#${bill.id}`)
              ]),
              React.createElement('div', { key: 'col2', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'Patient: '),
                React.createElement('span', { key: 'value' }, bill.patient_name)
              ])
            ]),
            React.createElement('div', { key: 'row2', className: 'row', style: { marginTop: '10px' } }, [
              React.createElement('div', { key: 'col1', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'Total Amount: '),
                React.createElement('span', { key: 'value', className: 'amount' }, formatCurrency(bill.total_amount))
              ]),
              React.createElement('div', { key: 'col2', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'Balance: '),
                React.createElement('span', { key: 'value', className: 'amount' }, formatCurrency(bill.balance_amount))
              ])
            ])
          ]),
          
          React.createElement('div', { key: 'form', className: 'row' }, [
            React.createElement('div', { key: 'col1', className: 'col-md-6' }, [
              React.createElement('div', { key: 'method', className: 'form-group' }, [
                React.createElement('label', { key: 'label' }, 'Payment Method *'),
                React.createElement('select', {
                  key: 'select',
                  value: formData.payment_method,
                  onChange: (e) => setFormData(prev => ({ ...prev, payment_method: e.target.value }))
                }, [
                  React.createElement('option', { key: 'cash', value: 'cash' }, 'Cash'),
                  React.createElement('option', { key: 'card', value: 'card' }, 'Card'),
                  React.createElement('option', { key: 'mobile_banking', value: 'mobile_banking' }, 'Mobile Banking'),
                  React.createElement('option', { key: 'bank_transfer', value: 'bank_transfer' }, 'Bank Transfer'),
                  React.createElement('option', { key: 'cheque', value: 'cheque' }, 'Cheque')
                ])
              ]),
              
              React.createElement('div', { key: 'amount', className: 'form-group' }, [
                React.createElement('label', { key: 'label' }, 'Payment Amount *'),
                React.createElement('input', {
                  key: 'input',
                  type: 'number',
                  step: '0.01',
                  min: '0.01',
                  max: bill.balance_amount,
                  value: formData.payment_amount,
                  onChange: (e) => setFormData(prev => ({ ...prev, payment_amount: parseFloat(e.target.value) || 0 }))
                })
              ]),
              
              React.createElement('div', { key: 'reference', className: 'form-group' }, [
                React.createElement('label', { key: 'label' }, 'Payment Reference'),
                React.createElement('input', {
                  key: 'input',
                  type: 'text',
                  value: formData.payment_reference,
                  onChange: (e) => setFormData(prev => ({ ...prev, payment_reference: e.target.value })),
                  placeholder: 'Transaction reference number'
                })
              ])
            ]),
            
            React.createElement('div', { key: 'col2', className: 'col-md-6' }, [
              formData.payment_method === 'card' && React.createElement('div', { key: 'card-info' }, [
                React.createElement('div', { key: 'bank', className: 'form-group' }, [
                  React.createElement('label', { key: 'label' }, 'Bank Name'),
                  React.createElement('input', {
                    key: 'input',
                    type: 'text',
                    value: formData.bank_name,
                    onChange: (e) => setFormData(prev => ({ ...prev, bank_name: e.target.value })),
                    placeholder: 'Bank name'
                  })
                ]),
                React.createElement('div', { key: 'transaction', className: 'form-group' }, [
                  React.createElement('label', { key: 'label' }, 'Transaction ID'),
                  React.createElement('input', {
                    key: 'input',
                    type: 'text',
                    value: formData.transaction_id,
                    onChange: (e) => setFormData(prev => ({ ...prev, transaction_id: e.target.value })),
                    placeholder: 'Transaction ID'
                  })
                ])
              ]),
              
              formData.payment_method === 'mobile_banking' && React.createElement('div', { key: 'mobile-info' }, [
                React.createElement('div', { key: 'provider', className: 'form-group' }, [
                  React.createElement('label', { key: 'label' }, 'Provider'),
                  React.createElement('select', {
                    key: 'select',
                    value: formData.mobile_banking_provider,
                    onChange: (e) => setFormData(prev => ({ ...prev, mobile_banking_provider: e.target.value }))
                  }, [
                    React.createElement('option', { key: 'none', value: '' }, 'Select Provider'),
                    React.createElement('option', { key: 'bkash', value: 'bkash' }, 'bKash'),
                    React.createElement('option', { key: 'rocket', value: 'rocket' }, 'Rocket'),
                    React.createElement('option', { key: 'nagad', value: 'nagad' }, 'Nagad'),
                    React.createElement('option', { key: 'upay', value: 'upay' }, 'Upay')
                  ])
                ]),
                React.createElement('div', { key: 'mobile', className: 'form-group' }, [
                  React.createElement('label', { key: 'label' }, 'Mobile Number'),
                  React.createElement('input', {
                    key: 'input',
                    type: 'text',
                    value: formData.mobile_number,
                    onChange: (e) => setFormData(prev => ({ ...prev, mobile_number: e.target.value })),
                    placeholder: 'Mobile number'
                  })
                ])
              ]),
              
              formData.payment_method === 'bank_transfer' && React.createElement('div', { key: 'bank-info' }, [
                React.createElement('div', { key: 'bank', className: 'form-group' }, [
                  React.createElement('label', { key: 'label' }, 'Bank Name'),
                  React.createElement('input', {
                    key: 'input',
                    type: 'text',
                    value: formData.bank_name,
                    onChange: (e) => setFormData(prev => ({ ...prev, bank_name: e.target.value })),
                    placeholder: 'Bank name'
                  })
                ]),
                React.createElement('div', { key: 'transaction', className: 'form-group' }, [
                  React.createElement('label', { key: 'label' }, 'Transaction ID'),
                  React.createElement('input', {
                    key: 'input',
                    type: 'text',
                    value: formData.transaction_id,
                    onChange: (e) => setFormData(prev => ({ ...prev, transaction_id: e.target.value })),
                    placeholder: 'Transaction ID'
                  })
                ])
              ])
            ])
          ]),
          
          React.createElement('div', { key: 'actions', style: { marginTop: '20px', textAlign: 'right' } }, [
            React.createElement('button', {
              key: 'cancel',
              className: 'btn',
              onClick: onCancel,
              style: { marginRight: '10px' }
            }, 'Cancel'),
            React.createElement('button', {
              key: 'save',
              className: 'btn btn-success',
              onClick: handleSubmit,
              disabled: formData.payment_amount <= 0
            }, 'Record Payment')
          ])
        ])
      ]);
    };

    if (loading) {
      return React.createElement('div', { className: 'loading' }, 'Loading payments...');
    }

    return React.createElement('div', null, [
      React.createElement('div', { key: 'header', className: 'card' }, [
        React.createElement('div', { key: 'row1', className: 'row' }, [
          React.createElement('div', { key: 'col1', className: 'col-md-6' }, [
            React.createElement('h3', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Payment Management'),
            React.createElement('input', {
              key: 'search',
              type: 'text',
              placeholder: 'Search payments...',
              value: searchTerm,
              onChange: (e) => setSearchTerm(e.target.value),
              style: { marginBottom: '10px' }
            })
          ]),
          React.createElement('div', { key: 'col2', className: 'col-md-6', style: { display: 'flex', alignItems: 'end', justifyContent: 'flex-end' } }, [
            React.createElement('select', {
              key: 'method-filter',
              value: methodFilter,
              onChange: (e) => setMethodFilter(e.target.value),
              style: { marginRight: '10px', width: '150px' }
            }, [
              React.createElement('option', { key: 'all', value: '' }, 'All Methods'),
              React.createElement('option', { key: 'cash', value: 'cash' }, 'Cash'),
              React.createElement('option', { key: 'card', value: 'card' }, 'Card'),
              React.createElement('option', { key: 'mobile_banking', value: 'mobile_banking' }, 'Mobile Banking'),
              React.createElement('option', { key: 'bank_transfer', value: 'bank_transfer' }, 'Bank Transfer')
            ])
          ])
        ])
      ]),
      
      React.createElement('div', { key: 'outstanding-bills', className: 'card' }, [
        React.createElement('h4', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Outstanding Bills'),
        
        bills.length > 0 ? 
          React.createElement('table', { key: 'table' }, [
            React.createElement('thead', { key: 'thead' }, [
              React.createElement('tr', { key: 'header' }, [
                React.createElement('th', { key: 'id' }, 'Bill ID'),
                React.createElement('th', { key: 'patient' }, 'Patient'),
                React.createElement('th', { key: 'total' }, 'Total Amount'),
                React.createElement('th', { key: 'paid' }, 'Paid Amount'),
                React.createElement('th', { key: 'balance' }, 'Balance'),
                React.createElement('th', { key: 'date' }, 'Issue Date'),
                React.createElement('th', { key: 'actions' }, 'Actions')
              ])
            ]),
            React.createElement('tbody', { key: 'tbody' }, 
              bills.map(bill => 
                React.createElement('tr', { key: bill.id }, [
                  React.createElement('td', { key: 'id' }, `#${bill.id}`),
                  React.createElement('td', { key: 'patient' }, bill.patient_name),
                  React.createElement('td', { key: 'total', className: 'amount' }, formatCurrency(bill.total_amount)),
                  React.createElement('td', { key: 'paid', className: 'amount' }, formatCurrency(bill.paid_amount)),
                  React.createElement('td', { key: 'balance', className: 'amount' }, formatCurrency(bill.balance_amount)),
                  React.createElement('td', { key: 'date' }, new Date(bill.issued_date).toLocaleDateString()),
                  React.createElement('td', { key: 'actions' }, [
                    React.createElement('button', {
                      key: 'pay',
                      className: 'btn btn-success',
                      onClick: () => {
                        setSelectedBill(bill);
                        setShowPaymentForm(true);
                      },
                      style: { padding: '5px 10px', fontSize: '12px' }
                    }, 'Make Payment')
                  ])
                ])
              )
            )
          ]) :
          React.createElement('div', { key: 'no-bills', style: { textAlign: 'center', color: '#b3b3b3', padding: '20px' } }, 'No outstanding bills found')
      ]),
      
      showPaymentForm && selectedBill && React.createElement(PaymentForm, {
        key: 'payment-form',
        bill: selectedBill,
        onSave: () => {
          setShowPaymentForm(false);
          setSelectedBill(null);
        },
        onCancel: () => {
          setShowPaymentForm(false);
          setSelectedBill(null);
        }
      })
    ]);
  }
  
  window.Components = window.Components || {};
  window.Components.PaymentManagement = PaymentManagement;
})();
