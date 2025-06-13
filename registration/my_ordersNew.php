<?php
//session_start();
include '../../header.php';


// נתוני חיבור לבסיס הנתונים
$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

// יצירת חיבור לבסיס הנתונים
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

/*
$host="127.0.0.1";
$port=3306;
$socket="";
$user="root";
$password="";
$dbname="itayrm_dogs_boarding_house";

$conn = new mysqli($host, $user, $password, $dbname, $port, $socket)
or die ('Could not connect to the database server' . mysqli_connect_error());
$conn->set_charset("utf8");
*/
// בדיקת חיבור
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_code = '';
// נסה לקבל מ-session אם קיים
if (isset($_SESSION['user_code'])) {
    $user_code = $_SESSION['user_code'];
} 
// אם אין גישה למשתמש, נציג הודעה מתאימה בהמשך
else if (isset($_SESSION['username'])) {
    $user_code = $_SESSION['username'];
}

// Fetch reservations
$sql = "SELECT r.id, r.start_date, r.end_date, r.total_payments, r.status, r.created_at 
        FROM reservation r 
        WHERE r.user_code = ? AND r.dog_id = ? AND r.status != ?
        ORDER BY r.start_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_code, $dog_id);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>ההזמנות שלי - פנסיון לכלבים</title>
<style>
/* עיצוב מודרני ומרשים יותר */
    body {
        font-family: 'Helvetica Neue', Arial, sans-serif;
        background-color: #f0f4f8;
        color: #333;
        line-height: 1.6;
        margin: 0;
        padding: 0;
    }
    .my-orders-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 20px;
    }
    .my-orders-title {
        text-align: center;
        font-size: 28px;
        margin-bottom: 30px;
        color: #2c3e50;
        font-weight: 700;
    }
    .order-item {
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        padding: 20px;
        margin-bottom: 25px;
        transition: box-shadow 0.3s, transform 0.3s;
    }
    .order-item:hover {
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        transform: translateY(-3px);
    }
    .order-header {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .order-dates {
        font-size: 18px;
        font-weight: bold;
        color: #34495e;
    }
    .order-number {
        font-size: 18px;
        font-weight: bold;
        background-color: #3498db;
        color: #fff;
        border-radius: 8px;
        padding: 8px 14px;
    }
    .order-details {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }
    .detail-box {
        background-color: #f9fafb;
        border-radius: 8px;
        padding: 15px;
        min-width: 200px;
    }
    .detail-box h4 {
        margin-top: 0;
        margin-bottom: 10px;
        color: #2980b9;
        font-size: 16px;
    }
    .detail-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    .detail-label {
        font-weight: bold;
        color: #555;
    }
    .detail-value {
        font-weight: normal;
    }
    .total-price {
        font-size: 20px;
        font-weight: bold;
        color: #27ae60;
        margin-top: 10px;
        text-align: right;
    }
    .buttons-container {
        display: flex;
        gap: 12px;
        margin-top: 20px;
        flex-wrap: wrap;
        justify-content: flex-start;
    }
    .btn {
        padding: 10px 16px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: background-color 0.2s;
    }
    .btn-pay {
        background-color: #27ae60;
        color: #fff;
    }
    .btn-pay:hover {
        background-color: #1e8449;
    }
    .btn-summary {
        background-color: #2980b9;
        color: #fff;
    }
    .btn-summary:hover {
        background-color: #2471a3;
    }
    .no-orders {
        text-align: center;
        padding: 40px 20px;
        font-size: 20px;
        background-color: #eef2f3;
        border-radius: 10px;
        color: #7f8c8d;
    }
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>
</head>
<body>
<div class="my-orders-container">
    <h2 class="my-orders-title">ההזמנות שלי</h2>
    <?php //echo $cancel_message; ?>

<?php if (empty($user_code)): ?>
    <div class="alert alert-warning">עליך להתחבר למערכת כדי לצפות בהזמנות שלך.</div>
    <div class="no-orders">
        <p>אין הזמנות להצגה. אנא התחבר למערכת תחילה.</p>
    </div>
<?php elseif (empty($orders)): ?>
    <div class="no-orders">
        <p>אין הזמנות להצגה</p>
        <p>ניתן להזמין שהייה חדשה בפנסיון דרך דף "הזמנה חדשה"</p>
    </div>
<?php else: ?>
    <?php foreach ($orders as $row): ?>
        <?php
            $status = $row['status'];
            $status_text = ($status == 'active') ? 'פעילה' : 'הושלמה';
            $status_class = ($status == 'active') ? 'status-active' : 'status-completed';

            // פורמט תאריכים
            $start_date = date("d/m/Y", strtotime($row['start_date']));
            $end_date = date("d/m/Y", strtotime($row['end_date']));
            $created_at = date("d/m/Y H:i", strtotime($row['created_at']));
            // סכום ההזמנה
            $total_payment = number_format($row['total_payment'], 2);
        ?>
        <div class="order-item">
            <div class="order-header">
                <div class="order-dates">תאריכי שהייה: <?php echo $start_date; ?> - <?php echo $end_date; ?></div>
                <div class="order-number">מספר הזמנה: <?php echo $row['id']; ?></div>
            </div>
            <div class="order-details">
                <div class="detail-box">
                    <h4>פרטים נוספים</h4>
                    <div class="detail-row">
                        <span class="detail-label">סטטוס:</span>
                        <span class="detail-value"><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">מספר ימים:</span>
                        <span class="detail-value"><?php echo $row['total_days']; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">תאריך יצירה:</span>
                        <span class="detail-value"><?php echo $created_at; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">סכום ההזמנה:</span>
                        <span class="detail-value"><?php echo $total_payment; ?> ש"ח</span>
                    </div>
                </div>
            </div>
            <div class="total-price">סכום כולל: <?php echo $total_payment; ?> ש"ח</div>
            <div class="buttons-container">
                <a href="pay_order.php?reservation_id=<?php echo $row['id']; ?>" class="btn btn-pay">תשלום הזמנה</a>
                <a href="reservation_summary.php?reservation_id=<?php echo $row['id']; ?>" class="btn btn-summary">הצג סיכום הזמנה</a>
            </div>
            <?php if ($status == 'active'): ?>
                <form method="post" style="margin-top:15px;" onsubmit="return confirm('האם אתה בטוח שברצונך לבטל הזמנה זו?')">
                    <input type="hidden" name="reservation_id" value="<?php echo $row['id']; ?>">
                    <button type="submit" name="cancel_reservation" class="btn btn-cancel">ביטול הזמנה</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
