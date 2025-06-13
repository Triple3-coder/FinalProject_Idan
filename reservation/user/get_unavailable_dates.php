<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode(['error' => 'שגיאת חיבור למסד הנתונים: ' . $conn->connect_error]);
    exit;
}

try {
    // שליפת כל התאריכים עם אפס מקומות זמינים או פחות
    $sql = "SELECT date FROM Availability WHERE available_spots <= 0 ORDER BY date";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("שגיאה בשליפת נתונים: " . $conn->error);
    }

    $unavailableDates = [];
    while ($row = $result->fetch_assoc()) {
        // המרה לפורמט הנדרש ללוח השנה (dd/mm/yyyy)
        $dateObj = new DateTime($row['date']);
        $formattedDate = $dateObj->format('d/m/Y');
        $unavailableDates[] = $formattedDate;
    }

    // הוספת לוג לבדיקה (אופציונלי - יכול להסיר בסביבת ייצור)
    error_log("Unavailable dates loaded: " . count($unavailableDates) . " dates");
    error_log("Dates: " . implode(', ', $unavailableDates));

    echo json_encode($unavailableDates);

} catch (Exception $e) {
    error_log("Error in get_unavailable_dates.php: " . $e->getMessage());
    echo json_encode([]);
}

$conn->close();
?>