<?php
// Make sure we have a database connection from `admin_panel.php`
global $conn;
if (!$conn) {
    die("Database connection error.");
}

// Default date range: 21st of last month to 20th of current month
$defaultStartDate = date('Y-m-21', strtotime('first day of last month'));
$defaultEndDate   = date('Y-m-20');

$startDate        = $_POST['start_date']   ?? $defaultStartDate;
$endDate          = $_POST['end_date']     ?? $defaultEndDate;
$selectedEmployee = $_POST['employee']     ?? '';
$applyBreaks      = isset($_POST['apply_breaks']);

if (!validateDate($startDate) || !validateDate($endDate) || $startDate > $endDate) {
    die("Invalid date range.");
}

// Fetch all active employees
$employeesQuery  = "SELECT empfullname, displayname FROM employees WHERE disabled = 0 AND admin = 0";
$employeesResult = $conn->query($employeesQuery);
if (!$employeesResult) {
    die("Error fetching employees: " . $conn->error);
}
$employees = $employeesResult->fetch_all(MYSQLI_ASSOC);

// Fetch punches for either a single employee or everyone, plus fullname
$punchesQuery = "
    SELECT
        fullname,
        DATE(FROM_UNIXTIME(timestamp)) AS work_date,
        `inout`,
        timestamp
    FROM info
    WHERE
        (LOWER(fullname) = LOWER(?) OR ? = '')
        AND timestamp BETWEEN UNIX_TIMESTAMP(?) AND UNIX_TIMESTAMP(?)
    ORDER BY fullname, timestamp ASC;
";
$stmt = $conn->prepare($punchesQuery);
if (!$stmt) {
    die("Error preparing punches query: " . $conn->error);
}
$stmt->bind_param("ssss",
    $selectedEmployee,
    $selectedEmployee,
    $startDate,
    $endDate
);
$stmt->execute();
$punches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Now calculate worked seconds
$workSeconds = [];  // [ fullname ][ work_date ] = seconds
foreach ($punches as $p) {
    $name      = $p['fullname'];
    $date      = $p['work_date'];
    static $lastIn;  // persists between iterations

    if ($p['inout'] === 'in') {
        $lastIn[$name][$date] = $p['timestamp'];
    }
    else if ($p['inout'] === 'out'
          && isset($lastIn[$name][$date])
    ) {
        $delta = $p['timestamp'] - $lastIn[$name][$date];
        // apply breaks
        if ($applyBreaks) {
            if ($delta > 8*3600)   $delta -= 3600;
            elseif ($delta > 5*3600) $delta -= 1800;
        }
        $workSeconds[$name][$date] = ($workSeconds[$name][$date] ?? 0) + $delta;
        unset($lastIn[$name][$date]);
    }
}

// Prepare the two views:
?>
<style>
  /* ... keep your existing dark-mode CSS here ... */
</style>

<h1>Work Hours Report</h1>

<form method="POST" class="mb-4">
  <div class="mb-3">
    <label for="employee">Select Employee:</label>
    <select id="employee" name="employee" class="form-select w-25 d-inline-block">
      <option value="" <?= $selectedEmployee === '' ? 'selected' : '' ?>>All Employees</option>
      <?php foreach ($employees as $e): ?>
        <option value="<?= htmlspecialchars($e['empfullname']) ?>"
          <?= $selectedEmployee === $e['empfullname'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($e['displayname']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3">
    <label for="start_date">Start:</label>
    <input type="date" id="start_date" name="start_date"
           value="<?= htmlspecialchars($startDate) ?>" class="form-control w-25 d-inline-block">
    <label for="end_date">End:</label>
    <input type="date" id="end_date" name="end_date"
           value="<?= htmlspecialchars($endDate) ?>" class="form-control w-25 d-inline-block">
  </div>
  <div class="mb-3">
    <input type="checkbox" id="apply_breaks" name="apply_breaks" <?= $applyBreaks ? 'checked' : '' ?>>
    <label for="apply_breaks">Apply Automatic Breaks</label>
  </div>
  <button type="submit" class="btn btn-primary">Generate Report</button>
</form>

<?php if ($selectedEmployee === ''): ?>
  <!-- ALL EMPLOYEES: show each person’s TOTAL hours in range -->
  <h2>Total Hours by Employee</h2>
  <table class="table table-striped">
    <thead>
      <tr><th>Employee</th><th>Hours</th></tr>
    </thead>
    <tbody>
      <?php foreach ($workSeconds as $name => $days): 
          $sum = array_sum($days);
          $hours = round($sum/3600,2);
      ?>
        <tr>
          <td><?= htmlspecialchars($name) ?></td>
          <td><?= $hours ?> hours</td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

<?php else: ?>
  <!-- SINGLE EMPLOYEE: show daily breakdown -->
  <h2>Daily Work Hours for <?= htmlspecialchars($selectedEmployee) ?></h2>
  <table class="table table-striped">
    <thead>
      <tr><th>Date</th><th>Hours</th></tr>
    </thead>
    <tbody>
      <?php 
        $total = 0;
        foreach (($workSeconds[$selectedEmployee] ?? []) as $date => $sec):
          $h = round($sec/3600,2);
          $total += $h;
      ?>
        <tr>
          <td><?= date('l, Y-m-d', strtotime($date)) ?></td>
          <td><?= $h ?> hours</td>
        </tr>
      <?php endforeach; ?>
      <tr>
        <td><strong>Total</strong></td>
        <td><strong><?= round($total,2) ?> hours</strong></td>
      </tr>
    </tbody>
  </table>
<?php endif; ?>

<script>
  // your togglePunches() can remain if you still want per‑punch detail below…
</script>

<?php
function validateDate($d, $fmt='Y-m-d') {
    $dt = DateTime::createFromFormat($fmt, $d);
    return $dt && $dt->format($fmt) === $d;
}
