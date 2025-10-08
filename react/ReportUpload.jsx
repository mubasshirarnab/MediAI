(function(){
  const { useEffect, useState } = React;

  function UploadModal({ open, onClose, patient }) {
    const [testName, setTestName] = useState('');
    const [reportDate, setReportDate] = useState('');
    const [file, setFile] = useState(null);
    const [error, setError] = useState('');
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => { if (!open) { setTestName(''); setReportDate(''); setFile(null); setError(''); } }, [open]);

    const onFileChange = (e) => {
      const f = e.target.files && e.target.files[0];
      if (!f) { setFile(null); return; }
      const ext = (f.name.split('.').pop() || '').toLowerCase();
      const okExt = ['pdf','jpg','jpeg','png'];
      if (!okExt.includes(ext)) { setError('Only PDF, JPG, JPEG, PNG are allowed'); setFile(null); return; }
      if (f.size > 10 * 1024 * 1024) { setError('Max file size is 10MB'); setFile(null); return; }
      setError(''); setFile(f);
    };

    const submit = async () => {
      setError('');
      if (!testName || !reportDate || !file) { setError('Please fill all fields and select a valid file'); return; }
      try {
        setSubmitting(true);
        const form = new FormData();
        form.append('patient_id', patient.patient_id);
        form.append('test_name', testName);
        form.append('report_date', reportDate);
        form.append('file', file);
        const res = await axios.post('lab_reports_api.php?action=upload_report', form);
        if (!res.data.success) throw new Error(res.data.error || 'Upload failed');
        onClose(true);
      } catch (e) {
        const status = e.response?.status;
        const serverErr = e.response?.data?.error || e.response?.data || '';
        if (status) setError(`Request failed with status ${status}${serverErr ? `: ${serverErr}` : ''}`);
        else setError(e.message || 'Upload failed');
      } finally {
        setSubmitting(false);
      }
    };

    return React.createElement(React.Fragment, null, [
      React.createElement('div', { key:'bd', className: 'modal-backdrop' + (open ? ' show' : '') }),
      React.createElement('div', { key:'md', className: 'modal' + (open ? ' show' : '') }, [
        React.createElement('div', { key:'h', className:'modal-header' }, `Upload Report for ${patient?.name || ''} (ID: ${patient?.patient_id || ''})`),
        error ? React.createElement('div', { key:'err', className:'error' }, error) : null,
        React.createElement('div', { key:'f' }, [
          React.createElement('div', { key:'tn' }, [
            React.createElement('label', null, 'Test Name'),
            React.createElement('input', { value:testName, onChange:e=>setTestName(e.target.value), placeholder:'e.g. Full Blood Count' })
          ]),
          React.createElement('div', { key:'rd' }, [
            React.createElement('label', null, 'Report Date'),
            React.createElement('input', { type:'date', value:reportDate, onChange:e=>setReportDate(e.target.value) })
          ]),
          React.createElement('div', { key:'fl' }, [
            React.createElement('label', null, 'Report File (PDF/JPG/PNG)'),
            React.createElement('input', { type:'file', accept:'.pdf,.jpg,.jpeg,.png', onChange:onFileChange })
          ])
        ]),
        React.createElement('div', { key:'ac', className:'actions' }, [
          React.createElement('button', { key:'c', className:'btn', onClick:()=>onClose(false), disabled:submitting }, 'Cancel'),
          React.createElement('button', { key:'s', className:'btn primary', onClick:submit, disabled:submitting }, submitting ? 'Uploading…' : 'Upload')
        ])
      ])
    ]);
  }

  function PatientRow({ patient, onUploaded }) {
    const [reports, setReports] = useState([]);
    const [loading, setLoading] = useState(false);
    const [open, setOpen] = useState(false);

    const loadReports = async () => {
      try {
        setLoading(true);
        const res = await axios.get('lab_reports_api.php', { params: { action: 'patient_reports', patient_id: patient.patient_id }});
        if (res.data.success) setReports(res.data.data);
      } finally { setLoading(false); }
    };

    useEffect(() => { loadReports(); }, []);

    const onClose = (refresh)=>{
      setOpen(false);
      if (refresh) { loadReports(); onUploaded && onUploaded(); }
    }

    return React.createElement('div', { className:'card' }, [
      React.createElement('div', { key:'hdr', className:'header' }, [
        React.createElement('div', { key:'t', className:'title' }, `${patient.name} (ID: ${patient.patient_id})`),
        React.createElement('button', { key:'u', className:'btn primary', onClick: ()=> setOpen(true) }, 'Upload Report')
      ]),
      React.createElement('div', { key:'tbl' }, [
        React.createElement('table', { key:'t' }, [
          React.createElement('thead', { key:'h' }, React.createElement('tr', null, [
            React.createElement('th', { key:'tn' }, 'Test Name'),
            React.createElement('th', { key:'rd' }, 'Report Date'),
            React.createElement('th', { key:'ud' }, 'Uploaded At'),
            React.createElement('th', { key:'dl' }, 'Download')
          ])),
          React.createElement('tbody', { key:'b' }, loading ? 
            React.createElement('tr', null, React.createElement('td', { colSpan:4 }, 'Loading…')) :
            (reports.length ? reports.map(r => React.createElement('tr', { key:r.id }, [
              React.createElement('td', { key:'tn' }, r.test_name),
              React.createElement('td', { key:'rd' }, r.report_date),
              React.createElement('td', { key:'ud' }, r.uploaded_at),
              React.createElement('td', { key:'dl' }, React.createElement('a', { className:'link', href: r.report_file, target:'_blank', rel:'noopener noreferrer' }, 'Download'))
            ])) : React.createElement('tr', null, React.createElement('td', { colSpan:4 }, 'No reports yet.')))
          )
        ])
      ]),
      React.createElement(UploadModal, { key:'m', open, onClose, patient })
    ]);
  }

  function ReportUpload() {
    const [patients, setPatients] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [q, setQ] = useState('');
    const [hasReports, setHasReports] = useState(false);
    const [typingTimer, setTypingTimer] = useState(null);

    const load = async (opts={}) => {
      const { q: q1 = q, has_reports: hr = hasReports } = opts;
      try {
        setLoading(true); setError('');
        const params = { action: 'patients_list' };
        if (q1 && q1.trim()) params.q = q1.trim();
        if (hr) params.has_reports = 1;
        const res = await axios.get('lab_reports_api.php', { params });
        if (res.data.success) setPatients(res.data.data); else setError(res.data.error || 'Failed to load');
      } catch (e) { setError('Failed to load'); }
      finally { setLoading(false); }
    };

    useEffect(() => { load(); }, []);

    const onQChange = (e)=>{
      const v = e.target.value; setQ(v);
      if (typingTimer) clearTimeout(typingTimer);
      setTypingTimer(setTimeout(()=> load({ q: v }), 350));
    };

    const onHasReports = (e)=>{
      const v = e.target.checked; setHasReports(v); load({ has_reports: v });
    };

    return React.createElement('div', null, [
      React.createElement('div', { key:'hdr', className:'header' }, [
        React.createElement('div', { key:'t', className:'title' }, 'Report Upload'),
        React.createElement('div', { key:'filters', style:{ display:'flex', gap:'10px', alignItems:'center' } }, [
          React.createElement('input', { key:'q', placeholder:'Search by name, email, or ID…', value:q, onChange:onQChange, style:{ minWidth:'280px' } }),
          React.createElement('label', { key:'hr', style:{ display:'flex', alignItems:'center', gap:'6px' } }, [
            React.createElement('input', { type:'checkbox', checked:hasReports, onChange:onHasReports }),
            'Has reports'
          ]),
          React.createElement('button', { key:'r', className:'btn', onClick:()=>load(), disabled:loading }, loading ? 'Loading…' : 'Refresh')
        ])
      ]),
      React.createElement('div', { key:'meta', style:{ color:'#9aa', margin:'6px 0' } }, `Results: ${patients.length}`),
      error ? React.createElement('div', { key:'err', className:'error' }, error) : null,
      patients.map(p => React.createElement(PatientRow, { key:p.patient_id, patient:p }))
    ]);
  }

  window.Components = window.Components || {};
  window.Components.ReportUpload = ReportUpload;
})();
