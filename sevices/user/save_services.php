<?php
//קוד ששומר את פרטי השירותים שנבחרו
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'שיטת הבקשה אינה חוקית']);
    exit;
}

// לוג לדיבאג
error_log("save_services.php called with: " . json_encode($_POST));

// בדיקה שהתקבל מידע על שירותים נבחרים
if (!isset($_POST['selected_services']) || !isset($_POST['total_additional_price'])) {
    echo json_encode(['success' => false, 'message' => 'חסרים נתונים בבקשה']);
    exit;
}

// קבלת הנתונים מהבקשה
$selectedServicesJson = $_POST['selected_services'];
$totalAdditionalPrice = floatval($_POST['total_additional_price']);

// פרסור המחרוזת JSON
$selectedServices = json_decode($selectedServicesJson, true);

// בדיקה שהצליח
if ($selectedServices === null) {
    echo json_encode(['success' => false, 'message' => 'שגיאה בפרסור נתוני השירותים']);
    exit;
}

// שמירת המידע בסשן
$_SESSION['selected_services'] = $selectedServices;
$_SESSION['total_additional_price'] = $totalAdditionalPrice;

// לוג לדיבאג - מה נשמר בסשן
error_log("Saved to SESSION: selected_services=" . json_encode($_SESSION['selected_services']) . 
          ", total_additional_price=" . $_SESSION['total_additional_price']);

// החזרת תשובת הצלחה
echo json_encode(['success' => true, 'message' => 'השירותים נשמרו בהצלחה']);
?>