<?php include '../../header.php'; ?>
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
    <div id="price-info" class="price-info">
    <h3>מחיר ליום בפנסיון</h3>
    <p>מחיר רגיל: <span>50</span> ש"ח</p>
    </div>
    <form action="reservationServerUpdate.php" method="POST">
    <div class="reservation-form">
        <div id="dateSelection">
            <h1>בחירת תאריכים</h1>
            <div class="form-group">
                <label for="start-date">תאריך התחלה:</label>
                <input type="text" id="start-date" name="start_date" placeholder="הקלד תאריך התחלה"required>
            </div>
            <div class="form-group">
                <label for="end-date">תאריך סיום:</label>
                <input type="text" id="end-date" name="end_date" placeholder=" הקלד תאריך סיום"required>
            </div>
        </div>
        <div id="booking-summary">
            <h3>סה"כ ימים</h3>
            <p id="total-days">0 ימים</p>
        </div>

        <div id="booking-summary">
            <h3>סה"כ לתשלום</h3>
            <p><span id="totalAmount">0</span> ש"ח</p>
        </div>
        
        <button id="submit" class="submit-button">שמור והמשך</button>
    </form>

        <div id="message"></div>
    </div>
    
    <script src="js-reservation.js"></script>
</body>
</html>
