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
input[type="date"] {
  color-scheme: dark;
}
input[type="date"]::-webkit-calendar-picker-indicator {
  filter: invert(1);
}

/* Chart and Report Styles */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: linear-gradient(135deg, #a259ff 0%, #8b4cff 100%);
  padding: 25px;
  border-radius: 15px;
  text-align: center;
  color: white;
  box-shadow: 0 8px 25px rgba(162, 89, 255, 0.3);
  transition: all 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 35px rgba(162, 89, 255, 0.4);
}

.stat-value {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 8px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.stat-label {
  font-size: 0.9rem;
  opacity: 0.9;
  font-weight: 500;
}

/* Chart containers */
.chart-container {
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  border: 1px solid #e9ecef;
  margin-bottom: 20px;
}

/* Report cards */
.report-card {
  background: #fff;
  border-radius: 12px;
  padding: 25px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  border: 1px solid #e9ecef;
  margin-bottom: 20px;
  transition: all 0.3s ease;
}

.report-card:hover {
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

/* Chart legends */
.chart-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  margin-top: 15px;
  justify-content: center;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.9rem;
}

.legend-color {
  width: 12px;
  height: 12px;
  border-radius: 2px;
}

/* Responsive charts */
@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
  }
  
  .stat-card {
    padding: 20px;
  }
  
  .stat-value {
    font-size: 1.5rem;
  }
  
  .chart-container {
    padding: 15px;
  }
}

/* Chart animations */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.chart-container {
  animation: fadeInUp 0.6s ease-out;
}

/* Hover effects for chart elements */
.chart-bar:hover,
.chart-slice:hover {
  opacity: 0.8;
  transform: scale(1.05);
  transition: all 0.3s ease;
}

/* Report loading states */
.report-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 200px;
  color: #6c757d;
  font-size: 1.1rem;
}

.report-loading::before {
  content: '';
  width: 20px;
  height: 20px;
  border: 2px solid #e9ecef;
  border-top: 2px solid #a259ff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-right: 10px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Enhanced report controls */
.report-controls {
  display: flex;
  gap: 15px;
  align-items: end;
  flex-wrap: wrap;
}

.report-control-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
  min-width: 150px;
}

.report-refresh-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: linear-gradient(135deg, #2ed573 0%, #20c997 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(46, 213, 115, 0.3);
}

.report-refresh-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(46, 213, 115, 0.4);
}

.report-refresh-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.report-refresh-btn .refresh-icon {
  transition: transform 0.3s ease;
}

.report-refresh-btn:disabled .refresh-icon {
  animation: spin 1s linear infinite;
}

/* Report status indicators */
.report-status-success {
  background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
  border: 1px solid #2ed573;
  color: #155724;
  padding: 10px 15px;
  border-radius: 8px;
  margin-top: 15px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.9rem;
  font-weight: 500;
}

.report-status-error {
  background: linear-gradient(135deg, #ffe8e8 0%, #f8d7da 100%);
  border: 1px solid #ff4757;
  color: #721c24;
  padding: 10px 15px;
  border-radius: 8px;
  margin-top: 15px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.9rem;
  font-weight: 500;
}

/* Enhanced empty states */
.report-empty-state {
  text-align: center;
  padding: 60px 20px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 12px;
  border: 2px dashed #dee2e6;
  margin: 20px 0;
}

.report-empty-icon {
  font-size: 4rem;
  margin-bottom: 20px;
  color: #adb5bd;
  opacity: 0.7;
}

.report-empty-title {
  margin-bottom: 15px;
  color: #6c757d;
  font-size: 1.2rem;
  font-weight: 600;
}

.report-empty-text {
  margin: 0;
  color: #adb5bd;
  font-size: 0.95rem;
  line-height: 1.5;
}

/* Report loading enhancements */
.report-loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 200px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 12px;
  border: 1px solid #dee2e6;
}

.report-loading-spinner {
  width: 24px;
  height: 24px;
  border: 3px solid #e9ecef;
  border-top: 3px solid #a259ff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-right: 15px;
}

.report-loading-text {
  color: #6c757d;
  font-size: 1.1rem;
  font-weight: 500;
}

/* Discharge Summary Styles */
.patient-discharge-summary {
  max-width: 1200px;
  margin: 0 auto;
}

.discharge-search-container {
  position: relative;
}

.discharge-search-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: #181d36;
  border: 1px solid #2a2a4a;
  border-radius: 8px;
  max-height: 200px;
  overflow-y: auto;
  z-index: 1000;
  box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

.discharge-search-result-item {
  padding: 12px 15px;
  cursor: pointer;
  border-bottom: 1px solid #2a2a4a;
  transition: background-color 0.2s ease;
}

.discharge-search-result-item:hover {
  background-color: #2a2a4a;
}

.discharge-search-result-item:last-child {
  border-bottom: none;
}

.discharge-patient-name {
  font-weight: 600;
  color: #fff;
  margin-bottom: 4px;
}

.discharge-patient-details {
  font-size: 0.85rem;
  color: #b3b3b3;
}

