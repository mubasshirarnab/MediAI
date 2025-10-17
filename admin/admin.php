<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header('Location: ../index.php'); 
    exit; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="css/admin.css" />
  <style>
    body { background:#0b0e23; color:#fff; font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
    .container { max-width: 1200px; margin: 40px auto 40px; padding: 0 20px; }
    .header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
    .title { font-size:1.6rem; font-weight:700; }
    .tabs { display:flex; gap:10px; margin-bottom:16px; }
    .btn { background:#181d36; color:#fff; border:1px solid rgb(163,184,239); border-radius:30px; padding:8px 16px; cursor:pointer; transition: all 0.3s ease; }
    .btn:hover { background:#2a2d4a; transform: translateY(-1px); }
    .btn.primary { background:#a259ff; font-weight:600; }
    .btn.primary:hover { background:#8a4ae6; transform: translateY(-1px); }
    .card { background:#13153a; border:1px solid rgb(163,184,239); border-radius:18px; padding:16px; margin-bottom:12px; }
    .row { display:flex; gap:12px; flex-wrap:wrap; }
    input, select, textarea { background:#0f1230; color:#fff; border:1px solid #2a2a4a; border-radius:8px; padding:8px 10px; width:100%; }
    label { color:#b3b3b3; font-size:.95rem; }
    .error { color:#ff7777; margin-top:8px; }
    .success { color:#6aff8a; margin-top:8px; }
    table { width:100%; border-collapse: collapse; }
    th, td { padding:10px; border-bottom:1px solid #23244a; text-align:left; }
    th { color:#b3b3b3; background:#0f1230; }
    .form-group { margin-bottom:12px; }
    .btn-danger { background:#ff4757; transition: all 0.3s ease; }
    .btn-danger:hover { background:#ff3742; transform: translateY(-1px); }
    .btn-success { background:#2ed573; transition: all 0.3s ease; }
    .btn-success:hover { background:#26c965; transform: translateY(-1px); }
    .btn-warning { background:#ffa502; transition: all 0.3s ease; }
    .btn-warning:hover { background:#ff9500; transform: translateY(-1px); }
    .logout-btn { position: fixed; top: 20px; right: 20px; background: #181d36; color: #fff; border: 1px solid rgb(163,184,239); border-radius: 30px; padding: 8px 16px; cursor: pointer; font-weight: 600; z-index: 1000; transition: all 0.3s ease; }
    .logout-btn:hover { background: #a259ff; transform: translateY(-1px); }
    .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
    .status-active { background: #2ed573; color: #fff; }
    .status-blocked { background: #ff4757; color: #fff; }
    .status-verified { background: #2ed573; color: #fff; }
    .status-unverified { background: #ffa502; color: #fff; }
    .badge-primary { background: #a259ff; color: #fff; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; }
    .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 1000; }
    .modal-content { background: #fff; padding: 20px; border-radius: 12px; max-width: 700px; width: 90%; max-height: 80vh; overflow: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
  </style>
</head>
<body>
  <button class="logout-btn" onclick="logout()">Logout</button>
  <div class="container">
    <div id="root"></div>
  </div>

  <script>
    function logout() {
      if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../logout.php';
      }
    }
  </script>

  <script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script>
  <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios@1.6.7/dist/axios.min.js"></script>
  <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
  <script type="text/babel" src="react/UserManagement.jsx"></script>
  <script type="text/babel" src="react/HospitalManagement.jsx"></script>
  <script type="text/babel" src="react/DoctorManagement.jsx"></script>
  <script type="text/babel" src="react/AppointmentManagement.jsx"></script>
  <script type="text/babel" src="react/CabinManagement.jsx"></script>
  <script type="text/babel" src="react/CommunityManagement.jsx"></script>
  <script type="text/babel" src="react/SystemSettings.jsx"></script>
  <script type="text/babel" src="react/AppAdmin.jsx"></script>
  <script type="text/babel">
    const App = window.Apps.AppAdmin;
    ReactDOM.createRoot(document.getElementById('root')).render(React.createElement(App));
  </script>
</body>
</html>
