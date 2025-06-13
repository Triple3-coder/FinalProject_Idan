<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$reservation_id = isset($_SESSION['reservation_id']) ? $_SESSION['reservation_id'] : null;

if (!$reservation_id) {
    die("לא נקבע מזהה הזמנה");
}

// משיכת פרטי ההזמנה
$sql = "SELECT start_date, end_date, total_payments_services FROM reservation WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $start_date = $row['start_date'];
    $end_date = $row['end_date'];
    $total_price = $row['total_payments_services'];

    // חישוב לילות (אם זה רלוונטי)
    $start = date_create($start_date);
    $end = date_create($end_date);
    $nights = date_diff($start, $end)->format('%a');

    // שאילתה של השירותים שנבחרו
    $sql_services = "SELECT s.name, s.price 
                     FROM reservation_services rs
                     JOIN services s ON rs.service_id = s.id
                     WHERE rs.reservation_id = ?";
    $stmt_services = $conn->prepare($sql_services);
    $stmt_services->bind_param("i", $reservation_id);
    $stmt_services->execute();
    $result_services = $stmt_services->get_result();

    // הצגת כל המידע
    echo "<h1>סיכום ההזמנה</h1>";

    echo "<h2>תקופת ההזמנה</h2>";
    echo "מתאריך: " . date_format($start, 'd/m/Y') . "<br>";
    echo "עד תאריך: " . date_format($end, 'd/m/Y') . "<br>";

    echo "<h2>השירותים שנבחרו</h2>";
    echo "<table border='1'>";
    echo "<tr><th>שירות</th><th>מחיר</th></tr>";
    while ($row_service = $result_services->fetch_assoc()) {
        echo "<tr><td>" . $row_service["name"] . "</td><td>" . number_format($row_service["price"], 2) . " ₪</td></tr>";
    }
    echo "</table>";

    echo "<h2>עלות לינה</h2>";
    $roomRate = 50;
    $accommodationCost = $nights * $roomRate;
    echo "מספר לילות: " . $nights . "<br>";
    echo "מחיר ללילה: " . number_format($roomRate, 2) . " ₪<br>";
    echo "עלות לינה כוללת: " . number_format($accommodationCost, 2) . " ₪<br>";

    echo "<h2>מחיר סופי</h2>";
    echo "מחיר שירותים: " . number_format($total_price, 2) . " ₪<br>";
    echo "עלות לינה: " . number_format($accommodationCost, 2) . " ₪<br>";
    echo "<strong>סה\"כ לתשלום: " . number_format($total_price + $accommodationCost, 2) . " ₪</strong>";

    $stmt_services->close();
} else {
    echo "ההזמנה לא נמצאה.";
}

$stmt->close();
$conn->close();
?>


