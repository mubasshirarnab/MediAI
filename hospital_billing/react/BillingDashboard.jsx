(function(){
  const { useState, useEffect } = React;
  
  function BillingDashboard({ onError, onSuccess, refresh }) {
    const [stats, setStats] = useState({
      totalBills: 0,
      totalRevenue: 0,
      outstandingAmount: 0,
      todayCollection: 0
    });
    const [recentBills, setRecentBills] = useState([]);
    const [todayCollection, setTodayCollection] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
      loadDashboardData();
    }, []);

    useEffect(() => {
      if (typeof refresh !== 'undefined') {
        loadDashboardData();
      }
    }, [refresh]);

    const loadDashboardData = async () => {
      try {
        setLoading(true);
        
        // Fresh stats from API
        const statsResponse = await axios.get('billing_api.php?action=dashboard_stats');
        if (statsResponse.data.success) {
          setStats(prev => ({
            ...prev,
            totalBills: statsResponse.data.data.total_bills,
            totalRevenue: statsResponse.data.data.total_revenue,
            outstandingAmount: statsResponse.data.data.outstanding_amount,
            todayCollection: statsResponse.data.data.today_collection
          }));
        }

        // Load recent bills
        const billsResponse = await axios.get('billing_api.php?action=bills_list&limit=5');
        if (billsResponse.data.success) {
          setRecentBills(billsResponse.data.data);
        }

        // Load today's collection (for breakdown table)
        const collectionResponse = await axios.get('billing_api.php?action=daily_collection');
        if (collectionResponse.data.success) {
          setTodayCollection(collectionResponse.data.data.collection);
        }

        // No client-side revenue math; taken from API

      } catch (err) {
        onError('Failed to load dashboard data');
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

    if (loading) {
      return React.createElement('div', { className: 'loading' }, 'Loading dashboard...');
    }

    return React.createElement('div', null, [
      // Stats Grid
      React.createElement('div', { key: 'stats', className: 'stats-grid' }, [
        React.createElement('div', { key: 'total-bills', className: 'stat-card' }, [
          React.createElement('div', { key: 'value', className: 'stat-value' }, stats.totalBills),
          React.createElement('div', { key: 'label', className: 'stat-label' }, 'Total Bills')
        ]),
        
        React.createElement('div', { key: 'total-revenue', className: 'stat-card' }, [
          React.createElement('div', { key: 'value', className: 'stat-value' }, formatCurrency(stats.totalRevenue)),
          React.createElement('div', { key: 'label', className: 'stat-label' }, 'Total Revenue')
        ]),
        
        React.createElement('div', { key: 'outstanding', className: 'stat-card' }, [
          React.createElement('div', { key: 'value', className: 'stat-value' }, formatCurrency(stats.outstandingAmount)),
          React.createElement('div', { key: 'label', className: 'stat-label' }, 'Outstanding Amount')
        ]),
        
        React.createElement('div', { key: 'today-collection', className: 'stat-card' }, [
          React.createElement('div', { key: 'value', className: 'stat-value' }, formatCurrency(stats.todayCollection)),
          React.createElement('div', { key: 'label', className: 'stat-label' }, 'Today\'s Collection')
        ])
      ]),

      // Recent Bills
      React.createElement('div', { key: 'recent-bills', className: 'card' }, [
        React.createElement('h3', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Recent Bills'),
        
        recentBills.length > 0 ? 
          React.createElement('table', { key: 'table' }, [
            React.createElement('thead', { key: 'thead' }, [
              React.createElement('tr', { key: 'header' }, [
                React.createElement('th', { key: 'id' }, 'Bill ID'),
                React.createElement('th', { key: 'patient' }, 'Patient'),
                React.createElement('th', { key: 'amount' }, 'Amount'),
                React.createElement('th', { key: 'status' }, 'Status'),
                React.createElement('th', { key: 'date' }, 'Date')
              ])
            ]),
            React.createElement('tbody', { key: 'tbody' }, 
              recentBills.map(bill => 
                React.createElement('tr', { key: bill.id }, [
                  React.createElement('td', { key: 'id' }, `#${bill.id}`),
                  React.createElement('td', { key: 'patient' }, bill.patient_name),
                  React.createElement('td', { key: 'amount', className: 'amount' }, formatCurrency(bill.total_amount)),
                  React.createElement('td', { key: 'status' }, getStatusBadge(bill.status)),
                  React.createElement('td', { key: 'date' }, new Date(bill.issued_date).toLocaleDateString())
                ])
              )
            )
          ]) :
          React.createElement('div', { key: 'no-bills', style: { textAlign: 'center', color: '#b3b3b3', padding: '20px' } }, 'No recent bills found')
      ]),

      // Today's Collection
      React.createElement('div', { key: 'today-collection', className: 'card' }, [
        React.createElement('h3', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Today\'s Collection by Payment Method'),
        
        todayCollection.length > 0 ? 
          React.createElement('table', { key: 'table' }, [
            React.createElement('thead', { key: 'thead' }, [
              React.createElement('tr', { key: 'header' }, [
                React.createElement('th', { key: 'method' }, 'Payment Method'),
                React.createElement('th', { key: 'count' }, 'Transactions'),
                React.createElement('th', { key: 'amount' }, 'Amount')
              ])
            ]),
            React.createElement('tbody', { key: 'tbody' }, 
              todayCollection.map(item => 
                React.createElement('tr', { key: item.payment_method }, [
                  React.createElement('td', { key: 'method' }, item.payment_method.replace('_', ' ').toUpperCase()),
                  React.createElement('td', { key: 'count' }, item.transaction_count),
                  React.createElement('td', { key: 'amount', className: 'amount' }, formatCurrency(item.total_amount))
                ])
              )
            )
          ]) :
          React.createElement('div', { key: 'no-collection', style: { textAlign: 'center', color: '#b3b3b3', padding: '20px' } }, 'No collection data for today')
      ])
    ]);
  }
  
  window.Components = window.Components || {};
  window.Components.BillingDashboard = BillingDashboard;
})();
