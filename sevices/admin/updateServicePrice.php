<?php
include '../../header.php';
//עדכון מחירי השירות במשתמש מנהל

$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// טיפול בעדכון מחיר - אם נשלח בקשת POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $new_price = floatval($_POST['price']);
    // העדכון בטבלת המחירים
    $stmt = $conn->prepare("UPDATE services_prices SET price = ? WHERE id = ?");
    $stmt->bind_param("di", $new_price, $id);
    $stmt->execute();
    $stmt->close();
}

// שליפת כל השורות מטבלת שירותי המחירים
$result = $conn->query("SELECT * FROM services_prices");
//מערך שמכיל את כל שמות השירותים לתצוגה
$service_types = [
    'lodge' => 'לינה',
    'toys' => 'צעצועים',
    'bathing' => 'רחצה',
    'photos' => 'תמונות וסרטונים',
    'special_food' => 'אוכל מיוחד',
    'training' => 'אילוף',
    
];
?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8" />
    <title>עדכון מחירי שירותים</title>
    <!-- הוספת Bootstrap CSS לעיצוב הטבלה -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            direction: rtl;
            background-color: #f8f9fa; /* צבע רקע כללי בהיר */
            padding: 20px;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #343a40; /* צבע כותרת ראשי */
        }
        /* עיצוב לטבלה המרכזית */
        table {
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15); /* צל עדין */
            margin: auto; /* מרכוז הטבלה */
            width: auto; /* התאמת רוחב אוטומטית לתוכן */
        }
        /* עיצוב תאים וכותרות */
        th, td {
            padding: 0.75rem;
            border: 1px solid #dee2e6; /* צבע גבול עדין */
            text-align: right;
            width: 120px; /* רוחב אחיד לכל העמודות - הקטנה */
            word-break: break-word; /* שבירת מילים ארוכות */
        }
        /* עיצוב לכותרות */
        th {
            background-color: #e9ecef; /* צבע כותרת בהיר */
            color: #495057; /* צבע טקסט כותרת */
            font-size: 1rem;
        }

        thead th {
            font-weight: 600; /* הדגשת כותרות */
        }

        form {
            display: inline;
        }
        /* עיצוב לשדות קלט של מספרים */
        input[type="number"] {
            width: 80px; /* הקטנת השדה */
            padding: 0.5rem;
            border: 1px solid #ced4da; /* גבול עדין לשדה */
            border-radius: 0.25rem;
            font-size: 1rem;
        }
        /* עיצוב לכפתורים של שמירה */
        button[type="submit"] {
            padding: 0.5rem 1rem;
            background-color: #5cb85c; /* ירוק בהיר */
            color: white;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            font-weight: bold; /* טקסט מודגש */
            font-size: 1.1rem; /* הגדלת הפונט */
        }

        button[type="submit"]:hover {
            background-color: #4cae4c; /* ירוק כהה יותר במעבר עכבר */
        }

        tbody tr:hover {
            background-color: #f5f5f5; /* הבהרה במעבר עכבר */
        }
        /* מעטפת הטבלה*/
        .table-container {
            display: flex;
            justify-content: center; /* מרכוז אופקי */
        }
    </style>
</head>
<body>

<h2>עדכון מחירי שירותים</h2>

<div class="table-container">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>סוג השירות</th>
                <th>מחיר</th>
                <th>עדכן</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>   
                <!--הצגת מזהה השירות,שם שירות,מחיר-->
                <td><?php echo htmlspecialchars($row['id']); ?></td>
                <td><?php echo htmlspecialchars($service_types[$row['service_type']] ?? $row['service_type']); ?></td>
                <td><?php echo htmlspecialchars($row['price']); ?></td>
                <td><!-- לעדכון מחיר השירות -->
                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>" />
                        <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($row['price']); ?>" required />
                        <button type="submit" name="update">שמירה</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>

<?php
$conn->close();
?>
