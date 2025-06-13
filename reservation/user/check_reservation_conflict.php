<?php
session_start();
header('Content-Type: application/json');

$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

// בדיקת נתוני תאריכים
if (!isset($_POST['start_date']) || !isset($_POST['end_date'])) {
    echo json_encode(['success' => false, 'error' => 'חסר תאריך התחלה או סיום']);
    exit;
}

// בדיקה אם יש כלב פעיל
if (!isset($_SESSION['active_dog_id'])) {
    echo json_encode(['success' => false, 'error' => 'לא נמצא מזהה כלב פעיל']);
    exit;
}

$dog_id = $_SESSION['active_dog_id'];

// המרת פורמט תאריכים
$start_date = DateTime::createFromFormat('d/m/Y', $_POST['start_date']);
$end_date = DateTime::createFromFormat('d/m/Y', $_POST['end_date']);

if (!$start_date || !$end_date) {
    echo json_encode(['success' => false, 'error' => 'פורמט תאריך לא תקין']);
    exit;
}

$start_date_str = $start_date->format('Y-m-d');
$end_date_str = $end_date->format('Y-m-d');

// בדיקה שהתאריכים לא בעבר
$today = new DateTime();
$today->setTime(0, 0, 0);

if ($start_date < $today) {
    echo json_encode(['success' => false, 'error' => 'לא ניתן לבחור תאריך התחלה בעבר']);
    exit;
}

if ($end_date < $today) {
    echo json_encode(['success' => false, 'error' => 'לא ניתן לבחור תאריך סיום בעבר']);
    exit;
}

if ($start_date > $end_date) {
    echo json_encode(['success' => false, 'error' => 'תאריך התחלה חייב להיות לפני תאריך הסיום']);
    exit;
}

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'שגיאה בחיבור למסד הנתונים']);
    exit;
}

try {
    // בדיקה אם יש הזמנה קיימת עבור הכלב בטווח התאריכים
    $sql = "SELECT COUNT(*) as count FROM reservation 
            WHERE dog_id = ? 
            AND (
                (start_date <= ? AND end_date >= ?) OR
                (start_date <= ? AND end_date >= ?) OR
                (start_date >= ? AND end_date <= ?)
            )";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssss", 
        $dog_id, 
        $start_date_str, $start_date_str,  // בדיקה אם התחלה בטווח
        $end_date_str, $end_date_str,      // בדיקה אם סיום בטווח
        $start_date_str, $end_date_str     // בדיקה אם הטווח הנוכחי כולל הזמנה קיימת
    );
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row['count'] > 0) {
        echo json_encode([
            'success' => true, 
            'hasConflict' => true
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'hasConflict' => false
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'שגיאה בבדיקת חפיפות: ' . $e->getMessage()
    ]);
}

$conn->close();
?>