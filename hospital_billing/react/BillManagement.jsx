(function(){
  const { useState, useEffect } = React;
  
  function BillManagement({ onError, onSuccess }) {
    const [bills, setBills] = useState([]);
    const [loading, setLoading] = useState(true);
    const [pagination, setPagination] = useState({ page: 1, limit: 20, total: 0, pages: 1 });
    const [showCreateForm, setShowCreateForm] = useState(false);
    const [showBillDetails, setShowBillDetails] = useState(false);
    const [selectedBill, setSelectedBill] = useState(null);
    const [searchTerm, setSearchTerm] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [serviceCharges, setServiceCharges] = useState([]);
    const [discounts, setDiscounts] = useState([]);
    const [packages, setPackages] = useState([]);

    useEffect(() => {
      loadBills();
      loadServiceCharges();
      loadDiscounts();
      loadPackages();
    }, [pagination.page, searchTerm, statusFilter]);

    const loadBills = async () => {
      try {
        setLoading(true);
        const params = new URLSearchParams({
          page: pagination.page,
          limit: pagination.limit
        });
        
        if (searchTerm) params.append('search', searchTerm);
        if (statusFilter) params.append('status', statusFilter);
        
        const response = await axios.get(`billing_api.php?action=bills_list&${params}`);
        if (response.data.success) {
          setBills(response.data.data);
          setPagination(response.data.pagination);
        } else {
          onError(response.data.error || 'Failed to load bills');
        }
      } catch (err) {
        onError('Failed to load bills');
      } finally {
        setLoading(false);
      }
    };

    const loadServiceCharges = async () => {
      try {
        const response = await axios.get('billing_api.php?action=service_charges');
        if (response.data.success) {
          setServiceCharges(response.data.data);
        }
      } catch (err) {
        console.error('Failed to load service charges:', err);
      }
    };

    const loadDiscounts = async () => {
      try {
        const response = await axios.get('billing_api.php?action=discounts');
        if (response.data.success) {
          setDiscounts(response.data.data);
        }
      } catch (err) {
        console.error('Failed to load discounts:', err);
      }
    };

    const loadPackages = async () => {
      try {
        const response = await axios.get('billing_api.php?action=packages');
        if (response.data.success) {
          setPackages(response.data.data);
        }
      } catch (err) {
        console.error('Failed to load packages:', err);
      }
    };

    const handleViewBillDetails = async (bill) => {
      try {
        const response = await axios.get(`billing_api.php?action=bill_details&bill_id=${bill.id}`);
        if (response.data.success) {
          setSelectedBill(response.data.data);
          setShowBillDetails(true);
        } else {
          onError(response.data.error || 'Failed to load bill details');
        }
      } catch (err) {
        onError('Failed to load bill details');
      }
    };

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('en-BD', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 2
      }).format(amount);
    };

    const getStatusBadge = (status) => {
      const statusClasses = {
        'pending': 'status-pending',
        'paid': 'status-paid',
        'partial': 'status-partial',
        'cancelled': 'status-cancelled'
      };
      
      return React.createElement('span', {
        className: `status-badge ${statusClasses[status] || 'status-pending'}`
      }, status.toUpperCase());
    };

    const CreateBillForm = ({ onSave, onCancel }) => {
      const [formData, setFormData] = useState({
        patient_id: '',
        bill_type: 'final',
        issued_date: new Date().toISOString().split('T')[0], // Set current date as default
        items: [],
        discount_id: '',
        insurance_id: '',
        corporate_id: ''
      });
      const [selectedService, setSelectedService] = useState('');
      const [quantity, setQuantity] = useState(1);
      const [customPrice, setCustomPrice] = useState('');

      const addItem = () => {
        if (!selectedService) return;
        
        const service = serviceCharges.find(s => s.id == selectedService);
        if (!service) return;
        
        const price = customPrice ? parseFloat(customPrice) : service.base_price;
        const totalPrice = price * quantity;
        
        const newItem = {
          item_type: service.service_type,
          item_name: service.service_name,
          item_description: `${service.service_name} - ${service.department}`,
          quantity: quantity,
          unit_price: price,
          total_price: totalPrice
        };
        
        setFormData(prev => ({
          ...prev,
          items: [...prev.items, newItem]
        }));
        
        setSelectedService('');
        setQuantity(1);
        setCustomPrice('');
      };

      const removeItem = (index) => {
        setFormData(prev => ({
          ...prev,
          items: prev.items.filter((_, i) => i !== index)
        }));
      };

      const handleSubmit = async () => {
        if (formData.patient_id <= 0 || formData.items.length === 0) {
          onError('Please fill all required fields');
          return;
        }
        
        try {
          const response = await axios.post('billing_api.php?action=create_bill', formData);
          if (response.data.success) {
            onSuccess('Bill created successfully');
            onSave();
            loadBills();
          } else {
            onError(response.data.error || 'Failed to create bill');
          }
        } catch (err) {
          onError('Failed to create bill');
        }
      };

      const totalAmount = formData.items.reduce((sum, item) => sum + item.total_price, 0);

      return React.createElement('div', { className: 'modal-overlay', onClick: onCancel }, [
        React.createElement('div', { 
          className: 'modal-content', 
          onClick: (e) => e.stopPropagation() 
        }, [
          React.createElement('div', { key: 'header', className: 'modal-header' }, [
            React.createElement('h3', { key: 'title', className: 'modal-title' }, 'Create New Bill'),
            React.createElement('button', { key: 'close', className: 'close-btn', onClick: onCancel }, '×')
          ]),
          
          React.createElement('div', { key: 'form', className: 'row' }, [
            React.createElement('div', { key: 'col1', className: 'col-md-6' }, [
              React.createElement('div', { key: 'patient', className: 'form-group' }, [
                React.createElement('label', { key: 'label' }, 'Patient ID *'),
                React.createElement('input', {
                  key: 'input',
                  type: 'number',
                  value: formData.patient_id,
                  onChange: (e) => setFormData(prev => ({ ...prev, patient_id: e.target.value })),
                  placeholder: 'Enter Patient ID'
                })
              ]),
              
              React.createElement('div', { key: 'bill-type', className: 'form-group' }, [
                React.createElement('label', { key: 'label' }, 'Bill Type'),
                React.createElement('select', {
                  key: 'select',
                  value: formData.bill_type,
                  onChange: (e) => setFormData(prev => ({ ...prev, bill_type: e.target.value }))
                }, [
                  React.createElement('option', { key: 'final', value: 'final' }, 'Final Bill'),
                  React.createElement('option', { key: 'interim', value: 'interim' }, 'Interim Bill'),
                  React.createElement('option', { key: 'advance', value: 'advance' }, 'Advance Bill')
                ])
              ]),
              
              React.createElement('div', { key: 'issued-date', className: 'form-group' }, [
                React.createElement('label', { key: 'label' }, 'Issue Date'),
                React.createElement('input', {
                  key: 'input',
                  type: 'date',
                  value: formData.issued_date,
                  onChange: (e) => setFormData(prev => ({ ...prev, issued_date: e.target.value })),
                  style: { colorScheme: 'dark' }
                })
              ])
            ]),
            
            React.createElement('div', { key: 'col2', className: 'col-md-6' }, [
              React.createElement('div', { key: 'discount', className: 'form-group' }, [
                React.createElement('label', { key: 'label' }, 'Discount'),
                React.createElement('select', {
                  key: 'select',
                  value: formData.discount_id,
                  onChange: (e) => setFormData(prev => ({ ...prev, discount_id: e.target.value }))
                }, [
                  React.createElement('option', { key: 'none', value: '' }, 'No Discount'),
                  ...discounts.map(discount => 
                    React.createElement('option', { key: discount.id, value: discount.id }, 
                      `${discount.discount_name} (${discount.discount_value}%)`)
                  )
                ])
              ])
            ])
          ]),
          
          React.createElement('div', { key: 'items-section' }, [
            React.createElement('h4', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Add Items'),
            
            React.createElement('div', { key: 'add-item', className: 'row' }, [
              React.createElement('div', { key: 'col1', className: 'col-md-4' }, [
                React.createElement('label', { key: 'label' }, 'Service'),
                React.createElement('select', {
                  key: 'select',
                  value: selectedService,
                  onChange: (e) => setSelectedService(e.target.value)
                }, [
                  React.createElement('option', { key: 'none', value: '' }, 'Select Service'),
                  ...serviceCharges.map(service => 
                    React.createElement('option', { key: service.id, value: service.id }, 
                      `${service.service_name} - ${formatCurrency(service.base_price)}`)
                  )
                ])
              ]),
              
              React.createElement('div', { key: 'col2', className: 'col-md-2' }, [
                React.createElement('label', { key: 'label' }, 'Quantity'),
                React.createElement('input', {
                  key: 'input',
                  type: 'number',
                  min: '1',
                  value: quantity,
                  onChange: (e) => setQuantity(parseInt(e.target.value) || 1)
                })
              ]),
              
              React.createElement('div', { key: 'col3', className: 'col-md-3' }, [
                React.createElement('label', { key: 'label' }, 'Custom Price'),
                React.createElement('input', {
                  key: 'input',
                  type: 'number',
                  step: '0.01',
                  value: customPrice,
                  onChange: (e) => setCustomPrice(e.target.value),
                  placeholder: 'Leave empty for default'
                })
              ]),
              
              React.createElement('div', { key: 'col4', className: 'col-md-3', style: { display: 'flex', alignItems: 'end' } }, [
                React.createElement('button', {
                  key: 'add-btn',
                  className: 'btn btn-success',
                  onClick: addItem,
                  style: { width: '100%' }
                }, 'Add Item')
              ])
            ]),
            
            formData.items.length > 0 && React.createElement('div', { key: 'items-list' }, [
              React.createElement('h5', { key: 'title', style: { marginTop: '20px', marginBottom: '10px' } }, 'Bill Items'),
              React.createElement('table', { key: 'table' }, [
                React.createElement('thead', { key: 'thead' }, [
                  React.createElement('tr', { key: 'header' }, [
                    React.createElement('th', { key: 'name' }, 'Item'),
                    React.createElement('th', { key: 'qty' }, 'Qty'),
                    React.createElement('th', { key: 'price' }, 'Unit Price'),
                    React.createElement('th', { key: 'total' }, 'Total'),
                    React.createElement('th', { key: 'action' }, 'Action')
                  ])
                ]),
                React.createElement('tbody', { key: 'tbody' }, 
                  formData.items.map((item, index) => 
                    React.createElement('tr', { key: index }, [
                      React.createElement('td', { key: 'name' }, item.item_name),
                      React.createElement('td', { key: 'qty' }, item.quantity),
                      React.createElement('td', { key: 'price' }, formatCurrency(item.unit_price)),
                      React.createElement('td', { key: 'total' }, formatCurrency(item.total_price)),
                      React.createElement('td', { key: 'action' }, [
                        React.createElement('button', {
                          key: 'remove',
                          className: 'btn btn-danger',
                          onClick: () => removeItem(index),
                          style: { padding: '5px 10px', fontSize: '12px' }
                        }, 'Remove')
                      ])
                    ])
                  )
                )
              ]),
              React.createElement('div', { 
                key: 'total', 
                style: { 
                  textAlign: 'right', 
                  marginTop: '10px', 
                  fontSize: '18px', 
                  fontWeight: 'bold',
                  color: '#a259ff'
                } 
              }, `Total: ${formatCurrency(totalAmount)}`)
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
              disabled: formData.items.length === 0
            }, 'Create Bill')
          ])
        ])
      ]);
    };

    const BillDetailsModal = ({ bill, onClose }) => {
      if (!bill) return null;
      
      const [showPaymentForm, setShowPaymentForm] = useState(false);
      const [paymentFormData, setPaymentFormData] = useState({
        payment_method: 'cash',
        payment_amount: bill.bill.balance_amount,
        payment_reference: '',
        transaction_id: '',
        bank_name: '',
        mobile_banking_provider: '',
        mobile_number: ''
      });

      const handleAddPayment = async () => {
        try {
          const response = await axios.post('billing_api.php?action=make_payment', {
            bill_id: bill.bill.id,
            ...paymentFormData
          });
          
          if (response.data.success) {
            onSuccess('Payment recorded successfully');
            setShowPaymentForm(false);
            // Reload bill details
            handleViewBillDetails({ id: bill.bill.id });
          } else {
            onError(response.data.error || 'Failed to record payment');
          }
        } catch (err) {
          onError('Failed to record payment');
        }
      };

      const PaymentForm = () => {
        return React.createElement('div', { 
          key: 'payment-form',
          style: { 
            marginTop: '20px', 
            padding: '20px', 
            backgroundColor: '#181d36', 
            borderRadius: '8px',
            border: '1px solid #2a2a4a'
          } 
        }, [
          React.createElement('h5', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Add Payment'),
          
          React.createElement('div', { key: 'form', className: 'row' }, [
            React.createElement('div', { key: 'col1', className: 'col-md-6' }, [
              React.createElement('div', { key: 'method', className: 'form-group' }, [
                React.createElement('label', { key: 'label' }, 'Payment Method *'),
                React.createElement('select', {
                  key: 'select',
                  value: paymentFormData.payment_method,
                  onChange: (e) => setPaymentFormData(prev => ({ ...prev, payment_method: e.target.value }))
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
                  max: bill.bill.balance_amount,
                  value: paymentFormData.payment_amount,
                  onChange: (e) => setPaymentFormData(prev => ({ ...prev, payment_amount: parseFloat(e.target.value) || 0 }))
                })
              ])
            ]),
            
            React.createElement('div', { key: 'col2', className: 'col-md-6' }, [
              React.createElement('div', { key: 'reference', className: 'form-group' }, [
                React.createElement('label', { key: 'label' }, 'Payment Reference'),
                React.createElement('input', {
                  key: 'input',
                  type: 'text',
                  value: paymentFormData.payment_reference,
                  onChange: (e) => setPaymentFormData(prev => ({ ...prev, payment_reference: e.target.value })),
                  placeholder: 'Transaction reference number'
                })
              ]),
              
              paymentFormData.payment_method === 'card' && React.createElement('div', { key: 'card-info' }, [
                React.createElement('div', { key: 'bank', className: 'form-group' }, [
                  React.createElement('label', { key: 'label' }, 'Bank Name'),
                  React.createElement('input', {
                    key: 'input',
                    type: 'text',
                    value: paymentFormData.bank_name,
                    onChange: (e) => setPaymentFormData(prev => ({ ...prev, bank_name: e.target.value })),
                    placeholder: 'Bank name'
                  })
                ]),
                React.createElement('div', { key: 'transaction', className: 'form-group' }, [
                  React.createElement('label', { key: 'label' }, 'Transaction ID'),
                  React.createElement('input', {
                    key: 'input',
                    type: 'text',
                    value: paymentFormData.transaction_id,
                    onChange: (e) => setPaymentFormData(prev => ({ ...prev, transaction_id: e.target.value })),
                    placeholder: 'Transaction ID'
                  })
                ])
              ]),
              
              paymentFormData.payment_method === 'mobile_banking' && React.createElement('div', { key: 'mobile-info' }, [
                React.createElement('div', { key: 'provider', className: 'form-group' }, [
                  React.createElement('label', { key: 'label' }, 'Provider'),
                  React.createElement('select', {
                    key: 'select',
                    value: paymentFormData.mobile_banking_provider,
                    onChange: (e) => setPaymentFormData(prev => ({ ...prev, mobile_banking_provider: e.target.value }))
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
                    value: paymentFormData.mobile_number,
                    onChange: (e) => setPaymentFormData(prev => ({ ...prev, mobile_number: e.target.value })),
                    placeholder: 'Mobile number'
                  })
                ])
              ])
            ])
          ]),
          
          React.createElement('div', { key: 'actions', style: { marginTop: '20px', textAlign: 'right' } }, [
            React.createElement('button', {
              key: 'cancel',
              className: 'btn',
              onClick: () => setShowPaymentForm(false),
              style: { marginRight: '10px' }
            }, 'Cancel'),
            React.createElement('button', {
              key: 'save',
              className: 'btn btn-success',
              onClick: handleAddPayment,
              disabled: paymentFormData.payment_amount <= 0
            }, 'Record Payment')
          ])
        ]);
      };
      
      return React.createElement('div', { className: 'modal-overlay', onClick: onClose }, [
        React.createElement('div', { 
          className: 'modal-content', 
          onClick: (e) => e.stopPropagation(),
          style: { maxWidth: '1000px', width: '95%' }
        }, [
          React.createElement('div', { key: 'header', className: 'modal-header' }, [
            React.createElement('h3', { key: 'title', className: 'modal-title' }, `Bill Details #${bill.bill.id}`),
            React.createElement('button', { key: 'close', className: 'close-btn', onClick: onClose }, '×')
          ]),
          
          // Main Bill Information
          React.createElement('div', { key: 'bill-info', style: { marginBottom: '25px' } }, [
            React.createElement('h4', { key: 'title', style: { marginBottom: '15px', color: '#a259ff', borderBottom: '2px solid #a259ff', paddingBottom: '8px' } }, 'Bill Information'),
            
            React.createElement('div', { key: 'info-grid', style: { 
              display: 'grid', 
              gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', 
              gap: '15px',
              marginBottom: '20px'
            } }, [
              React.createElement('div', { key: 'patient', style: { padding: '15px', backgroundColor: '#181d36', borderRadius: '8px' } }, [
                React.createElement('strong', { key: 'label', style: { color: '#a259ff', display: 'block', marginBottom: '5px' } }, 'Patient Name'),
                React.createElement('span', { key: 'value' }, bill.bill.patient_name)
              ]),
              
              React.createElement('div', { key: 'status', style: { padding: '15px', backgroundColor: '#181d36', borderRadius: '8px' } }, [
                React.createElement('strong', { key: 'label', style: { color: '#a259ff', display: 'block', marginBottom: '5px' } }, 'Status'),
                getStatusBadge(bill.bill.status)
              ]),
              
              React.createElement('div', { key: 'total', style: { padding: '15px', backgroundColor: '#181d36', borderRadius: '8px' } }, [
                React.createElement('strong', { key: 'label', style: { color: '#a259ff', display: 'block', marginBottom: '5px' } }, 'Total Amount'),
                React.createElement('span', { key: 'value', className: 'amount', style: { fontSize: '18px', fontWeight: 'bold' } }, formatCurrency(bill.bill.total_amount))
              ]),
              
              React.createElement('div', { key: 'paid', style: { padding: '15px', backgroundColor: '#181d36', borderRadius: '8px' } }, [
                React.createElement('strong', { key: 'label', style: { color: '#a259ff', display: 'block', marginBottom: '5px' } }, 'Paid Amount'),
                React.createElement('span', { key: 'value', className: 'amount', style: { fontSize: '18px', fontWeight: 'bold', color: '#2ed573' } }, formatCurrency(bill.bill.paid_amount))
              ]),
              
              React.createElement('div', { key: 'balance', style: { padding: '15px', backgroundColor: '#181d36', borderRadius: '8px' } }, [
                React.createElement('strong', { key: 'label', style: { color: '#a259ff', display: 'block', marginBottom: '5px' } }, 'Due Amount'),
                React.createElement('span', { key: 'value', className: 'amount', style: { fontSize: '18px', fontWeight: 'bold', color: bill.bill.balance_amount > 0 ? '#ff4757' : '#2ed573' } }, formatCurrency(bill.bill.balance_amount))
              ]),
              
              React.createElement('div', { key: 'date', style: { padding: '15px', backgroundColor: '#181d36', borderRadius: '8px' } }, [
                React.createElement('strong', { key: 'label', style: { color: '#a259ff', display: 'block', marginBottom: '5px' } }, 'Issue Date'),
                React.createElement('span', { key: 'value' }, new Date(bill.bill.issued_date).toLocaleDateString())
              ])
            ])
          ]),
          
          // Bill Items Table
          bill.items.length > 0 && React.createElement('div', { key: 'items-section' }, [
            React.createElement('h4', { key: 'title', style: { marginBottom: '15px', color: '#a259ff', borderBottom: '2px solid #a259ff', paddingBottom: '8px' } }, 'Bill Items'),
            React.createElement('div', { key: 'table-container', style: { overflowX: 'auto' } }, [
              React.createElement('table', { key: 'table', style: { minWidth: '600px' } }, [
                React.createElement('thead', { key: 'thead' }, [
                  React.createElement('tr', { key: 'header' }, [
                    React.createElement('th', { key: 'name' }, 'Item Name'),
                    React.createElement('th', { key: 'qty' }, 'Quantity'),
                    React.createElement('th', { key: 'price' }, 'Unit Price'),
                    React.createElement('th', { key: 'total' }, 'Total Price')
                  ])
                ]),
                React.createElement('tbody', { key: 'tbody' }, 
                  bill.items.map((item, index) => 
                    React.createElement('tr', { key: index }, [
                      React.createElement('td', { key: 'name' }, [
                        React.createElement('div', { key: 'item-name', style: { fontWeight: 'bold' } }, item.item_name),
                        item.item_description && React.createElement('div', { 
                          key: 'item-desc', 
                          style: { fontSize: '12px', color: '#b3b3b3', marginTop: '2px' } 
                        }, item.item_description)
                      ]),
                      React.createElement('td', { key: 'qty' }, item.quantity),
                      React.createElement('td', { key: 'price', className: 'amount' }, formatCurrency(item.unit_price)),
                      React.createElement('td', { key: 'total', className: 'amount', style: { fontWeight: 'bold' } }, formatCurrency(item.final_price))
                    ])
                  )
                )
              ])
            ])
          ]),
          
          // Payment History Table
          React.createElement('div', { key: 'payments-section' }, [
            React.createElement('div', { key: 'payments-header', style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' } }, [
              React.createElement('h4', { key: 'title', style: { color: '#a259ff', borderBottom: '2px solid #a259ff', paddingBottom: '8px', margin: 0 } }, 'Payment History'),
              bill.bill.balance_amount > 0 && React.createElement('button', {
                key: 'add-payment-btn',
                className: 'btn btn-success',
                onClick: () => setShowPaymentForm(!showPaymentForm),
                style: { padding: '8px 16px', fontSize: '14px' }
              }, showPaymentForm ? 'Cancel Payment' : 'Add Payment')
            ]),
            
            bill.payments.length > 0 ? 
              React.createElement('div', { key: 'table-container', style: { overflowX: 'auto' } }, [
                React.createElement('table', { key: 'table', style: { minWidth: '600px' } }, [
                  React.createElement('thead', { key: 'thead' }, [
                    React.createElement('tr', { key: 'header' }, [
                      React.createElement('th', { key: 'date' }, 'Date'),
                      React.createElement('th', { key: 'amount' }, 'Amount Paid'),
                      React.createElement('th', { key: 'method' }, 'Payment Method'),
                      React.createElement('th', { key: 'reference' }, 'Reference'),
                      React.createElement('th', { key: 'status' }, 'Status')
                    ])
                  ]),
                  React.createElement('tbody', { key: 'tbody' }, 
                    bill.payments.map((payment, index) => 
                      React.createElement('tr', { key: index }, [
                        React.createElement('td', { key: 'date' }, new Date(payment.payment_date).toLocaleDateString()),
                        React.createElement('td', { key: 'amount', className: 'amount', style: { fontWeight: 'bold', color: '#2ed573' } }, formatCurrency(payment.payment_amount)),
                        React.createElement('td', { key: 'method' }, [
                          React.createElement('div', { key: 'method-name', style: { fontWeight: 'bold' } }, payment.payment_method.replace('_', ' ').toUpperCase()),
                          payment.bank_name && React.createElement('div', { 
                            key: 'bank-name', 
                            style: { fontSize: '12px', color: '#b3b3b3' } 
                          }, payment.bank_name),
                          payment.mobile_banking_provider && React.createElement('div', { 
                            key: 'provider', 
                            style: { fontSize: '12px', color: '#b3b3b3' } 
                          }, payment.mobile_banking_provider)
                        ]),
                        React.createElement('td', { key: 'reference' }, payment.payment_reference || 'N/A'),
                        React.createElement('td', { key: 'status' }, getStatusBadge(payment.payment_status))
                      ])
                    )
                  )
                ])
              ]) :
              React.createElement('div', { 
                key: 'no-payments', 
                style: { 
                  textAlign: 'center', 
                  color: '#b3b3b3', 
                  padding: '20px',
                  backgroundColor: '#181d36',
                  borderRadius: '8px'
                } 
              }, 'No payments recorded yet'),
            
            showPaymentForm && React.createElement(PaymentForm, { key: 'payment-form' })
          ])
        ])
      ]);
    };

    if (loading) {
      return React.createElement('div', { className: 'loading' }, 'Loading bills...');
    }

    return React.createElement('div', null, [
      React.createElement('div', { key: 'header', className: 'card' }, [
        React.createElement('div', { key: 'row1', className: 'row' }, [
          React.createElement('div', { key: 'col1', className: 'col-md-6' }, [
            React.createElement('h3', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Bill Management'),
            React.createElement('input', {
              key: 'search',
              type: 'text',
              placeholder: 'Search by patient name or bill ID...',
              value: searchTerm,
              onChange: (e) => setSearchTerm(e.target.value),
              style: { marginBottom: '10px' }
            })
          ]),
          React.createElement('div', { key: 'col2', className: 'col-md-6', style: { display: 'flex', alignItems: 'end', justifyContent: 'flex-end' } }, [
            React.createElement('select', {
              key: 'status-filter',
              value: statusFilter,
              onChange: (e) => setStatusFilter(e.target.value),
              style: { marginRight: '10px', width: '150px' }
            }, [
              React.createElement('option', { key: 'all', value: '' }, 'All Status'),
              React.createElement('option', { key: 'pending', value: 'pending' }, 'Pending'),
              React.createElement('option', { key: 'paid', value: 'paid' }, 'Paid'),
              React.createElement('option', { key: 'partial', value: 'partial' }, 'Partial'),
              React.createElement('option', { key: 'cancelled', value: 'cancelled' }, 'Cancelled')
            ]),
            React.createElement('button', {
              key: 'create-btn',
              className: 'btn btn-success',
              onClick: () => setShowCreateForm(true)
            }, 'Create New Bill')
          ])
        ])
      ]),
      
      React.createElement('div', { key: 'bills-table', className: 'card' }, [
        bills.length > 0 ? 
          React.createElement('table', { key: 'table' }, [
            React.createElement('thead', { key: 'thead' }, [
              React.createElement('tr', { key: 'header' }, [
                React.createElement('th', { key: 'id' }, 'Bill ID'),
                React.createElement('th', { key: 'patient' }, 'Patient'),
                React.createElement('th', { key: 'amount' }, 'Amount'),
                React.createElement('th', { key: 'paid' }, 'Paid'),
                React.createElement('th', { key: 'balance' }, 'Balance'),
                React.createElement('th', { key: 'status' }, 'Status'),
                React.createElement('th', { key: 'date' }, 'Date'),
                React.createElement('th', { key: 'actions' }, 'Actions')
              ])
            ]),
            React.createElement('tbody', { key: 'tbody' }, 
              bills.map(bill => 
                React.createElement('tr', { key: bill.id }, [
                  React.createElement('td', { key: 'id' }, `#${bill.id}`),
                  React.createElement('td', { key: 'patient' }, bill.patient_name),
                  React.createElement('td', { key: 'amount', className: 'amount' }, formatCurrency(bill.total_amount)),
                  React.createElement('td', { key: 'paid', className: 'amount' }, formatCurrency(bill.paid_amount)),
                  React.createElement('td', { key: 'balance', className: 'amount' }, formatCurrency(bill.balance_amount)),
                  React.createElement('td', { key: 'status' }, getStatusBadge(bill.status)),
                  React.createElement('td', { key: 'date' }, new Date(bill.issued_date).toLocaleDateString()),
                  React.createElement('td', { key: 'actions' }, [
                    React.createElement('button', {
                      key: 'view',
                      className: 'btn btn-info',
                      onClick: () => handleViewBillDetails(bill),
                      style: { marginRight: '5px', padding: '5px 10px', fontSize: '12px' }
                    }, 'View')
                  ])
                ])
              )
            )
          ]) :
          React.createElement('div', { key: 'no-bills', style: { textAlign: 'center', color: '#b3b3b3', padding: '20px' } }, 'No bills found'),
        
        pagination.pages > 1 && React.createElement('div', { 
          key: 'pagination', 
          style: { 
            marginTop: '20px', 
            textAlign: 'center',
            display: 'flex',
            justifyContent: 'center',
            gap: '10px'
          } 
        }, [
          React.createElement('button', {
            key: 'prev',
            className: 'btn',
            onClick: () => setPagination(prev => ({ ...prev, page: Math.max(1, prev.page - 1) })),
            disabled: pagination.page === 1
          }, 'Previous'),
          React.createElement('span', { 
            key: 'info', 
            style: { 
              display: 'flex', 
              alignItems: 'center', 
              color: '#b3b3b3' 
            } 
          }, `Page ${pagination.page} of ${pagination.pages}`),
          React.createElement('button', {
            key: 'next',
            className: 'btn',
            onClick: () => setPagination(prev => ({ ...prev, page: Math.min(prev.pages, prev.page + 1) })),
            disabled: pagination.page === pagination.pages
          }, 'Next')
        ])
      ]),
      
      showCreateForm && React.createElement(CreateBillForm, {
        key: 'create-form',
        onSave: () => setShowCreateForm(false),
        onCancel: () => setShowCreateForm(false)
      }),
      
      showBillDetails && selectedBill && React.createElement(BillDetailsModal, {
        key: 'bill-details',
        bill: selectedBill,
        onClose: () => {
          setShowBillDetails(false);
          setSelectedBill(null);
        }
      })
    ]);
  }
  
  window.Components = window.Components || {};
  window.Components.BillManagement = BillManagement;
})();
