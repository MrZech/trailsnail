<?php
// Make sure we have a database connection from `admin_panel.php`
global $conn;
if (!$conn) {
    die("Database connection error.");
}

// Default date range: 21st of last month to 20th of current month
$defaultStartDate = date('Y-m-21', strtotime('first day of last month'));
$defaultEndDate   = date('Y-m-20');

$startDate          = $_POST['start_date']   ?? $defaultStartDate;
$endDate            = $_POST['end_date']     ?? $defaultEndDate;
$selectedVolunteer  = $_POST['volunteer']    ?? '';
$applyBreaks        = isset($_POST['apply_breaks']);

if (!validateDate($startDate) || !validateDate($endDate) || $startDate > $endDate) {
    die("Invalid date range.");
}

// Fetch all active volunteers
$volQuery    = "SELECT volfullname, displayname FROM volunteers WHERE disabled = 0";
$volResult   = $conn->query($volQuery);
if (!$volResult) {
    die("Error fetching volunteers: " . $conn->error);
}
$volunteers = $volResult->fetch_all(MYSQLI_ASSOC);

// Fetch punches for either a single volunteer or everyone
$punchesQuery = "
    SELECT
        fullname,
        DATE(FROM_UNIXTIME(timestamp)) AS work_date,
        `inout`,
        timestamp
    FROM volunteer_info
    WHERE
        (LOWER(fullname) = LOWER(?) OR ? = '')
        AND timestamp BETWEEN UNIX_TIMESTAMP(?) AND UNIX_TIMESTAMP(?)
    ORDER BY fullname, timestamp ASC;
";
$stmt = $conn->prepare($punchesQuery);
if (!$stmt) {
    die("Error preparing punches query: " . $conn->error);
}
$stmt->bind_param(
    "ssss",
    $selectedVolunteer,
    $selectedVolunteer,
    $startDate,
    $endDate
);
$stmt->execute();
$punches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate worked seconds per volunteer per day
$workSeconds = [];  // [ fullname ][ work_date ] = seconds
foreach ($punches as $p) {
    $name = $p['fullname'];
    $date = $p['work_date'];
    static $lastIn;

    if ($p['inout'] === 'in') {
        $lastIn[$name][$date] = $p['timestamp'];
    } elseif ($p['inout'] === 'out' && isset($lastIn[$name][$date])) {
        $delta = $p['timestamp'] - $lastIn[$name][$date];
        if ($applyBreaks) {
            if    ($delta > 8*3600)   $delta -= 3600;
            elseif($delta > 5*3600)   $delta -= 1800;
        }
        $workSeconds[$name][$date] = ($workSeconds[$name][$date] ?? 0) + $delta;
        unset($lastIn[$name][$date]);
    }
}
?>
<style>
  /* keep your existing dark‑mode CSS here */
</style>

<h1>Volunteer Hours Report</h1>

<form method="POST" class="mb-4">
  <div class="mb-3">
    <label for="volunteer">Select Volunteer:</label>
    <select id="volunteer" name="volunteer" class="form-select w-25 d-inline-block">
      <option value="" <?= $selectedVolunteer === '' ? 'selected' : '' ?>>All Volunteers</option>
      <?php foreach ($volunteers as $v): ?>
        <option value="<?= htmlspecialchars($v['volfullname']) ?>"
          <?= $selectedVolunteer === $v['volfullname'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($v['displayname']) ?>
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

<?php if ($selectedVolunteer === ''): ?>
  <!-- ALL VOLUNTEERS: total across range -->
  <h2>Total Hours by Volunteer</h2>
  <table class="table table-striped">
    <thead>
      <tr><th>Volunteer</th><th>Hours</th></tr>
    </thead>
    <tbody>
      <?php foreach ($workSeconds as $name => $days):
          $sum   = array_sum($days);
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
  <!-- SINGLE VOLUNTEER: daily breakdown -->
  <h2>Daily Hours for <?= htmlspecialchars($selectedVolunteer) ?></h2>
  <table class="table table-striped">
    <thead>
      <tr><th>Date</th><th>Hours</th></tr>
    </thead>
    <tbody>
      <?php 
        $total = 0;
        foreach (($workSeconds[$selectedVolunteer] ?? []) as $date => $sec):
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
  // If you still want to show per‑punch detail, reuse your existing JS here
</script>

<?php
function validateDate($d, $fmt='Y-m-d') {
    $dt = DateTime::createFromFormat($fmt, $d);
    return $dt && $dt->format($fmt) === $d;
}
?>
