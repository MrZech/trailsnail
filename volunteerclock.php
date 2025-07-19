<?php
// Include your database config
include 'config/config.php';

// Initialize variables
$errorMessage = '';
$message = '';

// Fetch active volunteers for dropdown
$sql = "SELECT volfullname, displayname
        FROM volunteers
        WHERE disabled = 0";
$result = $conn->query($sql);
if (!$result) {
    die("Error fetching volunteers: " . $conn->error);
}
$volunteers = [];
while ($row = $result->fetch_assoc()) {
    $volunteers[] = $row;
}

// Handle punch action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['volunteer']) && isset($_POST['password'])) {
        // Manual form submit
        $volunteer = $_POST['volunteer'];
        $password  = $_POST['password'];
    } elseif (isset($_POST['qrdata'])) {
        // QR code scanned string: username:password
        $parts = explode(':', $_POST['qrdata']);
        if (count($parts) === 2) {
            list($volunteer, $password) = $parts;
        } else {
            $errorMessage = "Invalid QR code data format.";
        }
    } else {
        $errorMessage = "Missing credentials.";
    }

    if (empty($errorMessage)) {
        $notes     = isset($_POST['notes']) ? $conn->real_escape_string($_POST['notes']) : '';
        $timestamp = time();
        $ipAddress = $_SERVER['REMOTE_ADDR'];

        // Fetch volunteer password
        $stmt = $conn->prepare(
          "SELECT volunteer_passwd
           FROM volunteers
           WHERE volfullname = ?"
        );
        $stmt->bind_param("s", $volunteer);
        $stmt->execute();
        $res = $stmt->get_result();

        if (!$res || $res->num_rows === 0) {
            $errorMessage = "Error: Volunteer not found.";
        } else {
            $stored = $res->fetch_assoc()['volunteer_passwd'];
            $hash   = crypt($password, 'xy');
            if ($hash !== $stored) {
                $errorMessage = "Error: Invalid password.";
            } else {
                // Get last in/out
                $stmt = $conn->prepare(
                  "SELECT `inout`
                   FROM volunteer_info
                   WHERE fullname = ?
                   ORDER BY timestamp DESC
                   LIMIT 1"
                );
                $stmt->bind_param("s", $volunteer);
                $stmt->execute();
                $last = $stmt->get_result();
                $lastStatus = $last->num_rows
                            ? $last->fetch_assoc()['inout']
                            : 'out';
                $newStatus = ($lastStatus === 'in') ? 'out' : 'in';

                // Insert new punch
                $stmt = $conn->prepare(
                  "INSERT INTO volunteer_info
                   (fullname, `inout`, timestamp, notes, ipaddress)
                   VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param(
                  "ssiss",
                  $volunteer,
                  $newStatus,
                  $timestamp,
                  $notes,
                  $ipAddress
                );
                if ($stmt->execute()) {
                    $message = "Punch '$newStatus' recorded for $volunteer.";
                    header("Location: volunteerclock.php?success=" . urlencode($message));
                    exit;
                } else {
                    $errorMessage = "Error: " . $conn->error;
                }
            }
        }
    }
}

// Fetch current statuses
$statusSql = "
SELECT v.displayname, i.`inout`, i.timestamp
FROM volunteers v
LEFT JOIN (
  SELECT t1.fullname, t1.`inout`, t1.timestamp
  FROM volunteer_info t1
  WHERE t1.timestamp = (
    SELECT MAX(t2.timestamp)
    FROM volunteer_info t2
    WHERE t1.fullname = t2.fullname
  )
) i ON v.volfullname = i.fullname
WHERE v.disabled = 0
ORDER BY v.displayname ASC;
";
$statusResult = $conn->query($statusSql);
if (!$statusResult) {
    die("Error fetching statuses: " . $conn->error);
}
$statuses = [];
while ($row = $statusResult->fetch_assoc()) {
    $statuses[] = $row;
}

// Grab any success message
if (isset($_GET['success'])) {
    $message = htmlspecialchars($_GET['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Volunteer Clock</title>
  <!-- Bootstrap CSS & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet"/>
  <style>
    /* (copy your dark‐mode CSS here unchanged) */
  </style>
  <script>
    // (copy your live clock, QR toggle, fade‐out JS here unchanged)
  </script>
</head>
<body>
  <div class="container py-5">
    <div class="row mb-4 text-center">
      <h2>Volunteer Clock</h2>
      <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
      <?php elseif ($errorMessage): ?>
        <div class="alert alert-danger"><?= $errorMessage ?></div>
      <?php endif; ?>
    </div>
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card p-4 mb-3">
          <form method="POST">
            <div class="mb-3">
              <label for="volunteer" class="form-label">Select Volunteer:</label>
              <select name="volunteer" id="volunteer" class="form-select" required>
                <option disabled selected>Choose a volunteer…</option>
                <?php foreach ($volunteers as $v): ?>
                  <option value="<?= htmlspecialchars($v['volfullname']) ?>">
                    <?= htmlspecialchars($v['displayname']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password:</label>
              <input type="password" name="password" class="form-control" required/>
            </div>
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-person-check"></i> Punch
            </button>
          </form>
        </div>
        <div class="card p-4">
          <label for="qrdata" class="form-label">Scan QR Code:</label>
          <input type="password" id="qrdata" name="qrdata"
                 class="form-control"
                 placeholder="username:password" />
        </div>
      </div>
    </div>
    <div class="row mt-4">
      <div class="col-md-8 offset-md-2">
        <h5>Current Statuses</h5>
        <ul class="list-group">
          <?php foreach ($statuses as $s): ?>
            <li class="list-group-item d-flex justify-content-between">
              <?= htmlspecialchars($s['displayname']) ?>
              <span>
                <?= $s['inout'] === 'in' ? '🟢 In' : '🔴 Out' ?>
                (<?= date('H:i:s', $s['timestamp']) ?>)
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
