<?php
// נתוני חיבור לבסיס הנתונים
$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

// יצירת חיבור לבסיס הנתונים
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

// קריאת הנתונים מ-JSON שהגיע
$input = json_decode(file_get_contents('php://input'), true);
$reservation_id = isset($input['reservation_id']) ? (int)$input['reservation_id'] : 0;

if ($reservation_id > 0) {
    // הכנת השאילתה והזרמת הנתונים
    $stmt = $conn->prepare("UPDATE reservation SET status = 'paid' WHERE id = ?");
    $stmt->bind_param("i", $reservation_id);
    $result = $stmt->execute();

    // בדיקת תוצאות והדפסה
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid reservation ID']);
}

?>
