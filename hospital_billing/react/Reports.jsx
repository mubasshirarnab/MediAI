(function(){
  const { useState, useEffect } = React;
  
  function Reports({ onError, onSuccess, refresh }) {
    const [activeReport, setActiveReport] = useState('financial_summary');
    const [loading, setLoading] = useState(false);
    const [reportData, setReportData] = useState(null);
    const [dateRange, setDateRange] = useState({
      start_date: new Date().toISOString().split('T')[0],
      end_date: new Date().toISOString().split('T')[0]
    });
    const [period, setPeriod] = useState('monthly');

    useEffect(() => {
      if (activeReport) {
        loadReportData();
      }
    }, [activeReport, dateRange, period]);

    useEffect(() => {
      if (typeof refresh !== 'undefined') {
        loadReportData();
      }
    }, [refresh]);

    const loadReportData = async () => {
      try {
        setLoading(true);
        setReportData(null); // Clear previous data
        
        console.log(`Loading report: ${activeReport}`, { dateRange, period });
        
        let url;
        let response;
        
        switch (activeReport) {
          case 'financial_summary':
            url = `billing_api.php?action=financial_summary&start_date=${dateRange.start_date}&end_date=${dateRange.end_date}`;
            break;
            
          case 'revenue_trend':
            url = `billing_api.php?action=revenue_trend&period=${period}&start_date=${dateRange.start_date}&end_date=${dateRange.end_date}`;
            break;
            
          case 'service_revenue':
            url = `billing_api.php?action=service_revenue&start_date=${dateRange.start_date}&end_date=${dateRange.end_date}`;
            break;
            
          case 'payment_methods':
            url = `billing_api.php?action=payment_method_analysis&start_date=${dateRange.start_date}&end_date=${dateRange.end_date}`;
            break;
            
          case 'daily_collection':
            url = `billing_api.php?action=daily_collection&date=${dateRange.start_date}`;
            break;
            
          case 'outstanding':
            url = 'billing_api.php?action=outstanding_reports';
            break;
            
          default:
            console.warn('Unknown report type:', activeReport);
            setReportData(null);
            return;
        }
        
        console.log('API URL:', url);
        response = await axios.get(url);
        console.log('API Response:', response.data);
        
        if (response && response.data && response.data.success) {
          // Handle different response structures
          if (activeReport === 'daily_collection') {
            setReportData(response.data.data);
          } else {
            setReportData(response.data);
          }
          console.log('Report data set successfully');
        } else {
          const errorMsg = response?.data?.error || `Failed to load ${activeReport.replace('_', ' ')} report`;
          console.error('API Error:', errorMsg);
          onError(errorMsg);
          setReportData(null);
        }
      } catch (err) {
        console.error('Report loading error:', err);
        console.error('Error details:', {
          message: err.message,
          response: err.response?.data,
          status: err.response?.status
        });
        
        let errorMessage = 'Failed to load report data';
        if (err.response?.data?.error) {
          errorMessage = err.response.data.error;
        } else if (err.message) {
          errorMessage = err.message;
        }
        
        onError(errorMessage);
        setReportData(null);
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

    const formatNumber = (num) => {
      return new Intl.NumberFormat('en-BD').format(num);
    };

    // Chart Components
    const LineChart = ({ data, title, height = 300 }) => {
      if (!data || data.length === 0) {
        return React.createElement('div', { 
          style: { 
            height: `${height}px`, 
            display: 'flex', 
            alignItems: 'center', 
            justifyContent: 'center',
            backgroundColor: '#f8f9fa',
            borderRadius: '8px',
            color: '#6c757d'
          } 
        }, 'No data available for chart');
      }

      const maxValue = Math.max(...data.map(d => d.total_revenue));
      const chartHeight = height - 60;
      const chartWidth = 100;
      const barWidth = chartWidth / data.length;

      return React.createElement('div', { 
        style: { 
          backgroundColor: '#fff', 
          padding: '20px', 
          borderRadius: '12px',
          border: '1px solid #e9ecef',
          boxShadow: '0 2px 8px rgba(0,0,0,0.1)'
        } 
      }, [
        React.createElement('h5', { 
          key: 'title', 
          style: { 
            marginBottom: '20px', 
            color: '#495057',
            textAlign: 'center'
          } 
        }, title),
        
        React.createElement('div', { 
          key: 'chart', 
          style: { 
            height: `${chartHeight}px`, 
            position: 'relative',
            border: '1px solid #e9ecef',
            borderRadius: '8px',
            padding: '10px',
            backgroundColor: '#f8f9fa'
          } 
        }, [
          // Chart bars
          ...data.map((item, index) => {
            const barHeight = (item.total_revenue / maxValue) * (chartHeight - 40);
            return React.createElement('div', {
              key: `bar-${index}`,
              style: {
                position: 'absolute',
                bottom: '30px',
                left: `${(index * barWidth) + (barWidth * 0.1)}%`,
                width: `${barWidth * 0.8}%`,
                height: `${barHeight}px`,
                backgroundColor: '#a259ff',
                borderRadius: '4px 4px 0 0',
                transition: 'all 0.3s ease',
                cursor: 'pointer'
              },
              onMouseOver: (e) => {
                e.target.style.backgroundColor = '#8b4cff';
                e.target.style.transform = 'scaleY(1.05)';
              },
              onMouseOut: (e) => {
                e.target.style.backgroundColor = '#a259ff';
                e.target.style.transform = 'scaleY(1)';
              },
              title: `${item.period}: ${formatCurrency(item.total_revenue)}`
            });
          }),
          
          // Chart labels
          ...data.map((item, index) => 
            React.createElement('div', {
              key: `label-${index}`,
              style: {
                position: 'absolute',
                bottom: '5px',
                left: `${(index * barWidth) + (barWidth * 0.5)}%`,
                transform: 'translateX(-50%)',
                fontSize: '0.8rem',
                color: '#6c757d',
                textAlign: 'center',
                width: `${barWidth}%`
              }
            }, item.period)
          )
        ])
      ]);
    };

    const PieChart = ({ data, title, height = 300 }) => {
      if (!data || data.length === 0) {
        return React.createElement('div', { 
          style: { 
            height: `${height}px`, 
            display: 'flex', 
            alignItems: 'center', 
            justifyContent: 'center',
            backgroundColor: '#f8f9fa',
            borderRadius: '8px',
            color: '#6c757d'
          } 
        }, 'No data available for chart');
      }

      const total = data.reduce((sum, item) => sum + item.total_amount, 0);
      const colors = ['#a259ff', '#2ed573', '#ff4757', '#ffa502', '#3742fa', '#ff6b6b', '#4ecdc4', '#45b7d1'];
      
      let currentAngle = 0;
      const radius = 80;
      const centerX = 150;
      const centerY = 150;

      return React.createElement('div', { 
        style: { 
          backgroundColor: '#fff', 
          padding: '20px', 
          borderRadius: '12px',
          border: '1px solid #e9ecef',
          boxShadow: '0 2px 8px rgba(0,0,0,0.1)'
        } 
      }, [
        React.createElement('h5', { 
          key: 'title', 
          style: { 
            marginBottom: '20px', 
            color: '#495057',
            textAlign: 'center'
          } 
        }, title),
        
        React.createElement('div', { 
          key: 'chart-container', 
          style: { 
            display: 'flex', 
            alignItems: 'center', 
            justifyContent: 'center',
            height: `${height - 100}px`
          } 
        }, [
          React.createElement('svg', {
            key: 'pie-chart',
            width: '300',
            height: '300',
            style: { marginRight: '20px' }
          }, [
            ...data.map((item, index) => {
              const percentage = (item.total_amount / total) * 100;
              const angle = (percentage / 100) * 360;
              const startAngle = currentAngle;
              const endAngle = currentAngle + angle;
              
              const x1 = centerX + radius * Math.cos((startAngle - 90) * Math.PI / 180);
              const y1 = centerY + radius * Math.sin((startAngle - 90) * Math.PI / 180);
              const x2 = centerX + radius * Math.cos((endAngle - 90) * Math.PI / 180);
              const y2 = centerY + radius * Math.sin((endAngle - 90) * Math.PI / 180);
              
              const largeArcFlag = angle > 180 ? 1 : 0;
              const pathData = `M ${centerX} ${centerY} L ${x1} ${y1} A ${radius} ${radius} 0 ${largeArcFlag} 1 ${x2} ${y2} Z`;
              
              currentAngle += angle;
              
              return React.createElement('path', {
                key: `slice-${index}`,
                d: pathData,
                fill: colors[index % colors.length],
                stroke: '#fff',
                strokeWidth: '2',
                style: { cursor: 'pointer' },
                onMouseOver: (e) => {
                  e.target.style.opacity = '0.8';
                  e.target.style.transform = 'scale(1.05)';
                },
                onMouseOut: (e) => {
                  e.target.style.opacity = '1';
                  e.target.style.transform = 'scale(1)';
                }
              });
            })
          ]),
          
          React.createElement('div', { 
            key: 'legend', 
            style: { 
              display: 'flex', 
              flexDirection: 'column', 
              gap: '8px' 
            } 
          }, data.map((item, index) => 
            React.createElement('div', {
              key: `legend-${index}`,
              style: {
                display: 'flex',
                alignItems: 'center',
                gap: '8px',
                fontSize: '0.9rem'
              }
            }, [
              React.createElement('div', {
                key: 'color',
                style: {
                  width: '12px',
                  height: '12px',
                  backgroundColor: colors[index % colors.length],
                  borderRadius: '2px'
                }
              }),
              React.createElement('span', { key: 'label' }, item.payment_method || item.item_name),
              React.createElement('span', { 
                key: 'value', 
                style: { 
                  fontWeight: '600', 
                  color: '#495057' 
                } 
              }, `${((item.total_amount / total) * 100).toFixed(1)}%`)
            ])
          ))
        ])
      ]);
    };

    const BarChart = ({ data, title, height = 300 }) => {
      if (!data || data.length === 0) {
        return React.createElement('div', { 
          style: { 
            height: `${height}px`, 
            display: 'flex', 
            alignItems: 'center', 
            justifyContent: 'center',
            backgroundColor: '#f8f9fa',
            borderRadius: '8px',
            color: '#6c757d'
          } 
        }, 'No data available for chart');
      }

      const maxValue = Math.max(...data.map(d => d.total_revenue));
      const chartHeight = height - 80;
      const barWidth = Math.max(20, Math.min(60, (400 / data.length) - 10));

      return React.createElement('div', { 
        style: { 
          backgroundColor: '#fff', 
          padding: '20px', 
          borderRadius: '12px',
          border: '1px solid #e9ecef',
          boxShadow: '0 2px 8px rgba(0,0,0,0.1)'
        } 
      }, [
        React.createElement('h5', { 
          key: 'title', 
          style: { 
            marginBottom: '20px', 
            color: '#495057',
            textAlign: 'center'
          } 
        }, title),
        
        React.createElement('div', { 
          key: 'chart', 
          style: { 
            height: `${chartHeight}px`, 
            display: 'flex', 
            alignItems: 'end', 
            justifyContent: 'space-around',
            border: '1px solid #e9ecef',
            borderRadius: '8px',
            padding: '20px',
            backgroundColor: '#f8f9fa'
          } 
        }, data.map((item, index) => {
          const barHeight = (item.total_revenue / maxValue) * (chartHeight - 40);
          return React.createElement('div', {
            key: `bar-${index}`,
            style: {
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              gap: '5px'
            }
          }, [
            React.createElement('div', {
              key: 'value',
              style: {
                fontSize: '0.8rem',
                fontWeight: '600',
                color: '#495057',
                marginBottom: '5px'
              }
            }, formatCurrency(item.total_revenue)),
            React.createElement('div', {
              key: 'bar',
              style: {
                width: `${barWidth}px`,
                height: `${barHeight}px`,
                backgroundColor: '#a259ff',
                borderRadius: '4px 4px 0 0',
                transition: 'all 0.3s ease',
                cursor: 'pointer'
              },
              onMouseOver: (e) => {
                e.target.style.backgroundColor = '#8b4cff';
                e.target.style.transform = 'scaleY(1.05)';
              },
              onMouseOut: (e) => {
                e.target.style.backgroundColor = '#a259ff';
                e.target.style.transform = 'scaleY(1)';
              }
            }),
            React.createElement('div', {
              key: 'label',
              style: {
                fontSize: '0.8rem',
                color: '#6c757d',
                textAlign: 'center',
                maxWidth: `${barWidth + 20}px`,
                wordWrap: 'break-word'
              }
            }, item.item_name.length > 15 ? item.item_name.substring(0, 15) + '...' : item.item_name)
          ]);
        }))
      ]);
    };

    const FinancialSummaryReport = ({ data }) => {
      if (!data || !data.data) {
        return React.createElement('div', { 
          className: 'report-empty-state',
          style: { 
            textAlign: 'center', 
            padding: '60px 20px',
            backgroundColor: '#f8f9fa',
            borderRadius: '12px',
            border: '2px dashed #dee2e6',
            margin: '20px 0'
          } 
        }, [
          React.createElement('div', { 
            key: 'icon', 
            style: { 
              fontSize: '4rem', 
              marginBottom: '20px',
              color: '#adb5bd'
            } 
          }, '📊'),
          React.createElement('h4', { 
            key: 'title', 
            style: { 
              marginBottom: '15px',
              color: '#6c757d',
              fontSize: '1.2rem',
              fontWeight: '600'
            } 
          }, 'No Financial Data Available'),
          React.createElement('p', { 
            key: 'text', 
            style: { 
              margin: 0, 
              color: '#adb5bd',
              fontSize: '0.95rem',
              lineHeight: '1.5'
            } 
          }, 'No financial data found for the selected date range. Try adjusting your date range or check if there are any bills or payments in the system.')
        ]);
      }
      
      const summary = data.data;
      
      return React.createElement('div', null, [
        // Key Metrics Cards
        React.createElement('div', { key: 'metrics', className: 'stats-grid', style: { marginBottom: '30px' } }, [
          React.createElement('div', { key: 'revenue', className: 'stat-card' }, [
            React.createElement('div', { key: 'icon', style: { fontSize: '2rem', color: '#2ed573', marginBottom: '10px' } }, '💰'),
            React.createElement('div', { key: 'value', className: 'stat-value', style: { color: '#2ed573' } }, formatCurrency(summary.total_revenue)),
            React.createElement('div', { key: 'label', className: 'stat-label' }, 'Total Revenue')
          ]),
          React.createElement('div', { key: 'bills', className: 'stat-card' }, [
            React.createElement('div', { key: 'icon', style: { fontSize: '2rem', color: '#a259ff', marginBottom: '10px' } }, '📋'),
            React.createElement('div', { key: 'value', className: 'stat-value', style: { color: '#a259ff' } }, formatNumber(summary.total_bills)),
            React.createElement('div', { key: 'label', className: 'stat-label' }, 'Total Bills')
          ]),
          React.createElement('div', { key: 'outstanding', className: 'stat-card' }, [
            React.createElement('div', { key: 'icon', style: { fontSize: '2rem', color: '#ff4757', marginBottom: '10px' } }, '⚠️'),
            React.createElement('div', { key: 'value', className: 'stat-value', style: { color: '#ff4757' } }, formatCurrency(summary.total_outstanding)),
            React.createElement('div', { key: 'label', className: 'stat-label' }, 'Outstanding Amount')
          ]),
          React.createElement('div', { key: 'avg-bill', className: 'stat-card' }, [
            React.createElement('div', { key: 'icon', style: { fontSize: '2rem', color: '#ffa502', marginBottom: '10px' } }, '📊'),
            React.createElement('div', { key: 'value', className: 'stat-value', style: { color: '#ffa502' } }, formatCurrency(summary.avg_bill_amount)),
            React.createElement('div', { key: 'label', className: 'stat-label' }, 'Average Bill')
          ]),
          React.createElement('div', { key: 'collection-rate', className: 'stat-card' }, [
            React.createElement('div', { key: 'icon', style: { fontSize: '2rem', color: '#3742fa', marginBottom: '10px' } }, '📈'),
            React.createElement('div', { key: 'value', className: 'stat-value', style: { color: '#3742fa' } }, `${summary.collection_rate}%`),
            React.createElement('div', { key: 'label', className: 'stat-label' }, 'Collection Rate')
          ])
        ]),
        
        // Financial Analysis
        React.createElement('div', { key: 'analysis', className: 'card' }, [
          React.createElement('h4', { key: 'title', style: { marginBottom: '20px', color: '#a259ff' } }, 'Financial Analysis'),
          React.createElement('div', { key: 'analysis-content', className: 'row' }, [
            React.createElement('div', { key: 'col1', className: 'col-md-6' }, [
              React.createElement('h6', { key: 'subtitle', style: { color: '#495057', marginBottom: '15px' } }, 'Revenue Breakdown'),
              React.createElement('div', { key: 'breakdown' }, [
                React.createElement('div', { key: 'item1', style: { display: 'flex', justifyContent: 'space-between', marginBottom: '10px' } }, [
                  React.createElement('span', { key: 'label' }, 'Total Billed:'),
                  React.createElement('span', { key: 'value', style: { fontWeight: '600' } }, formatCurrency(summary.total_billed))
                ]),
                React.createElement('div', { key: 'item2', style: { display: 'flex', justifyContent: 'space-between', marginBottom: '10px' } }, [
                  React.createElement('span', { key: 'label' }, 'Total Collected:'),
                  React.createElement('span', { key: 'value', style: { fontWeight: '600', color: '#2ed573' } }, formatCurrency(summary.total_revenue))
                ]),
                React.createElement('div', { key: 'item3', style: { display: 'flex', justifyContent: 'space-between', marginBottom: '10px' } }, [
                  React.createElement('span', { key: 'label' }, 'Outstanding:'),
                  React.createElement('span', { key: 'value', style: { fontWeight: '600', color: '#ff4757' } }, formatCurrency(summary.total_outstanding))
                ])
              ])
            ]),
            React.createElement('div', { key: 'col2', className: 'col-md-6' }, [
              React.createElement('h6', { key: 'subtitle', style: { color: '#495057', marginBottom: '15px' } }, 'Performance Metrics'),
              React.createElement('div', { key: 'metrics' }, [
                React.createElement('div', { key: 'item1', style: { display: 'flex', justifyContent: 'space-between', marginBottom: '10px' } }, [
                  React.createElement('span', { key: 'label' }, 'Collection Rate:'),
                  React.createElement('span', { 
                    key: 'value', 
                    style: { 
                      fontWeight: '600',
                      color: summary.collection_rate >= 80 ? '#2ed573' : summary.collection_rate >= 60 ? '#ffa502' : '#ff4757'
                    } 
                  }, `${summary.collection_rate}%`)
                ]),
                React.createElement('div', { key: 'item2', style: { display: 'flex', justifyContent: 'space-between', marginBottom: '10px' } }, [
                  React.createElement('span', { key: 'label' }, 'Average Bill:'),
                  React.createElement('span', { key: 'value', style: { fontWeight: '600' } }, formatCurrency(summary.avg_bill_amount))
                ]),
                React.createElement('div', { key: 'item3', style: { display: 'flex', justifyContent: 'space-between', marginBottom: '10px' } }, [
                  React.createElement('span', { key: 'label' }, 'Total Bills:'),
                  React.createElement('span', { key: 'value', style: { fontWeight: '600' } }, formatNumber(summary.total_bills))
                ])
              ])
            ])
          ])
        ])
      ]);
    };

    const RevenueTrendReport = ({ data }) => {
      if (!data || !data.data || data.data.length === 0) {
        return React.createElement('div', { 
          className: 'report-empty-state',
          style: { 
            textAlign: 'center', 
            padding: '60px 20px',
            backgroundColor: '#f8f9fa',
            borderRadius: '12px',
            border: '2px dashed #dee2e6',
            margin: '20px 0'
          } 
        }, [
          React.createElement('div', { 
            key: 'icon', 
            style: { 
              fontSize: '4rem', 
              marginBottom: '20px',
              color: '#adb5bd'
            } 
          }, '📈'),
          React.createElement('h4', { 
            key: 'title', 
            style: { 
              marginBottom: '15px',
              color: '#6c757d',
              fontSize: '1.2rem',
              fontWeight: '600'
            } 
          }, 'No Revenue Trend Data'),
          React.createElement('p', { 
            key: 'text', 
            style: { 
              margin: 0, 
              color: '#adb5bd',
              fontSize: '0.95rem',
              lineHeight: '1.5'
            } 
          }, 'No revenue data found for the selected period. Try adjusting your date range or period selection.')
        ]);
      }
      
      return React.createElement('div', null, [
        React.createElement('div', { key: 'chart', className: 'card' }, [
          React.createElement(LineChart, { 
            key: 'line-chart',
            data: data.data, 
            title: `Revenue Trend (${data.period})`,
            height: 400
          })
        ]),
        
        data.data.length > 0 && React.createElement('div', { key: 'summary', className: 'card' }, [
          React.createElement('h4', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Trend Summary'),
          React.createElement('div', { key: 'summary-content', className: 'row' }, [
            React.createElement('div', { key: 'col1', className: 'col-md-4' }, [
              React.createElement('div', { key: 'metric', style: { textAlign: 'center' } }, [
                React.createElement('div', { key: 'value', style: { fontSize: '1.5rem', fontWeight: '600', color: '#2ed573' } }, 
                  formatCurrency(data.data.reduce((sum, item) => sum + item.total_revenue, 0))),
                React.createElement('div', { key: 'label', style: { color: '#6c757d' } }, 'Total Revenue')
              ])
            ]),
            React.createElement('div', { key: 'col2', className: 'col-md-4' }, [
              React.createElement('div', { key: 'metric', style: { textAlign: 'center' } }, [
                React.createElement('div', { key: 'value', style: { fontSize: '1.5rem', fontWeight: '600', color: '#a259ff' } }, 
                  data.data.length),
                React.createElement('div', { key: 'label', style: { color: '#6c757d' } }, 'Periods')
              ])
            ]),
            React.createElement('div', { key: 'col3', className: 'col-md-4' }, [
              React.createElement('div', { key: 'metric', style: { textAlign: 'center' } }, [
                React.createElement('div', { key: 'value', style: { fontSize: '1.5rem', fontWeight: '600', color: '#ffa502' } }, 
                  formatCurrency(data.data.reduce((sum, item) => sum + item.total_revenue, 0) / data.data.length)),
                React.createElement('div', { key: 'label', style: { color: '#6c757d' } }, 'Average Revenue')
              ])
            ])
          ])
        ])
      ]);
    };

    const ServiceRevenueReport = ({ data }) => {
      if (!data || !data.data || data.data.length === 0) {
        return React.createElement('div', { 
          className: 'report-empty-state',
          style: { 
            textAlign: 'center', 
            padding: '60px 20px',
            backgroundColor: '#f8f9fa',
            borderRadius: '12px',
            border: '2px dashed #dee2e6',
            margin: '20px 0'
          } 
        }, [
          React.createElement('div', { 
            key: 'icon', 
            style: { 
              fontSize: '4rem', 
              marginBottom: '20px',
              color: '#adb5bd'
            } 
          }, '🏥'),
          React.createElement('h4', { 
            key: 'title', 
            style: { 
              marginBottom: '15px',
              color: '#6c757d',
              fontSize: '1.2rem',
              fontWeight: '600'
            } 
          }, 'No Service Revenue Data'),
          React.createElement('p', { 
            key: 'text', 
            style: { 
              margin: 0, 
              color: '#adb5bd',
              fontSize: '0.95rem',
              lineHeight: '1.5'
            } 
          }, 'No service revenue data found for the selected date range. Try adjusting your date range or check if there are any bills with service items.')
        ]);
      }
      
      return React.createElement('div', null, [
        React.createElement('div', { key: 'chart', className: 'card' }, [
          React.createElement(BarChart, { 
            key: 'bar-chart',
            data: data.data.slice(0, 10), // Top 10 services
            title: 'Top Services by Revenue',
            height: 400
          })
        ]),
        
        data.data.length > 0 && React.createElement('div', { key: 'table', className: 'card' }, [
          React.createElement('h4', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Service Revenue Details'),
          React.createElement('table', { key: 'table' }, [
            React.createElement('thead', { key: 'thead' }, [
              React.createElement('tr', { key: 'header' }, [
                React.createElement('th', { key: 'service' }, 'Service'),
                React.createElement('th', { key: 'type' }, 'Type'),
                React.createElement('th', { key: 'count' }, 'Count'),
                React.createElement('th', { key: 'revenue' }, 'Revenue'),
                React.createElement('th', { key: 'avg' }, 'Avg Price')
              ])
            ]),
            React.createElement('tbody', { key: 'tbody' }, 
              data.data.map((service, index) => 
                React.createElement('tr', { key: index }, [
                  React.createElement('td', { key: 'service' }, service.item_name),
                  React.createElement('td', { key: 'type' }, service.item_type),
                  React.createElement('td', { key: 'count' }, service.service_count),
                  React.createElement('td', { key: 'revenue', className: 'amount' }, formatCurrency(service.total_revenue)),
                  React.createElement('td', { key: 'avg', className: 'amount' }, formatCurrency(service.avg_price))
                ])
              )
            )
          ])
        ])
      ]);
    };

    const PaymentMethodReport = ({ data }) => {
      if (!data || !data.data || data.data.length === 0) {
        return React.createElement('div', { 
          className: 'report-empty-state',
          style: { 
            textAlign: 'center', 
            padding: '60px 20px',
            backgroundColor: '#f8f9fa',
            borderRadius: '12px',
            border: '2px dashed #dee2e6',
            margin: '20px 0'
          } 
        }, [
          React.createElement('div', { 
            key: 'icon', 
            style: { 
              fontSize: '4rem', 
              marginBottom: '20px',
              color: '#adb5bd'
            } 
          }, '💳'),
          React.createElement('h4', { 
            key: 'title', 
            style: { 
              marginBottom: '15px',
              color: '#6c757d',
              fontSize: '1.2rem',
              fontWeight: '600'
            } 
          }, 'No Payment Method Data'),
          React.createElement('p', { 
            key: 'text', 
            style: { 
              margin: 0, 
              color: '#adb5bd',
              fontSize: '0.95rem',
              lineHeight: '1.5'
            } 
          }, 'No payment method data found for the selected date range. Try adjusting your date range or check if there are any payments recorded.')
        ]);
      }
      
      return React.createElement('div', null, [
        React.createElement('div', { key: 'chart', className: 'card' }, [
          React.createElement(PieChart, { 
            key: 'pie-chart',
            data: data.data, 
            title: 'Payment Methods Distribution',
            height: 400
          })
        ]),
        
        data.data.length > 0 && React.createElement('div', { key: 'summary', className: 'card' }, [
          React.createElement('h4', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Payment Method Analysis'),
          React.createElement('div', { key: 'summary-content', className: 'row' }, [
            React.createElement('div', { key: 'col1', className: 'col-md-6' }, [
              React.createElement('h6', { key: 'subtitle', style: { color: '#495057', marginBottom: '15px' } }, 'Transaction Summary'),
              React.createElement('div', { key: 'summary' }, [
                React.createElement('div', { key: 'item1', style: { display: 'flex', justifyContent: 'space-between', marginBottom: '10px' } }, [
                  React.createElement('span', { key: 'label' }, 'Total Transactions:'),
                  React.createElement('span', { key: 'value', style: { fontWeight: '600' } }, 
                    data.data.reduce((sum, item) => sum + item.transaction_count, 0))
                ]),
                React.createElement('div', { key: 'item2', style: { display: 'flex', justifyContent: 'space-between', marginBottom: '10px' } }, [
                  React.createElement('span', { key: 'label' }, 'Total Amount:'),
                  React.createElement('span', { key: 'value', style: { fontWeight: '600', color: '#2ed573' } }, 
                    formatCurrency(data.total_amount))
                ])
              ])
            ]),
            React.createElement('div', { key: 'col2', className: 'col-md-6' }, [
              React.createElement('h6', { key: 'subtitle', style: { color: '#495057', marginBottom: '15px' } }, 'Top Payment Method'),
              React.createElement('div', { key: 'top-method', style: { textAlign: 'center' } }, [
                React.createElement('div', { key: 'method', style: { fontSize: '1.2rem', fontWeight: '600', color: '#a259ff' } }, 
                  data.data[0]?.payment_method?.replace('_', ' ').toUpperCase() || 'N/A'),
                React.createElement('div', { key: 'percentage', style: { fontSize: '2rem', fontWeight: '700', color: '#2ed573' } }, 
                  `${data.data[0]?.percentage || 0}%`),
                React.createElement('div', { key: 'amount', style: { color: '#6c757d' } }, 
                  formatCurrency(data.data[0]?.total_amount || 0))
              ])
            ])
          ])
        ])
      ]);
    };

    const DailyCollectionReport = ({ data }) => {
      if (!data || !data.collection || data.collection.length === 0) {
        return React.createElement('div', { 
          className: 'report-empty-state',
          style: { 
            textAlign: 'center', 
            padding: '60px 20px',
            backgroundColor: '#f8f9fa',
            borderRadius: '12px',
            border: '2px dashed #dee2e6',
            margin: '20px 0'
          } 
        }, [
          React.createElement('div', { 
            key: 'icon', 
            style: { 
              fontSize: '4rem', 
              marginBottom: '20px',
              color: '#adb5bd'
            } 
          }, '💰'),
          React.createElement('h4', { 
            key: 'title', 
            style: { 
              marginBottom: '15px',
              color: '#6c757d',
              fontSize: '1.2rem',
              fontWeight: '600'
            } 
          }, 'No Collection Data'),
          React.createElement('p', { 
            key: 'text', 
            style: { 
              margin: 0, 
              color: '#adb5bd',
              fontSize: '0.95rem',
              lineHeight: '1.5'
            } 
          }, 'No collection data found for the selected date. Try selecting a different date or check if there are any payments recorded.')
        ]);
      }
      
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
        
        data.collection.length > 0 && React.createElement('div', { key: 'chart', className: 'card' }, [
          React.createElement(PieChart, { 
            key: 'pie-chart',
            data: data.collection, 
            title: 'Collection by Payment Method',
            height: 300
          })
        ]),
        
        data.collection.length > 0 && React.createElement('div', { key: 'details', className: 'card' }, [
          React.createElement('h4', { key: 'title', style: { marginBottom: '15px', color: '#a259ff' } }, 'Collection Details'),
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
      if (!data || !data.data || data.data.length === 0) {
        return React.createElement('div', { 
          className: 'report-empty-state',
          style: { 
            textAlign: 'center', 
            padding: '60px 20px',
            backgroundColor: '#f8f9fa',
            borderRadius: '12px',
            border: '2px dashed #dee2e6',
            margin: '20px 0'
          } 
        }, [
          React.createElement('div', { 
            key: 'icon', 
            style: { 
              fontSize: '4rem', 
              marginBottom: '20px',
              color: '#adb5bd'
            } 
          }, '⚠️'),
          React.createElement('h4', { 
            key: 'title', 
            style: { 
              marginBottom: '15px',
              color: '#6c757d',
              fontSize: '1.2rem',
              fontWeight: '600'
            } 
          }, 'No Outstanding Bills'),
          React.createElement('p', { 
            key: 'text', 
            style: { 
              margin: 0, 
              color: '#adb5bd',
              fontSize: '0.95rem',
              lineHeight: '1.5'
            } 
          }, 'Great news! There are no outstanding bills at the moment. All bills have been paid in full.')
        ]);
      }
      
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
        return React.createElement('div', { 
          className: 'report-loading',
          style: { 
            display: 'flex', 
            alignItems: 'center', 
            justifyContent: 'center', 
            height: '200px',
            color: '#6c757d',
            fontSize: '1.1rem'
          } 
        }, [
          React.createElement('div', {
            key: 'spinner',
            style: {
              width: '20px',
              height: '20px',
              border: '2px solid #e9ecef',
              borderTop: '2px solid #a259ff',
              borderRadius: '50%',
              animation: 'spin 1s linear infinite',
              marginRight: '10px'
            }
          }),
          'Loading report...'
        ]);
      }
      
      if (!reportData) {
        return React.createElement('div', { 
          style: { 
            textAlign: 'center', 
            color: '#b3b3b3', 
            padding: '40px',
            backgroundColor: '#f8f9fa',
            borderRadius: '12px',
            border: '2px dashed #dee2e6'
          } 
        }, [
          React.createElement('div', { 
            key: 'icon', 
            style: { 
              fontSize: '4rem', 
              marginBottom: '20px',
              color: '#adb5bd'
            } 
          }, '📊'),
          React.createElement('h4', { 
            key: 'title', 
            style: { 
              marginBottom: '15px',
              color: '#6c757d',
              fontSize: '1.2rem',
              fontWeight: '600'
            } 
          }, 'No Data Available'),
          React.createElement('p', { 
            key: 'text', 
            style: { 
              margin: 0, 
              color: '#adb5bd',
              fontSize: '0.95rem',
              lineHeight: '1.5'
            } 
          }, 'Select a report type and date range to view financial analytics and insights.')
        ]);
      }
      
      try {
        switch (activeReport) {
          case 'financial_summary':
            return React.createElement(FinancialSummaryReport, { data: reportData });
          case 'revenue_trend':
            return React.createElement(RevenueTrendReport, { data: reportData });
          case 'service_revenue':
            return React.createElement(ServiceRevenueReport, { data: reportData });
          case 'payment_methods':
            return React.createElement(PaymentMethodReport, { data: reportData });
          case 'daily_collection':
            return React.createElement(DailyCollectionReport, { data: reportData });
          case 'outstanding':
            return React.createElement(OutstandingReport, { data: reportData });
          default:
            return React.createElement('div', { 
              style: { 
                textAlign: 'center', 
                color: '#b3b3b3', 
                padding: '40px' 
              } 
            }, 'Unknown report type');
        }
      } catch (error) {
        console.error('Report rendering error:', error);
        return React.createElement('div', { 
          style: { 
            textAlign: 'center', 
            color: '#ff4757', 
            padding: '40px',
            backgroundColor: '#ffe8e8',
            borderRadius: '12px',
            border: '1px solid #ff4757'
          } 
        }, [
          React.createElement('h4', { 
            key: 'title', 
            style: { 
              marginBottom: '15px',
              color: '#ff4757'
            } 
          }, 'Report Error'),
          React.createElement('p', { 
            key: 'text', 
            style: { 
              margin: 0, 
              color: '#ff4757',
              fontSize: '0.9rem'
            } 
          }, 'There was an error rendering this report. Please try again.')
        ]);
      }
    };

    const handleRefresh = () => {
      loadReportData();
    };

    const handleReportChange = (newReport) => {
      setActiveReport(newReport);
      setReportData(null); // Clear data when switching reports
    };

    return React.createElement('div', null, [
      React.createElement('div', { key: 'header', className: 'card' }, [
        React.createElement('div', { 
          key: 'title-section', 
          style: { 
            display: 'flex', 
            justifyContent: 'space-between', 
            alignItems: 'center', 
            marginBottom: '20px' 
          } 
        }, [
          React.createElement('h3', { 
            key: 'title', 
            style: { 
              margin: 0, 
              color: '#a259ff',
              fontSize: '1.5rem',
              fontWeight: '600'
            } 
          }, 'Financial Reports & Analytics'),
          React.createElement('button', {
            key: 'refresh-btn',
            className: 'btn btn-success',
            onClick: handleRefresh,
            disabled: loading,
            style: {
              padding: '8px 16px',
              fontSize: '0.9rem',
              display: 'flex',
              alignItems: 'center',
              gap: '8px'
            }
          }, [
            React.createElement('span', { 
              key: 'refresh-icon',
              style: { 
                fontSize: '1rem',
                transform: loading ? 'rotate(360deg)' : 'rotate(0deg)',
                transition: 'transform 0.3s ease'
              }
            }, '🔄'),
            'Refresh'
          ])
        ]),
        
        React.createElement('div', { key: 'controls', className: 'row' }, [
          React.createElement('div', { key: 'col1', className: 'col-md-3' }, [
            React.createElement('label', { key: 'label' }, 'Report Type'),
            React.createElement('select', {
              key: 'select',
              value: activeReport,
              onChange: (e) => handleReportChange(e.target.value),
              disabled: loading
            }, [
              React.createElement('option', { key: 'financial_summary', value: 'financial_summary' }, '📊 Financial Summary'),
              React.createElement('option', { key: 'revenue_trend', value: 'revenue_trend' }, '📈 Revenue Trend'),
              React.createElement('option', { key: 'service_revenue', value: 'service_revenue' }, '🏥 Service Revenue'),
              React.createElement('option', { key: 'payment_methods', value: 'payment_methods' }, '💳 Payment Methods'),
              React.createElement('option', { key: 'daily_collection', value: 'daily_collection' }, '💰 Daily Collection'),
              React.createElement('option', { key: 'outstanding', value: 'outstanding' }, '⚠️ Outstanding Bills')
            ])
          ]),
          
          activeReport === 'revenue_trend' && React.createElement('div', { key: 'col2', className: 'col-md-2' }, [
            React.createElement('label', { key: 'label' }, 'Period'),
            React.createElement('select', {
              key: 'period-select',
              value: period,
              onChange: (e) => setPeriod(e.target.value),
              disabled: loading
            }, [
              React.createElement('option', { key: 'daily', value: 'daily' }, 'Daily'),
              React.createElement('option', { key: 'weekly', value: 'weekly' }, 'Weekly'),
              React.createElement('option', { key: 'monthly', value: 'monthly' }, 'Monthly'),
              React.createElement('option', { key: 'yearly', value: 'yearly' }, 'Yearly')
            ])
          ]),
          
          activeReport !== 'outstanding' && React.createElement('div', { 
            key: 'col3', 
            className: activeReport === 'revenue_trend' ? 'col-md-3' : 'col-md-4' 
          }, [
            React.createElement('label', { key: 'label' }, activeReport === 'daily_collection' ? 'Date' : 'Date Range'),
            activeReport === 'daily_collection' ? 
              React.createElement('input', {
                key: 'date-input',
                type: 'date',
                value: dateRange.start_date,
                onChange: (e) => setDateRange(prev => ({ ...prev, start_date: e.target.value })),
                disabled: loading,
                max: new Date().toISOString().split('T')[0]
              }) :
              React.createElement('div', { key: 'range', style: { display: 'flex', gap: '10px' } }, [
                React.createElement('input', {
                  key: 'start',
                  type: 'date',
                  value: dateRange.start_date,
                  onChange: (e) => setDateRange(prev => ({ ...prev, start_date: e.target.value })),
                  style: { flex: 1 },
                  disabled: loading,
                  max: new Date().toISOString().split('T')[0]
                }),
                React.createElement('input', {
                  key: 'end',
                  type: 'date',
                  value: dateRange.end_date,
                  onChange: (e) => setDateRange(prev => ({ ...prev, end_date: e.target.value })),
                  style: { flex: 1 },
                  disabled: loading,
                  max: new Date().toISOString().split('T')[0]
                })
              ])
          ])
        ]),
        
        // Report info section
        reportData && React.createElement('div', { 
          key: 'report-info', 
          style: { 
            marginTop: '15px', 
            padding: '10px 15px', 
            backgroundColor: '#e8f5e8', 
            borderRadius: '8px',
            border: '1px solid #2ed573'
          } 
        }, [
          React.createElement('div', { 
            key: 'info-content',
            style: { 
              display: 'flex', 
              alignItems: 'center', 
              gap: '10px',
              color: '#2ed573',
              fontSize: '0.9rem',
              fontWeight: '500'
            } 
          }, [
            React.createElement('span', { key: 'icon' }, '✅'),
            React.createElement('span', { key: 'text' }, 
              `Report loaded successfully - ${activeReport.replace('_', ' ').toUpperCase()}`
            )
          ])
        ])
      ]),
      
      React.createElement('div', { key: 'report-content' }, renderReport())
    ]);
  }
  
  window.Components = window.Components || {};
  window.Components.Reports = Reports;
})();