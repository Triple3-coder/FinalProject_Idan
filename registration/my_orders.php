<?php
include '../../header.php';
//דף ההזמנות של משתמש
//בנוסף הקוד מטפל באורח דינמי בביטולי הזמנות ומעדכן את מלאי המקומות בלוח הזמנות
//קיים מעבר לתשלום בדף זה

// נתוני חיבור לבסיס הנתונים
$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

// יצירת חיבור לבסיס הנתונים
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

// בדיקת חיבור
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

//הצבת הסשן של המשתמש לאחר בדיקה אם קיים
$user_code = '';
if (isset($_SESSION['user_code'])) {
    $user_code = $_SESSION['user_code'];
} else if (isset($_SESSION['username'])) {
    $user_code = $_SESSION['username'];
}

//טיפול בביטול הזמנה
//אם נשלחה קריאה לביטול בודקים אם הוא מורשה לבטל
$cancel_message = '';
if (isset($_POST['cancel_reservation']) && isset($_POST['reservation_id'])) {
    $reservation_id = $_POST['reservation_id'];
    //בודקים אם קוד משתמש קיים
    if (!empty($user_code)) {
        // מוודא שההזמנה שייכת למשתמש 
        $check_sql = "SELECT start_date, end_date FROM reservation WHERE id = ? AND user_code = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $reservation_id, $user_code);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        //בודקים אם יש הזמנה לביטול אם כן מבטלים
        if ($result && $result->num_rows > 0) {
            //מושך את התאריכים של ההזמנה שרוצים לבטל
            $row = $result->fetch_assoc();
            $start_date_str = $row['start_date'];
            $end_date_str = $row['end_date'];
            // עדכון הסטטוס לבוטלה
            $update_sql = "UPDATE reservation SET status = 'deleted' WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $reservation_id);
            if ($update_stmt->execute()) {
                $cancel_message = '<div class="alert alert-success">ההזמנה בוטלה! צוות הפנסיון ייצור איתך קשר כדי לזכות את העסקה.</div>';
                // רענן את הדף לאחר 2 שניות
                echo '<script>setTimeout(function() { window.location.href = window.location.pathname; }, 2000);</script>';
                restoreAvailabilityForCancellation($conn, $start_date_str, $end_date_str);
            }else {
                $cancel_message = '<div class="alert alert-error">אירעה שגיאה בעדכון ההזמנה.</div>';
            }
        } else {
            $cancel_message = '<div class="alert alert-error">אין הרשאה לבטל הזמנה זו או שההזמנה לא קיימת.</div>';
        }
    } else {
        $cancel_message = '<div class="alert alert-error">עליך להתחבר כדי לבטל הזמנות.</div>';
    }
    //סגירת עדכון 
    $update_stmt->close();
}
//פונקציה שמחזירה את כל הימים של ההזמנה שבוטלה
function restoreAvailabilityForCancellation($conn, $start_date_str, $end_date_str) {
    $start_date_obj = new DateTime($start_date_str);
    $end_date_obj = new DateTime($end_date_str);
    //לולאה שרצה על כל הימים של ההזמנה
    $current = clone $start_date_obj;
    while ($current <= $end_date_obj) {
        $date_str = $current->format('Y-m-d');

        // בדיקת זמינות מקום לתאריך
        $stmt = $conn->prepare("SELECT id, available_spots FROM Availability WHERE date = ?");
        $stmt->bind_param("s", $date_str);
        $stmt->execute();
        $result = $stmt->get_result();
        $availability = $result->fetch_assoc();
        $stmt->close();
        //אם קיים יום כזה מוסיפים אותו לטבלה
        if ($availability) {
            // הוספת מקום ליום הזה
            $stmt = $conn->prepare("UPDATE Availability SET available_spots = available_spots + 1 WHERE id = ?");
            $stmt->bind_param("i", $availability['id']);
            $stmt->execute();
            $stmt->close();
        } else {
            //  אם בכלל הזמנות באותו יום אז מעדכנים את היום ל 50 ימים בסך הכל כברירת מחדל
            $default_spots = 50; // מספר מקומות מקסימלי מותר
            $stmt = $conn->prepare("INSERT INTO Availability (date, available_spots) VALUES (?, ?)");
            $stmt->bind_param("si", $date_str, $default_spots);
            $stmt->execute();
            $stmt->close();
        }

        $current->modify('+1 day');
    }
}

//בודק את כל ההזמנות הקיימות וסטטוסים שלהן
//במידה וקיים משתמש מראים לו את כל ההזמנות כולל חישוב של כמות הימים
$orders = [];
if (!empty($user_code)) {
    $sql = "SELECT *, 
            DATEDIFF(end_date, start_date) + 1 AS total_days,
            CASE 
                WHEN status = 'paid' THEN 'paid'
                WHEN status = 'deleted' THEN 'deleted'
                WHEN end_date >= CURDATE() AND status != 'paid' THEN 'active'
                ELSE 'deleted'
            END AS status
            FROM reservation 
            WHERE user_code = ?
            ORDER BY start_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_code);
    $stmt->execute();
    $result = $stmt->get_result();
