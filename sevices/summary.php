<?php
// התחלת סשן בתחילת הקובץ - חשוב!
session_start();
include '../../header.php';

// בדיקה שיש נתוני הזמנה ב-SESSION
if (!isset($_SESSION['reservation']) || !isset($_SESSION['selected_services'])) {
    // אם אין נתונים, חזרה לדף הראשי
    header('Location: reservation.php');
    exit;
}

// קבלת נתוני ההזמנה מה-SESSION
$reservation = $_SESSION['reservation'];
$selectedServices = $_SESSION['selected_services'];
$totalAdditionalPrice = $_SESSION['total_additional_price'] ?? 0;
$totalLodgePrice = $reservation['total_price'] ?? 0;
$grandTotal = $totalLodgePrice + $totalAdditionalPrice;

// המרת תאריכים לתצוגה
$startDate = $reservation['start_date'] ?? '';
$endDate = $reservation['end_date'] ?? '';
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>סיכום הזמנה - פנסיון כלבים</title>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Rubik', Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            direction: rtl;
        }
        
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
        }
        
        h1 {
            color: #2E6A29;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.2em;
            position: relative;
            padding-bottom: 15px;
        }
        
        h1:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(to right, #4CAF50, #6FCF7C);
            border-radius: 2px;
        }
        
        .summary-section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .summary-section h2 {
            color: #2E6A29;
            margin-top: 0;
            font-size: 1.5em;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .total-section {
            background-color: #e8f5e9;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.1em;
            padding: 10px 0;
        }
        
        .grand-total {
            font-size: 1.4em;
            font-weight: bold;
            color: #2E6A29;
            border-top: 2px solid #4CAF50;
            padding-top: 15px;
            margin-top: 10px;
        }
        
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .button {
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 1em;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        
        .primary-button {
            background: linear-gradient(to right, #4CAF50, #6FCF7C);
            color: white;
            flex-grow: 2;
            margin-right: 10px;
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
        }
        
        .secondary-button {
            background-color: #f1f1f1;
            color: #555;
            border: 1px solid #ddd;
            flex-grow: 1;
        }
        
        .primary-button:hover {
            background: linear-gradient(to right, #3d9740, #5fb86a);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(76, 175, 80, 0.4);
        }
        
        .secondary-button:hover {
            background-color: #e5e5e5;
        }
        
        .empty-message {
            text-align: center;
            color: #888;
            font-style: italic;
            padding: 15px;
        }
        
        #loading-message {
            display: none;
            text-align: center;
            padding: 15px;
            margin-top: 20px;
            background-color: #fff9c4;
            border-radius: 8px;
            border: 1px solid #ffd600;
        }
        
        .message {
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
        }
        
        .success-message {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .error-message {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-left: 10px;
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: #4CAF50;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container">
        <h1>סיכום הזמנה</h1>
        
        <div id="result-message"></div>
        
        <div class="summary-section">
            <h2>פרטי הלינה</h2>
            <div class="summary-item">
                <span>תאריך הגעה:</span>
                <span><?php echo $startDate; ?></span>
            </div>
            <div class="summary-item">
                <span>תאריך עזיבה:</span>
                <span><?php echo $endDate; ?></span>
            </div>
            <div class="summary-item">
                <span>מחיר לינה:</span>
                <span><?php echo number_format($totalLodgePrice, 0); ?> ₪</span>
            </div>
        </div>
        
        <div class="summary-section">
            <h2>שירותים נוספים</h2>
            
            <?php if (empty($selectedServices)): ?>
                <div class="empty-message">לא נבחרו שירותים נוספים</div>
            <?php else: ?>
                <?php foreach ($selectedServices as $service): ?>
                    <div class="summary-item">
                        <span><?php 
                            switch ($service['type']) {
                                case 'toys':
                                    echo 'צעצועים';
                                    break;
                                case 'bathing':
                                    echo 'מקלחות';
                                    break;
                                case 'photos':
                                    echo 'תמונות וסרטונים';
                                    break;
                                case 'special_food':
                                    echo 'אוכל מיוחד/חטיפים';
                                    break;
                                case 'training':
                                    echo 'אילופים/אימונים';
                                    break;
                                default:
                                    echo $service['type'];
                            }
                        ?></span>
                        <span><?php echo number_format($service['price'], 0); ?> ₪</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="total-section">
            <div class="total-row">
                <span>מחיר לינה:</span>
                <span><?php echo number_format($totalLodgePrice, 0); ?> ₪</span>
            </div>
            <div class="total-row">
                <span>מחיר שירותים נוספים:</span>
                <span><?php echo number_format($totalAdditionalPrice, 0); ?> ₪</span>
            </div>
            <div class="total-row grand-total">
                <span>סה"כ לתשלום:</span>
                <span><?php echo number_format($grandTotal, 0); ?> ₪</span>
            </div>
        </div>
        
        <div id="loading-message">מעבד את ההזמנה... <div class="spinner"></div></div>
        
        <div class="action-buttons">
            <a href="services.php" class="button secondary-button">חזרה לבחירת שירותים</a>
            <button id="confirm-button" class="button primary-button">אשר הזמנה</button>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            // טיפול בלחיצה על כפתור אישור ההזמנה
            $('#confirm-button').on('click', function(e) {
                e.preventDefault();
                
                // הצגת הודעת טעינה
                $('#loading-message').show();
                $('#confirm-button').prop('disabled', true);
                
                // שליחת הבקשה לשרת
                $.ajax({
                    type: 'POST',
                    url: 'create_reservation.php',
                    data: {
                        start_date: '<?php echo $startDate; ?>',
                        end_date: '<?php echo $endDate; ?>',
                        total_price: <?php echo $totalLodgePrice; ?>,
                        total_services_price: <?php echo $totalAdditionalPrice; ?>
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#loading-message').hide();
                        
                        if (response.success) {
                            // הצגת הודעת הצלחה
                            $('#result-message').html('<div class="message success-message">ההזמנה נשמרה בהצלחה! מספר ההזמנה: ' + response.reservation_id + '</div>');
                            
                            // הפניה לדף הצלחה אחרי 2 שניות
                            setTimeout(function() {
                                window.location.href = "success.php";
                            }, 2000);
                        } else {
                            // הצגת הודעת שגיאה
                            $('#result-message').html('<div class="message error-message">אירעה שגיאה: ' + response.error + '</div>');
                            $('#confirm-button').prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#loading-message').hide();
                        $('#result-message').html('<div class="message error-message">אירעה שגיאה בשרת: ' + error + '</div>');
                        $('#confirm-button').prop('disabled', false);
                        console.error('AJAX Error:', status, error);
                        console.error('Response Text:', xhr.responseText);
                    }
                });
            });
        });
    </script>
</body>
</html>