<?php
// ====== KONFIG DB (samain dengan punyamu) ======
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "pengukurKolam";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
  http_response_code(500);
  die("Koneksi gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ====== API KEY ======
$API_KEY = "RAHASIA123"; // samain di Arduino

$key  = $_GET["key"] ?? "";
$id   = (int)($_GET["id_kolam"] ?? 0);
$ph   = isset($_GET["ph"]) ? floatval($_GET["ph"]) : null;
$tds  = isset($_GET["tds"]) ? intval($_GET["tds"]) : null;
$temp = isset($_GET["temp"]) ? floatval($_GET["temp"]) : null;

//100126 add statusAir
$statusAir = isset($_GET["status"]) ? trim($_GET["status"]) : "";

if ($key !== $API_KEY) {
  http_response_code(401);
  echo "KEY SALAH";
  exit;
}

if ($id <= 0 || $ph === null || $tds === null || $temp === null) {
  http_response_code(400);
  echo "PARAM KURANG";
  exit;
}

//100126
if ($statusAir === "") $statusAir = "UNKNOWN";
if (strlen($statusAir) > 50) $statusAir = substr($statusAir, 0, 50);

// insert ke tabel measurements sesuai struktur kamu
$stmt = $conn->prepare("
  INSERT INTO measurements (id_kolam, ph_value, tds_value, temperature_value, statusAir, created_at)
  VALUES (?, ?, ?, ?, ?, NOW())
");
$stmt->bind_param("idids", $id, $ph, $tds, $temp, $statusAir);

if ($stmt->execute()) {
  echo "OK";
} else {
  http_response_code(500);
  echo "ERROR: " . $stmt->error;
}

$stmt->close();
$conn->close();
