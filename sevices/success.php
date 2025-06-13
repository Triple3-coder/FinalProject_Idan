<?php
session_start();
include '../../header.php';

// בדיקה שיש מזהה הזמנה ב-SESSION
if (!isset($_SESSION['current_reservation_id'])) {
    header('Location: reservation.php');
    exit;
}

$reservation_id = $_SESSION['current_reservation_id'];
$start_date = $_SESSION['reservation_start_date_display'] ?? '';
$end_date = $_SESSION['reservation_end_date_display'] ?? '';
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ההזמנה הושלמה בהצלחה - פנסיון כלבים</title>
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
            text-align: center;
        }
        
        h1 {
            color: #2E6A29;
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
        
        .success-icon {
            font-size: 80px;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        .success-message {
            background-color: #e8f5e9;
            padding: 25px;
            border-radius: 10px;
            margin: 30px 0;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.1);
            border: 1px solid #c8e6c9;
        }
        
        .success-message h2 {
            color: #2E6A29;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.6em;
        }
        
        .reservation-details {
            text-align: right;
            margin: 20px 0;
            padding: 0 20px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #e0e0e0;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        
        .button {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(to right, #4CAF50, #6FCF7C);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            margin-top: 20px;
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
            transition: all 0.3s ease;
        }
        
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(76, 175, 80, 0.4);
        }
        
        .print-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #f1f1f1;
            color: #333;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            margin-top: 10px;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }
        
        .print-button:hover {
            background-color: #e5e5e5;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                background: white;
            }
            
            .container {
                box-shadow: none;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✓</div>
        <h1>ההזמנה הושלמה בהצלחה</h1>
        
        <div class="success-message">
            <h2>תודה על הזמנתך בפנסיון הכלבים שלנו!</h2>
            <p>הזמנתך נקלטה במערכת ותטופל בהקדם. צוות הפנסיון ייצור איתך קשר לפני מועד ההגעה.</p>
        </div>
        
        <div class="reservation-details">
            <div class="detail-item">
                <span class="detail-label">מספר הזמנה:</span>
                <span><?php echo $reservation_id; ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">תאריך הגעה:</span>
                <span><?php echo $start_date; ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">תאריך עזיבה:</span>
                <span><?php echo $end_date; ?></span>
            </div>
        </div>
        
        <p>אישור הזמנה מפורט נשלח לכתובת המייל שלך.</p>
        
        <a href="#" onclick="window.print();" class="print-button">הדפס אישור</a>
        <br>
        <a href="index.php" class="button no-print">חזרה לדף הבית</a>
    </div>
    
    <script>
        // הסרה של אלמנטים מההדפסה
        function beforePrint() {
            document.querySelectorAll('.no-print').forEach(function(element) {
                element.style.display = 'none';
            });
        }
        
        function afterPrint() {
            document.querySelectorAll('.no-print').forEach(function(element) {
                element.style.display = '';
            });
        }
        
        window.onbeforeprint = beforePrint;
        window.onafterprint = afterPrint;
    </script>
</body>
</html>