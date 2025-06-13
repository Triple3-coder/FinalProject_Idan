<?php
// פרטי החיבור לבסיס הנתונים
$servername = "localhost";
$username = "itayrm_ItayRam";
$password = "itay0547862155";
$dbname = "itayrm_dogs_boarding_house"; 

// פרטי ה-Client ID וה-Secret שלך ב-PayPal
$client_id = 'AZsMEPgdZ8ij16miVWreeoc4U3GztW24GFo6a3HwYJV3Z6xJ7Lx7RVgYNgV0yts8wEWsQvvnowgUiWhr';
$secret = 'undefined';

// חיבור לבסיס הנתונים
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    echo json_encode(['error' => 'שגיאת חיבור למסד הנתונים']);
    exit;
}

// שליפת הערך מתוך בסיס הנתונים
$order_id = $_GET['id'] ?? 1; // ID של ההזמנה (אפשר לשלוח כפרמטר ב-URL)
$sql = "SELECT total_payments FROM orders WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($total_payments);
$stmt->fetch();
$stmt->close();
$conn->close();

// בדיקת נתון
if (empty($total_payments)) {
    die("Order not found or total_payments is empty.");
}

// פונקציה להפקת Access Token
function get_access_token($client_id, $secret) {
    $url = "https://api-m.sandbox.paypal.com/v1/oauth2/token";
    $headers = [
        "Authorization: Basic " . base64_encode("$client_id:$secret"),
        "Content-Type: application/x-www-form-urlencoded"
    ];
    $data = "grant_type=client_credentials";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $response_data = json_decode($response, true);
    return $response_data['access_token'] ?? null;
}

// פונקציה ליצירת תשלום
function create_payment($access_token, $amount, $description) {
    $url = "https://api-m.sandbox.paypal.com/v1/payments/payment";
    $headers = [
        "Authorization: Bearer $access_token",
        "Content-Type: application/json"
    ];
    $data = [
        "intent" => "sale",
        "payer" => [
            "payment_method" => "paypal"
        ],
        "transactions" => [[
            "amount" => [
                "total" => $amount,
                "currency" => "ILS"
            ],
            "description" => $description
        ]],
        "redirect_urls" => [
            "return_url" => "https://example.com/success",
            "cancel_url" => "https://example.com/cancel"
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// הפקת Access Token
$access_token = get_access_token($client_id, $secret);

if ($access_token) {
    // יצירת תשלום
    $payment = create_payment($access_token, $total_payments, "Payment for Order #$id");
    if (isset($payment['links'])) {
        // הפניה ל-approval_url
        foreach ($payment['links'] as $link) {
            if ($link['rel'] === 'approval_url') {
                header("Location: " . $link['href']);
                exit();
            }
        }
    } else {
        echo "שגיאה ביצירת תשלום: " . json_encode($payment);
    }
} else {
    echo "שגיאה בהפקת Access Token";
}
?>

