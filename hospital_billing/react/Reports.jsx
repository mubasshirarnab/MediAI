(function(){
  const { useState, useEffect } = React;
  
  function Reports({ onError, onSuccess, refresh }) {
    const [activeReport, setActiveReport] = useState('daily_collection');
    const [loading, setLoading] = useState(false);
    const [reportData, setReportData] = useState(null);
    const [dateRange, setDateRange] = useState({
      start_date: new Date().toISOString().split('T')[0],
      end_date: new Date().toISOString().split('T')[0]
    });

    useEffect(() => {
      if (activeReport) {
        loadReportData();
      }
    }, [activeReport, dateRange]);

    useEffect(() => {
      if (typeof refresh !== 'undefined') {
        loadReportData();
      }
    }, [refresh]);

    const loadReportData = async () => {
      try {
        setLoading(true);
        
        switch (activeReport) {
          case 'daily_collection':
            const collectionResponse = await axios.get(`billing_api.php?action=daily_collection&date=${dateRange.start_date}`);
            if (collectionResponse.data.success) {
              setReportData(collectionResponse.data.data);
            } else {
              onError(collectionResponse.data.error || 'Failed to load collection report');
            }
            break;
            
          case 'outstanding':
            const outstandingResponse = await axios.get('billing_api.php?action=outstanding_reports');
            if (outstandingResponse.data.success) {
              setReportData(outstandingResponse.data);
            } else {
              onError(outstandingResponse.data.error || 'Failed to load outstanding report');
            }
            break;
            
          case 'service_revenue':
            // This would be implemented in the API
            setReportData({ message: 'Service revenue report coming soon' });
            break;
            
          case 'patient_reports':
            // This would be implemented in the API
            setReportData({ message: 'Patient reports coming soon' });
            break;
            
          default:
            setReportData(null);
        }
      } catch (err) {
        onError('Failed to load report data');
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

    const DailyCollectionReport = ({ data }) => {
      if (!data) return null;
      
      return React.createElement('div', null, [
        React.createElement('div', { key: 'summary', className: 'card' }, [
          React.createElement('h4', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Collection Summary'),
          React.createElement('div', { key: 'row', className: 'row' }, [
            React.createElement('div', { key: 'col1', className: 'col-md-6' }, [
              React.createElement('strong', { key: 'label' }, 'Date: '),
              React.createElement('span', { key: 'value' }, new Date(data.date).toLocaleDateString())
            ]),
            React.createElement('div', { key: 'col2', className: 'col-md-6' }, [
              React.createElement('strong', { key: 'label' }, 'Total Collection: '),
              React.createElement('span', { key: 'value', className: 'amount' }, formatCurrency(data.total_amount))
            ])
          ])
        ]),
        
        data.collection.length > 0 && React.createElement('div', { key: 'details', className: 'card' }, [
          React.createElement('h4', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Collection by Payment Method'),
          React.createElement('table', { key: 'table' }, [
            React.createElement('thead', { key: 'thead' }, [
              React.createElement('tr', { key: 'header' }, [
                React.createElement('th', { key: 'method' }, 'Payment Method'),
                React.createElement('th', { key: 'count' }, 'Transactions'),
                React.createElement('th', { key: 'amount' }, 'Amount'),
                React.createElement('th', { key: 'percentage' }, 'Percentage')
              ])
            ]),
            React.createElement('tbody', { key: 'tbody' }, 
              data.collection.map(item => {
                const percentage = data.total_amount > 0 ? ((item.total_amount / data.total_amount) * 100).toFixed(1) : 0;
                return React.createElement('tr', { key: item.payment_method }, [
                  React.createElement('td', { key: 'method' }, item.payment_method.replace('_', ' ').toUpperCase()),
                  React.createElement('td', { key: 'count' }, item.transaction_count),
                  React.createElement('td', { key: 'amount', className: 'amount' }, formatCurrency(item.total_amount)),
                  React.createElement('td', { key: 'percentage' }, `${percentage}%`)
                ]);
              })
            )
          ])
        ])
      ]);
    };

    const OutstandingReport = ({ data }) => {
      if (!data) return null;
      
      return React.createElement('div', null, [
        React.createElement('div', { key: 'summary', className: 'card' }, [
          React.createElement('h4', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Outstanding Summary'),
          React.createElement('div', { key: 'row', className: 'row' }, [
            React.createElement('div', { key: 'col1', className: 'col-md-6' }, [
              React.createElement('strong', { key: 'label' }, 'Total Outstanding: '),
              React.createElement('span', { key: 'value', className: 'amount' }, formatCurrency(data.total_outstanding))
            ]),
            React.createElement('div', { key: 'col2', className: 'col-md-6' }, [
              React.createElement('strong', { key: 'label' }, 'Number of Bills: '),
              React.createElement('span', { key: 'value' }, data.data.length)
            ])
          ])
        ]),
        
        data.data.length > 0 && React.createElement('div', { key: 'details', className: 'card' }, [
          React.createElement('h4', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Outstanding Bills'),
          React.createElement('table', { key: 'table' }, [
            React.createElement('thead', { key: 'thead' }, [
              React.createElement('tr', { key: 'header' }, [
                React.createElement('th', { key: 'bill_id' }, 'Bill ID'),
                React.createElement('th', { key: 'patient' }, 'Patient'),
                React.createElement('th', { key: 'phone' }, 'Phone'),
                React.createElement('th', { key: 'total' }, 'Total Amount'),
                React.createElement('th', { key: 'paid' }, 'Paid Amount'),
                React.createElement('th', { key: 'balance' }, 'Balance'),
                React.createElement('th', { key: 'date' }, 'Issue Date'),
                React.createElement('th', { key: 'due' }, 'Due Date')
              ])
            ]),
            React.createElement('tbody', { key: 'tbody' }, 
              data.data.map(bill => 
                React.createElement('tr', { key: bill.bill_id }, [
                  React.createElement('td', { key: 'bill_id' }, `#${bill.bill_id}`),
                  React.createElement('td', { key: 'patient' }, bill.patient_name),
                  React.createElement('td', { key: 'phone' }, bill.patient_phone),
                  React.createElement('td', { key: 'total', className: 'amount' }, formatCurrency(bill.total_amount)),
                  React.createElement('td', { key: 'paid', className: 'amount' }, formatCurrency(bill.paid_amount)),
                  React.createElement('td', { key: 'balance', className: 'amount' }, formatCurrency(bill.balance_amount)),
                  React.createElement('td', { key: 'date' }, new Date(bill.issued_date).toLocaleDateString()),
                  React.createElement('td', { key: 'due' }, bill.due_date ? new Date(bill.due_date).toLocaleDateString() : 'N/A')
                ])
              )
            )
          ])
        ])
      ]);
    };

    const renderReport = () => {
      if (loading) {
        return React.createElement('div', { className: 'loading' }, 'Loading report...');
      }
      
      if (!reportData) {
        return React.createElement('div', { 
          style: { textAlign: 'center', color: '#b3b3b3', padding: '40px' } 
        }, 'Select a report type to view data');
      }
      
      switch (activeReport) {
        case 'daily_collection':
          return React.createElement(DailyCollectionReport, { data: reportData });
        case 'outstanding':
          return React.createElement(OutstandingReport, { data: reportData });
        case 'service_revenue':
          return React.createElement('div', { 
            style: { textAlign: 'center', color: '#b3b3b3', padding: '40px' } 
          }, 'Service revenue report coming soon');
        case 'patient_reports':
          return React.createElement('div', { 
            style: { textAlign: 'center', color: '#b3b3b3', padding: '40px' } 
          }, 'Patient reports coming soon');
        default:
          return null;
      }
    };

    return React.createElement('div', null, [
      React.createElement('div', { key: 'header', className: 'card' }, [
        React.createElement('h3', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Reports & Analytics'),
        
        React.createElement('div', { key: 'controls', className: 'row' }, [
          React.createElement('div', { key: 'col1', className: 'col-md-4' }, [
            React.createElement('label', { key: 'label' }, 'Report Type'),
            React.createElement('select', {
              key: 'select',
              value: activeReport,
              onChange: (e) => setActiveReport(e.target.value)
            }, [
              React.createElement('option', { key: 'daily_collection', value: 'daily_collection' }, 'Daily Collection Report'),
              React.createElement('option', { key: 'outstanding', value: 'outstanding' }, 'Outstanding Bills Report'),
              React.createElement('option', { key: 'service_revenue', value: 'service_revenue' }, 'Service-wise Revenue'),
              React.createElement('option', { key: 'patient_reports', value: 'patient_reports' }, 'Patient Reports')
            ])
          ]),
          
          activeReport === 'daily_collection' && React.createElement('div', { key: 'col2', className: 'col-md-4' }, [
            React.createElement('label', { key: 'label' }, 'Date'),
            React.createElement('input', {
              key: 'input',
              type: 'date',
              value: dateRange.start_date,
              onChange: (e) => setDateRange(prev => ({ ...prev, start_date: e.target.value }))
            })
          ]),
          
          (activeReport === 'service_revenue' || activeReport === 'patient_reports') && React.createElement('div', { key: 'col3', className: 'col-md-4' }, [
            React.createElement('label', { key: 'label' }, 'Date Range'),
            React.createElement('div', { key: 'range', style: { display: 'flex', gap: '10px' } }, [
              React.createElement('input', {
                key: 'start',
                type: 'date',
                value: dateRange.start_date,
                onChange: (e) => setDateRange(prev => ({ ...prev, start_date: e.target.value })),
                style: { flex: 1 }
              }),
              React.createElement('input', {
                key: 'end',
                type: 'date',
                value: dateRange.end_date,
                onChange: (e) => setDateRange(prev => ({ ...prev, end_date: e.target.value })),
                style: { flex: 1 }
              })
            ])
          ])
        ])
      ]),
      
      React.createElement('div', { key: 'report-content' }, renderReport())
    ]);
  }
  
  window.Components = window.Components || {};
  window.Components.Reports = Reports;
})();
