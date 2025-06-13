<?php
session_start();

// מניעת מטמון
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// אם אין הזמנה פעילה, הפנה להתחלה
if (!isset($_SESSION['reservation_id'])) {
    header("Location: reservation.php");
    exit;
}

?>