.discharge-patient-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.discharge-info-card {
  padding: 20px;
  background: linear-gradient(135deg, #181d36 0%, #1a1f3a 100%);
  border-radius: 12px;
  border: 1px solid #2a2a4a;
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.discharge-info-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

.discharge-info-icon-label {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
}

.discharge-info-label {
  color: #a259ff;
  font-size: 0.9rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.discharge-info-value {
  font-size: 1.1rem;
  font-weight: 600;
  color: #fff;
}

.discharge-summary-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  padding-bottom: 15px;
  border-bottom: 2px solid #a259ff;
}

.discharge-summary-title {
  display: flex;
  align-items: center;
  gap: 10px;
}

.discharge-charges-count {
  padding: 4px 8px;
  background-color: #2ed573;
  color: #fff;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
}

.discharge-total-display {
  margin-bottom: 30px;
  padding: 25px;
  background: linear-gradient(135deg, #181d36 0%, #1a1f3a 100%);
  border-radius: 12px;
  border: 2px solid #a259ff;
  text-align: center;
  box-shadow: 0 8px 25px rgba(162, 89, 255, 0.2);
}

.discharge-total-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-bottom: 15px;
}

.discharge-total-label {
  margin: 0;
  color: #a259ff;
  font-size: 1.2rem;
  font-weight: 600;
}

.discharge-total-amount {
  font-size: 2.5rem;
  font-weight: bold;
  color: #fff;
  text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.discharge-bill-card {
  margin-bottom: 30px;
  background: linear-gradient(135deg, #181d36 0%, #1a1f3a 100%);
  border-radius: 12px;
  border: 1px solid #2a2a4a;
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
  overflow: hidden;
}

.discharge-bill-header {
  padding: 20px;
  background: linear-gradient(135deg, #2a2a4a 0%, #1a1f3a 100%);
  border-bottom: 1px solid #2a2a4a;
}

.discharge-bill-title {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.discharge-bill-meta {
  display: flex;
  align-items: center;
  gap: 15px;
  font-size: 0.9rem;
  color: #b3b3b3;
}

.discharge-bill-total {
  font-size: 1.3rem;
  font-weight: bold;
  color: #fff;
  text-align: right;
}

.discharge-bill-items {
  padding: 20px;
  overflow-x: auto;
}

.discharge-items-table {
  min-width: 700px;
  width: 100%;
  border-collapse: collapse;
}

.discharge-items-header {
  background: linear-gradient(135deg, #2a2a4a 0%, #1a1f3a 100%);
  border-bottom: 2px solid #a259ff;
}

.discharge-items-header th {
  padding: 12px 15px;
  text-align: left;
  color: #a259ff;
  font-weight: 600;
  font-size: 0.9rem;
}

.discharge-items-row {
  border-bottom: 1px solid #2a2a4a;
  transition: background-color 0.2s ease;
}

.discharge-items-row:hover {
  background-color: #2a2a4a;
}

.discharge-items-row td {
  padding: 15px;
  color: #fff;
}

.discharge-item-name {
  font-weight: 600;
  color: #fff;
  margin-bottom: 4px;
}

.discharge-item-description {
  font-size: 0.85rem;
  color: #b3b3b3;
  line-height: 1.4;
}

.discharge-item-quantity {
  text-align: center;
  font-weight: 600;
}

.discharge-item-price {
  text-align: right;
  color: #b3b3b3;
}

.discharge-item-total {
  text-align: right;
  font-weight: bold;
  color: #2ed573;
}

.discharge-item-date {
  text-align: center;
  color: #b3b3b3;
  font-size: 0.9rem;
}

.discharge-empty-state {
  text-align: center;
  padding: 60px 20px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 12px;
  border: 2px dashed #dee2e6;
  margin: 20px 0;
}

.discharge-empty-icon {
  font-size: 4rem;
  margin-bottom: 20px;
  color: #adb5bd;
}

.discharge-empty-title {
  margin-bottom: 15px;
  color: #6c757d;
  font-size: 1.2rem;
  font-weight: 600;
}

.discharge-empty-text {
  margin: 0 0 20px 0;
  color: #adb5bd;
  font-size: 0.95rem;
  line-height: 1.5;
}

.discharge-loading-state {
  text-align: center;
  padding: 60px 20px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 12px;
  border: 1px solid #dee2e6;
}

.discharge-loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e9ecef;
  border-top: 4px solid #a259ff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px auto;
}

.discharge-loading-title {
  margin-bottom: 10px;
  color: #6c757d;
  font-size: 1.1rem;
  font-weight: 600;
}

.discharge-loading-text {
  margin: 0;
  color: #adb5bd;
  font-size: 0.9rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .discharge-patient-info-grid {
    grid-template-columns: 1fr;
    gap: 15px;
  }
  
  .discharge-summary-header {
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
  }
  
  .discharge-total-amount {
    font-size: 2rem;
  }
  
  .discharge-bill-meta {
    flex-direction: column;
    gap: 8px;
    align-items: flex-start;
  }
  
  .discharge-items-table {
    min-width: 500px;
  }
}

/* Debug Button Styles */
.btn.btn-info {
  background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
}

.btn.btn-info:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(23, 162, 184, 0.4);
}

.btn.btn-info:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: #fff;
  padding: 30px;
  border-radius: 15px;
  max-width: 600px;
  width: 90%;
  max-height: 80vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-50px) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-content textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-family: inherit;
  font-size: 14px;
  resize: vertical;
  min-height: 80px;
}

.modal-content textarea:focus {
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
