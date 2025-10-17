(function(){
  const { useState, useEffect, useRef } = React;
  
  function PatientDischargeSummary({ onError, onSuccess }) {
    const [patientId, setPatientId] = useState('');
    const [patientName, setPatientName] = useState('');
    const [patientSearchTerm, setPatientSearchTerm] = useState('');
    const [patientSearchResults, setPatientSearchResults] = useState([]);
    const [patientInfo, setPatientInfo] = useState(null);
    const [cabinBooking, setCabinBooking] = useState(null);
    const [allCharges, setAllCharges] = useState([]);
    const [billsSummary, setBillsSummary] = useState(null);
    const [loading, setLoading] = useState(false);
    const [searchPerformed, setSearchPerformed] = useState(false);
    const [showSearchResults, setShowSearchResults] = useState(false);
    const [showDischargeModal, setShowDischargeModal] = useState(false);
    const [dischargeDate, setDischargeDate] = useState(new Date().toISOString().split('T')[0]);
    const [dischargeNotes, setDischargeNotes] = useState('');
    const searchTimeoutRef = useRef(null);

    // Load patient search results
    const handlePatientSearch = async (term) => {
      if (term.length < 2) {
        setPatientSearchResults([]);
        setShowSearchResults(false);
        return;
      }

      try {
        const response = await axios.get(`billing_api.php?action=get_patients&search=${encodeURIComponent(term)}`);
        if (response.data.success) {
          setPatientSearchResults(response.data.data || []);
          setShowSearchResults(true);
        }
      } catch (err) {
        console.error('Patient search error:', err);
      }
    };

    // Handle search input with debouncing
    useEffect(() => {
      if (searchTimeoutRef.current) {
        clearTimeout(searchTimeoutRef.current);
      }
      
      searchTimeoutRef.current = setTimeout(() => {
        handlePatientSearch(patientSearchTerm);
      }, 300);

      return () => {
        if (searchTimeoutRef.current) {
          clearTimeout(searchTimeoutRef.current);
        }
      };
    }, [patientSearchTerm]);

    // Select patient from search results
    const handleSelectPatient = (patient) => {
      setPatientId(patient.id);
      setPatientName(patient.name);
      setPatientSearchTerm(patient.name);
      setPatientInfo(patient);
      setShowSearchResults(false);
      setPatientSearchResults([]);
    };


    // Search for patient charges
    const searchPatientCharges = async () => {
      if (!patientId) {
        onError('Please select a patient first');
        return;
      }

      try {
        setLoading(true);
        setSearchPerformed(true);
        
        console.log('Searching charges for patient ID:', patientId);
        const response = await axios.get(`billing_api.php?action=patient_discharge_summary&patient_id=${patientId}`);
        console.log('API Response:', response.data);
        
        if (response.data.success) {
          setPatientInfo(response.data.patient_info);
          setCabinBooking(response.data.cabin_booking);
          setAllCharges(response.data.charges);
          setBillsSummary(response.data.bills_summary);
          onSuccess(`Found ${response.data.charges.length} charges for ${response.data.patient_info.name}`);
        } else {
          const errorMsg = response.data.error || 'No charges found for this patient';
          console.error('API Error:', errorMsg);
          onError(errorMsg);
          setPatientInfo(null);
          setCabinBooking(null);
          setAllCharges([]);
          setBillsSummary(null);
        }
      } catch (err) {
        console.error('Charges search error:', err);
        console.error('Error details:', {
          message: err.message,
          response: err.response?.data,
          status: err.response?.status,
          url: err.config?.url
        });
        
        let errorMessage = 'Failed to load patient charges';
        if (err.response?.data?.error) {
          errorMessage = err.response.data.error;
        } else if (err.message) {
          errorMessage = `Network error: ${err.message}`;
        }
        
        onError(errorMessage);
        setPatientInfo(null);
        setAllCharges([]);
      } finally {
        setLoading(false);
      }
    };

    // Complete patient discharge
    const completePatientDischarge = async () => {
      if (!patientInfo) {
        onError('No patient selected for discharge');
        return;
      }

      // Check if there are unpaid bills
      const unpaidAmount = calculateUnpaidAmount();
      if (unpaidAmount > 0) {
        const confirmPayment = window.confirm(
          `This patient has unpaid bills totaling ${formatCurrency(unpaidAmount)}.\n\n` +
          `Please ensure all bills are paid before completing discharge.\n\n` +
          `Do you want to proceed with discharge anyway?`
        );
        
        if (!confirmPayment) {
          onSuccess('Discharge cancelled. Please collect payment for unpaid bills first.');
          return;
        }
      }

      try {
        setLoading(true);
        
        const response = await axios.post('billing_api.php?action=complete_patient_discharge', {
          patient_id: patientId,
          discharge_date: dischargeDate,
          discharge_notes: dischargeNotes,
          unpaid_amount: unpaidAmount
        });
        
        if (response.data.success) {
          onSuccess(`Patient discharge completed successfully! Final bill: ৳${formatCurrency(response.data.total_amount)}`);
          setShowDischargeModal(false);
          // Refresh the data
          searchPatientCharges();
        } else {
          // Check if it's a payment requirement error
          if (response.data.requires_payment) {
            onError(`Payment Required: ${response.data.error}`);
          } else {
            onError(response.data.error || 'Failed to complete discharge');
          }
        }
      } catch (err) {
        console.error('Complete discharge error:', err);
        onError('Failed to complete discharge');
      } finally {
        setLoading(false);
      }
    };

    // Print discharge bill
    const printDischargeBill = () => {
      if (!patientInfo || allCharges.length === 0) {
        onError('No bill data to print');
        return;
      }

      const printWindow = window.open('', '_blank');
      const totalAmount = calculateTotal();
      
      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
          <title>Discharge Bill - ${patientInfo.name}</title>
          <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .hospital-name { font-size: 24px; font-weight: bold; color: #a259ff; }
            .bill-title { font-size: 18px; margin: 10px 0; }
            .patient-info { margin-bottom: 30px; }
            .info-row { display: flex; margin: 5px 0; }
            .info-label { font-weight: bold; width: 120px; }
            .charges-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
            .charges-table th, .charges-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .charges-table th { background-color: #a259ff; color: white; }
            .total-section { text-align: right; margin-top: 20px; }
            .total-amount { font-size: 24px; font-weight: bold; color: #a259ff; }
            .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; }
            @media print { body { margin: 0; } }
          </style>
        </head>
        <body>
          <div class="header">
            <div class="hospital-name">MediAI Hospital</div>
            <div class="bill-title">PATIENT DISCHARGE BILL</div>
            <div>Date: ${formatDate(new Date().toISOString())}</div>
          </div>
          
          <div class="patient-info">
            <div class="info-row"><span class="info-label">Patient Name:</span> ${patientInfo.name}</div>
            <div class="info-row"><span class="info-label">Patient ID:</span> ${patientInfo.id}</div>
            <div class="info-row"><span class="info-label">Phone:</span> ${patientInfo.phone || 'N/A'}</div>
            <div class="info-row"><span class="info-label">Email:</span> ${patientInfo.email || 'N/A'}</div>
            ${cabinBooking ? `
              <div class="info-row"><span class="info-label">Cabin:</span> #${cabinBooking.cabin_number} (${cabinBooking.cabin_type.toUpperCase()})</div>
              <div class="info-row"><span class="info-label">Check-in:</span> ${formatDate(cabinBooking.check_in)}</div>
              <div class="info-row"><span class="info-label">Days Stayed:</span> ${cabinBooking.days_stayed} days</div>
              <div class="info-row"><span class="info-label">Daily Rate:</span> ${formatCurrency(cabinBooking.daily_rate)}</div>
            ` : ''}
          </div>
          
          <table class="charges-table">
            <thead>
              <tr>
                <th>Item Name</th>
                <th>Type</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total Price</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              ${allCharges.map(charge => `
                <tr>
                  <td>${charge.item_name}</td>
                  <td>${charge.item_type}</td>
                  <td>${charge.quantity}</td>
                  <td>${formatCurrency(charge.unit_price)}</td>
                  <td>${formatCurrency(charge.total_price)}</td>
                  <td>${formatDate(charge.service_date || charge.item_date)}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
          
          <div class="total-section">
            <div class="total-amount">Total Amount: ${formatCurrency(totalAmount)} (Taka)</div>
          </div>
          
          <div class="footer">
            <p>Thank you for choosing MediAI Hospital</p>
            <p>For any queries, please contact our billing department</p>
          </div>
        </body>
        </html>
      `);
      
      printWindow.document.close();
      printWindow.focus();
      setTimeout(() => {
        printWindow.print();
        printWindow.close();
      }, 500);
    };

    const formatCurrency = (amount) => {
      if (amount === null || amount === undefined || isNaN(amount)) {
        return '৳0.00';
      }
      
      // Format with Taka symbol and proper number formatting
      const formattedAmount = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(amount);
      
      return `৳${formattedAmount}`;
    };

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A';
      
      try {
        // Handle different date formats
        let date;
        if (typeof dateString === 'string') {
          // Clean the date string
          const cleanDateString = dateString.trim();
          
          // Try different date formats
          if (cleanDateString.includes('T')) {
            date = new Date(cleanDateString);
          } else if (cleanDateString.includes('-')) {
            // Handle YYYY-MM-DD format
            date = new Date(cleanDateString + 'T00:00:00');
          } else if (cleanDateString.includes('/')) {
            // Handle MM/DD/YYYY or DD/MM/YYYY format
            date = new Date(cleanDateString);
          } else {
            date = new Date(cleanDateString);
          }
        } else if (dateString instanceof Date) {
          date = dateString;
        } else {
          date = new Date(dateString);
        }
        
        // Check if date is valid
        if (isNaN(date.getTime())) {
          console.warn('Invalid date:', dateString);
          return 'Invalid Date';
        }
        
        // Format as DD/MM/YYYY
        return date.toLocaleDateString('en-GB', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric'
        });
      } catch (error) {
        console.error('Date formatting error:', error, 'Input:', dateString);
        return 'Invalid Date';
      }
    };

    const calculateTotal = () => {
      // Use bills summary if available, otherwise calculate from charges
      if (billsSummary && billsSummary.total_bills_amount) {
        return parseFloat(billsSummary.total_bills_amount) || 0;
      }
      return allCharges.reduce((sum, charge) => sum + (charge.total_price || 0), 0);
    };

    const calculatePaidAmount = () => {
      if (billsSummary && billsSummary.paid_amount) {
        return parseFloat(billsSummary.paid_amount) || 0;
      }
      return allCharges
        .filter(charge => charge.status === 'paid')
        .reduce((sum, charge) => sum + (charge.total_price || 0), 0);
    };

    const calculateUnpaidAmount = () => {
      if (billsSummary && billsSummary.unpaid_amount) {
        return parseFloat(billsSummary.unpaid_amount) || 0;
      }
      return allCharges
        .filter(charge => charge.status !== 'paid')
        .reduce((sum, charge) => sum + (charge.total_price || 0), 0);
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
      // Header Section
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
          }, '🏥 Patient Discharge Summary'),
          React.createElement('div', { 
            key: 'action-buttons', 
            style: { 
              display: 'flex', 
              gap: '10px', 
              alignItems: 'center' 
            } 
          }, [
            allCharges.length > 0 && React.createElement('button', {
              key: 'print-btn',
              className: 'btn btn-warning',
              onClick: printDischargeBill,
              style: {
                padding: '8px 16px',
                fontSize: '0.9rem',
                display: 'flex',
                alignItems: 'center',
                gap: '8px'
              }
            }, [
              React.createElement('span', { key: 'print-icon' }, '🖨️'),
              'Print Bill'
            ])
          ])
        ]),
        
        // Patient Search Section
        React.createElement('div', { key: 'search-section', className: 'row' }, [
          React.createElement('div', { key: 'col1', className: 'col-md-8' }, [
            React.createElement('div', { key: 'search-form', className: 'form-group' }, [
              React.createElement('label', { key: 'label' }, 'Search Patient'),
              React.createElement('div', { key: 'search-container', className: 'discharge-search-container' }, [
              React.createElement('input', {
                key: 'input',
                  type: 'text',
                  value: patientSearchTerm,
                  onChange: (e) => {
                    setPatientSearchTerm(e.target.value);
                    if (e.target.value === '') {
                      setPatientId('');
                      setPatientName('');
                      setPatientInfo(null);
                      setAllCharges([]);
                      setSearchPerformed(false);
                    }
                  },
                  placeholder: 'Search by patient name, ID, or phone number',
                  onFocus: () => setShowSearchResults(true),
                onKeyPress: (e) => e.key === 'Enter' && searchPatientCharges()
                }),
                
                // Search Results Dropdown
                showSearchResults && patientSearchResults.length > 0 && React.createElement('div', { 
                  key: 'search-results', 
                  className: 'discharge-search-results' 
                }, 
                  patientSearchResults.map((patient, index) => 
                    React.createElement('div', { 
                      key: index, 
                      className: 'discharge-search-result-item',
                      onClick: () => handleSelectPatient(patient)
                    }, [
                      React.createElement('div', { 
                        key: 'name', 
                        className: 'discharge-patient-name' 
                      }, patient.name),
                      React.createElement('div', { 
                        key: 'details', 
                        className: 'discharge-patient-details' 
                      }, `ID: ${patient.id} | Phone: ${patient.phone || 'N/A'}`)
                    ])
                  )
                )
              ])
            ])
          ]),
          React.createElement('div', { key: 'col2', className: 'col-md-4' }, [
            React.createElement('div', { key: 'search-btn', className: 'form-group' }, [
              React.createElement('label', { key: 'label', style: { visibility: 'hidden' } }, 'Search'),
              React.createElement('button', {
                key: 'btn',
                className: 'btn btn-primary',
                onClick: searchPatientCharges,
                disabled: loading || !patientId,
                style: { width: '100%' }
              }, loading ? 'Searching...' : '🔍 Search Charges')
            ])
          ])
        ])
      ]),

      // Patient Information
      patientInfo && React.createElement('div', { key: 'patient-info', className: 'card' }, [
        React.createElement('h4', { 
          key: 'title', 
          style: { 
            marginBottom: '15px', 
            color: '#a259ff', 
            borderBottom: '2px solid #a259ff', 
            paddingBottom: '8px',
            display: 'flex',
            alignItems: 'center',
            gap: '10px'
          } 
        }, [
          React.createElement('span', { key: 'icon' }, '👤'),
          'Patient Information'
        ]),
        
        React.createElement('div', { key: 'info-grid', className: 'discharge-patient-info-grid' }, [
          React.createElement('div', { key: 'name', className: 'discharge-info-card' }, [
            React.createElement('div', { key: 'icon-label', className: 'discharge-info-icon-label' }, [
              React.createElement('span', { key: 'icon' }, '👤'),
              React.createElement('span', { key: 'label', className: 'discharge-info-label' }, 'Patient Name')
            ]),
            React.createElement('div', { key: 'value', className: 'discharge-info-value' }, patientInfo.name)
          ]),
          
          React.createElement('div', { key: 'id', className: 'discharge-info-card' }, [
            React.createElement('div', { key: 'icon-label', className: 'discharge-info-icon-label' }, [
              React.createElement('span', { key: 'icon' }, '🆔'),
              React.createElement('span', { key: 'label', className: 'discharge-info-label' }, 'Patient ID')
            ]),
            React.createElement('div', { key: 'value', className: 'discharge-info-value' }, patientInfo.id)
          ]),
          
          React.createElement('div', { key: 'phone', className: 'discharge-info-card' }, [
            React.createElement('div', { key: 'icon-label', className: 'discharge-info-icon-label' }, [
              React.createElement('span', { key: 'icon' }, '📞'),
              React.createElement('span', { key: 'label', className: 'discharge-info-label' }, 'Phone')
            ]),
            React.createElement('div', { key: 'value', className: 'discharge-info-value' }, patientInfo.phone || 'N/A')
          ]),
          
          React.createElement('div', { key: 'email', className: 'discharge-info-card' }, [
            React.createElement('div', { key: 'icon-label', className: 'discharge-info-icon-label' }, [
              React.createElement('span', { key: 'icon' }, '📧'),
              React.createElement('span', { key: 'label', className: 'discharge-info-label' }, 'Email')
            ]),
            React.createElement('div', { key: 'value', className: 'discharge-info-value' }, patientInfo.email || 'N/A')
          ])
        ])
      ]),

      // Cabin Booking Information
      cabinBooking && React.createElement('div', { key: 'cabin-info', className: 'card' }, [
        React.createElement('h4', { 
          key: 'title', 
          style: { 
            marginBottom: '15px', 
            color: '#a259ff', 
            borderBottom: '2px solid #a259ff', 
            paddingBottom: '8px',
            display: 'flex',
            alignItems: 'center',
            gap: '10px'
          } 
        }, [
          React.createElement('span', { key: 'icon' }, '🏥'),
          'Cabin Booking Information'
        ]),
        
        React.createElement('div', { key: 'cabin-grid', className: 'discharge-patient-info-grid' }, [
          React.createElement('div', { key: 'cabin-number', className: 'discharge-info-card' }, [
            React.createElement('div', { key: 'icon-label', className: 'discharge-info-icon-label' }, [
              React.createElement('span', { key: 'icon' }, '🏠'),
              React.createElement('span', { key: 'label', className: 'discharge-info-label' }, 'Cabin Number')
            ]),
            React.createElement('div', { key: 'value', className: 'discharge-info-value' }, `#${cabinBooking.cabin_number}`)
          ]),
          
          React.createElement('div', { key: 'cabin-type', className: 'discharge-info-card' }, [
            React.createElement('div', { key: 'icon-label', className: 'discharge-info-icon-label' }, [
              React.createElement('span', { key: 'icon' }, '⭐'),
              React.createElement('span', { key: 'label', className: 'discharge-info-label' }, 'Cabin Type')
            ]),
            React.createElement('div', { key: 'value', className: 'discharge-info-value' }, cabinBooking.cabin_type.toUpperCase())
          ]),
          
          React.createElement('div', { key: 'check-in', className: 'discharge-info-card' }, [
            React.createElement('div', { key: 'icon-label', className: 'discharge-info-icon-label' }, [
              React.createElement('span', { key: 'icon' }, '📅'),
              React.createElement('span', { key: 'label', className: 'discharge-info-label' }, 'Check-in Date')
            ]),
            React.createElement('div', { key: 'value', className: 'discharge-info-value' }, formatDate(cabinBooking.check_in))
          ]),
          
          React.createElement('div', { key: 'days-stayed', className: 'discharge-info-card' }, [
            React.createElement('div', { key: 'icon-label', className: 'discharge-info-icon-label' }, [
              React.createElement('span', { key: 'icon' }, '⏰'),
              React.createElement('span', { key: 'label', className: 'discharge-info-label' }, 'Days Stayed')
            ]),
            React.createElement('div', { key: 'value', className: 'discharge-info-value' }, `${cabinBooking.days_stayed} days`)
          ]),
          
          React.createElement('div', { key: 'daily-rate', className: 'discharge-info-card' }, [
            React.createElement('div', { key: 'icon-label', className: 'discharge-info-icon-label' }, [
              React.createElement('span', { key: 'icon' }, '💰'),
              React.createElement('span', { key: 'label', className: 'discharge-info-label' }, 'Daily Rate')
            ]),
            React.createElement('div', { key: 'value', className: 'discharge-info-value' }, formatCurrency(cabinBooking.daily_rate))
          ]),
          
          React.createElement('div', { key: 'cabin-total', className: 'discharge-info-card' }, [
            React.createElement('div', { key: 'icon-label', className: 'discharge-info-icon-label' }, [
              React.createElement('span', { key: 'icon' }, '💳'),
              React.createElement('span', { key: 'label', className: 'discharge-info-label' }, 'Cabin Total')
            ]),
            React.createElement('div', { key: 'value', className: 'discharge-info-value' }, formatCurrency(cabinBooking.cabin_total_cost))
          ])
        ])
      ]),

      // Loading State
      loading && React.createElement('div', { key: 'loading', className: 'card' }, [
        React.createElement('div', { key: 'loading-content', className: 'discharge-loading-state' }, [
          React.createElement('div', { key: 'spinner', className: 'discharge-loading-spinner' }),
          React.createElement('h4', { key: 'title', className: 'discharge-loading-title' }, 'Loading Patient Data...'),
          React.createElement('p', { key: 'text', className: 'discharge-loading-text' }, 'Please wait while we fetch the patient information and charges.')
        ])
      ]),

      // Charges Summary
      allCharges.length > 0 && React.createElement('div', { key: 'charges-summary', className: 'card' }, [
        React.createElement('div', { key: 'summary-header', className: 'discharge-summary-header' }, [
          React.createElement('div', { key: 'title-section', className: 'discharge-summary-title' }, [
            React.createElement('h4', { key: 'title', style: { margin: 0, color: '#a259ff' } }, '💰 All Charges Summary'),
            React.createElement('span', { key: 'count', className: 'discharge-charges-count' }, `${allCharges.length} items`)
          ]),
          React.createElement('button', {
            key: 'generate-btn',
            className: 'btn btn-success',
            onClick: () => setShowDischargeModal(true),
            disabled: loading,
            style: { 
              padding: '10px 20px',
              backgroundColor: calculateUnpaidAmount() > 0 ? '#ff4757' : '#2ed573',
              boxShadow: calculateUnpaidAmount() > 0 ? '0 4px 15px rgba(255, 71, 87, 0.3)' : '0 4px 15px rgba(46, 213, 115, 0.3)'
            }
          }, loading ? 'Processing...' : calculateUnpaidAmount() > 0 ? '⚠️ Complete Discharge (Unpaid Bills)' : '🏥 Complete Discharge')
        ]),
        
        React.createElement('div', { key: 'total-display', className: 'discharge-total-display' }, [
          React.createElement('div', { key: 'total-header', className: 'discharge-total-header' }, [
            React.createElement('span', { key: 'icon' }, '💰'),
            React.createElement('h3', { key: 'total-label', className: 'discharge-total-label' }, 'Total Amount from Bills Table (Taka)')
          ]),
          React.createElement('div', { 
            key: 'total-amount', 
            className: 'discharge-total-amount',
            style: {
              fontSize: '2rem',
              fontWeight: 'bold',
              color: '#a259ff',
              textAlign: 'center',
              padding: '15px',
              backgroundColor: '#f8f9fa',
              borderRadius: '10px',
              border: '2px solid #a259ff',
              marginTop: '10px'
            }
          }, formatCurrency(calculateTotal())),
          billsSummary && React.createElement('div', { 
            key: 'bills-info', 
            style: { 
              marginTop: '10px', 
              fontSize: '0.9rem', 
              color: '#6c757d',
          textAlign: 'center'
            } 
          }, [
            React.createElement('div', { key: 'paid-info' }, `Paid: ${formatCurrency(calculatePaidAmount())}`),
            React.createElement('div', { key: 'unpaid-info' }, `Unpaid: ${formatCurrency(calculateUnpaidAmount())}`),
            React.createElement('div', { key: 'bills-count' }, `Total Bills: ${billsSummary.total_bills || 0}`)
          ])
        ]),

        // Bills grouped by bill_id
        groupedBills.map((bill, billIndex) => 
          React.createElement('div', { key: `bill-${bill.bill_id}`, className: 'discharge-bill-card' }, [
            React.createElement('div', { key: 'bill-header', className: 'discharge-bill-header' }, [
              React.createElement('div', { key: 'bill-title' }, [
                React.createElement('h5', { key: 'title', style: { margin: '0 0 8px 0', color: '#a259ff' } }, 
                  `📋 Bill #${bill.bill_id} - ${bill.bill_type.toUpperCase()}`
                ),
                React.createElement('div', { key: 'bill-meta', className: 'discharge-bill-meta' }, [
                  React.createElement('span', { key: 'date' }, `📅 Issued: ${formatDate(bill.issued_date)}`),
                  React.createElement('span', { key: 'separator' }, '•'),
                    React.createElement('span', { key: 'status', className: `status-badge status-${bill.status}` }, bill.status.toUpperCase())
                  ])
                ]),
              React.createElement('div', { key: 'bill-total', className: 'discharge-bill-total' }, 
                formatCurrency(bill.items.reduce((sum, item) => sum + item.total_price, 0))
              )
            ]),
            
            React.createElement('div', { key: 'bill-items', className: 'discharge-bill-items' }, [
              React.createElement('table', { key: 'table', className: 'discharge-items-table' }, [
                React.createElement('thead', { key: 'thead', className: 'discharge-items-header' }, [
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
                    React.createElement('tr', { key: itemIndex, className: 'discharge-items-row' }, [
                      React.createElement('td', { key: 'item' }, [
                        React.createElement('div', { key: 'item-name', className: 'discharge-item-name' }, item.item_name),
                        item.item_description && React.createElement('div', { 
                          key: 'item-desc', 
                          className: 'discharge-item-description' 
                        }, item.item_description)
                      ]),
                      React.createElement('td', { key: 'type' }, item.item_type),
                      React.createElement('td', { key: 'qty', className: 'discharge-item-quantity' }, item.quantity),
                      React.createElement('td', { key: 'price', className: 'discharge-item-price' }, formatCurrency(item.unit_price)),
                      React.createElement('td', { key: 'total', className: 'discharge-item-total' }, formatCurrency(item.total_price)),
                      React.createElement('td', { key: 'date', className: 'discharge-item-date' }, formatDate(item.service_date || item.item_date))
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
        className: 'discharge-empty-state'
      }, [
        React.createElement('div', { key: 'icon', className: 'discharge-empty-icon' }, '📋'),
        React.createElement('h4', { key: 'title', className: 'discharge-empty-title' }, 'No Charges Found'),
        React.createElement('p', { key: 'message', className: 'discharge-empty-text' }, 
          `No charges found for ${patientInfo ? patientInfo.name : 'the selected patient'}. This patient may not have any bills or charges recorded in the system.`
        )
      ]),

      // Discharge Confirmation Modal
      showDischargeModal && React.createElement('div', { 
        key: 'discharge-modal', 
        className: 'modal-overlay',
        onClick: (e) => e.target.className === 'modal-overlay' && setShowDischargeModal(false)
      }, [
        React.createElement('div', { key: 'modal-content', className: 'modal-content' }, [
          React.createElement('div', { key: 'modal-header', style: { 
            display: 'flex', 
            justifyContent: 'space-between', 
            alignItems: 'center', 
            marginBottom: '20px',
            paddingBottom: '15px',
            borderBottom: '2px solid #a259ff'
          } }, [
            React.createElement('h3', { key: 'modal-title', style: { margin: 0, color: '#a259ff' } }, '🏥 Complete Patient Discharge'),
            React.createElement('button', {
              key: 'close-btn',
              onClick: () => setShowDischargeModal(false),
              style: {
                background: 'none',
                border: 'none',
                fontSize: '24px',
                cursor: 'pointer',
                color: '#a259ff'
              }
            }, '×')
          ]),
          
          React.createElement('div', { key: 'modal-body' }, [
            React.createElement('div', { key: 'patient-summary', style: { 
              backgroundColor: '#f8f9fa', 
              padding: '15px', 
              borderRadius: '8px', 
              marginBottom: '20px' 
            } }, [
              React.createElement('h4', { key: 'summary-title', style: { margin: '0 0 10px 0', color: '#495057' } }, 'Discharge Summary'),
              React.createElement('p', { key: 'patient-name', style: { margin: '5px 0', color: '#6c757d' } }, `Patient: ${patientInfo?.name}`),
              React.createElement('p', { key: 'total-amount', style: { margin: '5px 0', color: '#6c757d' } }, `Total Amount from Bills: ${formatCurrency(calculateTotal())} (Taka)`),
              React.createElement('p', { key: 'paid-amount', style: { margin: '5px 0', color: '#2ed573' } }, `Paid Amount: ${formatCurrency(calculatePaidAmount())} (Taka)`),
              calculateUnpaidAmount() > 0 && React.createElement('p', { 
                key: 'unpaid-warning', 
                style: { 
                  margin: '5px 0', 
                  color: '#ff4757', 
                  fontWeight: 'bold',
                  backgroundColor: '#ffe8e8',
                  padding: '8px',
                  borderRadius: '4px',
                  border: '1px solid #ff4757'
                } 
              }, `⚠️ Unpaid Amount: ${formatCurrency(calculateUnpaidAmount())} (Taka) - Please collect payment before discharge`),
              cabinBooking && React.createElement('p', { key: 'cabin-info', style: { margin: '5px 0', color: '#6c757d' } }, 
                `Cabin: #${cabinBooking.cabin_number} (${cabinBooking.cabin_type}) - ${cabinBooking.days_stayed} days`
              )
            ]),
            
            React.createElement('div', { key: 'form-group', className: 'form-group' }, [
              React.createElement('label', { key: 'date-label' }, 'Discharge Date'),
              React.createElement('input', {
                key: 'date-input',
                type: 'date',
                value: dischargeDate,
                onChange: (e) => setDischargeDate(e.target.value),
                max: new Date().toISOString().split('T')[0]
              })
            ]),
            
            React.createElement('div', { key: 'notes-group', className: 'form-group' }, [
              React.createElement('label', { key: 'notes-label' }, 'Discharge Notes (Optional)'),
              React.createElement('textarea', {
                key: 'notes-input',
                value: dischargeNotes,
                onChange: (e) => setDischargeNotes(e.target.value),
                placeholder: 'Enter any discharge notes or instructions...',
                rows: 3,
                style: { resize: 'vertical' }
              })
            ])
          ]),
          
          React.createElement('div', { key: 'modal-footer', style: { 
            display: 'flex', 
            justifyContent: 'flex-end', 
            gap: '10px', 
            marginTop: '20px',
            paddingTop: '15px',
            borderTop: '1px solid #e9ecef'
          } }, [
            React.createElement('button', {
              key: 'cancel-btn',
              className: 'btn btn-secondary',
              onClick: () => setShowDischargeModal(false),
              disabled: loading
            }, 'Cancel'),
            React.createElement('button', {
              key: 'confirm-btn',
              className: 'btn btn-success',
              onClick: completePatientDischarge,
              disabled: loading
            }, loading ? 'Processing...' : '✅ Complete Discharge')
          ])
        ])
      ])
    ]);
  }
  
  window.Components = window.Components || {};
  window.Components.PatientDischargeSummary = PatientDischargeSummary;
})();
