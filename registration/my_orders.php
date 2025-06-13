<?php
session_start();
include '../../header.php';

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


$user_code = '';

// נסה לקבל מ-session אם קיים
if (isset($_SESSION['user_code'])) {
    $user_code = $_SESSION['user_code'];
}

// אם אין גישה למשתמש, נציג הודעה מתאימה בהמשך
else if (isset($_SESSION['username'])) {
    $user_code = $_SESSION['username'];
}


echo '<style>
    .my-orders-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
        font-family: Arial, sans-serif;
        direction: rtl;
    }
    
    .my-orders-title {
        text-align: center;
        margin-bottom: 30px;
        color: #2c3e50;
    }
    
    .order-item {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
    }
    
    .order-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.15);
    }
    
    .order-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e1e1e1;
    }
    
    .order-dates {
        font-weight: bold;
        color: #3a7bd5;
    }
    
    .order-details {
        margin-bottom: 15px;
    }
    
    .order-detail-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        padding: 5px 0;
    }
    
    .order-detail-row:nth-child(even) {
        background-color: #f2f2f2;
    }
    
    .detail-label {
        color: #666;
        font-weight: bold;
    }
    
    .detail-value {
        font-weight: bold;
    }
    
    .order-actions {
        text-align: left;
        margin-top: 10px;
    }
    
    .btn-cancel {
        background-color: #e74c3c;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .btn-cancel:hover {
        background-color: #c0392b;
    }
    
    .no-orders {
        text-align: center;
        padding: 30px;
        background-color: #f8f9fa;
        border-radius: 8px;
        color: #7f8c8d;
        margin-top: 30px;
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
    
    .alert-warning {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: bold;
    }
    
    .status-active {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-completed {
        background-color: #e2e3e5;
        color: #383d41;
    }
    
    @media (max-width: 768px) {
        .order-header {
            flex-direction: column;
        }
        
        .order-detail-row {
            flex-direction: column;
            border-bottom: 1px solid #eee;
        }
        
        .detail-value {
            margin-top: 5px;
        }
    }
</style>';

echo '<div class="my-orders-container">';
echo '<h2 class="my-orders-title">ההזמנות שלי</h2>';

// טיפול בביטול הזמנה
if (isset($_POST['cancel_reservation']) && isset($_POST['reservation_id'])) {
    $reservation_id = $_POST['reservation_id'];
    
    // וידוא שההזמנה שייכת למשתמש המחובר
    if (!empty($user_code)) {
        $check_sql = "SELECT COUNT(*) as count FROM reservation WHERE id = ? AND user_code = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $reservation_id, $user_code);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        
        if ($check_row['count'] > 0) {
            // ביטול ההזמנה
            $cancel_sql = "DELETE FROM reservation WHERE id = ?";
            $cancel_stmt = $conn->prepare($cancel_sql);
            $cancel_stmt->bind_param("i", $reservation_id);
            
            if ($cancel_stmt->execute()) {
                echo '<div class="alert alert-success">ההזמנה בוטלה בהצלחה!</div>';
                // רענון העמוד אחרי 2 שניות
                echo '<script>setTimeout(function() { window.location.href = window.location.pathname; }, 2000);</script>';
            } else {
                echo '<div class="alert alert-error">אירעה שגיאה בביטול ההזמנה.</div>';
            }
        } else {
            echo '<div class="alert alert-error">אין הרשאה לבטל הזמנה זו או שההזמנה לא קיימת.</div>';
        }
    } else {
        echo '<div class="alert alert-error">עליך להתחבר כדי לבטל הזמנות.</div>';
    }
}

// אם המשתמש לא מחובר
if (empty($user_code)) {
    echo '<div class="alert alert-warning">עליך להתחבר למערכת כדי לצפות בהזמנות שלך.</div>';
    echo '<div class="no-orders">';
    echo '<p>אין הזמנות להצגה. אנא התחבר למערכת תחילה.</p>';
    echo '</div>';
} else {
    // שליפת הזמנות לפי קוד משתמש
    $sql = "SELECT *, 
            DATEDIFF(end_date, start_date) + 1 AS total_days,
            CASE 
                WHEN end_date >= CURDATE() THEN 'active'
                ELSE 'completed'
            END AS status
            FROM reservation 
            WHERE user_code = ?
            ORDER BY start_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $status = $row['status'];
            $status_text = ($status == 'active') ? 'פעילה' : 'הושלמה';
            $status_class = ($status == 'active') ? 'status-active' : 'status-completed';
            
            // פורמט תאריכים
            $start_date = date("d/m/Y", strtotime($row['start_date']));
            $end_date = date("d/m/Y", strtotime($row['end_date']));
            $created_at = date("d/m/Y H:i", strtotime($row['created_at']));
            
            echo '<div class="order-item">';
            
            echo '<div class="order-header">';
            echo '<div class="order-dates">תאריכי שהייה: ' . $start_date . ' - ' . $end_date . '</div>';
            echo '<div>מספר הזמנה: ' . $row['id'] . '</div>';
            echo '</div>';
            
            echo '<div class="order-details">';
            
            echo '<div class="order-detail-row">';
            echo '<span class="detail-label">סטטוס:</span>';
            echo '<span class="detail-value"><span class="status-badge ' . $status_class . '">' . $status_text . '</span></span>';
            echo '</div>';
            
            echo '<div class="order-detail-row">';
            echo '<span class="detail-label">מספר ימים:</span>';
            echo '<span class="detail-value">' . $row['total_days'] . '</span>';
            echo '</div>';
            
            echo '<div class="order-detail-row">';
            echo '<span class="detail-label">תאריך יצירה:</span>';
            echo '<span class="detail-value">' . $created_at . '</span>';
            echo '</div>';
            
            echo '</div>';
            
            // הצגת כפתור ביטול רק להזמנות פעילות
            if ($status == 'active') {
                echo '<div class="order-actions">';
                echo '<form method="post" onsubmit="return confirm(\'האם אתה בטוח שברצונך לבטל הזמנה זו?\')">';
                echo '<input type="hidden" name="reservation_id" value="' . $row['id'] . '">';
                echo '<button type="submit" name="cancel_reservation" class="btn-cancel">ביטול הזמנה</button>';
                echo '</form>';
                echo '</div>';
            }
            
            echo '</div>';
        }
    } else {
        echo '<div class="no-orders">';
        echo '<p>אין הזמנות להצגה</p>';
        echo '<p>ניתן להזמין שהייה חדשה בפנסיון דרך דף "הזמנה חדשה"</p>';
        echo '</div>';
    }
}

echo '</div>';

$conn->close();
include '../../footer.php';
?>