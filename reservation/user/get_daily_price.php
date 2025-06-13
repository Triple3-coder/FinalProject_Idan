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
    // שליפת מחיר הלינה היומי מתוך טבלת המחירי שירות
    $sql = "SELECT price FROM services_prices WHERE service_type = 'lodge' LIMIT 1";
    $result = $conn->query($sql);
    //בדיקה אם יש תוצאה ולפחות שורה אחת לפחות
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        //החזרת תגובה עם הצלחה ומחיר
        echo json_encode([
            'success' => true, 
            'price' => $row['price']
        ]);
    } else {
        //במידה ולא נמצא מחיר לשירות מחזיר הודעת שגיאה
        echo json_encode([
            'success' => false, 
            'error' => 'לא נמצא מחיר לשירות לינה'
        ]);
    }
} catch (Exception $e) {
    //החזרת הודעה שגיאה בבעית שליפת מחיר מהשרת
    echo json_encode([
        'success' => false, 
        'error' => 'שגיאה בשליפת המחיר: ' . $e->getMessage()
    ]);
}

$conn->close();
?>