<?php
session_start(); //   כדי לגשת ל-SESSION
header('Content-Type: application/json');

$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode(['error' => 'שגיאה בחיבור למסד הנתונים']);
    exit;
}

$user_code = '';
// בדיקת חיבור של משתמש
if (isset($_SESSION['user_code'])) {
    $user_code = $_SESSION['user_code'];
}
else if (isset($_SESSION['username'])) {
    $user_code = $_SESSION['username'];
}

// אם המשתמש לא מחובר
if (empty($user_code)) {
    echo '<div class="alert alert-warning">עליך להתחבר למערכת כדי לצפות בהזמנות שלך.</div>';
    echo '<div class="no-orders">';
    echo '<p>אין הזמנות להצגה. אנא התחבר למערכת תחילה.</p>';
    echo '</div>';
}else{

try {
        //הכנסת הזמנה חדשה למערכת
        $conn->begin_transaction();
            // בדיקת נתוני תאריכים
        if (!isset($_POST['start_date']) || !isset($_POST['end_date'])) {
            echo json_encode(['error' => 'חסר תאריך התחלה או סיום']);
            exit;
        }

        // המרת פורמט תאריכים
        $start_date = DateTime::createFromFormat('d/m/Y', $_POST['start_date']);
        $end_date = DateTime::createFromFormat('d/m/Y', $_POST['end_date']);

        $start_date_str = $start_date->format('Y-m-d');
        $end_date_str = $end_date->format('Y-m-d');
        //הכנסת הזמנה
        $stmt = $conn->prepare("INSERT INTO reservation (start_date, end_date, user_code, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $start_date_str, $end_date_str, $user_code);
        if (!$stmt->execute()) {
            throw new Exception("שגיאה בהכנסת הזמנה: " . $stmt->error);
        }

        $reservation_id = $conn->insert_id;
        $_SESSION['reservation_id'] = $reservation_id;
        $stmt->close();

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
        
        // הכנה להפניה לדף הצלחה
        echo json_encode([
            'success' => true, 
            'reservation_id' => $reservation_id,
            'user_code' => $user_code,
            'message' => 'ההזמנה נשמרה בהצלחה'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        //ביטול הזמנה שמומשה בגלל תקלה
        if (isset($reservation_id)) {
            $stmt = $conn->prepare("DELETE FROM reservation WHERE id = ?");
            $stmt->bind_param("i", $reservation_id);
            $stmt->execute();
            $stmt->close();
        }

        // עדכון טבלת הזמינויות לימים
        $current = clone $start_date;
        while ($current <= $end_date) {
            $date_str = $current->format('Y-m-d');

            // בדיקת קיום ערך קיים בטבלה
            $stmt = $conn->prepare("SELECT id, available_spots FROM Availability WHERE date = ?");
            $stmt->bind_param("s", $date_str);
            $stmt->execute();
            $result = $stmt->get_result();
            $availability = $result->fetch_assoc();
            $stmt->close();

            if ($availability) {
                $max_spots = 50; // המספר המקסימלי של הזמנות ביום
                if ($availability['available_spots'] < $max_spots) {
                    $new_spots = $availability['available_spots'] + 1;

                    $stmt = $conn->prepare("UPDATE Availability SET available_spots = ? WHERE id = ?");
                    $stmt->bind_param("ii", $new_spots, $availability['id']);
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                // אם אין שורה על תאריך זה, הוספת שורה עם מקומות מלאים
                $max_spots = 50;
                $stmt = $conn->prepare("INSERT INTO Availability (date, available_spots) VALUES (?, ?)");
                $stmt->bind_param("si", $date_str, $max_spots);
                $stmt->execute();
                $stmt->close();
            }
            $current->modify('+1 day');
        }

        echo json_encode(['error' => $e->getMessage()]);
    }
}
$conn->close();
?>