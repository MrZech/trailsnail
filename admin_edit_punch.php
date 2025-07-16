<?php
// Include database configuration
include 'admin_header.php';
include 'config/config.php';

// Ensure database connection is available
if (!$conn) {
    die("Database connection error. Please check config.php.");
}

// Get the punch ID from the URL
$punchId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch punch details
$punchQuery = "SELECT fullname, FROM_UNIXTIME(timestamp) AS punch_datetime, `inout` FROM info WHERE id = ?";
$stmt = $conn->prepare($punchQuery);
$stmt->bind_param("i", $punchId);
$stmt->execute();
$punchResult = $stmt->get_result();
$punch = $punchResult->fetch_assoc();

if (!$punch) {
    die("Invalid punch ID.");
}

// Handle form submission for editing punch
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newDatetime = $_POST['punch_datetime'];
    $newInOut = $_POST['inout'];
    
    $updateQuery = "UPDATE info SET timestamp = UNIX_TIMESTAMP(?), `inout` = ? WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ssi", $newDatetime, $newInOut, $punchId);
    
    if ($stmt->execute()) {
        echo "<p class='alert alert-success'>Punch updated successfully!</p>";
    } else {
        echo "<p class='alert alert-danger'>Error updating punch.</p>";
    }
}
include 'admin_htmlheader.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Punch</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
    h2, label {
      color: #fff;
    }
    .form-control, .form-select {
      background-color: rgba(255, 255, 255, 0.1);
      color: #fff;
      border: 1px solid #666;
    }
    .form-control:focus, .form-select:focus {
      background-color: rgba(255, 255, 255, 0.15);
      color: #fff;
    }
    input.form-control[disabled] {
  background-color: rgba(255, 255, 255, 0.1);
  color: #ccc;
  border: 1px solid #666;
  opacity: 1; /* prevents default Bootstrap graying */
}
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card p-4">
          <h2 class="text-center mb-4">Edit Punch</h2>

          <?= isset($message) ? $message : '' ?>

          <form method="POST">
            <div class="mb-3">
              <label for="fullname">Employee:</label>
              <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($punch['fullname']) ?>" class="form-control" disabled>
            </div>

            <div class="mb-3">
              <label for="punch_datetime">Timestamp:</label>
              <input type="datetime-local" id="punch_datetime" name="punch_datetime" value="<?= date('Y-m-d\TH:i', strtotime($punch['punch_datetime'])) ?>" class="form-control" required>
            </div>

            <div class="mb-3">
              <label for="inout">IN/OUT:</label>
              <select id="inout" name="inout" class="form-select">
                <option value="in" <?= ($punch['inout'] === 'in') ? 'selected' : '' ?>>IN</option>
                <option value="out" <?= ($punch['inout'] === 'out') ? 'selected' : '' ?>>OUT</option>
              </select>
            </div>

            <div class="d-flex justify-content-between">
              <button type="submit" class="btn btn-primary">Update Punch</button>
              <a href="admin_panel.php" class="btn btn-secondary">Cancel</a>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</body>
</html>

<?php include 'admin_footer.php'; ?>
