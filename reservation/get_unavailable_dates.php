<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['error' => 'שגיאת חיבור למסד הנתונים']);
    exit;
}

$sql = "SELECT date FROM Availability WHERE available_spots = 0";
$result = $conn->query($sql);

$unavailableDates = [];
while ($row = $result->fetch_assoc()) {
    $unavailableDates[] = date('d/m/Y', strtotime($row['date']));
}

$conn->close();
echo json_encode($unavailableDates);
?>