(function(){
  const { useState, useEffect } = React;
  
  function PatientLedger({ onError, onSuccess }) {
    const [ledger, setLedger] = useState([]);
    const [loading, setLoading] = useState(false);
    const [pagination, setPagination] = useState({ page: 1, limit: 20, total: 0, pages: 1 });
    const [patientId, setPatientId] = useState('');
    const [patientName, setPatientName] = useState('');
    const [patientInfo, setPatientInfo] = useState(null);
    const [summary, setSummary] = useState(null);
    const [searchResults, setSearchResults] = useState([]);
    const [showSearchResults, setShowSearchResults] = useState(false);
    const [searchLoading, setSearchLoading] = useState(false);

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
          setSummary(response.data.summary);
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
        const response = await axios.get(`billing_api.php?action=get_patients&patient_id=${patientId}`);
        if (response.data.success && response.data.data.length > 0) {
          setPatientInfo(response.data.data[0]);
        }
      } catch (err) {
        console.error('Failed to load patient info:', err);
      }
    };

    const searchPatients = async (searchTerm) => {
      if (searchTerm.length < 2) {
        setSearchResults([]);
        setShowSearchResults(false);
        return;
      }

      try {
        setSearchLoading(true);
        const response = await axios.get(`billing_api.php?action=get_patients&search=${encodeURIComponent(searchTerm)}`);
        if (response.data.success) {
          setSearchResults(response.data.data);
          setShowSearchResults(true);
        }
      } catch (err) {
        console.error('Failed to search patients:', err);
      } finally {
        setSearchLoading(false);
      }
    };

    const selectPatient = (patient) => {
      setPatientId(patient.id);
      setPatientName(patient.name);
      setShowSearchResults(false);
      setSearchResults([]);
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

    const getReferenceBadge = (transaction) => {
      if (!transaction.reference_details) return null;
      
      let badgeClass = 'badge-primary';
      let badgeText = transaction.reference_details;
      
      if (transaction.reference_type === 'bill') {
        badgeClass = transaction.reference_status === 'paid' ? 'status-paid' : 'status-pending';
        badgeText = `${transaction.reference_details.toUpperCase()} BILL`;
      } else if (transaction.reference_type === 'payment') {
        badgeClass = 'status-paid';
        badgeText = `${transaction.reference_details.replace('_', ' ').toUpperCase()} PAYMENT`;
      }
      
      return React.createElement('span', {
        className: `status-badge ${badgeClass}`,
        style: { fontSize: '0.7rem', marginLeft: '5px' }
      }, badgeText);
    };

    const handleSearch = () => {
      if (patientId <= 0) {
        onError('Please enter a valid patient ID');
        return;
      }
      setPagination(prev => ({ ...prev, page: 1 }));
    };

    const clearSearch = () => {
      setPatientId('');
      setPatientName('');
      setPatientInfo(null);
      setLedger([]);
      setSummary(null);
      setSearchResults([]);
      setShowSearchResults(false);
    };

    if (loading) {
      return React.createElement('div', { className: 'loading' }, 'Loading patient ledger...');
    }

    return React.createElement('div', null, [
      // Search Section
      React.createElement('div', { key: 'search-section', className: 'card' }, [
        React.createElement('h3', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Patient Ledger Search'),
        
        React.createElement('div', { key: 'search-form', className: 'row' }, [
          React.createElement('div', { key: 'col1', className: 'col-md-6' }, [
            React.createElement('label', { key: 'label' }, 'Search Patient'),
            React.createElement('div', { key: 'search-container', style: { position: 'relative' } }, [
              React.createElement('input', {
                key: 'search-input',
                type: 'text',
                value: patientName,
                onChange: (e) => {
                  setPatientName(e.target.value);
                  searchPatients(e.target.value);
                },
                placeholder: 'Type patient name, phone, email, or ID...',
                style: { paddingRight: '40px' }
              }),
              searchLoading && React.createElement('div', {
                key: 'loading-spinner',
                style: {
                  position: 'absolute',
                  right: '10px',
                  top: '50%',
                  transform: 'translateY(-50%)',
                  color: '#a259ff'
                }
              }, '⏳'),
              
              showSearchResults && searchResults.length > 0 && React.createElement('div', {
                key: 'search-results',
                style: {
                  position: 'absolute',
                  top: '100%',
                  left: 0,
                  right: 0,
                  backgroundColor: '#13153a',
                  border: '1px solid #2a2a4a',
                  borderRadius: '8px',
                  maxHeight: '200px',
                  overflowY: 'auto',
                  zIndex: 1000,
                  marginTop: '2px'
                }
              }, searchResults.map(patient => 
                React.createElement('div', {
                  key: patient.id,
                  style: {
                    padding: '10px 15px',
                    cursor: 'pointer',
                    borderBottom: '1px solid #2a2a4a',
                    transition: 'background-color 0.2s'
                  },
                  onMouseOver: (e) => e.target.style.backgroundColor = '#2a2a4a',
                  onMouseOut: (e) => e.target.style.backgroundColor = 'transparent',
                  onClick: () => selectPatient(patient)
                }, [
                  React.createElement('div', { key: 'name', style: { fontWeight: '600', color: '#fff' } }, patient.name),
                  React.createElement('div', { key: 'details', style: { fontSize: '0.8rem', color: '#b3b3b3' } }, 
                    `ID: ${patient.id} | Phone: ${patient.phone} | Email: ${patient.email}`)
                ])
              ))
            ])
          ]),
          
          React.createElement('div', { key: 'col2', className: 'col-md-3' }, [
            React.createElement('label', { key: 'label' }, 'Patient ID'),
            React.createElement('input', {
              key: 'input',
              type: 'number',
              value: patientId,
              onChange: (e) => setPatientId(e.target.value),
              placeholder: 'Enter Patient ID'
            })
          ]),
          
          React.createElement('div', { key: 'col3', className: 'col-md-3', style: { display: 'flex', alignItems: 'end', gap: '10px' } }, [
            React.createElement('button', {
              key: 'search-btn',
              className: 'btn btn-success',
              onClick: handleSearch,
              style: { flex: 1 }
            }, 'Search Ledger'),
            patientId && React.createElement('button', {
              key: 'clear-btn',
              className: 'btn btn-danger',
              onClick: clearSearch,
              style: { flex: 1 }
            }, 'Clear')
          ])
        ])
      ]),
      
      // Patient Information
      patientInfo && React.createElement('div', { key: 'patient-info', className: 'card' }, [
        React.createElement('h4', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Patient Information'),
        
        React.createElement('div', { key: 'info-grid', className: 'row' }, [
          React.createElement('div', { key: 'col1', className: 'col-md-3' }, [
            React.createElement('div', { key: 'info-item', style: { marginBottom: '10px' } }, [
              React.createElement('strong', { key: 'label', style: { color: '#b3b3b3' } }, 'Patient ID: '),
              React.createElement('span', { key: 'value', style: { color: '#fff' } }, patientInfo.id)
            ])
          ]),
          React.createElement('div', { key: 'col2', className: 'col-md-3' }, [
            React.createElement('div', { key: 'info-item', style: { marginBottom: '10px' } }, [
              React.createElement('strong', { key: 'label', style: { color: '#b3b3b3' } }, 'Name: '),
              React.createElement('span', { key: 'value', style: { color: '#fff' } }, patientInfo.name)
            ])
          ]),
          React.createElement('div', { key: 'col3', className: 'col-md-3' }, [
            React.createElement('div', { key: 'info-item', style: { marginBottom: '10px' } }, [
              React.createElement('strong', { key: 'label', style: { color: '#b3b3b3' } }, 'Phone: '),
              React.createElement('span', { key: 'value', style: { color: '#fff' } }, patientInfo.phone)
            ])
          ]),
          React.createElement('div', { key: 'col4', className: 'col-md-3' }, [
            React.createElement('div', { key: 'info-item', style: { marginBottom: '10px' } }, [
              React.createElement('strong', { key: 'label', style: { color: '#b3b3b3' } }, 'Email: '),
              React.createElement('span', { key: 'value', style: { color: '#fff' } }, patientInfo.email)
            ])
          ])
        ])
      ]),
      
      // Financial Summary
      summary && React.createElement('div', { key: 'summary', className: 'card' }, [
        React.createElement('h4', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Financial Summary'),
        
        React.createElement('div', { key: 'summary-grid', className: 'stats-grid' }, [
          React.createElement('div', { key: 'total-charged', className: 'stat-card' }, [
            React.createElement('div', { key: 'value', className: 'stat-value', style: { color: '#ff4757' } }, formatCurrency(summary.total_charged)),
            React.createElement('div', { key: 'label', className: 'stat-label' }, 'Total Charged')
          ]),
          React.createElement('div', { key: 'total-paid', className: 'stat-card' }, [
            React.createElement('div', { key: 'value', className: 'stat-value', style: { color: '#2ed573' } }, formatCurrency(summary.total_paid)),
            React.createElement('div', { key: 'label', className: 'stat-label' }, 'Total Paid')
          ]),
          React.createElement('div', { key: 'total-refunded', className: 'stat-card' }, [
            React.createElement('div', { key: 'value', className: 'stat-value', style: { color: '#ffa502' } }, formatCurrency(summary.total_refunded)),
            React.createElement('div', { key: 'label', className: 'stat-label' }, 'Total Refunded')
          ]),
          React.createElement('div', { key: 'total-discounted', className: 'stat-card' }, [
            React.createElement('div', { key: 'value', className: 'stat-value', style: { color: '#3742fa' } }, formatCurrency(summary.total_discounted)),
            React.createElement('div', { key: 'label', className: 'stat-label' }, 'Total Discounted')
          ]),
          React.createElement('div', { key: 'current-balance', className: 'stat-card' }, [
            React.createElement('div', { 
              key: 'value', 
              className: 'stat-value', 
              style: { 
                color: summary.current_balance >= 0 ? '#ff4757' : '#2ed573',
                fontSize: '1.8rem',
                fontWeight: '700'
              } 
            }, formatCurrency(summary.current_balance)),
            React.createElement('div', { key: 'label', className: 'stat-label' }, 'Current Balance')
          ])
        ])
      ]),
      
      // Transaction History
      ledger.length > 0 && React.createElement('div', { key: 'ledger-table', className: 'card' }, [
        React.createElement('div', { key: 'header', style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' } }, [
          React.createElement('h4', { key: 'title', style: { color: '#a259ff', margin: 0 } }, 'Transaction History'),
          React.createElement('div', { key: 'transaction-count', style: { color: '#b3b3b3', fontSize: '14px' } }, 
            `${pagination.total} transactions`)
        ]),
        
        React.createElement('table', { key: 'table' }, [
          React.createElement('thead', { key: 'thead' }, [
            React.createElement('tr', { key: 'header' }, [
              React.createElement('th', { key: 'date' }, 'Date'),
              React.createElement('th', { key: 'type' }, 'Type'),
              React.createElement('th', { key: 'description' }, 'Description'),
              React.createElement('th', { key: 'reference' }, 'Reference'),
              React.createElement('th', { key: 'amount' }, 'Amount'),
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
                React.createElement('td', { key: 'description' }, [
                  React.createElement('div', { key: 'desc', style: { marginBottom: '5px' } }, transaction.description || 'N/A'),
                  React.createElement('div', { key: 'balance', style: { fontSize: '0.8rem', color: '#b3b3b3' } }, 
                    `Balance: ${formatCurrency(runningBalance)}`)
                ]),
                React.createElement('td', { key: 'reference' }, [
                  transaction.reference_type && React.createElement('div', { key: 'ref-type', style: { fontSize: '0.8rem', color: '#b3b3b3' } }, 
                    `${transaction.reference_type.toUpperCase()} #${transaction.reference_id}`),
                  getReferenceBadge(transaction)
                ]),
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
      
      // Empty States
      !patientId && React.createElement('div', { key: 'no-search', className: 'card' }, [
        React.createElement('div', { 
          key: 'message', 
          style: { 
            textAlign: 'center', 
            color: '#b3b3b3', 
            padding: '40px' 
          } 
        }, [
          React.createElement('div', { key: 'icon', style: { fontSize: '4rem', marginBottom: '20px' } }, '📊'),
          React.createElement('h4', { key: 'title', style: { marginBottom: '15px' } }, 'Search Patient Ledger'),
          React.createElement('p', { key: 'text' }, 'Enter a patient name or ID above to view their complete financial transaction history, current balance, and payment details.')
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
          React.createElement('div', { key: 'icon', style: { fontSize: '4rem', marginBottom: '20px' } }, '📋'),
          React.createElement('h4', { key: 'title', style: { marginBottom: '15px' } }, 'No Transactions Found'),
          React.createElement('p', { key: 'text' }, 'No financial transactions found for this patient. Transactions will appear here when bills are created or payments are made.')
        ])
      ])
    ]);
  }
  
  window.Components = window.Components || {};
  window.Components.PatientLedger = PatientLedger;
})();