<?php
// Include your database config
include 'config/config.php';

// Initialize variables
$errorMessage = '';
$message = '';

// Fetch active employees for dropdown
$sql = "SELECT empfullname, displayname FROM employees WHERE disabled = 0 AND admin = 0";
$result = $conn->query($sql);
if (!$result) {
    die("Error fetching employees: " . $conn->error);
}
$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}

// Handle punch action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Detect if manual form or QR scan form submitted
    if (isset($_POST['employee']) && isset($_POST['password'])) {
        // Manual form submit
        $employee = $_POST['employee'];
        $password = $_POST['password'];
    } elseif (isset($_POST['qrdata'])) {
        // QR code scanned string, expected format: username:password
        $parts = explode(':', $_POST['qrdata']);
        if (count($parts) === 2) {
            $employee = $parts[0];
            $password = $parts[1];
        } else {
            $errorMessage = "Invalid QR code data format.";
        }
    } else {
        $errorMessage = "Missing credentials.";
    }

    if (empty($errorMessage)) {
        $notes = isset($_POST['notes']) ? $conn->real_escape_string($_POST['notes']) : '';
        $timestamp = time();
        $ipAddress = $_SERVER['REMOTE_ADDR'];

        // Fetch employee password from DB
        $stmt = $conn->prepare("SELECT employee_passwd FROM employees WHERE empfullname = ?");
        $stmt->bind_param("s", $employee);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result || $result->num_rows === 0) {
            $errorMessage = "Error: Employee not found.";
        } else {
            $row = $result->fetch_assoc();
            $storedPassword = $row['employee_passwd'];

            // Verify password with crypt
            $hashedInputPassword = crypt($password, 'xy');
            if ($hashedInputPassword !== $storedPassword) {
                $errorMessage = "Error: Invalid password.";
            } else {
                // Get last punch status
                $stmt = $conn->prepare("SELECT `inout` FROM info WHERE fullname = ? ORDER BY timestamp DESC LIMIT 1");
                $stmt->bind_param("s", $employee);
                $stmt->execute();
                $lastPunchResult = $stmt->get_result();

                $lastStatus = ($lastPunchResult->num_rows > 0) ? $lastPunchResult->fetch_assoc()['inout'] : 'out';
                $newStatus = ($lastStatus === 'in') ? 'out' : 'in';

                // Insert new punch
                $stmt = $conn->prepare("INSERT INTO info (fullname, `inout`, timestamp, notes, ipaddress) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiss", $employee, $newStatus, $timestamp, $notes, $ipAddress);
                if ($stmt->execute()) {
                    // Redirect with success message in URL
                    $message = "Punch '$newStatus' recorded for $employee.";
header("Location: ../timeclock.php?success=" . urlencode($message));
                    exit;
                } else {
                    $errorMessage = "Error: " . $conn->error;
                }
            }
        }
    }
}

// Fetch current punch statuses for all employees
$statusSql = "
SELECT e.displayname, i.`inout`, i.timestamp
FROM employees e
LEFT JOIN (
    SELECT t1.fullname, t1.`inout`, t1.timestamp
    FROM info t1
    WHERE t1.timestamp = (
        SELECT MAX(t2.timestamp)
        FROM info t2
        WHERE t1.fullname = t2.fullname
    )
) i ON e.empfullname = i.fullname
WHERE e.disabled = 0 AND e.admin = 0
ORDER BY e.displayname ASC;
";
$statusResult = $conn->query($statusSql);
if (!$statusResult) {
    die("Error fetching statuses: " . $conn->error);
}
$statuses = [];
while ($row = $statusResult->fetch_assoc()) {
    $statuses[] = $row;
}

// Check if success message exists in URL
if (isset($_GET['success'])) {
    $message = htmlspecialchars($_GET['success']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Dispo.time - Timeclock</title>

<!-- Bootstrap CSS & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
<link rel="icon" type="image/png" href="/timeclock/assets/favi.png" />

<style>

      .dashboard-link {
  transition: background 0.4s ease, color 0.4s ease;
}


/* OR if you want a moving rainbow effect: */
@keyframes rainbow {
  0%   { background-position: 0% 50%; }
  50%  { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.dashboard-link:hover {
  background: linear-gradient(270deg,
      #ff6a00,
      #ee0979,
      #330867,
      #30cfd0,
      #330867,
      #ee0979,
      #ff6a00
    );
  background-size: 1400% 1400%;
  animation: rainbow 10s ease infinite;
  color: #fff;
}

  body {
    background: linear-gradient(135deg, rgb(10, 38, 64), rgb(38, 86, 150));
    background-attachment: fixed;
    background-repeat: no-repeat;
    background-size: cover;
    color: #e0e0e0;
  }
  .form-select, .form-control {
    background-color: #2c2c2c;
    color: #e0e0e0;
    border: 1px solid #444;
  }
  .form-select:focus, .form-control:focus {
    background-color: #2c2c2c;
    color: #fff;
    border-color: #0d6efd;
    outline: none;
    box-shadow: 0 0 5px #0d6efd;
  }
  .btn-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
  }
  .btn-primary:hover {
    background-color: #0b5ed7;
    border-color: #0a58ca;
  }
  .card {
    background-color: rgba(0, 0, 0, 0.6);
    color: #fff;
    border: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  }
  #liveClock {
    color: #ccc;
  }
  .alert-fixed {
  position: relative;
  margin-top: 1rem;
  text-align: center;
  opacity: 1;
  max-height: 200px; /* or whatever max height you expect */
  overflow: hidden;
  transition: opacity 1s ease-out, max-height 0.5s ease-out, margin 0.5s ease-out, padding 0.5s ease-out;
}

.alert-fixed.fadeout {
  opacity: 0;
  max-height: 0;
  margin: 0;
  padding: 0;
}
  #qrInputContainer {
    display: block;
    margin-bottom: 1rem;
  }
  a img:hover {
    opacity: 0.8;
    transition: opacity 0.2s ease-in-out;
  }
  #blow {
  position: fixed;
  top: 5;
  left: 3;
  width: 100px;
  height: 10px;
  opacity: 1;
  z-index: 9999;
}
#blow:hover {
  opacity: 0.1; /* optional: slight hint for devs */
  background: red; /* or remove for complete invisibility */
}
</style>

