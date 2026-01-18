<?php
// ====== CONFIG DB ======
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "pengukurKolam";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) die("Koneksi gagal");
$conn->set_charset("utf8mb4");

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

// ====== HANDLE ACTIONS (POST -> REDIRECT -> GET) ======
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = $_POST["action"] ?? "";
  $id = (int)($_POST["id_kolam"] ?? 0);

  // redirect default
  $redirect = "index.php";
  if ($id > 0) $redirect .= "?id_kolam=".$id;

  if ($action === "add_kolam") {
    $nama = trim($_POST["nama_kolam"] ?? "");
    if ($nama !== "") {
      $stmt = $conn->prepare("INSERT INTO nama_kolam (nama) VALUES (?)");
      $stmt->bind_param("s", $nama);
      $stmt->execute();
      $stmt->close();
    }
    header("Location: index.php");
    exit;
  }

  if ($action === "delete_kolam" && $id > 0) {
    $stmt = $conn->prepare("DELETE FROM nama_kolam WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php");
    exit;
  }

  if ($action === "rename_kolam" && $id > 0) {
  $nama_baru = trim($_POST["nama_kolam_baru"] ?? "");
  if ($nama_baru !== "") {
    $stmt = $conn->prepare("UPDATE nama_kolam SET nama=? WHERE id=?");
    $stmt->bind_param("si", $nama_baru, $id);
    $stmt->execute();
    $stmt->close();
  }
  header("Location: index.php?id_kolam=".$id);
  exit;
}

  if ($action === "set_active_kolam" && $id > 0) {
    $stmt = $conn->prepare("
      INSERT INTO device_config (id, active_kolam_id)
      VALUES (1, ?)
      ON DUPLICATE KEY UPDATE active_kolam_id=VALUES(active_kolam_id)
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: $redirect");
    exit;
  }
}

// ====== GET DATA ======
$kolam = [];
$res = $conn->query("SELECT id,nama FROM nama_kolam ORDER BY nama ASC");
while($r=$res->fetch_assoc()) $kolam[]=$r;

$active_kolam_id = 0;
$res = $conn->query("SELECT active_kolam_id FROM device_config WHERE id=1");
if($res && $r=$res->fetch_assoc()) $active_kolam_id=(int)$r["active_kolam_id"];
if($active_kolam_id<=0 && count($kolam)>0) $active_kolam_id=$kolam[0]["id"];

$selected_id = (int)($_GET["id_kolam"] ?? $active_kolam_id);
$active_kolam_name = "";
if ($active_kolam_id > 0) {
  $stmt = $conn->prepare("SELECT nama FROM nama_kolam WHERE id=?");
  $stmt->bind_param("i", $active_kolam_id);
  $stmt->execute();
  $stmt->bind_result($active_kolam_name);
  $stmt->fetch();
  $stmt->close();
}

