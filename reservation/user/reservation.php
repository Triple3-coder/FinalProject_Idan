<?php 
// התחלת סשן והגדרת סוג התוכן
session_start();

// בדיקה אם זו בקשת AJAX
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

// אם זו בקשת AJAX, נגדיר את סוג התוכן כ-JSON
if ($isAjax) {
    header('Content-Type: application/json');
}

// בדיקה אם הטופס נשלח
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // קבלת הנתונים מהטופס
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
    $total_price = isset($_POST['total_price']) ? $_POST['total_price'] : 0;
    
    // בדיקת תקינות הנתונים
    if (empty($start_date) || empty($end_date)) {
        $error_message = 'חסרים נתונים נדרשים';
        
        // החזרת תשובת שגיאה אם זו בקשת AJAX
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => $error_message]);
            exit;
        }
        
        // אם זו לא בקשת AJAX, נשאיר את המשתמש בדף הנוכחי עם הודעת שגיאה
        // ההודעה תוצג בהמשך הדף
    } else {
        // שמירת הנתונים ב-SESSION (רק אם הנתונים תקינים)
        $_SESSION['reservation'] = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_price' => $total_price
        ];
        
        // החזרת תשובת הצלחה אם זו בקשת AJAX
        if ($isAjax) {
            echo json_encode(['success' => true, 'message' => 'הנתונים נשמרו בהצלחה']);
            exit;
        }
        
        // אם זו לא בקשת AJAX והשמירה הצליחה, הפניה לקובץ services.php
        header('Location: ../../services/user/services.php');
        exit;
    }
}

// רק אם זה לא בקשת AJAX, נציג את הדף HTML
if (!$isAjax) {
    include '../../header.php';
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <link rel="stylesheet" href="css-reservation.css">
    <title>מסך הזמנה</title>
</head>
<body>
    <h1>הזמנת מקום בפנסיון</h1>
    <?php if (isset($error_message)): ?>
    <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>
    <form id="reservationForm" method="POST">
    <div class="reservation-form">
        <div id="dateSelection">
            <h2>בחירת תאריכים</h2>
            <div class="form-group">
                <label for="start-date">תאריך התחלה:</label>
                <input type="text" id="start-date" name="start_date" placeholder="הקלד תאריך התחלה" required>
            </div>
            <div class="form-group">
                <label for="end-date">תאריך סיום:</label>
                <input type="text" id="end-date" name="end_date" placeholder="הקלד תאריך סיום" required>
            </div>
        </div>
        
        <div id="booking-summary">
            <h3>סיכום הזמנה</h3>
            <div class="summary-item">
                <span class="summary-label">סה"כ ימים:</span>
                <span id="total-days">0 ימים</span>
            </div>
            <div class="price-breakdown" style="display: none;">
                <div class="summary-item">
                    <span class="summary-label">מחיר ליום:</span>
                    <span id="daily-price">0 ₪</span>
                </div>
                <div class="summary-item total-price-item">
                    <span class="summary-label">סה"כ לתשלום:</span>
                    <span id="total-price">0 ₪</span>
                    <input type="hidden" id="total-price-value" name="total_price" value="0">
                </div>
            </div>
        </div>

        <button id="submit" class="submit-button" type="button">שמור הזמנה והמשך</button>

        <div id="message" class="message"></div>
    </div>
    </form>

    <footer>
        © 2025 כל הזכויות שמורות לפנסיון הכלבים שלנו
    </footer>
    
    <script src="js-reservation.js"></script>
</body>
</html>
<?php
}
?>