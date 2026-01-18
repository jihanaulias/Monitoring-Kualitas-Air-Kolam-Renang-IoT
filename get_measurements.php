<?php
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "pengukurKolam";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$conn->set_charset("utf8mb4");


$id = (int)($_GET['id_kolam'] ?? 0);
if ($id <= 0) exit;
$date = trim($_GET['date'] ?? "");


// $res = $conn->query("
//   SELECT ph_value, tds_value, temperature_value, created_at, statusAir
//   FROM measurements
//   WHERE id_kolam = $id
//   ORDER BY created_at DESC
//   LIMIT 20
// ");

if ($date !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
  $stmt = $conn->prepare("
    SELECT ph_value, tds_value, temperature_value, created_at, statusAir
    FROM measurements
    WHERE id_kolam = ? AND DATE(created_at) = ?
    ORDER BY created_at DESC
    LIMIT 50
  ");
  $stmt->bind_param("is", $id, $date);
} else {
  $stmt = $conn->prepare("
    SELECT ph_value, tds_value, temperature_value, created_at, statusAir
    FROM measurements
    WHERE id_kolam = ?
    ORDER BY created_at DESC
    LIMIT 50
  ");
  $stmt->bind_param("i", $id);
}

$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) $data[] = $row;

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);

$stmt->close();

