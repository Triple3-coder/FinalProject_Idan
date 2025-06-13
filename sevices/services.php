<?php include '../../header.php'; ?>
<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>פנסיון כלבים</title>
    <link rel="stylesheet" href="css-services.css">
</head>
<body>
    <div class="container">
        <h1>שירותי פנסיון כלבים</h1>
        <form id="services-form" action="updateServicesPrices.php" method="POST">
            <div class="services">
                <div class="service" data-price="50">
                    <img src="toys.jpg" alt="צעצועים">
                    <h2>צעצועים</h2>
                    <p>צעצועים לבילוי ואימונים.</p>
                    <span class="price">מחיר: 50 ש"ח</span><br>
                    <label>
                        <input type="checkbox" name="services[]" value="toys" class="service-select"> בחר שירות 
                    </label>
                </div>
                <div class="service" data-price="80">
                    <img src="bathing.jpg" alt="מקלחות">
                    <h2>מקלחות</h2>
                    <p>מקלחת מקצועית עם מוצרים איכותיים.</p>
                    <span class="price">מחיר: 80 ש"ח</span><br>
                    <label>
                    <label>
                        <input type="checkbox" name="services[]" value="bathing" class="service-select"> בחר שירות 
                    </label>
                    </label>
                </div>
                <div class="service" data-price="100">
                    <img src="photos_videos.jpg" alt="תמונות וסרטונים">
                    <h2>תמונות וסרטונים</h2>
                    <p>תיעוד הרגעים המרגשים.</p>
                    <span class="price">מחיר: 100 ש"ח</span><br>
                    <label>
                        <input type="checkbox" name="services[]" value="photos" class="service-select"> בחר שירות 
                    </label>
                </div>
                <div class="service" data-price="30">
                    <img src="special_food.jpg" alt="אוכל מיוחד/חטיפים">
                    <h2>אוכל מיוחד/חטיפים</h2>
                    <p>אוכל בריא וטעים עבור הכלבים.</p>
                    <span class="price">מחיר: 30 ש"ח</span><br>
                    <label>
                        <input type="checkbox" name="services[]" value="special_food" class="service-select"> בחר שירות 
                    </label>
                </div>
                <div class="service" data-price="200">
                    <img src="training.jpg" alt="אילופים/אימונים">
                    <h2>אילופים/אימונים</h2>
                    <p>אילופים מקצועיים בהתאם לצורך.</p>
                    <span class="price">מחיר: 200 ש"ח</span><br>
                    <label>
                        <input type="checkbox" name="services[]" value="training" class="service-select"> בחר שירות 
                    </label>
                </div>
            </div>
            <div class="summary">
                <h2>סיכום מחיר</h2>
                <p>מחיר כולל: <span id="total-price">0</span> ש"ח</p>
                <input type="hidden" name="total_price" id="hidden-total-price" value="0">
                <button id="checkout-button">המשך לסיכום הזמנה</button>
            </div>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js-services.js"></script>
</body>
</html>
