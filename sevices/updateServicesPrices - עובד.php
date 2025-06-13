<?php
session_start();
header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];

$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

// יצירת חיבור לבסיס הנתונים
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selectedServices = isset($_POST['services']) ? $_POST['services'] : [];
    $reservation_id = isset($_SESSION['reservation_id']) ? $_SESSION['reservation_id'] : null;
    $totalPrice = 0;

    // מערך של המחירים החדשים
    $prices = [
        'toys' => 50,
        'bathing' => 80,
        'photos' => 100,
        'special_food' => 30,
        'training' => 200
    ];

    if ($reservation_id === null) {
        echo "Reservation ID is missing.";
        exit();
    }

    // פונקציה לעדכון המחירים בטבלת reservation
    function updatePricesForReservation($conn, $prices, $selectedServices, $reservation_id) {
        $sql = "UPDATE reservation 
                SET toys = ?, bathing = ?, photos = ?, special_food = ?, training = ?, total_payments_services = ?
                WHERE id = ?";  // עדכון לפי מזהה ההזמנה
        $stmt = $conn->prepare($sql);

        $total_price = 0;
        // הגדרת מחירים עבור השירותים הנבחרים
        foreach ($prices as $service => $price) {
            $$service = in_array($service, $selectedServices) ? $price : 0;
            $total_price += $$service;
        }

        $stmt->bind_param("ddddddi", $toys, $bathing, $photos, $special_food, $training, $total_price, $reservation_id);

        if (!$stmt->execute()) {
            echo "שגיאה בעדכון המחירים: " . $stmt->error;
            return false;
        }

        $stmt->close();
        return true;
    }

    if (updatePricesForReservation($conn, $prices, $selectedServices, $reservation_id)) {
        header("Location: summary.php");
        exit();
    }
    
/*
    // קריאה לפונקציה לעדכון המחירים
    if (updatePricesForReservation($conn, $prices, $selectedServices, $reservation_id)) {
        // הפניה לדף summary.php עם הפרמטרים הנדרשים
        if (isset($_POST['start_date']) && isset($_POST['end_date'])) {
            header("Location: summary.php?total=$totalPrice&start_date=" . $_POST['start_date'] . "&end_date=" . $_POST['end_date']);
            exit();
        } else {
            echo "Missing start_date or end_date parameters.";
        }
    }
*/
} else {
    // אם הגיעו לדף זה שלא דרך שליחת טופס, החזר אותם לדף השירותים
    header("Location: services.php");
    exit();
}

$conn->close();
?>

