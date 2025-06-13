<?php
$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// קבלת תאריכים מהטופס
$start_date = $_POST["start_date"];
$end_date = $_POST["end_date"];

// פונקציה ליצירת רשימת תאריכים בין שני תאריכים
function createDateRangeArray($strDateFrom,$strDateTo) {
    $aryRange = [];

    $iDateInterval = new DateInterval('P1D');

    $iDateFrom = new DateTime($strDateFrom);
    $iDateTo = new DateTime($strDateTo);

    $iDateTo->modify('+1 day');

    $iDatePeriod = new DatePeriod($iDateFrom, $iDateInterval, $iDateTo);

    foreach ($iDatePeriod as $oDate) {
        $aryRange[] = $oDate->format("Y-m-d");
    }

    return $aryRange;
}

// יצירת רשימת תאריכים
$date_range = createDateRangeArray($start_date, $end_date);

// אתחול משתנה לבדיקת זמינות
$all_dates_available = true;

// בדיקה האם יש מקומות פנויים בכל התאריכים
foreach ($date_range as $date) {
    $sql = "SELECT available_spots FROM Availability WHERE date = '$date'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row["available_spots"] <= 0) {
            echo "אין מקומות פנויים בתאריך " . $date . "<br>";
            $all_dates_available = false;
        }
    } else {
        // אם התאריך לא קיים, נתייחס אליו כאילו אין מקומות פנויים
        echo "אין מקומות פנויים בתאריך " . $date . " (תאריך לא קיים במערכת)<br>";
        $all_dates_available = false;
    }
}

// אם יש מקומות פנויים בכל התאריכים, המשך בתהליך ההזמנה
if ($all_dates_available) {
    // הכנסת הזמנה לטבלת reservation
    $sql = "INSERT INTO reservation (start_date, end_date, created_at) VALUES ('$start_date', '$end_date', NOW())";

    if ($conn->query($sql) === TRUE) {
        $reservation_id = $conn->insert_id; // קבלת ה-ID של ההזמנה החדשה

        // עדכון טבלת Availability
        foreach ($date_range as $date) {
            // בדיקה האם התאריך קיים
            $sql = "SELECT available_spots FROM Availability WHERE date = '$date'";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                // התאריך קיים, עדכן את available_spots
                $sql = "UPDATE Availability SET available_spots = available_spots - 1 WHERE date = '$date'";
                $conn->query($sql);
            } else {
                // התאריך לא קיים, הוסף אותו עם 49 מקומות
                $sql = "INSERT INTO Availability (date, available_spots) VALUES ('$date', 49)";
                $conn->query($sql);
            }
        }

        echo "הזמנה בוצעה בהצלחה! מספר הזמנה: " . $reservation_id;
    } else {
        echo "שגיאה בהוספת הזמנה: " . $conn->error;
    }
} else {
    echo "לא ניתן לבצע את ההזמנה עקב חוסר מקומות פנויים בתאריכים המבוקשים.";
}
}
$conn->close();

?>