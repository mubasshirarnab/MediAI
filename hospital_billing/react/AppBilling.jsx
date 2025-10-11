(function(){
  const { useState, useEffect } = React;
  const { BillingDashboard, PatientLedger, BillManagement, PaymentManagement, Reports, PatientDischargeSummary } = window.Components;
  
  function AppBilling() {
    const [activeTab, setActiveTab] = useState('dashboard');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [refreshTick, setRefreshTick] = useState(0);

    const clearMessages = () => {
      setError('');
      setSuccess('');
    };

    useEffect(() => {
      clearMessages();
    }, [activeTab]);

    // Listen for payments to refresh dashboard/reports implicitly
    useEffect(() => {
      const interval = setInterval(() => {
        try {
          const ts = window.sessionStorage.getItem('last_payment_ts');
          if (ts && Number(ts) > 0) {
            setRefreshTick(prev => prev + 1);
            // consume once
            window.sessionStorage.removeItem('last_payment_ts');
          }
        } catch (e) {}
      }, 1500);
      return () => clearInterval(interval);
    }, []);

    return React.createElement('div', null, [
      React.createElement('div', { key: 'header', className: 'header' }, [
        React.createElement('div', { key: 'title', className: 'title' }, 'Hospital Billing System'),
        React.createElement('div', { key: 'user-info', style: { color: '#b3b3b3', fontSize: '14px' } }, 
          `Welcome, ${window.sessionStorage.getItem('user_name') || 'User'}`)
      ]),
      
      React.createElement('div', { key: 'tabs', className: 'tabs' }, [
        React.createElement('button', { 
          key: 'dashboard', 
          className: 'btn' + (activeTab === 'dashboard' ? ' primary' : ''), 
          onClick: () => setActiveTab('dashboard') 
        }, 'Dashboard'),
        
        React.createElement('button', { 
          key: 'ledger', 
          className: 'btn' + (activeTab === 'ledger' ? ' primary' : ''), 
          onClick: () => setActiveTab('ledger') 
        }, 'Patient Ledger'),
        
        React.createElement('button', { 
          key: 'bills', 
          className: 'btn' + (activeTab === 'bills' ? ' primary' : ''), 
          onClick: () => setActiveTab('bills') 
        }, 'Bill Management'),
        
        React.createElement('button', { 
          key: 'payments', 
          className: 'btn' + (activeTab === 'payments' ? ' primary' : ''), 
          onClick: () => setActiveTab('payments') 
        }, 'Payment Management'),
        
        React.createElement('button', { 
          key: 'reports', 
          className: 'btn' + (activeTab === 'reports' ? ' primary' : ''), 
          onClick: () => setActiveTab('reports') 
        }, 'Reports & Analytics'),
        
        React.createElement('button', { 
          key: 'discharge', 
          className: 'btn' + (activeTab === 'discharge' ? ' primary' : ''), 
          onClick: () => setActiveTab('discharge') 
        }, 'Discharge Summary')
      ]),
      
      error ? React.createElement('div', { key: 'error', className: 'error' }, error) : null,
      success ? React.createElement('div', { key: 'success', className: 'success' }, success) : null,
      
      activeTab === 'dashboard' ? React.createElement(BillingDashboard, { 
        key: 'dashboard',
        onError: setError,
        onSuccess: setSuccess,
        refresh: refreshTick
      }) :
      activeTab === 'ledger' ? React.createElement(PatientLedger, { 
        key: 'ledger',
        onError: setError,
        onSuccess: setSuccess
      }) :
      activeTab === 'bills' ? React.createElement(BillManagement, { 
        key: 'bills',
        onError: setError,
        onSuccess: setSuccess
      }) :
      activeTab === 'payments' ? React.createElement(PaymentManagement, { 
        key: 'payments',
        onError: setError,
        onSuccess: setSuccess
      }) :
      activeTab === 'reports' ? React.createElement(Reports, { 
        key: 'reports',
        onError: setError,
        onSuccess: setSuccess,
        refresh: refreshTick
      }) :
      activeTab === 'discharge' ? React.createElement(PatientDischargeSummary, { 
        key: 'discharge',
        onError: setError,
        onSuccess: setSuccess
      }) :
      null
    ]);
  }
  
  window.Apps = window.Apps || {};
  window.Apps.AppBilling = AppBilling;
})();
