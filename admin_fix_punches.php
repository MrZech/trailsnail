<?php
include 'admin_header.php';
// Ensure database connection is available
global $conn;

if (!$conn) {
    die("Database connection error.");
}

?>


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
    table.table {
      --bs-table-bg: transparent;
      --bs-table-color: #e0e0e0;
      --bs-table-striped-bg: rgba(255, 255, 255, 0.05);
      --bs-table-striped-color: #e0e0e0;
      --bs-table-hover-bg: rgba(255, 255, 255, 0.1);
      --bs-table-hover-color: #fff;
      color: var(--bs-table-color) !important;
      background-color: var(--bs-table-bg) !important;
    }
    .table thead {
      background-color: rgba(30, 30, 30, 0.85);
    }
    .table th,
    .table td {
      color: #e0e0e0 !important;
      border-color: #444;
    }
    h2, h3 {
      color: #fff;
    }
  </style>

<h2>Fix Missing Punches</h2>
<p>This page allows administrators to correct missing punches for employees.</p>

<!-- Example: List all employees with last punch -->
<table class="table table-striped">
    <thead>
        <tr>
            <th>Employee</th>
            <th>Last Punch</th>
            <th>Last Punch Type</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $query = "
            SELECT e.displayname, i.`inout`, i.timestamp 
            FROM employees e
            LEFT JOIN (
                SELECT fullname, `inout`, timestamp
                FROM info 
                WHERE (fullname, timestamp) IN (
                    SELECT fullname, MAX(timestamp)
                    FROM info
                    GROUP BY fullname
                )
            ) i ON e.empfullname = i.fullname
            WHERE e.disabled = 0 AND e.admin = 0
            ORDER BY e.displayname ASC
        ";

        $result = $conn->query($query);

        while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['displayname']) ?></td>
                <td><?= ($row['timestamp']) ? date('Y-m-d H:i:s', $row['timestamp']) : 'No Data' ?></td>
                <td><?= htmlspecialchars($row['inout'] ?? 'No Data') ?></td>
                <td>
                    <?php if ($row['inout'] === 'in'): ?>
                        <a href="fix_punches.php?employee=<?= urlencode($row['displayname']) ?>" class="btn btn-warning btn-sm">Fix</a>
                    <?php else: ?>
                        <span class="text-success">No Fix Needed</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

