<?php
$conn = new mysqli("localhost","root","","pengukurKolam");
$res = $conn->query("SELECT active_kolam_id FROM device_config WHERE id = 1");
$row = $res->fetch_assoc();
echo $row['active_kolam_id'];
