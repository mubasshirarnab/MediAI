<?php /* Single-file React admin UI served by PHP for consistency */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MediAI Admin</title>
    <link rel="icon" href="/favicon.ico" />
    <link rel="stylesheet" href="css/feed.css" />
    <style>
      body { background:#0b0e23; color:#fff; font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
      .admin-container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
      .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr)); gap: 16px; margin-bottom: 24px; }
      .card { background:#13153a; border:1px solid rgb(163,184,239); border-radius:18px; padding:20px; box-shadow:0 2px 12px #0002; }
      .card h3 { margin:0 0 8px 0; font-size:1.05rem; color:#b3b3b3; }
      .card .num { font-size:2rem; font-weight:700; color:#fff; }
      .toolbar { display:flex; gap:12px; margin: 8px 0 16px; }
      .btn { background:#181d36; color:#fff; border:1px solid rgb(163,184,239); border-radius:30px; padding:8px 16px; cursor:pointer; }
      .btn.primary { background:#a259ff; border-color: rgb(163,184,239); font-weight:600; }
      .btn:disabled { opacity:.6; cursor:not-allowed; }
      .list { background:#13153a; border:1px solid rgb(163,184,239); border-radius:18px; box-shadow:0 2px 12px #0002; overflow:hidden; }
      table { width:100%; border-collapse:collapse; }
      th, td { padding:12px 14px; border-bottom:1px solid #23244a; color:#fff; text-align:left; }
      th { color:#b3b3b3; font-weight:600; background:#0f1230; }
      tr:hover td { background:#181d36; }
      .header { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
      .title { font-size:1.4rem; font-weight:700; }
      .muted { color:#b3b3b3; }
      .pill { display:inline-block; padding:2px 10px; border-radius:999px; border:1px solid rgb(163,184,239); background:#181d36; font-size:.85rem; }
      .footer-note { color:#b3b3b3; font-size:.95rem; margin-top:10px; }
    </style>
</head>
<body>
  <div id="root"></div>

  <script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script>
  <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
  <script>
    const { useEffect, useState, useMemo } = React;

    const API = {
      counts: () => fetch('admin_api.php?action=counts').then(r=>r.json()),
      users: (limit=100, offset=0) => fetch(`admin_api.php?action=list_users&limit=${limit}&offset=${offset}`).then(r=>r.json()),
      doctors: (limit=100, offset=0) => fetch(`admin_api.php?action=list_doctors&limit=${limit}&offset=${offset}`).then(r=>r.json())
    };

    function NumberCard({ label, number }) {
      return React.createElement('div', { className: 'card' }, [
        React.createElement('h3', { key:'h' }, label),
        React.createElement('div', { key:'n', className:'num' }, number)
      ]);
    }

    function Table({ rows, columns }) {
      return React.createElement('div', { className:'list' }, [
        React.createElement('table', { key:'t' }, [
          React.createElement('thead', { key:'h' }, React.createElement('tr', null, columns.map(c => React.createElement('th', { key:c.key }, c.title)))),
          React.createElement('tbody', { key:'b' }, rows.map((row, idx) => React.createElement('tr', { key: idx }, columns.map(col => React.createElement('td', { key: col.key }, col.render ? col.render(row[col.key], row) : (row[col.key] ?? '—'))))))
        ])
      ]);
    }

    function AdminApp() {
      const [counts, setCounts] = useState({ totalUsers: 0, totalDoctors: 0 });
      const [loadingCounts, setLoadingCounts] = useState(false);

      const [activeList, setActiveList] = useState('users');
      const [listLoading, setListLoading] = useState(false);
      const [users, setUsers] = useState([]);
      const [doctors, setDoctors] = useState([]);

      useEffect(() => {
        setLoadingCounts(true);
        API.counts().then((res) => {
          if (res && res.success) setCounts(res.data);
        }).finally(() => setLoadingCounts(false));
      }, []);

      const loadUsers = () => {
        setListLoading(true);
        API.users(200, 0).then(res => { if (res.success) setUsers(res.data); }).finally(()=> setListLoading(false));
      };
      const loadDoctors = () => {
        setListLoading(true);
        API.doctors(200, 0).then(res => { if (res.success) setDoctors(res.data); }).finally(()=> setListLoading(false));
      };

      useEffect(() => {
        if (activeList === 'users') loadUsers(); else loadDoctors();
      }, [activeList]);

      const handleLogout = () => {
        fetch('logout.php', {
          method: 'POST',
          credentials: 'include'
        }).then(() => {
          window.location.href = 'login.php';
        }).catch(() => {
          window.location.href = 'login.php';
        });
      };

      const userCols = useMemo(() => ([
        { key:'id', title:'ID' },
        { key:'name', title:'Name' },
        { key:'email', title:'Email' },
        { key:'phone', title:'Phone' },
        { key:'role_name', title:'Role' },
        { key:'status', title:'Status', render:(v)=> v ? React.createElement('span', { className:'pill'}, v) : '—' },
        { key:'created_at', title:'Joined' }
      ]), []);

      const doctorCols = useMemo(() => ([
        { key:'id', title:'ID' },
        { key:'name', title:'Name' },
        { key:'email', title:'Email' },
        { key:'phone', title:'Phone' },
        { key:'specialization', title:'Specialization' },
        { key:'available', title:'Available', render:(v)=> v === 1 ? 'Yes' : (v === 0 ? 'No' : '—') },
        { key:'created_at', title:'Joined' }
      ]), []);

      return React.createElement('div', { className:'admin-container' }, [
        React.createElement('div', { key:'hdr', className:'header' }, [
          React.createElement('div', { key:'t', className:'title' }, 'Admin Dashboard'),
          React.createElement('div', { key:'actions', style: { display: 'flex', gap: '12px', alignItems: 'center' } }, [
            React.createElement('div', { key:'note', className:'muted' }, loadingCounts ? 'Loading…' : ''),
            React.createElement('button', { key:'logout', className:'btn', onClick: handleLogout, style: { background: '#dc3545', borderColor: '#dc3545' } }, '🚪 Logout')
          ])
        ]),

        React.createElement('div', { key:'cards', className:'cards' }, [
          React.createElement(NumberCard, { key:'u', label:'Total Users', number: counts.totalUsers }),
          React.createElement(NumberCard, { key:'d', label:'Total Doctors', number: counts.totalDoctors })
        ]),

        React.createElement('div', { key:'tb', className:'toolbar' }, [
          React.createElement('button', { key:'bu', className:'btn' + (activeList==='users' ? ' primary' : ''), onClick: ()=> setActiveList('users'), disabled: listLoading }, 'Users'),
          React.createElement('button', { key:'bd', className:'btn' + (activeList==='doctors' ? ' primary' : ''), onClick: ()=> setActiveList('doctors'), disabled: listLoading }, 'Doctors')
        ]),

        activeList === 'users' ? React.createElement(Table, { key:'lu', rows: users, columns: userCols }) : React.createElement(Table, { key:'ld', rows: doctors, columns: doctorCols }),

        React.createElement('div', { key:'ft', className:'footer-note' }, 'Colors and components aligned with feed styles for consistency.')
      ]);
    }

    ReactDOM.createRoot(document.getElementById('root')).render(React.createElement(AdminApp));
  </script>
</body>
</html>


