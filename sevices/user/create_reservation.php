<?php
session_start();
header('Content-Type: application/json');

// לוג לדיבאג
error_log("create_reservation.php called with POST: " . json_encode($_POST));
error_log("SESSION data: " . json_encode($_SESSION));

// בדיקה שיש נתוני הזמנה ב-SESSION
if (!isset($_SESSION['reservation'])) {
    echo json_encode(['success' => false, 'error' => 'לא נמצאו נתוני הזמנה ב-SESSION']);
    exit;
}

// בדיקה שיש מזהה כלב פעיל
if (!isset($_SESSION['active_dog_id'])) {
    echo json_encode(['success' => false, 'error' => 'לא נמצא מזהה כלב פעיל ב-SESSION']);
    exit;
}

// קבלת הנתונים מה-AJAX
$start_date = $_POST['start_date'] ?? $_SESSION['reservation']['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? $_SESSION['reservation']['end_date'] ?? '';
$total_price = $_POST['total_price'] ?? $_SESSION['reservation']['total_price'] ?? 0;

// אם אין תאריכים תקינים, מחזירים שגיאה
if (empty($start_date) || empty($end_date)) {
    echo json_encode(['success' => false, 'error' => 'תאריכי ההזמנה חסרים או לא תקינים']);
    exit;
}

$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

// המרת פורמט תאריכים מd/m/Y ל- Y-m-d
$start_date_obj = DateTime::createFromFormat('d/m/Y', $start_date);
$end_date_obj = DateTime::createFromFormat('d/m/Y', $end_date);

if (!$start_date_obj || !$end_date_obj) {
    echo json_encode(['success' => false, 'error' => 'פורמט תאריך לא תקין']);
    exit;
}
//שמירת תאריכים לשימוש עתידי בפורמט התקין לסשן
$start_date_str = $start_date_obj->format('Y-m-d');
$end_date_str = $end_date_obj->format('Y-m-d');

// שמירת התאריכים בסשן גם בתצוגה
$_SESSION['reservation_start_date'] = $start_date_str;//תאריכים למסד
$_SESSION['reservation_end_date'] = $end_date_str;
$_SESSION['reservation_start_date_display'] = $start_date;//תאריכים של קלט
$_SESSION['reservation_end_date_display'] = $end_date;

// קבלת קוד המשתמש מה-SESSION
$user_code = '';
if (isset($_SESSION['user_code'])) {
    $user_code = $_SESSION['user_code'];
}

// קבלת מזהה הכלב הפעיל מה-SESSION
$dog_id = $_SESSION['active_dog_id'];


// התחברות לבסיס הנתונים
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'שגיאה בחיבור למסד הנתונים']);
    exit;
}

