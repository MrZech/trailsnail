<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
    exit();
}
// Timeout in seconds (e.g., 15 minutes = 900 seconds)



// Require admin login
if (!isset($_SESSION['admin_user'])) {
    header("Location: admin_login.php");
    exit();
}
include 'config/config.php';

// Establish database connection
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel - Dispo.Tech</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="/timeclock/assets/favi.png" />
  <style>
    body {
    background: linear-gradient(135deg, rgb(10, 38, 64), rgb(38, 86, 150));
      background-attachment: fixed;
      background-repeat: no-repeat;
      background-size: cover;
      color: #e0e0e0;
    }
    .card {
      background-color: rgba(0, 0, 0, 0.6);
      color: #fff;
      border: none;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }
    a.list-group-item {
      background-color: rgba(255,255,255,0.05);
      color: #e0e0e0;
      border: none;
    }
    a.list-group-item:hover {
      background-color: rgba(136, 192, 64,0.7);
    }
    #liveClock {
      color: #ccc;
    }



  </style>
</head>
<body>
<div class="position-absolute top-0 end-0 m-3">
  <a href="timeclock.php" class="btn btn-outline-light btn-sm">Return to Timeclock</a>
</div>
  <div class="container py-5">
    <div class="row mb-4">
      <div class="col-12 text-center">
        <a href="https://www.dispo.tech" target="_blank">
        <img src="/timeclock/assets/logo.svg" alt="Dispo.Tech" class="img-fluid" style="max-height: 100px;">
        </a>
      </div>
    </div>
    <div class="row mb-4">
      <div class="col-12 text-center">
        <h5 id="liveClock" class="fw-light"></h5>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card p-4">
          <h2 class="mb-4 text-center">Admin Panel</h2>
          <div class="list-group mb-4">
            <a href="?page=dashboard" class="list-group-item list-group-item-action">📊 Dashboard</a>
            <a href="?page=fix_punches" class="list-group-item list-group-item-action">🔧 Fix Missing Daily Punches</a>
            <a href="?page=edit_punches" class="list-group-item list-group-item-action">✏️ Edit Punches</a>
            <a href="?page=manage_users" class="list-group-item list-group-item-action">👥 Manage Employees</a>
            <a href="?page=add_employee" class="list-group-item list-group-item-action">➕ Add Employee</a>
            <a href="?page=report" class="list-group-item list-group-item-action">📊 Generate Reports</a>
            <a href="?page=volunteer" class="list-group-item list-group-item-action">Generate Volunteer Reports</a>
            <a href="admin_login.php" class="list-group-item list-group-item-action text-danger">🚪 Logout</a>
          </div>
          <div class="content">
            <?php
            $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
            $allowed_pages = ['dashboard', 'fix_punches', 'edit_punches', 'edit_punch', 'manage_users', 'report', 'add_employee', 'edit_employee'];
            if (in_array($page, $allowed_pages)) {
              include "admin_$page.php";
            } else {
              include "admin_dashboard.php";
            }
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    function updateClock() {
      const now = new Date();
      document.getElementById('liveClock').textContent = now.toLocaleTimeString();
    }
    setInterval(updateClock, 1000);
    updateClock();
  </script>
</body>
</html>
