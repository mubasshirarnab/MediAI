<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link rel="stylesheet" href="css/navbar.css" />

<header class="navbar">
        <div class="navbar-logo">
            <a href="index.php" target="_top">
                <img src="images/LOGO.png" alt="MEDIAi Logo" class="logo" /></a>
        </div>
        <nav class="navbar-links">
            <a href="index.php" class="navbar-link" target="_top">Home</a>
            <a href="#" class="navbar-link" target="_top">About</a>
            <a href="#" class="navbar-link" target="_top">Contact</a>
            <a href="hospital_dashboard.php" class="navbar-link" target="_top">Profile</a>
            <a href="hospital_inventory.php" class="navbar-link" target="_top">Inventory</a>
            <a href="admin_cabin_management.php" class="navbar-link" target="_top">Cabins</a>
            <a href="hospital_billing/billing.php" class="navbar-link" target="_top">Billing</a>
            <a href="report_upload.php" class="navbar-link" target="_top">Report Upload</a>
        </nav>

        <div class="navbar-icons">
            <i class="fa-solid fa-bell navbar-bell"></i>
        </div>
    </header>

<script>
(function(){
  try {
    var path = (window.top && window.top.location && window.top.location.pathname) || window.location.pathname;
    var file = (path.split('/').pop() || '').toLowerCase();
    if (!file) file = 'index.php';
    var map = {
      'index.php': 'index.php',
      'hospital_dashboard.php': 'hospital_dashboard.php',
      'hospital_inventory.php': 'hospital_inventory.php',
      'admin_cabin_management.php': 'admin_cabin_management.php',
      'billing.php': 'hospital_billing/billing.php',
      'report_upload.php': 'report_upload.php'
    };

    var target = map[file] || 'index.php';
    var links = document.querySelectorAll('.navbar-links .navbar-link');
    links.forEach(function(a){
      var href = (a.getAttribute('href') || '').toLowerCase();
      if (href === target) { a.classList.add('active'); }
    });
  } catch (e) { /* no-op */ }
})();
</script>