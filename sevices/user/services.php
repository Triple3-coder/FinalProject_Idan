<?php 
include '../../header.php';
session_start();

$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    $error_message = "שגיאה בהתחברות למסד הנתונים: " . $conn->connect_error;
}

// מערך לאחסון מחירי השירותים
$servicesPrices = [
    'lodge' => 60,     // מחיר ברירת מחדל ללינה
    'toys' => 50,      // מחיר ברירת מחדל לצעצועים
    'bathing' => 80,   // מחיר ברירת מחדל למקלחות
    'photos' => 100,   // מחיר ברירת מחדל לתמונות וסרטונים
    'special_food' => 30, // מחיר ברירת מחדל לאוכל מיוחד
    'training' => 200  // מחיר ברירת מחדל לאילופים
];

// שליפת המחירים מהדאטה-בייס
try {
    $sql = "SELECT service_type, price FROM services_prices";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // עדכון מחיר השירות במערך
            $servicesPrices[$row['service_type']] = (int)$row['price'];
        }
    }
} catch (Exception $e) {
    $error_message = "שגיאה בשליפת מחירי השירותים: " . $e->getMessage();
}

$conn->close();
?>
<!--הדף הראשי של שירותים-->
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>שירותים נוספים - פנסיון כלבים</title>
    <link rel="stylesheet" href="css-services.css">
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>שירותים נוספים לפנסיון</h1>
        
        <?php if (isset($error_message)): ?>
            <div class="message error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form id="services-form">
            <div class="services">
                <!-- צעצועים -->
                <div class="service" data-price="<?php echo $servicesPrices['toys']; ?>">
                    <img src="toys.jpg" alt="צעצועים">
                    <h2>צעצועים</h2>
                    <p>צעצועים לבילוי ואימונים במהלך השהייה בפנסיון.</p>
                    <span class="price">מחיר: <?php echo $servicesPrices['toys']; ?> ₪</span>
                    <label>
                        <input type="checkbox" name="services[]" value="toys" class="service-select"> בחר שירות 
                    </label>
                </div>
                
                <!-- מקלחות -->
                <div class="service" data-price="<?php echo $servicesPrices['bathing']; ?>">
                    <img src="bathing.jpg" alt="מקלחות">
                    <h2>מקלחות</h2>
                    <p>מקלחת מקצועית עם מוצרים איכותיים לטיפוח הכלב.</p>
                    <span class="price">מחיר: <?php echo $servicesPrices['bathing']; ?> ₪</span>
                    <label>
                        <input type="checkbox" name="services[]" value="bathing" class="service-select"> בחר שירות 
                    </label>
                </div>
                
                <!-- תמונות וסרטונים -->
                <div class="service" data-price="<?php echo $servicesPrices['photos']; ?>">
                    <img src="photos_videos.jpg" alt="תמונות וסרטונים">
                    <h2>תמונות וסרטונים</h2>
                    <p>תיעוד הרגעים המרגשים של הכלב שלכם בפנסיון.</p>
                    <span class="price">מחיר: <?php echo $servicesPrices['photos']; ?> ₪</span>
                    <label>
                        <input type="checkbox" name="services[]" value="photos" class="service-select"> בחר שירות 
                    </label>
                </div>
                
                <!-- אוכל מיוחד/חטיפים -->
                <div class="service" data-price="<?php echo $servicesPrices['special_food']; ?>">
                    <img src="special_food.jpg" alt="אוכל מיוחד/חטיפים">
                    <h2>אוכל מיוחד/חטיפים</h2>
                    <p>אוכל בריא וטעים מותאם אישית עבור הכלב שלכם.</p>
                    <span class="price">מחיר: <?php echo $servicesPrices['special_food']; ?> ₪</span>
                    <label>
                        <input type="checkbox" name="services[]" value="special_food" class="service-select"> בחר שירות 
                    </label>
                </div>
                
                <!-- אילופים/אימונים -->
                <div class="service" data-price="<?php echo $servicesPrices['training']; ?>">
                    <img src="training.jpg" alt="אילופים/אימונים">
                    <h2>אילופים/אימונים</h2>
                    <p>אילופים ואימונים מקצועיים לפי צרכי הכלב בזמן השהייה.</p>
                    <span class="price">מחיר: <?php echo $servicesPrices['training']; ?> ₪</span>
                    <label>
                        <input type="checkbox" name="services[]" value="training" class="service-select"> בחר שירות 
                    </label>
                </div>
            </div>
            <!-- אזור סיכום הזמנה-->
            <div class="summary">
                <h2>סיכום מחיר</h2>
                <p>מחיר שירותים נוספים: <span id="total-price">0</span> ₪</p>
                <input type="hidden" name="total_price" id="hidden-total-price" value="0">
                <button type="button" id="checkout-button">המשך לסיכום הזמנה</button>
            </div>
        </form>
        
        <div id="loading-spinner" style="display: none;">
            <div class="spinner"></div>
            <p>מעבד את הבקשה...</p>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js-services.js"></script>
</body>
</html>