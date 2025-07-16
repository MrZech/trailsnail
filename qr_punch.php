<?php
include 'config/config.php';

$data = json_decode(file_get_contents('php://input'), true);
$qr = $data['qr_code'] ?? '';

if (!$qr) {
    echo json_encode(['message' => 'Invalid QR code']);
    exit;
}

$stmt = $conn->prepare("SELECT empfullname FROM employees WHERE qr_code = ?");
$stmt->bind_param("s", $qr);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['message' => 'QR code not recognized']);
    exit;
}

$employee = $result->fetch_assoc()['empfullname'];
$timestamp = time();
$ip = $_SERVER['REMOTE_ADDR'];

// Determine punch direction
$lastPunch = $conn->query("SELECT `inout` FROM info WHERE fullname = '$employee' ORDER BY timestamp DESC LIMIT 1");
$lastStatus = ($lastPunch->num_rows > 0) ? $lastPunch->fetch_assoc()['inout'] : 'out';
$newStatus = ($lastStatus === 'in') ? 'out' : 'in';

$conn->query("INSERT INTO info (fullname, `inout`, timestamp, notes, ipaddress) VALUES ('$employee', '$newStatus', $timestamp, '', '$ip')");

echo json_encode(['message' => "Punch $newStatus for $employee recorded."]);