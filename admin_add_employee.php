<?php
global $conn;

if (!$conn) {
    die("Database connection error.");
}

// Fetch Groups List
$groupsQuery = "SELECT * FROM groups ORDER BY groupname ASC";
$groupsResult = $conn->query($groupsQuery);
$groups = [];
while ($row = $groupsResult->fetch_assoc()) {
    $groups[] = $row;
}

// Fetch Offices List
$officesQuery = "SELECT * FROM offices ORDER BY officename ASC";
$officesResult = $conn->query($officesQuery);
$offices = [];
while ($row = $officesResult->fetch_assoc()) {
    $offices[] = $row;
}

// Handle Adding New Employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $fullname = $_POST['fullname'];
    $displayname = $_POST['displayname'];
    $email = $_POST['email'];
    $password = crypt($_POST['password'], 'xy');
    $group = $_POST['group'];
    $office = $_POST['office'];
    
    $addEmployeeQuery = "INSERT INTO employees (empfullname, displayname, email, employee_passwd, groups, office) 
                         VALUES ('$fullname', '$displayname', '$email', '$password', '$group', '$office')";
    $conn->query($addEmployeeQuery);
    echo "<div class='alert alert-success'>Employee added successfully!</div>";
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
<div class="container py-5">
    <h1>Add New Employee</h1>
    <form method="POST">
        <div class="mb-3">
            <label for="fullname">Full Name:</label>
            <input type="text" id="fullname" name="fullname" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="displayname">Display Name:</label>
            <input type="text" id="displayname" name="displayname" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="group">Group:</label>
            <select id="group" name="group" class="form-select" required>
                <?php foreach ($groups as $group): ?>
                    <option value="<?= $group['groupid'] ?>"><?= $group['groupname'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="office">Office:</label>
            <select id="office" name="office" class="form-select" required>
                <?php foreach ($offices as $office): ?>
                    <option value="<?= $office['officeid'] ?>"><?= $office['officename'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" name="add_employee" class="btn btn-primary">Add Employee</button>
    </form>
</div>