try {
    $conn->begin_transaction();

    // בדיקה נוספת לחפיפות הזמנות לפני ההוספה
    //חפיפות בהזמנות עבור אותו הכלב בתאריכים המבוקשים
    $conflict_sql = "SELECT COUNT(*) as count FROM reservation 
                    WHERE dog_id = ? 
                    AND status != 'deleted'
                    AND (
                        (start_date <= ? AND end_date >= ?) OR
                        (start_date <= ? AND end_date >= ?) OR
                        (start_date >= ? AND end_date <= ?)
                    )";
    
    $conflict_stmt = $conn->prepare($conflict_sql);
    $conflict_stmt->bind_param("issssss", 
        $dog_id, 
        $start_date_str, $start_date_str,
        $end_date_str, $end_date_str,
        $start_date_str, $end_date_str
    );
    //ביצוע הבדיקה ובדיקת תוצאות
    $conflict_stmt->execute();
    $conflict_result = $conflict_stmt->get_result();
    $conflict_row = $conflict_result->fetch_assoc();
    $conflict_stmt->close();
    
    if ($conflict_row['count'] > 0) {
        throw new Exception("כבר יש הזמנה קיימת עבור הכלב בתאריכים אלו");
    }

    // בדיקת זמינות לכל תאריך בטווח
    //לולאה שעוברת על כל יום בין תאריך התחלה לסיום
    $current = clone $start_date_obj;
    while ($current <= $end_date_obj) {
        $date_str = $current->format('Y-m-d');
        //בדיקה מול זבטלת זמינויות תאריך זה
        $availability_sql = "SELECT id, available_spots FROM Availability WHERE date = ?";
        $availability_stmt = $conn->prepare($availability_sql);
        $availability_stmt->bind_param("s", $date_str);
        $availability_stmt->execute();
        $availability_result = $availability_stmt->get_result();
        $availability = $availability_result->fetch_assoc();
        $availability_stmt->close();
        //אם אין תאריך כזה מוסיםים אותו, אם יש מקומות תפוסים יהיה שגיאה
        if ($availability) {
            if ($availability['available_spots'] < 1) {
                throw new Exception("אין מקומות זמינים בתאריך: " . $current->format('d/m/Y'));
            }
        }
        //עבור לתאריך הבא, קידום הלולאה
        $current->modify('+1 day');
    }

    // הוספת ההזמנה לטבלת הזמנות
    $insert_sql = "INSERT INTO reservation (start_date, end_date, user_code, dog_id, created_at) 
                   VALUES (?, ?, ?, ?, NOW())";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("sssi", $start_date_str, $end_date_str, $user_code, $dog_id);
    
    if (!$insert_stmt->execute()) {
        throw new Exception("שגיאה בהכנסת הזמנה: " . $insert_stmt->error);
    }
    //שומר את מזהה ההזמנה שנוצר
    $reservation_id = $conn->insert_id;
    $insert_stmt->close();

    // עדכון זמינות בטבלת זמינויות - כל יום בנפרד
    $current = clone $start_date_obj;
    while ($current <= $end_date_obj) {
        $date_str = $current->format('Y-m-d');
        //בדיקת זמינות מול הטבלה
        $stmt = $conn->prepare("SELECT id, available_spots FROM Availability WHERE date = ?");
        $stmt->bind_param("s", $date_str);
        $stmt->execute();
        $result = $stmt->get_result();
        $availability = $result->fetch_assoc();
        $stmt->close();
        //אם קיים מקום פנוי מעדכנים
        if ($availability) {
            if ($availability['available_spots'] < 1) {
                throw new Exception("אין מקומות זמינים בתאריך: $date_str");
            }
            //מפחיתים מקום אחד מכל יום שנבחר
            $stmt = $conn->prepare("UPDATE Availability SET available_spots = available_spots - 1 WHERE id = ?");
            $stmt->bind_param("i", $availability['id']);
            $stmt->execute();
            $stmt->close();
        } else {//במידה ואין תאריך - יוצרים לו רשומה חדשה
            $default_spots = 49; // הורדת מקום אחד מתוך 50 מקומות
            $stmt = $conn->prepare("INSERT INTO Availability (date, available_spots) VALUES (?, ?)");
            $stmt->bind_param("si", $date_str, $default_spots);
            $stmt->execute();
            $stmt->close();
        }
        //קידום תאריך הבא
        $current->modify('+1 day');
    }

    // עדכון השירותים הנוספים שנבחרו
    if (isset($_SESSION['selected_services']) && !empty($_SESSION['selected_services'])) {
        // כל שירות נמצא במערך שבו מופיעים בו סוג ושווי השירות
        foreach ($_SESSION['selected_services'] as $service) {
            // עדכון סוג שירות ומחירו
            $service_type = $service['type'];
            $service_price = $service['price'];
            
            $update_sql = "UPDATE reservation SET {$service_type} = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $service_price, $reservation_id);
            //סוגי השירותים תואמים את שמות העמודות בטבלה
            if (!$update_stmt->execute()) {
                error_log("שגיאה בעדכון שירות {$service_type}: " . $update_stmt->error);
            }
            
            $update_stmt->close();
        }
        
        //עדכון סך התשלום עבור השירותים הנוספים
        $total_services_price = $_SESSION['total_additional_price'] ?? 0;
        //עדכון שדה של סך כל שווי השירותים
        $update_total_sql = "UPDATE reservation SET total_payments_services = ? WHERE id = ?";
        $update_total_stmt = $conn->prepare($update_total_sql);
        $update_total_stmt->bind_param("ii", $total_services_price, $reservation_id);
        if (!$update_total_stmt->execute()) {
            error_log("שגיאה בעדכון סה\"כ שירותים: " . $update_total_stmt->error);
        }
        
        $update_total_stmt->close();
    }
    
    // עדכון מחיר הלינה בטבלת ההזמנה
    $lodge_price = $_SESSION['reservation']['total_price'] ?? 0;
    $update_lodge_sql = "UPDATE reservation SET lodge = ? WHERE id = ?";
    $update_lodge_stmt = $conn->prepare($update_lodge_sql);
    $update_lodge_stmt->bind_param("ii", $lodge_price, $reservation_id);
    
    if (!$update_lodge_stmt->execute()) {
        error_log("שגיאה בעדכון מחיר לינה: " . $update_lodge_stmt->error);
    }
    
    $update_lodge_stmt->close();
    
    // חישוב ועדכון סך כל התשלומים הכוללים בטבלה
    $total_payments = $lodge_price + ($_SESSION['total_additional_price'] ?? 0);
    $update_payments_sql = "UPDATE reservation SET total_payments = ? WHERE id = ?";
    $update_payments_stmt = $conn->prepare($update_payments_sql);
    $update_payments_stmt->bind_param("ii", $total_payments, $reservation_id);
    
    if (!$update_payments_stmt->execute()) {
        error_log("שגיאה בעדכון סך תשלומים: " . $update_payments_stmt->error);
    }
    
    $update_payments_stmt->close();

    $conn->commit();
    
    // שמירת מזהה ההזמנה בסשן
    $_SESSION['current_reservation_id'] = $reservation_id;
    
    // החזרת תשובת הצלחה
    echo json_encode([
        'success' => true, 
        'reservation_id' => $reservation_id,
        'user_code' => $user_code,
        'dog_id' => $dog_id,
        'message' => 'ההזמנה נשמרה בהצלחה'
    ]);

} catch (Exception $e) {
    // ביטול השינויים במקרה של שגיאה
    $conn->rollback();

    // מחיקת ההזמנה במידה והתהליך לא הצליח - נמנעים מכפילות הזמנה
    if (isset($reservation_id)) {
        $stmt = $conn->prepare("DELETE FROM reservation WHERE id = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $stmt->close();
    }

    // רישום השגיאה ללוג
    error_log("Error in create_reservation.php: " . $e->getMessage());
    
    // החזרת תשובת שגיאה
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>