(function(){
  const { useState, useEffect } = React;
  
  function DoctorManagement() {
    const [doctors, setDoctors] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [pagination, setPagination] = useState({ page: 1, limit: 10, total: 0, pages: 0 });
    const [editingDoctor, setEditingDoctor] = useState(null);
    const [showEditForm, setShowEditForm] = useState(false);
    const [showAddForm, setShowAddForm] = useState(false);
    const [deletingDoctorId, setDeletingDoctorId] = useState(null);
    const [searchTerm, setSearchTerm] = useState('');
    const [specializationFilter, setSpecializationFilter] = useState('');
    const [hospitalFilter, setHospitalFilter] = useState('');
    const [blockedFilter, setBlockedFilter] = useState('');
    const [hospitals, setHospitals] = useState([]);
    const [specializations, setSpecializations] = useState([]);
    const [selectedDoctor, setSelectedDoctor] = useState(null);
    const [showDoctorDetails, setShowDoctorDetails] = useState(false);
    const [showScheduleModal, setShowScheduleModal] = useState(false);
    const [doctorSchedules, setDoctorSchedules] = useState([]);

    useEffect(() => {
      loadDoctors();
      loadHospitals();
      loadSpecializations();
    }, [pagination.page, searchTerm, specializationFilter, hospitalFilter, blockedFilter]);

    const loadDoctors = async () => {
      try {
        setLoading(true);
        const params = new URLSearchParams({
          action: 'doctors_list',
          page: pagination.page,
          limit: pagination.limit
        });
        
        const response = await axios.get(`admin_api.php?${params.toString()}`);
        
        if (response.data.success) {
          setDoctors(response.data.data);
          setPagination(response.data.pagination);
        } else {
          setError(response.data.error || 'Failed to load doctors');
        }
      } catch (err) {
        setError('Failed to load doctors');
      } finally {
        setLoading(false);
      }
    };

    const loadHospitals = async () => {
      try {
        const response = await axios.get('admin_api.php?action=hospitals_for_doctors');
        if (response.data.success) {
          setHospitals(response.data.data);
        }
      } catch (err) {
        console.error('Failed to load hospitals:', err);
      }
    };

    const loadSpecializations = async () => {
      try {
        const response = await axios.get('admin_api.php?action=doctor_specializations');
        if (response.data.success) {
          setSpecializations(response.data.data);
        }
      } catch (err) {
        console.error('Failed to load specializations:', err);
      }
    };

    const loadDoctorSchedules = async (doctorId) => {
      try {
        const response = await axios.get(`admin_api.php?action=doctor_schedule_list&doctor_id=${doctorId}`);
        if (response.data.success) {
          setDoctorSchedules(response.data.data);
        }
      } catch (err) {
        console.error('Failed to load schedules:', err);
      }
    };

    const handleAddDoctor = async (formData) => {
      try {
        setError('');
        setSuccess('');
        const response = await axios.post('admin_api.php?action=doctor_add', formData);
        if (response.data.success) {
          setSuccess('Doctor added successfully');
          setShowAddForm(false);
          loadDoctors();
        } else {
          setError(response.data.error || 'Failed to add doctor');
        }
      } catch (err) {
        setError('Failed to add doctor');
      }
    };

    const handleUpdateDoctor = async (formData) => {
      try {
        setError('');
        setSuccess('');
        const response = await axios.post('admin_api.php?action=doctor_update', formData);
        if (response.data.success) {
          setSuccess('Doctor updated successfully');
          setShowEditForm(false);
          setEditingDoctor(null);
          loadDoctors();
        } else {
          setError(response.data.error || 'Failed to update doctor');
        }
      } catch (err) {
        setError('Failed to update doctor');
      }
    };

    const handleDeleteDoctor = async (doctorId) => {
      const doctor = doctors.find(d => d.user_id === doctorId);
      const doctorName = doctor ? doctor.doctor_name : 'this doctor';
      
      if (!confirm(`Are you sure you want to delete ${doctorName}? This action cannot be undone and will remove all associated data.`)) return;
      
      try {
        setDeletingDoctorId(doctorId);
        setError('');
        setSuccess('');
        const response = await axios.post('admin_api.php?action=doctor_delete', { doctor_id: doctorId });
        if (response.data.success) {
          setSuccess('Doctor deleted successfully');
          loadDoctors();
        } else {
          setError(response.data.error || 'Failed to delete doctor');
        }
      } catch (err) {
        setError('Failed to delete doctor');
      } finally {
        setDeletingDoctorId(null);
      }
    };

    const handleBlockDoctor = async (doctorId, action) => {
      try {
        setError('');
        setSuccess('');
        const response = await axios.post('admin_api.php?action=doctor_block', { 
          doctor_id: doctorId, 
          action: action 
        });
        if (response.data.success) {
          setSuccess(`Doctor ${action}ed successfully`);
          loadDoctors();
        } else {
          setError(response.data.error || `Failed to ${action} doctor`);
        }
      } catch (err) {
        setError(`Failed to ${action} doctor`);
      }
    };

    const clearFilters = () => {
      setSearchTerm('');
      setSpecializationFilter('');
      setHospitalFilter('');
      setBlockedFilter('');
      setPagination(prev => ({ ...prev, page: 1 }));
    };

    const handleViewDoctorDetails = (doctor) => {
      setSelectedDoctor(doctor);
      setShowDoctorDetails(true);
    };

    const handleManageSchedule = (doctor) => {
      setSelectedDoctor(doctor);
      setShowScheduleModal(true);
      loadDoctorSchedules(doctor.user_id);
    };

    const AddDoctorForm = ({ onSave, onCancel }) => {
      const [formData, setFormData] = useState({
        name: '', email: '', password: '', phone: '',
        specialization: '', license_number: '', hospital_ids: []
      });

      const handleSubmit = (e) => {
        e.preventDefault();
        onSave(formData);
      };

      return React.createElement('div', { 
        key: 'add-form',
        className: 'card',
        style: { marginBottom: '20px' }
      }, [
        React.createElement('h4', { key: 'title' }, 'Add New Doctor'),
        React.createElement('form', { key: 'form', onSubmit: handleSubmit }, [
          React.createElement('div', { key: 'row1', className: 'row' }, [
            React.createElement('div', { key: 'name-col', className: 'col-md-6' }, [
              React.createElement('label', { key: 'name-label' }, 'Full Name *'),
              React.createElement('input', {
                key: 'name-input',
                type: 'text',
                className: 'form-control',
                value: formData.name,
                onChange: (e) => setFormData({...formData, name: e.target.value}),
                required: true
              })
            ]),
            React.createElement('div', { key: 'email-col', className: 'col-md-6' }, [
              React.createElement('label', { key: 'email-label' }, 'Email *'),
              React.createElement('input', {
                key: 'email-input',
                type: 'email',
                className: 'form-control',
                value: formData.email,
                onChange: (e) => setFormData({...formData, email: e.target.value}),
                required: true
              })
            ])
          ]),
          
          React.createElement('div', { key: 'row2', className: 'row' }, [
            React.createElement('div', { key: 'password-col', className: 'col-md-6' }, [
              React.createElement('label', { key: 'password-label' }, 'Password *'),
              React.createElement('input', {
                key: 'password-input',
                type: 'password',
                className: 'form-control',
                value: formData.password,
                onChange: (e) => setFormData({...formData, password: e.target.value}),
                required: true
              })
            ]),
            React.createElement('div', { key: 'phone-col', className: 'col-md-6' }, [
              React.createElement('label', { key: 'phone-label' }, 'Phone'),
              React.createElement('input', {
                key: 'phone-input',
                type: 'tel',
                className: 'form-control',
                value: formData.phone,
                onChange: (e) => setFormData({...formData, phone: e.target.value})
              })
            ])
          ]),
          
          React.createElement('div', { key: 'row3', className: 'row' }, [
            React.createElement('div', { key: 'specialization-col', className: 'col-md-6' }, [
              React.createElement('label', { key: 'specialization-label' }, 'Specialization *'),
              React.createElement('input', {
                key: 'specialization-input',
                type: 'text',
                className: 'form-control',
                value: formData.specialization,
                onChange: (e) => setFormData({...formData, specialization: e.target.value}),
                required: true
              })
            ]),
            React.createElement('div', { key: 'license-col', className: 'col-md-6' }, [
              React.createElement('label', { key: 'license-label' }, 'License Number'),
              React.createElement('input', {
                key: 'license-input',
                type: 'text',
                className: 'form-control',
                value: formData.license_number,
                onChange: (e) => setFormData({...formData, license_number: e.target.value})
              })
            ])
          ]),
          
          React.createElement('div', { key: 'row4', className: 'row' }, [
            React.createElement('div', { key: 'hospitals-col', className: 'col-md-12' }, [
              React.createElement('label', { key: 'hospitals-label' }, 'Assign to Hospitals'),
              React.createElement('div', { key: 'hospitals-checkboxes' }, 
                hospitals.map(hospital => 
                  React.createElement('div', { key: hospital.id, className: 'form-check' }, [
                    React.createElement('input', {
                      key: 'checkbox',
                      type: 'checkbox',
                      className: 'form-check-input',
                      id: `hospital-${hospital.id}`,
                      checked: formData.hospital_ids.includes(hospital.id),
                      onChange: (e) => {
                        if (e.target.checked) {
                          setFormData({...formData, hospital_ids: [...formData.hospital_ids, hospital.id]});
                        } else {
                          setFormData({...formData, hospital_ids: formData.hospital_ids.filter(id => id !== hospital.id)});
                        }
                      }
                    }),
                    React.createElement('label', { 
                      key: 'label',
                      className: 'form-check-label',
                      htmlFor: `hospital-${hospital.id}`
                    }, hospital.name)
                  ])
                )
              )
            ])
          ]),
          
          React.createElement('div', { key: 'buttons', className: 'row', style: { marginTop: '15px' } }, [
            React.createElement('button', { key: 'save', type: 'submit', className: 'btn btn-success' }, 'Add Doctor'),
            React.createElement('button', { key: 'cancel', type: 'button', className: 'btn btn-secondary', onClick: onCancel, style: { marginLeft: '10px' } }, 'Cancel')
          ])
        ])
      ]);
    };

    const EditDoctorForm = ({ doctor, onSave, onCancel }) => {
      const [formData, setFormData] = useState({
        doctor_id: doctor.user_id,
        name: doctor.doctor_name,
        email: doctor.email,
        phone: doctor.phone,
        specialization: doctor.specialization,
        license_number: doctor.license_number,
        hospital_ids: doctor.hospital_ids ? doctor.hospital_ids.split(',').map(id => parseInt(id)) : []
      });

      const handleSubmit = (e) => {
        e.preventDefault();
        onSave(formData);
      };

      return React.createElement('div', { 
        key: 'edit-form',
        className: 'card',
        style: { marginBottom: '20px' }
      }, [
        React.createElement('h4', { key: 'title' }, 'Edit Doctor'),
        React.createElement('form', { key: 'form', onSubmit: handleSubmit }, [
          React.createElement('div', { key: 'row1', className: 'row' }, [
            React.createElement('div', { key: 'name-col', className: 'col-md-6' }, [
              React.createElement('label', { key: 'name-label' }, 'Full Name *'),
              React.createElement('input', {
                key: 'name-input',
                type: 'text',
                className: 'form-control',
                value: formData.name,
                onChange: (e) => setFormData({...formData, name: e.target.value}),
                required: true
              })
            ]),
            React.createElement('div', { key: 'email-col', className: 'col-md-6' }, [
              React.createElement('label', { key: 'email-label' }, 'Email *'),
              React.createElement('input', {
                key: 'email-input',
                type: 'email',
                className: 'form-control',
                value: formData.email,
                onChange: (e) => setFormData({...formData, email: e.target.value}),
                required: true
              })
            ])
          ]),
          
          React.createElement('div', { key: 'row2', className: 'row' }, [
            React.createElement('div', { key: 'phone-col', className: 'col-md-6' }, [
              React.createElement('label', { key: 'phone-label' }, 'Phone'),
              React.createElement('input', {
                key: 'phone-input',
                type: 'tel',
                className: 'form-control',
                value: formData.phone,
                onChange: (e) => setFormData({...formData, phone: e.target.value})
              })
            ]),
            React.createElement('div', { key: 'specialization-col', className: 'col-md-6' }, [
              React.createElement('label', { key: 'specialization-label' }, 'Specialization *'),
              React.createElement('input', {
                key: 'specialization-input',
                type: 'text',
                className: 'form-control',
                value: formData.specialization,
                onChange: (e) => setFormData({...formData, specialization: e.target.value}),
                required: true
              })
            ])
          ]),
          
          React.createElement('div', { key: 'row3', className: 'row' }, [
            React.createElement('div', { key: 'license-col', className: 'col-md-6' }, [
              React.createElement('label', { key: 'license-label' }, 'License Number'),
              React.createElement('input', {
                key: 'license-input',
                type: 'text',
                className: 'form-control',
                value: formData.license_number,
                onChange: (e) => setFormData({...formData, license_number: e.target.value})
              })
            ]),
            React.createElement('div', { key: 'hospitals-col', className: 'col-md-6' }, [
              React.createElement('label', { key: 'hospitals-label' }, 'Assign to Hospitals'),
              React.createElement('div', { key: 'hospitals-checkboxes' }, 
                hospitals.map(hospital => 
                  React.createElement('div', { key: hospital.id, className: 'form-check' }, [
                    React.createElement('input', {
                      key: 'checkbox',
                      type: 'checkbox',
                      className: 'form-check-input',
                      id: `edit-hospital-${hospital.id}`,
                      checked: formData.hospital_ids.includes(hospital.id),
                      onChange: (e) => {
                        if (e.target.checked) {
                          setFormData({...formData, hospital_ids: [...formData.hospital_ids, hospital.id]});
                        } else {
                          setFormData({...formData, hospital_ids: formData.hospital_ids.filter(id => id !== hospital.id)});
                        }
                      }
                    }),
                    React.createElement('label', { 
                      key: 'label',
                      className: 'form-check-label',
                      htmlFor: `edit-hospital-${hospital.id}`
                    }, hospital.name)
                  ])
                )
              )
            ])
          ]),
          
          React.createElement('div', { key: 'buttons', className: 'row', style: { marginTop: '15px' } }, [
            React.createElement('button', { key: 'save', type: 'submit', className: 'btn btn-success' }, 'Update Doctor'),
            React.createElement('button', { key: 'cancel', type: 'button', className: 'btn btn-secondary', onClick: onCancel, style: { marginLeft: '10px' } }, 'Cancel')
          ])
        ])
      ]);
    };

    const DoctorDetailsModal = ({ doctor, onClose }) => {
      return React.createElement('div', { 
        key: 'modal-overlay',
        className: 'modal-overlay',
        style: {
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          backgroundColor: 'rgba(0,0,0,0.8)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          zIndex: 1000
        },
        onClick: onClose
      }, [
        React.createElement('div', {
          key: 'modal-content',
          className: 'modal-content',
          style: {
            backgroundColor: '#fff',
            borderRadius: '20px',
            maxWidth: '600px',
            width: '90%',
            maxHeight: '85vh',
            overflow: 'auto',
            boxShadow: '0 20px 60px rgba(0,0,0,0.3)',
            position: 'relative'
          },
          onClick: (e) => e.stopPropagation()
        }, [
          // Header with close button
          React.createElement('div', { 
            key: 'modal-header', 
            style: { 
              background: 'linear-gradient(135deg, #a259ff 0%, #8a4ae6 100%)',
              color: '#fff',
              padding: '20px 30px',
              borderRadius: '20px 20px 0 0',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center'
            } 
          }, [
            React.createElement('h3', { 
              key: 'title', 
              style: { margin: 0, fontSize: '1.5rem', fontWeight: '600' } 
            }, 'Doctor Profile'),
            React.createElement('button', { 
              key: 'close', 
              onClick: onClose, 
              style: { 
                background: 'rgba(255,255,255,0.2)', 
                border: 'none', 
                borderRadius: '50%',
                width: '35px',
                height: '35px',
                fontSize: '18px', 
                cursor: 'pointer',
                color: '#fff',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center'
              } 
            }, '×')
          ]),
          
          // Content
          React.createElement('div', { 
            key: 'modal-body', 
            style: { padding: '30px' } 
          }, [
            // Doctor Photo and Basic Info
            React.createElement('div', { 
              key: 'profile-section', 
              style: { 
                textAlign: 'center', 
                marginBottom: '30px',
                paddingBottom: '25px',
                borderBottom: '2px solid #f0f0f0'
              } 
            }, [
              React.createElement('div', {
                key: 'photo-container',
                style: {
                  position: 'relative',
                  display: 'inline-block',
                  marginBottom: '15px'
                }
              }, [
                React.createElement('img', {
                  key: 'doctor-photo',
                  src: doctor.photo && doctor.photo !== 'default.jpg' ? `../uploads/doctors/${doctor.photo}` : '../img/portrait-medical-doctor-posing-office-16974063-1902546574.jpg',
                  alt: doctor.doctor_name,
                  style: {
                    width: '120px',
                    height: '120px',
                    borderRadius: '50%',
                    objectFit: 'cover',
                    border: '4px solid #a259ff',
                    boxShadow: '0 8px 25px rgba(162, 89, 255, 0.3)'
                  },
                  onError: (e) => {
                    e.target.src = '../img/portrait-medical-doctor-posing-office-16974063-1902546574.jpg';
                  }
                }),
                React.createElement('div', {
                  key: 'status-indicator',
                  style: {
                    position: 'absolute',
                    bottom: '5px',
                    right: '5px',
                    width: '20px',
                    height: '20px',
                    borderRadius: '50%',
                    backgroundColor: doctor.is_blocked == 1 ? '#ff4757' : '#2ed573',
                    border: '3px solid #fff',
                    boxShadow: '0 2px 8px rgba(0,0,0,0.2)'
                  }
                })
              ]),
              React.createElement('h2', { 
                key: 'doctor-name', 
                style: { 
                  margin: '0 0 5px 0', 
                  color: '#333',
                  fontSize: '1.8rem',
                  fontWeight: '600'
                } 
              }, doctor.doctor_name),
              React.createElement('p', { 
                key: 'specialization', 
                style: { 
                  margin: '0 0 10px 0', 
                  color: '#a259ff',
                  fontSize: '1.1rem',
                  fontWeight: '500'
                } 
              }, doctor.specialization),
              React.createElement('div', {
                key: 'status-badges',
                style: { display: 'flex', justifyContent: 'center', gap: '10px', flexWrap: 'wrap' }
              }, [
                React.createElement('span', {
                  key: 'availability-badge',
                  style: {
                    padding: '6px 12px',
                    borderRadius: '20px',
                    fontSize: '0.85rem',
                    fontWeight: '600',
                    backgroundColor: doctor.available == 1 ? '#e8f5e8' : '#ffe8e8',
                    color: doctor.available == 1 ? '#2ed573' : '#ff4757',
                    border: `1px solid ${doctor.available == 1 ? '#2ed573' : '#ff4757'}`
                  }
                }, doctor.available == 1 ? '✓ Available' : '✗ Unavailable'),
                React.createElement('span', {
                  key: 'verification-badge',
                  style: {
                    padding: '6px 12px',
                    borderRadius: '20px',
                    fontSize: '0.85rem',
                    fontWeight: '600',
                    backgroundColor: doctor.is_verified === 'authorized' ? '#e8f5e8' : '#fff3cd',
                    color: doctor.is_verified === 'authorized' ? '#2ed573' : '#ffa502',
                    border: `1px solid ${doctor.is_verified === 'authorized' ? '#2ed573' : '#ffa502'}`
                  }
                }, doctor.is_verified === 'authorized' ? '✓ Verified' : '⚠ Pending')
              ])
            ]),
            
            // Detailed Information Grid
            React.createElement('div', { 
              key: 'info-grid', 
              style: { 
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))',
                gap: '20px'
              } 
            }, [
              // Contact Information Card
              React.createElement('div', {
                key: 'contact-card',
                style: {
                  backgroundColor: '#f8f9fa',
                  padding: '20px',
                  borderRadius: '12px',
                  border: '1px solid #e9ecef'
                }
              }, [
                React.createElement('h4', {
                  key: 'contact-title',
                  style: {
                    margin: '0 0 15px 0',
                    color: '#495057',
                    fontSize: '1.1rem',
                    fontWeight: '600',
                    display: 'flex',
                    alignItems: 'center'
                  }
                }, [
                  React.createElement('span', { key: 'icon', style: { marginRight: '8px' } }, '📧'),
                  'Contact Information'
                ]),
                React.createElement('div', { key: 'contact-details', style: { fontSize: '0.95rem', lineHeight: '1.6' } }, [
                  React.createElement('div', { key: 'email', style: { marginBottom: '8px' } }, [
                    React.createElement('strong', { key: 'label', style: { color: '#6c757d' } }, 'Email: '),
                    React.createElement('span', { key: 'value', style: { color: '#495057' } }, doctor.email)
                  ]),
                  React.createElement('div', { key: 'phone', style: { marginBottom: '8px' } }, [
                    React.createElement('strong', { key: 'label', style: { color: '#6c757d' } }, 'Phone: '),
                    React.createElement('span', { key: 'value', style: { color: '#495057' } }, doctor.phone || 'Not provided')
                  ]),
                  React.createElement('div', { key: 'id', style: { marginBottom: '0' } }, [
                    React.createElement('strong', { key: 'label', style: { color: '#6c757d' } }, 'Doctor ID: '),
                    React.createElement('span', { key: 'value', style: { color: '#495057' } }, doctor.user_id)
                  ])
                ])
              ]),
              
              // Professional Information Card
              React.createElement('div', {
                key: 'professional-card',
                style: {
                  backgroundColor: '#f8f9fa',
                  padding: '20px',
                  borderRadius: '12px',
                  border: '1px solid #e9ecef'
                }
              }, [
                React.createElement('h4', {
                  key: 'professional-title',
                  style: {
                    margin: '0 0 15px 0',
                    color: '#495057',
                    fontSize: '1.1rem',
                    fontWeight: '600',
                    display: 'flex',
                    alignItems: 'center'
                  }
                }, [
                  React.createElement('span', { key: 'icon', style: { marginRight: '8px' } }, '👨‍⚕️'),
                  'Professional Details'
                ]),
                React.createElement('div', { key: 'professional-details', style: { fontSize: '0.95rem', lineHeight: '1.6' } }, [
                  React.createElement('div', { key: 'license', style: { marginBottom: '8px' } }, [
                    React.createElement('strong', { key: 'label', style: { color: '#6c757d' } }, 'License: '),
                    React.createElement('span', { key: 'value', style: { color: '#495057' } }, doctor.license_number || 'Not provided')
                  ]),
                  React.createElement('div', { key: 'hospitals', style: { marginBottom: '0' } }, [
                    React.createElement('strong', { key: 'label', style: { color: '#6c757d' } }, 'Hospitals: '),
                    React.createElement('span', { key: 'value', style: { color: '#495057' } }, doctor.hospitals || 'Not assigned')
                  ])
                ])
              ])
            ])
          ])
        ])
      ]);
    };

    const ScheduleModal = ({ doctor, schedules, onClose }) => {
      const [newSchedule, setNewSchedule] = useState({
        day_of_week: 1,
        start_time: '',
        end_time: ''
      });

      const handleAddSchedule = async () => {
        if (!newSchedule.start_time || !newSchedule.end_time) {
          alert('Please select both start and end times');
          return;
        }
        
        try {
          const response = await axios.post('admin_api.php?action=doctor_schedule_add', {
            doctor_id: doctor.user_id,
            ...newSchedule
          });
          if (response.data.success) {
            loadDoctorSchedules(doctor.user_id);
            setNewSchedule({ day_of_week: 1, start_time: '', end_time: '' });
          }
        } catch (err) {
          console.error('Failed to add schedule:', err);
        }
      };

      const handleDeleteSchedule = async (scheduleId) => {
        if (!confirm('Are you sure you want to delete this schedule?')) return;
        
        try {
          const response = await axios.post('admin_api.php?action=doctor_schedule_delete', {
            schedule_id: scheduleId
          });
          if (response.data.success) {
            loadDoctorSchedules(doctor.user_id);
          }
        } catch (err) {
          console.error('Failed to delete schedule:', err);
        }
      };

      const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

      return React.createElement('div', { 
        key: 'schedule-modal-overlay',
        className: 'modal-overlay',
        style: {
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          backgroundColor: 'rgba(0,0,0,0.8)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          zIndex: 1000
        },
        onClick: onClose
      }, [
        React.createElement('div', {
          key: 'schedule-modal-content',
          className: 'modal-content',
          style: {
            backgroundColor: '#fff',
            borderRadius: '20px',
            maxWidth: '900px',
            width: '95%',
            maxHeight: '90vh',
            overflow: 'auto',
            boxShadow: '0 20px 60px rgba(0,0,0,0.3)',
            position: 'relative'
          },
          onClick: (e) => e.stopPropagation()
        }, [
          // Header
          React.createElement('div', { 
            key: 'schedule-header', 
            style: { 
              background: 'linear-gradient(135deg, #a259ff 0%, #8a4ae6 100%)',
              color: '#fff',
              padding: '20px 30px',
              borderRadius: '20px 20px 0 0',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center'
            } 
          }, [
            React.createElement('div', { key: 'header-info' }, [
              React.createElement('h3', { 
                key: 'title', 
                style: { margin: 0, fontSize: '1.5rem', fontWeight: '600' } 
              }, 'Doctor Schedule Management'),
              React.createElement('p', { 
                key: 'subtitle', 
                style: { margin: '5px 0 0 0', fontSize: '0.9rem', opacity: 0.9 } 
              }, `Managing schedule for Dr. ${doctor.doctor_name}`)
            ]),
            React.createElement('button', { 
              key: 'close', 
              onClick: onClose, 
              style: { 
                background: 'rgba(255,255,255,0.2)', 
                border: 'none', 
                borderRadius: '50%',
                width: '35px',
                height: '35px',
                fontSize: '18px', 
                cursor: 'pointer',
                color: '#fff',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center'
              } 
            }, '×')
          ]),
          
          // Content
          React.createElement('div', { 
            key: 'schedule-body', 
            style: { padding: '30px' } 
          }, [
            // Add New Schedule Section
            React.createElement('div', { 
              key: 'add-schedule-section', 
              style: { 
                backgroundColor: '#f8f9fa',
                padding: '25px',
                borderRadius: '15px',
                marginBottom: '30px',
                border: '1px solid #e9ecef'
              } 
            }, [
              React.createElement('h4', { 
                key: 'add-title', 
                style: { 
                  margin: '0 0 20px 0', 
                  color: '#495057',
                  fontSize: '1.2rem',
                  fontWeight: '600',
                  display: 'flex',
                  alignItems: 'center'
                } 
              }, [
                React.createElement('span', { key: 'icon', style: { marginRight: '10px' } }, '➕'),
                'Add New Schedule Slot'
              ]),
              
              React.createElement('div', { 
                key: 'add-form', 
                style: { 
                  display: 'grid',
                  gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
                  gap: '20px',
                  alignItems: 'end'
                } 
              }, [
                React.createElement('div', { key: 'day-field' }, [
                  React.createElement('label', { 
                    key: 'day-label', 
                    style: { 
                      display: 'block', 
                      marginBottom: '8px', 
                      fontWeight: '600', 
                      color: '#495057',
                      fontSize: '0.9rem'
                    } 
                  }, 'Day of Week'),
                  React.createElement('select', {
                    key: 'day-select',
                    style: {
                      width: '100%',
                      padding: '12px',
                      border: '2px solid #e9ecef',
                      borderRadius: '8px',
                      fontSize: '0.95rem',
                      backgroundColor: '#fff',
                      cursor: 'pointer'
                    },
                    value: newSchedule.day_of_week,
                    onChange: (e) => setNewSchedule({...newSchedule, day_of_week: parseInt(e.target.value)})
                  }, days.map((day, index) => 
                    React.createElement('option', { key: index + 1, value: index + 1 }, day)
                  ))
                ]),
                
                React.createElement('div', { key: 'start-field' }, [
                  React.createElement('label', { 
                    key: 'start-label', 
                    style: { 
                      display: 'block', 
                      marginBottom: '8px', 
                      fontWeight: '600', 
                      color: '#495057',
                      fontSize: '0.9rem'
                    } 
                  }, 'Start Time'),
                  React.createElement('input', {
                    key: 'start-input',
                    type: 'time',
                    style: {
                      width: '100%',
                      padding: '12px',
                      border: '2px solid #e9ecef',
                      borderRadius: '8px',
                      fontSize: '0.95rem',
                      backgroundColor: '#fff'
                    },
                    value: newSchedule.start_time,
                    onChange: (e) => setNewSchedule({...newSchedule, start_time: e.target.value})
                  })
                ]),
                
                React.createElement('div', { key: 'end-field' }, [
                  React.createElement('label', { 
                    key: 'end-label', 
                    style: { 
                      display: 'block', 
                      marginBottom: '8px', 
                      fontWeight: '600', 
                      color: '#495057',
                      fontSize: '0.9rem'
                    } 
                  }, 'End Time'),
                  React.createElement('input', {
                    key: 'end-input',
                    type: 'time',
                    style: {
                      width: '100%',
                      padding: '12px',
                      border: '2px solid #e9ecef',
                      borderRadius: '8px',
                      fontSize: '0.95rem',
                      backgroundColor: '#fff'
                    },
                    value: newSchedule.end_time,
                    onChange: (e) => setNewSchedule({...newSchedule, end_time: e.target.value})
                  })
                ]),
                
                React.createElement('div', { key: 'add-button-field' }, [
                  React.createElement('button', {
                    key: 'add-btn',
                    style: {
                      width: '100%',
                      padding: '12px 20px',
                      backgroundColor: '#a259ff',
                      color: '#fff',
                      border: 'none',
                      borderRadius: '8px',
                      fontSize: '0.95rem',
                      fontWeight: '600',
                      cursor: 'pointer',
                      transition: 'all 0.3s ease'
                    },
                    onMouseOver: (e) => {
                      e.target.style.backgroundColor = '#8a4ae6';
                      e.target.style.transform = 'translateY(-1px)';
                    },
                    onMouseOut: (e) => {
                      e.target.style.backgroundColor = '#a259ff';
                      e.target.style.transform = 'translateY(0)';
                    },
                    onClick: handleAddSchedule
                  }, 'Add Schedule')
                ])
              ])
            ]),
            
            // Current Schedules Section
            React.createElement('div', { key: 'schedules-section' }, [
              React.createElement('h4', { 
                key: 'schedules-title', 
                style: { 
                  margin: '0 0 20px 0', 
                  color: '#495057',
                  fontSize: '1.2rem',
                  fontWeight: '600',
                  display: 'flex',
                  alignItems: 'center'
                } 
              }, [
                React.createElement('span', { key: 'icon', style: { marginRight: '10px' } }, '📅'),
                'Current Schedule'
              ]),
              
              schedules.length > 0 ? 
                React.createElement('div', { 
                  key: 'schedules-grid', 
                  style: { 
                    display: 'grid',
                    gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))',
                    gap: '15px'
                  } 
                }, 
                  schedules.map(schedule => 
                    React.createElement('div', { 
                      key: schedule.id, 
                      style: {
                        backgroundColor: '#fff',
                        border: '2px solid #e9ecef',
                        borderRadius: '12px',
                        padding: '20px',
                        position: 'relative',
                        transition: 'all 0.3s ease',
                        boxShadow: '0 2px 8px rgba(0,0,0,0.1)'
                      },
                      onMouseOver: (e) => {
                        e.target.style.borderColor = '#a259ff';
                        e.target.style.boxShadow = '0 4px 15px rgba(162, 89, 255, 0.2)';
                      },
                      onMouseOut: (e) => {
                        e.target.style.borderColor = '#e9ecef';
                        e.target.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                      }
                    }, [
                      React.createElement('div', { 
                        key: 'schedule-header', 
                        style: { 
                          display: 'flex', 
                          justifyContent: 'space-between', 
                          alignItems: 'center',
                          marginBottom: '15px'
                        } 
                      }, [
                        React.createElement('h5', { 
                          key: 'day-name', 
                          style: { 
                            margin: 0, 
                            color: '#a259ff',
                            fontSize: '1.1rem',
                            fontWeight: '600'
                          } 
                        }, days[schedule.day_of_week - 1]),
                        React.createElement('button', {
                          key: 'delete-btn',
                          style: {
                            background: '#ff4757',
                            color: '#fff',
                            border: 'none',
                            borderRadius: '6px',
                            padding: '6px 10px',
                            fontSize: '0.8rem',
                            cursor: 'pointer',
                            fontWeight: '600'
                          },
                          onClick: () => handleDeleteSchedule(schedule.id)
                        }, 'Delete')
                      ]),
                      
                      React.createElement('div', { 
                        key: 'time-info', 
                        style: { 
                          display: 'flex', 
                          justifyContent: 'space-between',
                          alignItems: 'center',
                          backgroundColor: '#f8f9fa',
                          padding: '12px',
                          borderRadius: '8px'
                        } 
                      }, [
                        React.createElement('div', { key: 'start-time', style: { textAlign: 'center' } }, [
                          React.createElement('div', { 
                            key: 'start-label', 
                            style: { 
                              fontSize: '0.8rem', 
                              color: '#6c757d', 
                              fontWeight: '600',
                              marginBottom: '4px'
                            } 
                          }, 'START'),
                          React.createElement('div', { 
                            key: 'start-value', 
                            style: { 
                              fontSize: '1.1rem', 
                              fontWeight: '600', 
                              color: '#495057' 
                            } 
                          }, schedule.start_time)
                        ]),
                        React.createElement('div', { 
                          key: 'separator', 
                          style: { 
                            fontSize: '1.2rem', 
                            color: '#a259ff',
                            fontWeight: 'bold'
                          } 
                        }, '→'),
                        React.createElement('div', { key: 'end-time', style: { textAlign: 'center' } }, [
                          React.createElement('div', { 
                            key: 'end-label', 
                            style: { 
                              fontSize: '0.8rem', 
                              color: '#6c757d', 
                              fontWeight: '600',
                              marginBottom: '4px'
                            } 
                          }, 'END'),
                          React.createElement('div', { 
                            key: 'end-value', 
                            style: { 
                              fontSize: '1.1rem', 
                              fontWeight: '600', 
                              color: '#495057' 
                            } 
                          }, schedule.end_time)
                        ])
                      ])
                    ])
                  )
                ) :
                React.createElement('div', { 
                  key: 'no-schedules', 
                  style: { 
                    textAlign: 'center', 
                    padding: '40px 20px',
                    backgroundColor: '#f8f9fa',
                    borderRadius: '12px',
                    border: '2px dashed #dee2e6'
                  } 
                }, [
                  React.createElement('div', { 
                    key: 'no-schedules-icon', 
                    style: { fontSize: '3rem', marginBottom: '15px' } 
                  }, '📅'),
                  React.createElement('h5', { 
                    key: 'no-schedules-title', 
                    style: { 
                      margin: '0 0 10px 0', 
                      color: '#6c757d',
                      fontSize: '1.1rem'
                    } 
                  }, 'No Schedule Found'),
                  React.createElement('p', { 
                    key: 'no-schedules-desc', 
                    style: { 
                      margin: 0, 
                      color: '#6c757d',
                      fontSize: '0.9rem'
                    } 
                  }, 'Add schedule slots to manage doctor availability')
                ])
            ])
          ])
        ])
      ]);
    };

    return React.createElement('div', null, [
      React.createElement('div', { key: 'header', className: 'card' }, [
        React.createElement('h3', { key: 'title' }, 'Doctor Management'),
        React.createElement('p', { key: 'desc' }, 'Manage doctor profiles, assignments, and schedules')
      ]),
      
      // Search and Filter Section
      React.createElement('div', { key: 'filters', className: 'card', style: { marginBottom: '20px' } }, [
        React.createElement('h4', { key: 'filter-title', style: { marginBottom: '15px' } }, 'Search & Filter Doctors'),
        
        // Search Row
        React.createElement('div', { key: 'search-row', className: 'row', style: { marginBottom: '15px' } }, [
          React.createElement('div', { key: 'search-col', className: 'col-md-8' }, [
            React.createElement('label', { key: 'search-label', style: { marginBottom: '5px', display: 'block', fontWeight: 'bold' } }, 'Search Doctors:'),
            React.createElement('input', {
              key: 'search-input',
              type: 'text',
              className: 'form-control',
              placeholder: 'Search by name, email, specialization...',
              value: searchTerm,
              onChange: (e) => setSearchTerm(e.target.value)
            })
          ]),
          React.createElement('div', { key: 'clear-col', className: 'col-md-4', style: { display: 'flex', alignItems: 'end' } }, [
            React.createElement('button', {
              key: 'clear-btn',
              className: 'btn btn-secondary',
              onClick: clearFilters,
              style: { marginLeft: '10px' }
            }, 'Clear Filters')
          ])
        ]),
        
        // Filter Row
        React.createElement('div', { key: 'filter-row', className: 'row' }, [
          React.createElement('div', { key: 'specialization-col', className: 'col-md-4' }, [
            React.createElement('label', { key: 'specialization-label', style: { marginBottom: '5px', display: 'block', fontWeight: 'bold' } }, 'Filter by Specialization:'),
            React.createElement('select', {
              key: 'specialization-select',
              className: 'form-control',
              value: specializationFilter,
              onChange: (e) => setSpecializationFilter(e.target.value)
            }, [
              React.createElement('option', { key: 'all-specializations', value: '' }, 'All Specializations'),
              ...specializations.map(spec => 
                React.createElement('option', { key: spec, value: spec }, spec)
              )
            ])
          ]),
          
          React.createElement('div', { key: 'hospital-col', className: 'col-md-4' }, [
            React.createElement('label', { key: 'hospital-label', style: { marginBottom: '5px', display: 'block', fontWeight: 'bold' } }, 'Filter by Hospital:'),
            React.createElement('select', {
              key: 'hospital-select',
              className: 'form-control',
              value: hospitalFilter,
              onChange: (e) => setHospitalFilter(e.target.value)
            }, [
              React.createElement('option', { key: 'all-hospitals', value: '' }, 'All Hospitals'),
              ...hospitals.map(hospital => 
                React.createElement('option', { key: hospital.id, value: hospital.id }, hospital.name)
              )
            ])
          ]),
          
          React.createElement('div', { key: 'blocked-col', className: 'col-md-4' }, [
            React.createElement('label', { key: 'blocked-label', style: { marginBottom: '5px', display: 'block', fontWeight: 'bold' } }, 'Filter by Block Status:'),
            React.createElement('select', {
              key: 'blocked-select',
              className: 'form-control',
              value: blockedFilter,
              onChange: (e) => setBlockedFilter(e.target.value)
            }, [
              React.createElement('option', { key: 'all-blocked', value: '' }, 'All Doctors'),
              React.createElement('option', { key: 'unblocked', value: '0' }, 'Unblocked'),
              React.createElement('option', { key: 'blocked', value: '1' }, 'Blocked')
            ])
          ])
        ])
      ]),
      
      error ? React.createElement('div', { key: 'error', className: 'error' }, error) : null,
      success ? React.createElement('div', { key: 'success', className: 'success' }, success) : null,

      showAddForm ? React.createElement(AddDoctorForm, {
        key: 'add-form',
        onSave: handleAddDoctor,
        onCancel: () => setShowAddForm(false)
      }) : null,
      
      showEditForm ? React.createElement(EditDoctorForm, {
        key: 'edit-form',
        doctor: editingDoctor,
        onSave: handleUpdateDoctor,
        onCancel: () => {
          setShowEditForm(false);
          setEditingDoctor(null);
        }
      }) : null,
      
      showDoctorDetails ? React.createElement(DoctorDetailsModal, {
        key: 'doctor-details-modal',
        doctor: selectedDoctor,
        onClose: () => setShowDoctorDetails(false)
      }) : null,
      
      showScheduleModal ? React.createElement(ScheduleModal, {
        key: 'schedule-modal',
        doctor: selectedDoctor,
        schedules: doctorSchedules,
        onClose: () => setShowScheduleModal(false)
      }) : null,

      React.createElement('div', { key: 'actions', className: 'card', style: { marginBottom: '20px' } }, [
        React.createElement('button', {
          key: 'add-doctor-btn',
          className: 'btn btn-primary',
          onClick: () => setShowAddForm(true)
        }, 'Add New Doctor')
      ]),

      React.createElement('div', { key: 'table', className: 'card' }, [
        React.createElement('table', { key: 'doctors-table' }, [
          React.createElement('thead', { key: 'head' }, 
            React.createElement('tr', { key: 'row' }, [
              React.createElement('th', { key: 'id' }, 'ID'),
              React.createElement('th', { key: 'name' }, 'Name'),
              React.createElement('th', { key: 'email' }, 'Email'),
              React.createElement('th', { key: 'specialization' }, 'Specialization'),
              React.createElement('th', { key: 'hospitals' }, 'Hospitals'),
              React.createElement('th', { key: 'blocked' }, 'Status'),
              React.createElement('th', { key: 'actions' }, 'Actions')
            ])
          ),
          React.createElement('tbody', { key: 'body' },
            loading ? 
              React.createElement('tr', { key: 'loading' }, 
                React.createElement('td', { colSpan: 7, style: { textAlign: 'center' } }, 'Loading...')
              ) :
              doctors.length === 0 ?
                React.createElement('tr', { key: 'no-data' }, 
                  React.createElement('td', { colSpan: 7, style: { textAlign: 'center' } }, 'No doctors found')
                ) :
                doctors.map(doctor => 
                  React.createElement('tr', { key: doctor.user_id }, [
                    React.createElement('td', { key: 'id' }, doctor.user_id),
                    React.createElement('td', { key: 'name' }, doctor.doctor_name),
                    React.createElement('td', { key: 'email' }, doctor.email),
                    React.createElement('td', { key: 'specialization' }, doctor.specialization),
                    React.createElement('td', { key: 'hospitals' }, doctor.hospitals || 'None'),
                    React.createElement('td', { key: 'blocked' }, 
                      React.createElement('span', { 
                        className: doctor.is_blocked == 1 ? 'status-badge status-blocked' : 'status-badge status-active'
                      }, doctor.is_blocked == 1 ? 'Blocked' : 'Active')
                    ),
                  React.createElement('td', { key: 'actions' }, [
                    React.createElement('button', {
                      key: 'view',
                      className: 'btn btn-info btn-sm',
                      style: { marginRight: '5px' },
                      onClick: () => handleViewDoctorDetails(doctor)
                    }, 'View'),
                    React.createElement('button', {
                      key: 'edit',
                      className: 'btn btn-warning btn-sm',
                      style: { marginRight: '5px' },
                      onClick: () => {
                        setEditingDoctor(doctor);
                        setShowEditForm(true);
                      }
                    }, 'Edit'),
                    React.createElement('button', {
                      key: 'schedule',
                      className: 'btn btn-secondary btn-sm',
                      style: { marginRight: '5px' },
                      onClick: () => handleManageSchedule(doctor)
                    }, 'Schedule'),
                    doctor.is_blocked == 0 ? 
                      React.createElement('button', {
                        key: 'block',
                        className: 'btn btn-danger btn-sm',
                        style: { marginRight: '5px' },
                        onClick: () => handleBlockDoctor(doctor.user_id, 'block')
                      }, 'Block') :
                      React.createElement('button', {
                        key: 'unblock',
                        className: 'btn btn-success btn-sm',
                        style: { marginRight: '5px' },
                        onClick: () => handleBlockDoctor(doctor.user_id, 'unblock')
                      }, 'Unblock'),
                    React.createElement('button', {
                      key: 'delete',
                      className: 'btn btn-danger btn-sm',
                      disabled: deletingDoctorId === doctor.user_id,
                      onClick: () => handleDeleteDoctor(doctor.user_id)
                    }, deletingDoctorId === doctor.user_id ? 'Deleting...' : 'Delete')
                  ])
                ])
              )
          )
        ])
      ])
    ]);
  }

  window.Components = window.Components || {};
  window.Components.DoctorManagement = DoctorManagement;
})();