$selected_name = "";
if($selected_id>0){
  $stmt=$conn->prepare("SELECT nama FROM nama_kolam WHERE id=?");
  $stmt->bind_param("i",$selected_id);
  $stmt->execute();
  $stmt->bind_result($selected_name);
  $stmt->fetch();
  $stmt->close();
}
?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8">
    <title>Monitoring Kolam</title>
    <style>
      body{
        font-family:Arial;
        margin:20px
      }
      .card{
        border:1px solid #dddddd;
        border-radius:10px;
        padding:14px;
        margin-top:14px
      }
      .row{
        display:flex;
        gap:10px;
        align-items:center
      }
      .right{
        margin-left:auto;
        display:flex;
        gap:10px
      }
      .btn{
        padding:8px 12px;
        border:1px solid #333333;
        background:#ffffff;
        border-radius:6px;cursor:pointer
      }
      .btn.danger{
        border-color:#b00020;
        color:#b00020
      }
      select{
        padding:8px
      }
      table{
        width:100%;
        border-collapse:collapse}
      th,td{
        padding:8px;
        border-bottom:1px solid #eeeeee;
        text-align: center;
      }
      th{
      background:#fafafa
      }
    </style>
  </head>

  <body>
    <h2>Monitoring Kolam</h2>

    <div class="card">
      <div class="row">
        <form method="GET">
          <label><b>Lihat Kolam:</b></label>
          <select name="id_kolam" onchange="this.form.submit()">
            <?php foreach($kolam as $k): ?>
              <option value="<?= $k["id"] ?>" <?= $k["id"]==$selected_id?"selected":"" ?>>
                <?= e($k["nama"]) ?>
              </option>
            <?php endforeach ?>
          </select>
        </form>

        <div class="right">
          <form method="POST" onsubmit="return confirm('Set kolam ini sebagai kolam aktif device?')">
            <input type="hidden" name="action" value="set_active_kolam">
            <input type="hidden" name="id_kolam" value="<?= $selected_id ?>">
            <button class="btn">Set Aktif</button>
          </form>

          <form method="POST" onsubmit="return addKolamPrompt(this)">
            <input type="hidden" name="action" value="add_kolam">
            <input type="hidden" name="nama_kolam">
            <button class="btn">+ Kolam</button>
          </form>

          <form method="POST" onsubmit="return renameKolamPrompt(this)">
      <input type="hidden" name="action" value="rename_kolam">
      <input type="hidden" name="id_kolam" value="<?= $selected_id ?>">
      <input type="hidden" name="nama_kolam_baru">
      <button class="btn">Rename</button>
    </form>

          <form method="POST" onsubmit="return confirm('Hapus kolam ini?')">
            <input type="hidden" name="action" value="delete_kolam">
            <input type="hidden" name="id_kolam" value="<?= $selected_id ?>">
            <button class="btn danger">Hapus</button>
          </form>
        </div>
      </div>

      <p>
        Kolam aktif device:
      <b><?= e($active_kolam_name) ?></b>
      <span style="color:#666">(ID: <?= $active_kolam_id ?>)</span>
      <br>
        Kolam ditampilkan: <b><?= e($selected_name) ?></b>
      </p>

      <label><b>Filter Tanggal:</b></label>
      <input type="text" id="dateFilter" placeholder="YYYY-MM-DD" style="padding:8px; width:140px;">
      <button class="btn" type="button" onclick="applyDate()">Filter</button>
      <button class="btn" type="button" onclick="clearDate()">Reset</button>
    </div>

    <div class="card">
      <h3>Data Sensor</h3>
      <table>
        <thead>
          <tr>
            <th>Waktu</th><th>pH</th><th>TDS</th><th>Suhu</th><th>Status</th>
          </tr>
        </thead>
        <tbody id="data-body"></tbody>
      </table>
    </div>

    <script>
    const idKolam = <?= (int)$selected_id ?>;
    let dateValue = ""; // YYYY-MM-DD or empty

    function loadData(){
      let url = "get_measurements.php?id_kolam=" + idKolam;
      if (dateValue)
        url += "&date=" + encodeURIComponent(dateValue);

      fetch(url)
        .then(r => r.json())
        .then(d => {
          const tb = document.getElementById("data-body");
          tb.innerHTML = "";
          d.forEach(x => {
            tb.innerHTML += `
              <tr>
                <td>${x.created_at}</td>
                <td>${x.ph_value}</td>
                <td>${x.tds_value}</td>
                <td>${x.temperature_value}</td>
                <td>${x.statusAir ?? ""}</td>
              </tr>`;
          });
        });
    }

    function applyDate(){
    const v = document.getElementById("dateFilter").value.trim();
    //YYYY-MM-DD
    if (v && !/^\d{4}-\d{2}-\d{2}$/.test(v)) {
      alert("Format tanggal harus YYYY-MM-DD");
      return;
    }
    dateValue = v;
    loadData();
    }

    function clearDate(){
    document.getElementById("dateFilter").value = "";
    dateValue = "";
    loadData();
    }

    loadData();
    setInterval(loadData,2000);

    function addKolamPrompt(f){
      const n=prompt("Nama kolam:");
      if(!n) return false;
      f.nama_kolam.value=n.trim();
      return f.nama_kolam.value!="";
    }
    
    function renameKolamPrompt(form){
      const lama = "<?= e($selected_name) ?>";
      const baru = prompt("Ganti nama kolam:", lama);
      if (!baru) return false;
      form.nama_kolam_baru.value = baru.trim();
      return form.nama_kolam_baru.value !== "";
    }
    </script>

  </body>
</html>

