<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'שגיאת חיבור למסד הנתונים']);
    exit;
}

try {
    // שליפת מחיר הלינה היומי
    $sql = "SELECT price FROM services_prices WHERE service_type = 'lodge' LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'success' => true, 
            'price' => $row['price']
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'לא נמצא מחיר לשירות לינה'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'שגיאה בשליפת המחיר: ' . $e->getMessage()
    ]);
}

$conn->close();
?>