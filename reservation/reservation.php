<?php
header('Content-Type: application/json');


$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";


if (!isset($_POST['start_date']) || !isset($_POST['end_date'])) {
    echo json_encode(['error' => 'חסר תאריך התחלה או סיום']);
    exit;
}


$start_date = DateTime::createFromFormat('d/m/Y', $_POST['start_date']);
$end_date = DateTime::createFromFormat('d/m/Y', $_POST['end_date']);

if (!$start_date || !$end_date) {
    echo json_encode(['error' => 'פורמט תאריך לא תקין']);
    exit;
}

$start_date_str = $start_date->format('Y-m-d');
$end_date_str = $end_date->format('Y-m-d');

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode(['error' => 'שגיאה בחיבור למסד הנתונים']);
    exit;
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("INSERT INTO reservation (start_date, end_date, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $start_date_str, $end_date_str);
    if (!$stmt->execute()) {
        throw new Exception("שגיאה בהכנסת הזמנה: " . $stmt->error);
    }
    $reservation_id = $conn->insert_id;
    $stmt->close();

    $current = clone $start_date;
    while ($current <= $end_date) {
        $date_str = $current->format('Y-m-d');

        $stmt = $conn->prepare("SELECT id, available_spots FROM Availability WHERE date = ?");
        $stmt->bind_param("s", $date_str);
        $stmt->execute();
        $result = $stmt->get_result();
        $availability = $result->fetch_assoc();
        $stmt->close();

        if ($availability) {
            if ($availability['available_spots'] < 1) {
                throw new Exception("אין מקומות זמינים בתאריך: $date_str");
            }

            $stmt = $conn->prepare("UPDATE Availability SET available_spots = available_spots - 1 WHERE id = ?");
            $stmt->bind_param("i", $availability['id']);
            $stmt->execute();
            $stmt->close();
        } else {
            $default_spots = 3;
            $stmt = $conn->prepare("INSERT INTO Availability (date, available_spots) VALUES (?, ?)");
            $stmt->bind_param("si", $date_str, $default_spots);
            $stmt->execute();
            $stmt->close();
        }

        $current->modify('+1 day');
    }

    $conn->commit();
    echo json_encode(['success' => true, 'reservation_id' => $reservation_id]);

} catch (Exception $e) {
    $conn->rollback();

    if (isset($reservation_id)) {
        $stmt = $conn->prepare("DELETE FROM reservation WHERE id = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>