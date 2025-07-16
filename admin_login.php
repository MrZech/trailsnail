<?php
session_start();
include 'config/config.php'; // Adjust the path if needed

$errorMessage = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT empfullname, employee_passwd, admin FROM employees WHERE empfullname = '$username' AND admin = 1";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Check password
        if (crypt($password, 'xy') === $row['employee_passwd']) {
            $_SESSION['admin_user'] = $row['empfullname']; // Store session
            header("Location: admin_panel.php");
            exit();
        } else {
            $errorMessage = "Invalid password.";
        }
    } else {
        $errorMessage = "User not found or not an admin.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login - Dispo.Tech</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap CSS & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="/timeclock/assets/favi.png">

  <!-- Gradient Dark Mode Styles -->
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
    .form-control {
      background-color: #2c2c2c;
      color: #e0e0e0;
      border: 1px solid #444;
    }
    .form-control:focus {
      background-color: #2c2c2c;
      color: #fff;
      border-color: #0d6efd;
    }
    .btn-primary {
      background-color: #0d6efd;
      border-color: #0d6efd;
    }
    .btn-primary:hover {
      background-color: #0b5ed7;
      border-color: #0a58ca;
    }
    #liveClock {
      color: #ccc;
    }
    .form-check-label {
      color: #ccc;
    }
  </style>
</head>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<body>
<div class="position-absolute top-0 end-0 m-3">
  <a href="timeclock.php" class="btn btn-outline-light btn-sm">Return to Timeclock</a>
</div>
  <div class="container py-5">
    
    <!-- Logo -->
    <div class="row mb-4">
      <div class="col-12 text-center">
        <a href="https://www.dispo.tech" target="_blank" rel="noopener noreferrer">
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
      <div class="col-md-6 col-lg-5">
        <div class="card p-4">
          <div class="text-center mb-4">
            <i class="bi bi-shield-lock display-3 d-block mb-2"></i>
            <h2 class="display-6 m-0">Admin Login</h2>
          </div>

          <!-- Timeout alert -->
          <?php if (isset($_GET['timeout'])): ?>
  <div class="alert alert-warning alert-dismissible fade show" role="alert">
    You have been logged out due to inactivity.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

          <!-- Existing error message -->
          <?php if ($errorMessage): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
          <?php endif; ?>

          <form method="POST">
            <div class="mb-3">
              <label for="username" class="form-label">Username:</label>
              <input type="text" name="username" id="username" class="form-control form-control-lg" required>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password:</label>
              <div class="input-group">
                <input type="password" name="password" id="password" class="form-control form-control-lg" required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                  <i class="bi bi-eye" id="toggleIcon"></i>
                </button>
              </div>
            </div>
            <div class="form-check mb-3">
              <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
              <label class="form-check-label" for="rememberMe">Remember me</label>
            </div>
            <div class="d-grid">
              <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-box-arrow-in-right"></i> Login</button>
            </div>
          </form>
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

    function togglePassword() {
      const pwd = document.getElementById('password');
      const icon = document.getElementById('toggleIcon');
      if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      } else {
        pwd.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
      }
    }
  </script>
</body>
</html>