//במידה ויש תוצאות בטבלה אז מתעדכנת השורה עבור ההזמנות והכל נאסף לתוך מערך
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>ההזמנות שלי - פנסיון לכלבים</title>
 <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* עיצוב כללי */
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }

        h1 {
            color: #343a40;
            text-align: center;
            margin-bottom: 30px;
        }

        /* עיצוב טבלה */
        .table-container {
            overflow-x: auto; /* הוספת גלילה אופקית אם הטבלה רחבה מדי */
        }

        .table {
            margin-top: 20px;
            border-collapse: collapse;
            width: 100%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* הוספת צל לטבלה */
        }

        .table th,
        .table td {
            padding: 12px 15px;
            text-align: right;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle; /* יישור אנכי לאמצע התא */
        }

        .table th {
            background-color: #f2f4f6;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase; /* הפיכת כותרות לעליונות */
            letter-spacing: 0.05em; /* מרווח בין אותיות */
        }

        .table tbody tr:hover {
            background-color: #f5f5f5;
            transition: background-color 0.2s ease; /* מעבר חלק בעת ריחוף */
        }

        /* סטטוסים */
        .status-active {
            color: #28a745;
            font-weight: bold;
        }

        .status-paid {
            color: #007bff;
            font-weight: bold;
        }

        .status-deleted {
            color: #dc3545;
            font-weight: bold;
        }

        /* כפתורים */
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 0.9rem;
            white-space: nowrap; /* מניעת שבירת שורה בכפתור */
        }

        .btn-cancel {
            background-color: #dc3545;
            color: white;
        }

        .btn-pay {
            background-color: #28a745;
            color: white;
        }

        .btn:hover {
            opacity: 0.8;
        }

        /* הודעות */
        .alert {
            margin-bottom: 20px;
            padding: 10px 15px;
            border-radius: 5px;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeeba;
            color: #856404;
        }

        /* רספונסיביות */
        @media (max-width: 768px) {

            .table th,
            .table td {
                padding: 8px;
                font-size: 0.9rem;
            }

            .btn {
                padding: 5px 10px;
                font-size: 0.8rem;
            }
        }

        /* סגנון לפילטרים */
        .filters-section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .filters-section .form-group {
            margin-bottom: 15px;
        }

        .filters-section label {
            font-weight: 500;
            color: #495057;
        }

        .filters-section .form-control {
            border-radius: 5px;
        }

        .filters-section .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            color: #fff;
            transition: background-color 0.3s ease;
        }

        .filters-section .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
    </style>
</head>
<body>

<div class="container">
        <h1>הזמנות הפנסיון שלי</h1>

        <?php echo $cancel_message; ?>
        <!--ברירות מחדל של תצוגה -->
        <?php if (empty($user_code)): ?>
            <div class="alert alert-warning">עליך להתחבר כדי לצפות בהזמנות שלך.</div>
            <div class="text-center">
                <p>אין הזמנות להצגה. אנא התחבר למערכת תחילה.</p>
            </div>
        <?php elseif (empty($orders)): ?>
            <div class="text-center">
                <h3>אין הזמנות פעילות</h3>
                <p>עדיין לא ביצעת הזמנות. ניתן להזמין שהייה חדשה דרך דף "הזמנה חדשה".</p>
                <a href="../../reservation/user/reservation.php" class="btn-pay">הזמן עכשיו</a>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>מספר הזמנה</th>
                        <th>תאריכי שהייה</th>
                        <th>סטטוס</th>
                        <th>מספר ימים</th>
                        <th>תאריך יצירה</th>
                        <th>סכום לתשלום</th>
                        <th>פעולות</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $row): ?>
                        <tr>
                            <td>
                                <?php echo $row['id']; ?>
                            </td>
                            <td>
                                <?php echo date("d/m/Y", strtotime($row['start_date'])); ?> -
                                <?php echo date("d/m/Y", strtotime($row['end_date'])); ?>
                            </td>
                            <td>
                                <span class="status-<?php echo $row['status']; ?>">
                                    <?php
                                    if ($row['status'] == 'active') {
                                        echo 'פעילה';
                                    } elseif ($row['status'] == 'paid') {
                                        echo 'שולם';
                                    } else {
                                        echo 'בוטלה';
                                    }
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $row['total_days']; ?>
                            </td>
                            <td>
                                <?php echo date("d/m/Y H:i", strtotime($row['created_at'])); ?>
                            </td>
                            <td>
                                <?php echo number_format($row['total_payments'], 2); ?> ש״ח
                            </td>
                            <td>
                                <?php if ($row['status'] == 'active'): ?>
                                    <!--כפתור ביטול הזמנה-->
                                    <form method="post"
                                        onsubmit="return confirm('האם אתה בטוח שברצונך לבטל הזמנה זו?')"
                                        style="display: inline-block;">
                                        <input type="hidden" name="reservation_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="cancel_reservation"
                                            class="btn btn-cancel">ביטול הזמנה</button>
                                    </form>
                                    <!--כפתור תשלום הזמנה-->
                                    <form action="../../payment/payment.php" method="get"
                                        onsubmit="return confirm('ברצונך לשלם עבור הזמנה זו?')"
                                        style="display: inline-block;">
                                        <input type="hidden" name="reservation_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="total_payments" value="<?php echo $row['total_payments']; ?>">
                                        <button type="submit" name="pay_reservation" class="btn btn-pay">לתשלום
                                            הזמנה</button>
                                    </form>
                                <!--טיפול בסטטוסים של הזמנות-->
                                <?php elseif ($row['status'] == 'paid'): ?>
                                    <span class="text-success">שולם</span>
                                <?php elseif ($row['status'] == 'deleted'): ?>
                                    <span class="text-danger">בוטל</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <?php
    $conn->close();
    ?>
</body>
</html>