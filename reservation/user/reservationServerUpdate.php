<?php
session_start(); //   כדי לגשת ל-SESSION
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");


// קבלת קוד המשתמש מה-SESSION
$user_code = '';

// נסה לקבל מ-session אם קיים
if (isset($_SESSION['user_code'])) {
    $user_code = $_SESSION['user_code'];
} 

// בדיקת נתוני תאריכים
if (!isset($_POST['start_date']) || !isset($_POST['end_date'])) {
    echo json_encode(['error' => 'חסר תאריך התחלה או סיום']);
    exit;
}


// המרת פורמט תאריכים
$start_date = DateTime::createFromFormat('d/m/Y', $_POST['start_date']);
$end_date = DateTime::createFromFormat('d/m/Y', $_POST['end_date']);

if (!$start_date || !$end_date) {
    echo json_encode(['error' => 'פורמט תאריך לא תקין']);
    exit;
}

$start_date_str = $start_date->format('Y-m-d');
$end_date_str = $end_date->format('Y-m-d');

if (!$conn->connect_error) {
    // אם יש הזמנה ב־Session, נבדוק אותה קודם
    if (isset($_SESSION['reservation_id'])) {
        // טען את ההזמנה מהמסד
        $stmt = $conn->prepare("SELECT status FROM reservation WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['reservation_id']);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($res && $res['status'] == 'Active') {
            // הזמנה קיימת, פשוט נעבור אליה
            //header("Location: ../../services/user/services.php");
            exit;
        } else {
            // הזמנה לא במצב פעיל, אפשר למחוק או לחדש
            unset($_SESSION['reservation_id']);
        }
    }

    // אם אין הזמנה קיימת או לא פעילה, ניצור חדשה
    $conn->begin_transaction();
    $stmt2 = $conn->prepare("INSERT INTO reservation (start_date, end_date, user_code, status, created_at) VALUES (?, ?, ?, 'Active', NOW())");
    $stmt2->bind_param("sss", $start_date_str, $end_date_str, $_SESSION['user_code']);
    if (!$stmt2->execute()) {
        $conn->rollback();
        echo json_encode(['error' => 'שגיאה ביצירת ההזמנה']);
        exit;
    }
    $reservation_id = $conn->insert_id;
    $_SESSION['reservation_id'] = $reservation_id;
    $stmt2->close();

                // עדכון זמינות - כל יום בנפרד
                $current = clone $start_date;
                while ($current <= $end_date) {
                    $date_str = $current->format('Y-m-d');
    
                    $stmt = $conn->prepare("SELECT id, available_spots FROM Availability WHERE date = ?");
                    $stmt->bind_param("s", $date_str);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $availability = $result->fetch_assoc();
                    $stmt->close();
    
                    if ($availability) {
                        if ($availability['available_spots'] < 1) {
                            throw new Exception("אין מקומות זמינים בתאריך: $date_str");
                        }
    
                        $stmt = $conn->prepare("UPDATE Availability SET available_spots = available_spots - 1 WHERE id = ?");
                        $stmt->bind_param("i", $availability['id']);
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        $default_spots = 49; // הורדת מקום אחד מהמספר המקורי של 50
                        $stmt = $conn->prepare("INSERT INTO Availability (date, available_spots) VALUES (?, ?)");
                        $stmt->bind_param("si", $date_str, $default_spots);
                        $stmt->execute();
                        $stmt->close();
                    }
    
                    $current->modify('+1 day');
                }

    $conn->commit();
    // עכשיו מפנה לדף הבא
    //header("Location: ../../services/user/services.php");
    exit;
} else {
    echo json_encode(['error' => 'שגיאה בחיבור למסד הנתונים']);
}

$conn->close();
?>