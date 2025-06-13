<?php
start(); //   כדי לגשת ל-SESSION
header('Content-Type: application/json');

$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

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

// קבלת קוד המשתמש מה-SESSION
$user_code = '';

// נסה לקבל מ-session אם קיים
if (isset($_SESSION['user_code'])) {
    $user_code = $_SESSION['user_code'];
} 


$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode(['error' => 'שגיאה בחיבור למסד הנתונים']);
    exit;
}

try {
    $conn->begin_transaction();

    // לבדוק אם קיימת הזמנה פעילה
    $stmt = $conn->prepare("SELECT * FROM reservation WHERE user_code= ? AND status = 'active' LIMIT 1");
    $stmt->bind_param("s", $user_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_order = $result->fetch_assoc();

    if ($existing_order) {
        // קיימת הזמנה פעילה
        // שמור את ה-ID שלה ב-SESSION כדי לעקוב
        $_SESSION['active_order_id'] = $existing_order['id'];
        header("Location: ../../services/user/services.php");
        // הודעה על המשך הזמנה
        echo json_encode([
            'status' => 'continuing',
            'message' => 'אתה ממשיך בהזמנה קיימת.',
            'order_id' => $existing_order['id']
        ]);
        exit;
    } else {
            $stmt = $conn->prepare("INSERT INTO reservation (start_date, end_date, user_code, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sss", $start_date_str, $end_date_str, $user_code);
            if (!$stmt->execute()) {
                throw new Exception("שגיאה בהכנסת הזמנה: " . $stmt->error);
            }

            $reservation_id = $conn->insert_id;
            $_SESSION['reservation_id'] = $reservation_id;
            $stmt->close();

            //עדכון מחיר הלינה הכולל
            $api_url = 'getLodgePrice.php';
            $response = file_get_contents($api_url);
            $data = json_decode($response, true);
            $lodge_price = $data['price'] ?? 0;

            $diff = $end_date->diff($start_date);
            $days = $diff->days + 1; // כולל גם את היום הראשון
            error_log("עדכון ל־" . $total_price);
            $total_price = $days * $lodge_price;
            error_log("עדכון ל־" . $total_price);
            // עדכון המחיר בטבלה במסד נתונים
            $sql = "UPDATE reservation SET lodge = ? WHERE id = ?";
            $stmt2 = $conn->prepare($sql);
            $stmt2->bind_param("di", $total_price, $reservation_id);
            $$result = $stmt2->execute();
            if (!$result) {
                error_log("שגיאה ב־execute: " . $stmt2->error);
            }
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
            
            // הכנה להפניה לדף הצלחה
            echo json_encode([
                'success' => true, 
                'reservation_id' => $reservation_id,
                'user_code' => $user_code,
                'message' => 'ההזמנה נשמרה בהצלחה'
            ]);
        }
} catch (Exception $e) {
    $conn->rollback();

    if (isset($reservation_id)) {
        $stmt = $conn->prepare("DELETE FROM reservation WHERE id = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>