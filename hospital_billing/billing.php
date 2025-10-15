<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    header('Location: ../login.php'); 
    exit; 
}
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'hospital', 'doctor'])) { 
    header('Location: ../index.php'); 
    exit; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Hospital Billing System</title>
  <link rel="stylesheet" href="css/billing.css" />
  <style>
    body { 
      background: #0b0e23; 
      color: #fff; 
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; 
      margin: 0;
      padding: 0;
    }
    .container { 
      max-width: 1400px; 
      margin: 100px auto 40px; 
      padding: 0 20px; 
    }
    .header { 
      display: flex; 
      align-items: center; 
      justify-content: space-between; 
      margin-bottom: 20px; 
    }
    .title { 
      font-size: 2rem; 
      font-weight: 700; 
      color: #a259ff;
    }
    .tabs { 
      display: flex; 
      gap: 10px; 
      margin-bottom: 20px; 
      flex-wrap: wrap;
    }
    .btn { 
      background: #181d36; 
      color: #fff; 
      border: 1px solid rgb(163,184,239); 
      border-radius: 30px; 
      padding: 10px 20px; 
      cursor: pointer; 
      transition: all 0.3s ease;
      font-weight: 500;
    }
    .btn:hover {
      background: #2a2a4a;
      transform: translateY(-2px);
    }
    .btn.primary { 
      background: #a259ff; 
      font-weight: 600; 
      border-color: #a259ff;
    }
    .card { 
      background: #13153a; 
      border: 1px solid rgb(163,184,239); 
      border-radius: 18px; 
      padding: 20px; 
      margin-bottom: 20px; 
    }
    .row { 
      display: flex; 
      gap: 15px; 
      flex-wrap: wrap; 
    }
    .col-md-6 { 
      flex: 1; 
      min-width: 300px; 
    }
    .col-md-4 { 
      flex: 0 0 33.333%; 
      min-width: 250px; 
    }
    .col-md-3 { 
      flex: 0 0 25%; 
      min-width: 200px; 
    }
    input, select, textarea { 
      background: #0f1230; 
      color: #fff; 
      border: 1px solid #2a2a4a; 
      border-radius: 8px; 
      padding: 10px 12px; 
      width: 100%; 
      font-size: 14px;
    }
    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: #a259ff;
      box-shadow: 0 0 0 2px rgba(162, 89, 255, 0.2);
    }
    label { 
      color: #b3b3b3; 
      font-size: 14px; 
      font-weight: 500;
      margin-bottom: 5px;
      display: block;
    }
    .error { 
      color: #ff7777; 
      margin-top: 8px; 
      font-size: 14px;
    }
    .success { 
      color: #6aff8a; 
      margin-top: 8px; 
      font-size: 14px;
    }
    table { 
      width: 100%; 
      border-collapse: collapse; 
      margin-top: 15px;
    }
    th, td { 
      padding: 12px; 
      border-bottom: 1px solid #23244a; 
      text-align: left; 
    }
    th { 
      color: #b3b3b3; 
      background: #0f1230; 
      font-weight: 600;
      font-size: 14px;
    }
    td {
      font-size: 14px;
    }
    .form-group { 
      margin-bottom: 15px; 
    }
    .btn-danger { 
      background: #ff4757; 
      border-color: #ff4757;
    }
    .btn-success { 
      background: #2ed573; 
      border-color: #2ed573;
    }
    .btn-warning { 
      background: #ffa502; 
      border-color: #ffa502;
    }
    .btn-info { 
      background: #3742fa; 
      border-color: #3742fa;
    }
    .status-badge {
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 500;
    }
    .status-pending {
      background: #ffa502;
      color: #fff;
    }
    .status-paid {
      background: #2ed573;
      color: #fff;
    }
    .status-partial {
      background: #ffa502;
      color: #fff;
    }
    .status-cancelled {
      background: #ff4757;
      color: #fff;
    }
    .amount {
      font-weight: 600;
      color: #a259ff;
    }
    .loading {
      text-align: center;
      padding: 20px;
      color: #b3b3b3;
    }
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.8);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }
    .modal-content {
      background: #13153a;
      border: 1px solid rgb(163,184,239);
      border-radius: 18px;
      padding: 30px;
      max-width: 800px;
      width: 90%;
      max-height: 80vh;
      overflow-y: auto;
    }
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid #23244a;
    }
    .modal-title {
      font-size: 1.5rem;
      font-weight: 600;
      color: #a259ff;
    }
    .close-btn {
      background: none;
      border: none;
      color: #b3b3b3;
      font-size: 24px;
      cursor: pointer;
      padding: 0;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .close-btn:hover {
      color: #fff;
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-bottom: 20px;
    }
    .stat-card {
      background: #181d36;
      border: 1px solid #2a2a4a;
      border-radius: 12px;
      padding: 20px;
      text-align: center;
    }
    .stat-value {
      font-size: 2rem;
      font-weight: 700;
      color: #a259ff;
      margin-bottom: 5px;
    }
    .stat-label {
      color: #b3b3b3;
      font-size: 14px;
    }
    @media (max-width: 768px) {
      .container {
        margin-top: 80px;
        padding: 0 15px;
      }
      .tabs {
        flex-direction: column;
      }
      .btn {
        width: 100%;
        margin-bottom: 10px;
      }
      .row {
        flex-direction: column;
      }
      .col-md-6, .col-md-4, .col-md-3 {
        min-width: 100%;
      }
    }
  </style>
</head>
<body>
  <iframe src="../navbar.php" frameborder="0" style="width:100%;height:80px"></iframe>
  <div class="container">
    <div id="root"></div>
  </div>

  <script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script>
  <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios@1.6.7/dist/axios.min.js"></script>
  <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
  <script type="text/babel" src="react/BillingDashboard.jsx"></script>
  <script type="text/babel" src="react/PatientLedger.jsx"></script>
  <script type="text/babel" src="react/BillManagement.jsx"></script>
  <script type="text/babel" src="react/PaymentManagement.jsx"></script>
  <script type="text/babel" src="react/Reports.jsx"></script>
  <script type="text/babel" src="react/PatientDischargeSummary.jsx"></script>
  <script type="text/babel" src="react/AppBilling.jsx"></script>
  <script type="text/babel">
    const App = window.Apps.AppBilling;
    ReactDOM.createRoot(document.getElementById('root')).render(React.createElement(App));
  </script>
</body>
</html>
