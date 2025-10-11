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
          backgroundColor: 'rgba(0,0,0,0.5)',
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
            backgroundColor: 'white',
            padding: '20px',
            borderRadius: '8px',
            maxWidth: '700px',
            width: '90%',
            maxHeight: '80vh',
            overflow: 'auto'
          },
          onClick: (e) => e.stopPropagation()
        }, [
          React.createElement('div', { key: 'modal-header', style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' } }, [
            React.createElement('h3', { key: 'title' }, 'Doctor Details'),
            React.createElement('button', { key: 'close', onClick: onClose, style: { background: 'none', border: 'none', fontSize: '24px', cursor: 'pointer' } }, '×')
          ]),
          
          React.createElement('div', { key: 'doctor-info' }, [
            React.createElement('div', { key: 'row-1', className: 'row', style: { marginBottom: '15px' } }, [
              React.createElement('div', { key: 'col-1', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'Doctor ID: '),
                React.createElement('span', { key: 'value' }, doctor.user_id)
              ]),
              React.createElement('div', { key: 'col-2', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'Name: '),
                React.createElement('span', { key: 'value' }, doctor.doctor_name)
              ])
            ]),
            
            React.createElement('div', { key: 'row-2', className: 'row', style: { marginBottom: '15px' } }, [
              React.createElement('div', { key: 'col-1', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'Email: '),
                React.createElement('span', { key: 'value' }, doctor.email)
              ]),
              React.createElement('div', { key: 'col-2', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'Phone: '),
                React.createElement('span', { key: 'value' }, doctor.phone || 'N/A')
              ])
            ]),
            
            React.createElement('div', { key: 'row-3', className: 'row', style: { marginBottom: '15px' } }, [
              React.createElement('div', { key: 'col-1', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'Specialization: '),
                React.createElement('span', { key: 'value', className: 'badge badge-primary' }, doctor.specialization)
              ]),
              React.createElement('div', { key: 'col-2', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'License Number: '),
                React.createElement('span', { key: 'value' }, doctor.license_number || 'N/A')
              ])
            ]),
            
            React.createElement('div', { key: 'row-4', className: 'row', style: { marginBottom: '15px' } }, [
              React.createElement('div', { key: 'col-1', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'Block Status: '),
                React.createElement('span', { 
                  key: 'value',
                  className: doctor.is_blocked == 1 ? 'status-badge status-blocked' : 'status-badge status-active'
                }, doctor.is_blocked == 1 ? 'Blocked' : 'Active')
              ]),
              React.createElement('div', { key: 'col-2', className: 'col-md-6' }, [
                React.createElement('strong', { key: 'label' }, 'Email Verified: '),
                React.createElement('span', { 
                  key: 'value',
                  className: doctor.is_verified === 'authorized' ? 'status-badge status-verified' : 'status-badge status-unverified'
                }, doctor.is_verified === 'authorized' ? 'Yes' : 'No')
              ])
            ]),
            
            React.createElement('div', { key: 'row-5', className: 'row', style: { marginBottom: '15px' } }, [
              React.createElement('div', { key: 'col-1', className: 'col-md-12' }, [
                React.createElement('strong', { key: 'label' }, 'Assigned Hospitals: '),
                React.createElement('span', { key: 'value' }, doctor.hospitals || 'None')
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
          backgroundColor: 'rgba(0,0,0,0.5)',
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
            backgroundColor: 'white',
            padding: '20px',
            borderRadius: '8px',
            maxWidth: '800px',
            width: '90%',
            maxHeight: '80vh',
            overflow: 'auto'
          },
          onClick: (e) => e.stopPropagation()
        }, [
          React.createElement('div', { key: 'schedule-header', style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' } }, [
            React.createElement('h3', { key: 'title' }, `Schedule for ${doctor.doctor_name}`),
            React.createElement('button', { key: 'close', onClick: onClose, style: { background: 'none', border: 'none', fontSize: '24px', cursor: 'pointer' } }, '×')
          ]),
          
          React.createElement('div', { key: 'add-schedule', style: { marginBottom: '20px', padding: '15px', backgroundColor: '#f8f9fa', borderRadius: '8px' } }, [
            React.createElement('h5', { key: 'add-title' }, 'Add New Schedule'),
            React.createElement('div', { key: 'add-form', className: 'row' }, [
              React.createElement('div', { key: 'day-col', className: 'col-md-4' }, [
                React.createElement('label', { key: 'day-label' }, 'Day'),
                React.createElement('select', {
                  key: 'day-select',
                  className: 'form-control',
                  value: newSchedule.day_of_week,
                  onChange: (e) => setNewSchedule({...newSchedule, day_of_week: parseInt(e.target.value)})
                }, days.map((day, index) => 
                  React.createElement('option', { key: index + 1, value: index + 1 }, day)
                ))
              ]),
              React.createElement('div', { key: 'start-col', className: 'col-md-3' }, [
                React.createElement('label', { key: 'start-label' }, 'Start Time'),
                React.createElement('input', {
                  key: 'start-input',
                  type: 'time',
                  className: 'form-control',
                  value: newSchedule.start_time,
                  onChange: (e) => setNewSchedule({...newSchedule, start_time: e.target.value})
                })
              ]),
              React.createElement('div', { key: 'end-col', className: 'col-md-3' }, [
                React.createElement('label', { key: 'end-label' }, 'End Time'),
                React.createElement('input', {
                  key: 'end-input',
                  type: 'time',
                  className: 'form-control',
                  value: newSchedule.end_time,
                  onChange: (e) => setNewSchedule({...newSchedule, end_time: e.target.value})
                })
              ]),
              React.createElement('div', { key: 'add-btn-col', className: 'col-md-2', style: { display: 'flex', alignItems: 'end' } }, [
                React.createElement('button', {
                  key: 'add-btn',
                  className: 'btn btn-primary',
                  onClick: handleAddSchedule
                }, 'Add')
              ])
            ])
          ]),
          
          React.createElement('div', { key: 'schedules-list' }, [
            React.createElement('h5', { key: 'list-title' }, 'Current Schedules'),
            schedules.length > 0 ? 
              React.createElement('table', { key: 'schedules-table', className: 'table' }, [
                React.createElement('thead', { key: 'thead' }, 
                  React.createElement('tr', { key: 'header-row' }, [
                    React.createElement('th', { key: 'day-header' }, 'Day'),
                    React.createElement('th', { key: 'start-header' }, 'Start Time'),
                    React.createElement('th', { key: 'end-header' }, 'End Time'),
                    React.createElement('th', { key: 'actions-header' }, 'Actions')
                  ])
                ),
                React.createElement('tbody', { key: 'tbody' },
                  schedules.map(schedule => 
                    React.createElement('tr', { key: schedule.id }, [
                      React.createElement('td', { key: 'day' }, days[schedule.day_of_week - 1]),
                      React.createElement('td', { key: 'start' }, schedule.start_time),
                      React.createElement('td', { key: 'end' }, schedule.end_time),
                      React.createElement('td', { key: 'actions' }, [
                        React.createElement('button', {
                          key: 'delete',
                          className: 'btn btn-danger btn-sm',
                          onClick: () => handleDeleteSchedule(schedule.id)
                        }, 'Delete')
                      ])
                    ])
                  )
                )
              ]) :
              React.createElement('p', { key: 'no-schedules' }, 'No schedules found')
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