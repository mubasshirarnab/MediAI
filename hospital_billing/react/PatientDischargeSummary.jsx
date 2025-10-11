(function(){
  const { useState, useEffect } = React;
  
  function PatientDischargeSummary({ onError, onSuccess }) {
    const [patientId, setPatientId] = useState('');
    const [patientInfo, setPatientInfo] = useState(null);
    const [allCharges, setAllCharges] = useState([]);
    const [loading, setLoading] = useState(false);
    const [searchPerformed, setSearchPerformed] = useState(false);

    const searchPatientCharges = async () => {
      if (!patientId.trim()) {
        onError('Please enter a Patient ID');
        return;
      }

      try {
        setLoading(true);
        setSearchPerformed(true);
        
        // Search for patient charges
        const response = await axios.get(`billing_api.php?action=patient_discharge_summary&patient_id=${patientId}`);
        
        if (response.data.success) {
          setPatientInfo(response.data.patient_info);
          setAllCharges(response.data.charges);
        } else {
          onError(response.data.error || 'No charges found for this patient');
          setPatientInfo(null);
          setAllCharges([]);
        }
      } catch (err) {
        onError('Failed to load patient charges');
        setPatientInfo(null);
        setAllCharges([]);
      } finally {
        setLoading(false);
      }
    };

    const generateDischargeBill = async () => {
      if (!patientInfo || allCharges.length === 0) {
        onError('No charges to generate bill');
        return;
      }

      try {
        setLoading(true);
        
        const response = await axios.post('billing_api.php?action=generate_discharge_bill', {
          patient_id: patientId,
          bill_type: 'final'
        });
        
        if (response.data.success) {
          onSuccess('Discharge bill generated successfully');
          // Refresh the data
          searchPatientCharges();
        } else {
          onError(response.data.error || 'Failed to generate discharge bill');
        }
      } catch (err) {
        onError('Failed to generate discharge bill');
      } finally {
        setLoading(false);
      }
    };

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('en-BD', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 2
      }).format(amount);
    };

    const calculateTotal = () => {
      return allCharges.reduce((sum, charge) => sum + charge.total_price, 0);
    };

    const groupChargesByBill = () => {
      const grouped = {};
      allCharges.forEach(charge => {
        const billId = charge.bill_id;
        if (!grouped[billId]) {
          grouped[billId] = {
            bill_id: billId,
            bill_type: charge.bill_type,
            issued_date: charge.issued_date,
            status: charge.status,
            items: []
          };
        }
        grouped[billId].items.push(charge);
      });
      return Object.values(grouped);
    };

    const groupedBills = groupChargesByBill();

    return React.createElement('div', { className: 'patient-discharge-summary' }, [
      React.createElement('div', { key: 'header', className: 'card' }, [
        React.createElement('h3', { key: 'title', style: { marginBottom: '20px', color: '#a259ff' } }, 'Patient Discharge Bill Summary'),
        
        React.createElement('div', { key: 'search-section', className: 'row' }, [
          React.createElement('div', { key: 'col1', className: 'col-md-8' }, [
            React.createElement('div', { key: 'search-form', className: 'form-group' }, [
              React.createElement('label', { key: 'label' }, 'Patient ID'),
              React.createElement('input', {
                key: 'input',
                type: 'number',
                value: patientId,
                onChange: (e) => setPatientId(e.target.value),
                placeholder: 'Enter Patient ID to search charges',
                onKeyPress: (e) => e.key === 'Enter' && searchPatientCharges()
              })
            ])
          ]),
          React.createElement('div', { key: 'col2', className: 'col-md-4' }, [
            React.createElement('div', { key: 'search-btn', className: 'form-group' }, [
              React.createElement('label', { key: 'label', style: { visibility: 'hidden' } }, 'Search'),
              React.createElement('button', {
                key: 'btn',
                className: 'btn btn-primary',
                onClick: searchPatientCharges,
                disabled: loading || !patientId.trim(),
                style: { width: '100%' }
              }, loading ? 'Searching...' : 'Search Charges')
            ])
          ])
        ])
      ]),

      // Patient Information
      patientInfo && React.createElement('div', { key: 'patient-info', className: 'card' }, [
        React.createElement('h4', { key: 'title', style: { marginBottom: '15px', color: '#a259ff', borderBottom: '2px solid #a259ff', paddingBottom: '8px' } }, 'Patient Information'),
        
        React.createElement('div', { key: 'info-grid', style: { 
          display: 'grid', 
          gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', 
          gap: '15px' 
        } }, [
          React.createElement('div', { key: 'name', style: { padding: '15px', backgroundColor: '#181d36', borderRadius: '8px' } }, [
            React.createElement('strong', { key: 'label', style: { color: '#a259ff', display: 'block', marginBottom: '5px' } }, 'Patient Name'),
            React.createElement('span', { key: 'value' }, patientInfo.name)
          ]),
          
          React.createElement('div', { key: 'id', style: { padding: '15px', backgroundColor: '#181d36', borderRadius: '8px' } }, [
            React.createElement('strong', { key: 'label', style: { color: '#a259ff', display: 'block', marginBottom: '5px' } }, 'Patient ID'),
            React.createElement('span', { key: 'value' }, patientInfo.id)
          ]),
          
          React.createElement('div', { key: 'phone', style: { padding: '15px', backgroundColor: '#181d36', borderRadius: '8px' } }, [
            React.createElement('strong', { key: 'label', style: { color: '#a259ff', display: 'block', marginBottom: '5px' } }, 'Phone'),
            React.createElement('span', { key: 'value' }, patientInfo.phone)
          ]),
          
          React.createElement('div', { key: 'email', style: { padding: '15px', backgroundColor: '#181d36', borderRadius: '8px' } }, [
            React.createElement('strong', { key: 'label', style: { color: '#a259ff', display: 'block', marginBottom: '5px' } }, 'Email'),
            React.createElement('span', { key: 'value' }, patientInfo.email)
          ])
        ])
      ]),

      // Charges Summary
      allCharges.length > 0 && React.createElement('div', { key: 'charges-summary', className: 'card' }, [
        React.createElement('div', { key: 'summary-header', style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' } }, [
          React.createElement('h4', { key: 'title', style: { color: '#a259ff', borderBottom: '2px solid #a259ff', paddingBottom: '8px', margin: 0 } }, 'All Charges Summary'),
          React.createElement('button', {
            key: 'generate-btn',
            className: 'btn btn-success',
            onClick: generateDischargeBill,
            disabled: loading,
            style: { padding: '10px 20px' }
          }, loading ? 'Generating...' : 'Generate Discharge Bill')
        ]),
        
        React.createElement('div', { key: 'total-display', style: { 
          marginBottom: '20px',
          padding: '20px',
          backgroundColor: '#181d36',
          borderRadius: '8px',
          border: '1px solid #2a2a4a',
          textAlign: 'center'
        } }, [
          React.createElement('h3', { key: 'total-label', style: { margin: '0 0 10px 0', color: '#a259ff' } }, 'Grand Total'),
          React.createElement('span', { key: 'total-amount', className: 'amount', style: { fontSize: '32px', fontWeight: 'bold' } }, formatCurrency(calculateTotal()))
        ]),

        // Bills grouped by bill_id
        groupedBills.map((bill, billIndex) => 
          React.createElement('div', { key: `bill-${bill.bill_id}`, style: { marginBottom: '25px' } }, [
            React.createElement('div', { key: 'bill-header', style: { 
              padding: '15px', 
              backgroundColor: '#181d36', 
              borderRadius: '8px',
              marginBottom: '10px',
              border: '1px solid #2a2a4a'
            } }, [
              React.createElement('div', { key: 'bill-info', style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center' } }, [
                React.createElement('div', { key: 'bill-details' }, [
                  React.createElement('h5', { key: 'bill-title', style: { margin: '0 0 5px 0', color: '#a259ff' } }, `Bill #${bill.bill_id} - ${bill.bill_type.toUpperCase()}`),
                  React.createElement('div', { key: 'bill-meta', style: { fontSize: '14px', color: '#b3b3b3' } }, [
                    React.createElement('span', { key: 'date' }, `Issued: ${new Date(bill.issued_date).toLocaleDateString()}`),
                    React.createElement('span', { key: 'separator', style: { margin: '0 10px' } }, '•'),
                    React.createElement('span', { key: 'status', className: `status-badge status-${bill.status}` }, bill.status.toUpperCase())
                  ])
                ]),
                React.createElement('div', { key: 'bill-total', className: 'amount', style: { fontSize: '18px', fontWeight: 'bold' } }, 
                  formatCurrency(bill.items.reduce((sum, item) => sum + item.total_price, 0)))
              ])
            ]),
            
            React.createElement('div', { key: 'bill-items', style: { overflowX: 'auto' } }, [
              React.createElement('table', { key: 'table', style: { minWidth: '600px' } }, [
                React.createElement('thead', { key: 'thead' }, [
                  React.createElement('tr', { key: 'header' }, [
                    React.createElement('th', { key: 'item' }, 'Item Name'),
                    React.createElement('th', { key: 'type' }, 'Type'),
                    React.createElement('th', { key: 'qty' }, 'Qty'),
                    React.createElement('th', { key: 'price' }, 'Unit Price'),
                    React.createElement('th', { key: 'total' }, 'Total Price'),
                    React.createElement('th', { key: 'date' }, 'Date')
                  ])
                ]),
                React.createElement('tbody', { key: 'tbody' }, 
                  bill.items.map((item, itemIndex) => 
                    React.createElement('tr', { key: itemIndex }, [
                      React.createElement('td', { key: 'item' }, [
                        React.createElement('div', { key: 'item-name', style: { fontWeight: 'bold' } }, item.item_name),
                        item.item_description && React.createElement('div', { 
                          key: 'item-desc', 
                          style: { fontSize: '12px', color: '#b3b3b3' } 
                        }, item.item_description)
                      ]),
                      React.createElement('td', { key: 'type' }, item.item_type),
                      React.createElement('td', { key: 'qty' }, item.quantity),
                      React.createElement('td', { key: 'price', className: 'amount' }, formatCurrency(item.unit_price)),
                      React.createElement('td', { key: 'total', className: 'amount', style: { fontWeight: 'bold' } }, formatCurrency(item.total_price)),
                      React.createElement('td', { key: 'date' }, new Date(item.item_date).toLocaleDateString())
                    ])
                  )
                )
              ])
            ])
          ])
        )
      ]),

      // No charges found message
      searchPerformed && !loading && allCharges.length === 0 && React.createElement('div', { 
        key: 'no-charges', 
        className: 'card',
        style: { textAlign: 'center', padding: '40px' }
      }, [
        React.createElement('h4', { key: 'title', style: { color: '#b3b3b3', marginBottom: '10px' } }, 'No Charges Found'),
        React.createElement('p', { key: 'message', style: { color: '#b3b3b3' } }, `No charges found for Patient ID: ${patientId}`)
      ])
    ]);
  }
  
  window.Components = window.Components || {};
  window.Components.PatientDischargeSummary = PatientDischargeSummary;
})();
