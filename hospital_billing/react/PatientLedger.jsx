(function(){
  const { useState, useEffect } = React;
  
  function PatientLedger({ onError, onSuccess }) {
    const [ledger, setLedger] = useState([]);
    const [loading, setLoading] = useState(true);
    const [pagination, setPagination] = useState({ page: 1, limit: 20, total: 0, pages: 1 });
    const [patientId, setPatientId] = useState('');
    const [patientName, setPatientName] = useState('');
    const [patientInfo, setPatientInfo] = useState(null);
    const [balance, setBalance] = useState(0);

    useEffect(() => {
      if (patientId) {
        loadPatientLedger();
        loadPatientInfo();
      }
    }, [patientId, pagination.page]);

    const loadPatientLedger = async () => {
      try {
        setLoading(true);
        const response = await axios.get(`billing_api.php?action=patient_ledger&patient_id=${patientId}&page=${pagination.page}&limit=${pagination.limit}`);
        if (response.data.success) {
          setLedger(response.data.data);
          setPagination(response.data.pagination);
          calculateBalance(response.data.data);
        } else {
          onError(response.data.error || 'Failed to load patient ledger');
        }
      } catch (err) {
        onError('Failed to load patient ledger');
      } finally {
        setLoading(false);
      }
    };

    const loadPatientInfo = async () => {
      try {
        // This would be implemented to get patient details
        // For now, we'll use a placeholder
        setPatientInfo({
          id: patientId,
          name: patientName,
          phone: 'N/A',
          email: 'N/A'
        });
      } catch (err) {
        console.error('Failed to load patient info:', err);
      }
    };

    const calculateBalance = (transactions) => {
      let balance = 0;
      transactions.forEach(transaction => {
        if (transaction.transaction_type === 'charge') {
          balance += parseFloat(transaction.amount);
        } else if (transaction.transaction_type === 'payment') {
          balance -= parseFloat(transaction.amount);
        } else if (transaction.transaction_type === 'refund') {
          balance -= parseFloat(transaction.amount);
        } else if (transaction.transaction_type === 'discount') {
          balance -= parseFloat(transaction.amount);
        }
      });
      setBalance(balance);
    };

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('en-BD', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 2
      }).format(amount);
    };

    const getTransactionTypeBadge = (type) => {
      const typeClasses = {
        'charge': 'status-pending',
        'payment': 'status-paid',
        'refund': 'status-cancelled',
        'discount': 'status-partial'
      };
      
      return React.createElement('span', {
        className: `status-badge ${typeClasses[type] || 'status-pending'}`
      }, type.toUpperCase());
    };

    const handleSearch = () => {
      if (patientId <= 0) {
        onError('Please enter a valid patient ID');
        return;
      }
      setPagination(prev => ({ ...prev, page: 1 }));
    };

    if (loading) {
      return React.createElement('div', { className: 'loading' }, 'Loading patient ledger...');
    }

    return React.createElement('div', null, [
      React.createElement('div', { key: 'header', className: 'card' }, [
        React.createElement('h3', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Patient Ledger'),
        
        React.createElement('div', { key: 'search', className: 'row' }, [
          React.createElement('div', { key: 'col1', className: 'col-md-4' }, [
            React.createElement('label', { key: 'label' }, 'Patient ID'),
            React.createElement('input', {
              key: 'input',
              type: 'number',
              value: patientId,
              onChange: (e) => setPatientId(e.target.value),
              placeholder: 'Enter Patient ID'
            })
          ]),
          React.createElement('div', { key: 'col2', className: 'col-md-4' }, [
            React.createElement('label', { key: 'label' }, 'Patient Name'),
            React.createElement('input', {
              key: 'input',
              type: 'text',
              value: patientName,
              onChange: (e) => setPatientName(e.target.value),
              placeholder: 'Enter Patient Name'
            })
          ]),
          React.createElement('div', { key: 'col3', className: 'col-md-4', style: { display: 'flex', alignItems: 'end' } }, [
            React.createElement('button', {
              key: 'search-btn',
              className: 'btn btn-success',
              onClick: handleSearch,
              style: { width: '100%' }
            }, 'Search Ledger')
          ])
        ])
      ]),
      
      patientInfo && React.createElement('div', { key: 'patient-info', className: 'card' }, [
        React.createElement('h4', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Patient Information'),
        
        React.createElement('div', { key: 'info', className: 'row' }, [
          React.createElement('div', { key: 'col1', className: 'col-md-3' }, [
            React.createElement('strong', { key: 'label' }, 'Patient ID: '),
            React.createElement('span', { key: 'value' }, patientInfo.id)
          ]),
          React.createElement('div', { key: 'col2', className: 'col-md-3' }, [
            React.createElement('strong', { key: 'label' }, 'Name: '),
            React.createElement('span', { key: 'value' }, patientInfo.name)
          ]),
          React.createElement('div', { key: 'col3', className: 'col-md-3' }, [
            React.createElement('strong', { key: 'label' }, 'Phone: '),
            React.createElement('span', { key: 'value' }, patientInfo.phone)
          ]),
          React.createElement('div', { key: 'col4', className: 'col-md-3' }, [
            React.createElement('strong', { key: 'label' }, 'Email: '),
            React.createElement('span', { key: 'value' }, patientInfo.email)
          ])
        ])
      ]),
      
      ledger.length > 0 && React.createElement('div', { key: 'ledger-table', className: 'card' }, [
        React.createElement('div', { key: 'header', style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' } }, [
          React.createElement('h4', { key: 'title', style: { color: '#a259ff', margin: 0 } }, 'Transaction History'),
          React.createElement('div', { key: 'balance', style: { textAlign: 'right' } }, [
            React.createElement('div', { key: 'label', style: { color: '#b3b3b3', fontSize: '14px' } }, 'Current Balance'),
            React.createElement('div', { key: 'amount', className: 'amount', style: { fontSize: '18px', fontWeight: 'bold' } }, formatCurrency(balance))
          ])
        ]),
        
        React.createElement('table', { key: 'table' }, [
          React.createElement('thead', { key: 'thead' }, [
            React.createElement('tr', { key: 'header' }, [
              React.createElement('th', { key: 'date' }, 'Date'),
              React.createElement('th', { key: 'type' }, 'Type'),
              React.createElement('th', { key: 'description' }, 'Description'),
              React.createElement('th', { key: 'amount' }, 'Amount'),
              React.createElement('th', { key: 'balance' }, 'Running Balance'),
              React.createElement('th', { key: 'created_by' }, 'Created By')
            ])
          ]),
          React.createElement('tbody', { key: 'tbody' }, 
            ledger.map((transaction, index) => {
              const runningBalance = ledger.slice(0, index + 1).reduce((sum, t) => {
                if (t.transaction_type === 'charge') return sum + parseFloat(t.amount);
                else return sum - parseFloat(t.amount);
              }, 0);
              
              return React.createElement('tr', { key: transaction.id }, [
                React.createElement('td', { key: 'date' }, new Date(transaction.created_at).toLocaleDateString()),
                React.createElement('td', { key: 'type' }, getTransactionTypeBadge(transaction.transaction_type)),
                React.createElement('td', { key: 'description' }, transaction.description || 'N/A'),
                React.createElement('td', { 
                  key: 'amount', 
                  className: 'amount',
                  style: { 
                    color: transaction.transaction_type === 'charge' ? '#ff4757' : '#2ed573',
                    fontWeight: 'bold'
                  }
                }, 
                  transaction.transaction_type === 'charge' ? '+' : '-',
                  formatCurrency(transaction.amount)
                ),
                React.createElement('td', { key: 'balance', className: 'amount' }, formatCurrency(runningBalance)),
                React.createElement('td', { key: 'created_by' }, transaction.created_by_name || 'System')
              ]);
            })
          )
        ]),
        
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
      
      !patientId && React.createElement('div', { key: 'no-search', className: 'card' }, [
        React.createElement('div', { 
          key: 'message', 
          style: { 
            textAlign: 'center', 
            color: '#b3b3b3', 
            padding: '40px' 
          } 
        }, [
          React.createElement('h4', { key: 'title', style: { marginBottom: '15px' } }, 'Search Patient Ledger'),
          React.createElement('p', { key: 'text' }, 'Enter a patient ID above to view their financial transaction history and current balance.')
        ])
      ]),
      
      patientId && ledger.length === 0 && !loading && React.createElement('div', { key: 'no-ledger', className: 'card' }, [
        React.createElement('div', { 
          key: 'message', 
          style: { 
            textAlign: 'center', 
            color: '#b3b3b3', 
            padding: '40px' 
          } 
        }, [
          React.createElement('h4', { key: 'title', style: { marginBottom: '15px' } }, 'No Transactions Found'),
          React.createElement('p', { key: 'text' }, 'No financial transactions found for this patient.')
        ])
      ])
    ]);
  }
  
  window.Components = window.Components || {};
  window.Components.PatientLedger = PatientLedger;
})();
