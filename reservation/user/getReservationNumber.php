<?php
session_start();
$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

$user_code = $_SESSION['user_code'] ?? '';

$result = $conn->query("SELECT id FROM reservation WHERE user_code = '$user_code' AND status='active' LIMIT 1");

if ($row = $result->fetch_assoc()) {
  echo json_encode(['resNum' => $row['id']]);
} else {
  echo json_encode(['resNum' => 0]);
}
$conn->close();
?>