<script>
// Live clock update
function updateClock() {
    const now = new Date();
    const clock = document.getElementById('liveClock');
    clock.textContent = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'});
}
setInterval(updateClock, 1000);
window.onload = updateClock;

// Show/hide QR input container
function toggleQR() {
    const container = document.getElementById('qrInputContainer');
    container.style.display = (container.style.display === 'none' || container.style.display === '') ? 'block' : 'none';
    if(container.style.display === 'block') {
        document.getElementById('qrdata').focus();
    }
}

// Handle Enter key press on QR input
function handleQRKey(event) {
    if(event.key === 'Enter') {
        event.preventDefault();
        document.getElementById('qrForm').submit();
    }
}

// Fade out alert after 4 seconds
function fadeOutAlert() {
  const alert = document.getElementById('alertMessage');
  if (alert) {
    setTimeout(() => {
      alert.classList.add('fadeout');
    }, 4000);
  }
}
setTimeout(() => {
  alert.classList.add('fadeout');
  setTimeout(() => {
    alert.remove(); // fully removes it from the page
  }, 1000); // wait for animation to finish
}, 4000);

window.onload = () => {
    updateClock();
    fadeOutAlert();
}
</script>
</head>
<body>
  <a href="explosion.html" id="blow" aria-hidden="true"></a>
<div class="container py-5 position-relative">

  <div class="position-absolute top-0 end-0 m-3">
    <a href="admin_login.php" class="btn btn-outline-light btn-sm dashboard-link" title="Admin Login">
      <i class="bi bi-shield-lock"></i> Admin
    </a>
  </div>

  <div class="row mb-4">
    <div class="col-12 text-center">
      <a href="https://www.dispo.tech" target="_blank" rel="noopener noreferrer">
        <img src="/timeclock/assets/logo.svg" alt="Dispo.Tech" class="img-fluid" style="max-height: 100px;" />
      </a>
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-12 text-center">
      <h5 id="liveClock" class="fw-light"></h5>
    </div>
  </div>

  <!-- Success or error message -->
  

  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">

      <div class="card shadow p-4 mb-3">
        <div class="text-center mb-4">
          <i class="bi bi-clock display-3 d-block mb-2"></i>
          <h2 class="display-5 m-0">Timeclock</h2>
          
          <?php if (!empty($message)): ?>
          <div id="alertMessage" class="alert alert-success alert-fixed"><?= $message ?></div>
          <?php elseif (!empty($errorMessage)): ?>
         <div id="alertMessage" class="alert alert-danger alert-fixed"><?= $errorMessage ?></div>
         <?php endif; ?>
         
          
        </div>

        <!-- Manual Punch Form -->
        <form method="POST" action="timeclock.php" id="punchForm">
          <div class="mb-3">
            <label for="employee" class="form-label">Select Employee:</label>
            <select name="employee" id="employee" class="form-select form-select-lg" required>
              <option value="" disabled selected>Choose an employee...</option>
              <?php foreach ($employees as $employee): ?>
                <option value="<?= htmlspecialchars($employee['empfullname']) ?>">
                  <?= htmlspecialchars($employee['displayname']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password:</label>
            <input type="password" name="password" id="password" class="form-control form-control-lg" required />
          </div>
          <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="bi bi-clock"></i> Punch Status
            </button>
          </div>
        </form>
      </div>

    

      <!-- QR Code Punch Form -->
      <div id="qrInputContainer" class="card shadow p-4">
        <form method="POST" id="qrForm" autocomplete="off">
          <label for="qrdata" class="form-label">Scan QR Code Here</label>
          <input
            type="password"
            id="qrdata"
            name="qrdata"
            class="form-control form-control-lg"
            placeholder="Scan QR code here"
            onkeydown="handleQRKey(event)"
            autofocus
            autocomplete="off"
          />
        </form>
      </div>

      <!-- Current statuses -->
      

    </div>
  </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
