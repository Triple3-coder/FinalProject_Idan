<?php
include '../../header.php';

$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// קבלת ערכי הפילטרים
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all'; // מצב הזמנה של כל הסטטוסים
$price_sort = isset($_GET['price_sort']) ? $_GET['price_sort'] : 'asc';//מיון לפי סכום עולה,יורד
$search_term = isset($_GET['search']) ? $_GET['search'] : ''; // קבלת מונח החיפוש

// טיפול בביטול הזמנה
$cancel_message = ''; //הודעה שתוצג למשתמש במידה ויש ביטול
if (isset($_POST['cancel_reservation']) && isset($_POST['reservation_id']) && isset($_POST['user_code'])) {
    $reservation_id = $_POST['reservation_id'];//מזהה ההזמנה לביטול
    $user_code = $_POST['user_code']; // קוד המשתמש לבדיקת ביטול
    if (!empty($user_code)) {
        // מוודא שההזמנה שייכת למשתמש
        $check_sql = "SELECT start_date, end_date FROM reservation WHERE id = ? AND user_code = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $reservation_id, $user_code);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        if ($result && $result->num_rows > 0) {
            // מושך את התאריכים של ההזמנה שרוצים לבטל
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
                //קוראים לפונקציה שתחזיר את המקומות התאריכים שבוטלו
                restoreAvailabilityForCancellation($conn, $start_date_str, $end_date_str);
            } else {
                $cancel_message = '<div class="alert alert-error">אירעה שגיאה בעדכון ההזמנה.</div>';
            }
        } else {
            $cancel_message = '<div class="alert alert-error">אין הרשאה לבטל הזמנה זו או שההזמנה לא קיימת.</div>';
        }
    } else {
        $cancel_message = '<div class="alert alert-error">עליך להתחבר כדי לבטל הזמנות.</div>';
    }
}
//פונקציה שמחזירה את כל ימים שביטל המשתמש
function restoreAvailabilityForCancellation($conn, $start_date_str, $end_date_str) {
    $start_date_obj = new DateTime($start_date_str);
    $end_date_obj = new DateTime($end_date_str);
    //רצים על לולאה עבור כל התאריכים בהזמנה
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

// שאילתות סטטיסטיקה- לסיכום ולביצוע סטטיסטיקות
$sql_total_orders = "SELECT COUNT(*) AS total FROM reservation";
$sql_active_orders = "SELECT COUNT(*) AS total FROM reservation WHERE status = 'active'";
$sql_deleted_orders = "SELECT COUNT(*) AS total FROM reservation WHERE status = 'deleted'";
$sql_paid_orders = "SELECT COUNT(*) AS total FROM reservation WHERE status = 'paid'";
$sql_unpaid_orders = "SELECT COUNT(*) AS total FROM reservation WHERE status != 'paid' AND status != 'deleted'";

// שאילתה לחישוב סה"כ כלבים פעילים השבוע
$sql_active_dogs_this_week = "SELECT COUNT(*) AS total FROM reservation 
                               WHERE start_date <= CURDATE() AND end_date >= CURDATE()";

// שאילתה לחישוב סה"כ הכנסות מהזמנות לינה ששולמו
$sql_total_income = "SELECT SUM(total_payments) AS total FROM reservation WHERE status = 'paid'";

// ביצוע שאילתות והקצאה של תוצאות למשתנים מוגדרים
$total_orders = $conn->query($sql_total_orders)->fetch_assoc()['total'];
$active_orders = $conn->query($sql_active_orders)->fetch_assoc()['total'];
$deleted_orders = $conn->query($sql_deleted_orders)->fetch_assoc()['total'];
$paid_orders = $conn->query($sql_paid_orders)->fetch_assoc()['total'];
$unpaid_orders = $conn->query($sql_unpaid_orders)->fetch_assoc()['total'];
$active_dogs_this_week = $conn->query($sql_active_dogs_this_week)->fetch_assoc()['total'];
$total_income = $conn->query($sql_total_income)->fetch_assoc()['total'];

// בניית שאילתת SQL להצגת ההזמנות לפי סינון ומיון
$sql = "SELECT reservation.*, users.first_name, users.last_name, users.phone
        FROM reservation
        INNER JOIN users ON reservation.user_code = users.user_code";

//הסינונים הרלוונטים עם תנאים מוגדרים
$where_clauses = [];

//סינון לפי סטטוס
if ($status_filter != 'all') {
    $where_clauses[] = "reservation.status = '$status_filter'";
}

// תנאי החיפוש - לפי טלפון, שם פרטי, שם משפחה
if (!empty($search_term)) {
    $where_clauses[] = "(users.first_name LIKE '%$search_term%' OR users.last_name LIKE '%$search_term%' OR users.phone LIKE '%$search_term%')";
}

// איגוד כל התנאים אם קיימים כאלו לפי הבחירה
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

// מיון לפי סכום התשלום המבוקש
if ($price_sort == 'asc') {// מיון מהנמוך לגבוה
    $sql .= " ORDER BY reservation.total_payments ASC";
} elseif ($price_sort == 'desc') {// מיון מהגבוה לנמוך
    $sql .= " ORDER BY reservation.total_payments DESC";
}

$result = $conn->query($sql);

//מערך הצבעים עבור ריבועי הסטטיסטיקות
$stats_colors = [
    '#e6f7ff', // כחול בהיר
    '#e6ffe6', // ירוק בהיר
    '#ffe6e6', // אדום בהיר
    '#ffffcc', // צהוב בהיר
    '#f2e6ff', // סגול בהיר
    '#e6f2ff', // תכלת בהיר
    '#ffffe0'  // קרם
];

?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>הצגת כל ההזמנות</title>
    <!-- הוספת Bootstrap CSS לעיצוב הטבלה -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- הוספת Font Awesome לאייקונים -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* עיצוב לאזור הסטטיסטיקות */
        .stats-container {
            display: flex;
            justify-content: space-around;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .stats-box {
            width: 150px; 
            padding: 15px; 
            border: 1px solid #ccc;
            border-radius: 5px;
            text-align: center;
            height: 120px; 
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            margin: 5px; 
        }
        /* הגדלת הפונט של הכותרות */
        .stats-box h4 {
            font-size: 18px; 
        }
        .stats-box p {
            font-size: 24px; 
            font-weight: bold;
        }
        /* עיצוב לאזור הפילטרים */
        .filter-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }
        .filter-box {
            width: 200px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin: 0 10px; 
        }
        .filter-label {
            direction: rtl;
            text-align: right;
            float: right;
            font-size: 18px; 
        }
        .form-control {
            font-size: 16px; 
        }
        /* צבע רקע לפילטר סטטוס */
        .status-filter {
            background-color: #f0f5ff; 
        }
        /* צבע רקע לפילטר מיון מחיר */
        .price-sort-filter {
            background-color: #fff0f5; 
        }
        /* עיצוב לכותרת הראשית */
        .main-title {
            text-align: center;
            margin-bottom: 30px;
        }
        /* עיצוב לאזור החיפוש */
        .search-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }
        .search-form {
            width: 80%;
            max-width: 600px;
            display: flex;
        }
        .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-left: 10px;
        }
        .search-button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }
        .clear-button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background-color: #6c757d;
            color: white;
            cursor: pointer;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- כותרת ראשית מעוצבת -->
        <h1 class="main-title">כל ההזמנות</h1>

        <!-- אזור סטטיסטיקות -->
        <div class="stats-container">
            <!-- כל קופסה מייצגת מדד שונה -->
            <div class="stats-box" style="background-color: <?php echo $stats_colors[0]; ?>">
                <h4><i class="fas fa-list"></i> מספר ההזמנות</h4>
                <p><?php echo $total_orders; ?></p>
            </div>
            <div class="stats-box" style="background-color: <?php echo $stats_colors[1]; ?>">
                <h4><i class="fas fa-check"></i> הזמנות פעילות</h4>
                <p><?php echo $active_orders; ?></p>
            </div>
            <div class="stats-box" style="background-color: <?php echo $stats_colors[2]; ?>">
                <h4><i class="fas fa-trash"></i> הזמנות שבוטלו</h4>
                <p><?php echo $deleted_orders; ?></p>
            </div>
            <div class="stats-box" style="background-color: <?php echo $stats_colors[3]; ?>">
                <h4><i class="fas fa-dollar-sign"></i> הזמנות ששולמו</h4>
                <p><?php echo $paid_orders; ?></p>
            </div>
            <div class="stats-box" style="background-color: <?php echo $stats_colors[4]; ?>">
                <h4><i class="fas fa-exclamation-triangle"></i> הזמנות שלא שולמו</h4>
                <p><?php echo $unpaid_orders; ?></p>
            </div>
            <div class="stats-box" style="background-color: <?php echo $stats_colors[5]; ?>">
                <h4><i class="fas fa-dog"></i> סה"כ כלבים בפנסיון השבוע</h4>
                <p><?php echo $active_dogs_this_week; ?></p>
            </div>
            <div class="stats-box" style="background-color: <?php echo $stats_colors[6]; ?>">
                <h4><i class="fas fa-coins"></i> הכנסות מהזמנות לינה</h4>
                <p><?php echo number_format($total_income, 0); ?> ₪</p>
            </div>
        </div>

        <!-- אזור חיפוש -->
        <div class="search-container">
            <form method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="חפש לפי שם או טלפון" value="<?php echo $search_term; ?>">
                <button type="submit" class="search-button">חפש</button>
                <?php if (!empty($search_term)): ?>
                    <a href="?" class="clear-button">נקה</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- אזור פילטרים -->
        <div class="filter-container">
            <!-- פילטר לסטטוס -->
            <div class="filter-box status-filter">
                <form method="GET">
                    <label for="status" class="filter-label">סטטוס:</label>
                    <select name="status" id="status" class="form-control" onchange="this.form.submit()">
                        <option value="all" <?php if($status_filter == 'all') echo 'selected'; ?>>כל הסטטוסים</option>
                        <option value="active" <?php if($status_filter == 'active') echo 'selected'; ?>>פעיל</option>
                        <option value="paid" <?php if($status_filter == 'paid') echo 'selected'; ?>>שולם</option>
                        <option value="deleted" <?php if($status_filter == 'deleted') echo 'selected'; ?>>בוטל</option>
                    </select>
                </form>
            </div>
            <!-- פילטר מיון סכום -->
            <div class="filter-box price-sort-filter">
                <form method="GET">
                    <label for="price_sort" class="filter-label">מיון לפי סכום:</label>
                    <select name="price_sort" id="price_sort" class="form-control" onchange="this.form.submit()">
                        <option value="asc" <?php if($price_sort == 'asc') echo 'selected'; ?>>נמוך לגבוה</option>
                        <option value="desc" <?php if($price_sort == 'desc') echo 'selected'; ?>>גבוה לנמוך</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- הצגת הודעה במידה וחוזר ביטול הזמנה -->
        <?php echo $cancel_message; ?>

        <!-- טבלה להצגת ההזמנות -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>מספר הזמנה</th>
                    <th>קוד משתמש</th>
                    <th>שם משתמש</th>
                    <th>מספר טלפון</th>
                    <th>תאריך התחלה</th>
                    <th>תאריך סיום</th>
                    <th>סטטוס</th>
                    <th>תשלום כולל</th>
                    <th>תאריך יצירה</th>
                    <th>ביטול הזמנה</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    // הצגת נתונים של כל שורה
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row["id"]. "</td>";
                        echo "<td>" . $row["user_code"]. "</td>";
                        echo "<td>" . $row["first_name"] . " " . $row["last_name"] . "</td>";
                        echo "<td>" . $row["phone"]. "</td>";
                        echo "<td>" . date('d/m/Y', strtotime($row["start_date"])). "</td>";
                        echo "<td>" . date('d/m/Y', strtotime($row["end_date"])). "</td>";
                        echo "<td>" . $row["status"]. "</td>";
                        echo "<td>" . $row["total_payments"]. "</td>";
                        echo "<td>" . date('d/m/Y', strtotime($row["created_at"])). "</td>";
                        // כפתור ביטול הזמנה - הסתרה להזמנות שבוטלו
                        echo "<td>";
                        if ($row["status"] != 'deleted') {
                            echo "<form method='post' onsubmit='return confirmCancel(\"" . $row["id"] . "\", \"" . $row["first_name"] . " " . $row["last_name"] . "\", \"" . $row["total_payments"] . "\")'>
                                        <input type='hidden' name='reservation_id' value='" . $row["id"] . "'>
                                        <input type='hidden' name='user_code' value='" . $row["user_code"] . "'>
                                        <button type='submit' name='cancel_reservation' class='btn btn-danger'>ביטול</button>
                                    </form>";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='10'>אין הזמנות להצגה</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>