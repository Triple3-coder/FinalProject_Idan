<?php

$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode(['error' => 'שגיאת חיבור למסד הנתונים']);
    exit;
}

$result = $conn->query("SELECT price FROM services_prices WHERE service_type='lodge' LIMIT 1");

if ($result && $row = $result->fetch_assoc()) {
    echo json_encode(['price' => intval($row['price'])]);
} else {
    // במידה ולא נמצא מחיר — מחזיר 0
    echo json_encode(['price' => 0]);
}

$conn->close();
?>