<?php 

if (isset($_GET['reservation_id']) && isset($_GET['total_payments'])) {
    $reservation_id = $_GET['reservation_id'];
    $total_payments = $_GET['total_payments'];

} else {
    //header("Location: mainpage.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="he">
<head>
    <script src="https://www.paypal.com/sdk/js?client-id=AZsMEPgdZ8ij16miVWreeoc4U3GztW24GFo6a3HwYJV3Z6xJ7Lx7RVgYNgV0yts8wEWsQvvnowgUiWhr&currency=ILS"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>תשלום - פנסיון כלבים</title>
    <style>
        body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #89f7fe, #66a6ff);
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
}

/* מיכל מרכזי */
.container {
    max-width: 1000px;
    width: 90%;
    margin-top: 50px;
    background-color:rgb(248, 239, 185);
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* סגנון פרטי ההזמנה */
.order-details {
    width: 100%;
    background-color: #f5f5f5;
    padding: 20px;
    border-radius: 15px;
    margin-bottom: 25px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    font-size: 1.2em;
    font-weight: bold;
}

/* אריזת הפרטים - שורה של שני ה-divים */
.details-wrapper {
    display: flex;
    gap: 20px;
    width: 100%;
    justify-content: center;
    margin-bottom: 25px;
}

/* כל אחד מהקופסאות של הפרטים */
.detail-box {
    flex: 1;
    background-color: #f5f5f5;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    text-align: center;
    font-size: 1.2em;
    font-weight: 600;
}

.payment-method-container {
    display: flex;
    flex-direction: column; /* עיצוב אחיד אנכי */
    align-items: center; /* מרכז אופקי */
    width: 100%;
    max-width: 700px;
    margin: 30px auto;
    background-color: #ffffff;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* הכותרת בתוך הקונטיינר */
.payment-title {
    margin-bottom: 20px;
    font-size: 1.5em;
    color: #333;
    text-align: center;
}

#paypal-button-container {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%; /* תעשה את הכפתורים לרוחב מלא של ההורה */
    max-width: 600px; /* מגבלת רוחב מרוצה */
    margin: 30px auto; /* מרווח סביב ומרכז אופקי בצורה אוטומטית */
    padding: 20px;
    background-color: #fff; /* רקע לבן כדי להבליט את הכפתורים */
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
/* רווח כללי */
.footer {
    margin-top: 40px;
    font-size: 1em;
    color: #555;
    text-align: center;
}

    </style>
</head>
<body>

    <div class="container">
        <div class="details-wrapper">
            <div class="detail-box">
                <strong>מספר הזמנה:</strong> <span id="orderId"></span>
            </div>
            <div class="detail-box">
                <strong>סכום לתשלום:</strong> <span id="totalAmount"></span> 
            </div>
        </div>

        <div class="payment-method-container">
            <h2 class="payment-title">בחר שיטת תשלום</h2>
            <div id="paypal-button-container"></div>
        </div>
    </div>

    <div class="footer">
        <p>תודה שבחרתם בפנסיון הכלבים שלנו! נשמח להעניק לחברכם על ארבע את הטיפול המיטבי.</p>
    </div>

    <script>
        // קבלת פרטי ההזמנה
        var orderId = <?php echo json_encode($_GET['reservation_id'] ?? "לא ידוע"); ?>;
        var totalAmount = <?php echo json_encode($_GET['total_payments'] ?? "0"); ?>;

        document.getElementById("orderId").textContent = orderId;
        document.getElementById("totalAmount").textContent = totalAmount+'₪';

        function showCreditCardForm() {
            document.getElementById('creditCardForm').style.display = 'block';
        }

        function validateForm() {
            let isValid = true;
            document.querySelectorAll('.error').forEach(error => error.style.display = 'none');

            function checkField(id, regex, errorMsg) {
                let field = document.getElementById(id);
                if (!regex.test(field.value.trim())) {
                    field.nextElementSibling.textContent = errorMsg;
                    field.nextElementSibling.style.display = 'block';
                    isValid = false;
                }
            }

            checkField("fullName", /.+/, "שדה חובה");
            checkField("idNumber", /^[0-9]{9}$/, "הכנס תעודת זהות חוקית (9 ספרות)");
            checkField("cardNumber", /^[0-9]{16}$/, "הכנס מספר כרטיס תקין (16 ספרות)");
            checkField("expiry", /^(0[1-9]|1[0-2])\/([0-9]{2})$/, "פורמט לא תקין (MM/YY)");
            checkField("cvv", /^[0-9]{3}$/, "הכנס 3 ספרות בגב הכרטיס");
            
            return isValid;
        }
        //מושך את התשלום הרצוי מההזמנה 
        var totalAmount = <?php echo json_encode($total_payments); ?>;
        paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    reference_id: orderId,
                    amount: {value: totalAmount // הסכום הרצוי
                        }
                }]
            });
    },
    onApprove: function(data, actions) {
        return actions.order.capture().then(function(details) {
            //alert('התשלום הצליח! תודה לך, ' + details.payer.name.given_name);
            fetch('update_order_status.php', {
                    method: 'POST',
                    headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                reservation_id: <?php echo json_encode($reservation_id); ?>,
                status: 'paid' // מעדכן לסטטוס paid
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('התשלום הצליח! תודה לך, ' + details.payer.name.given_name);
                
                var reservationID = <?php echo json_encode($reservation_id); ?>;
                var totalPay = <?php echo json_encode($total_payments); ?>;

                setTimeout(() => {
                    window.location.href = `./success.php?id=${reservationID}&totalAmount=${totalPay}`;
                }, 1000);
            } else {
                alert('שגיאה בעדכון הסטטוס: ' + data.error);
            }
        });
    });
    
    },
    onCancel: function(data) {
        alert('התשלום בוטל.');
    },
    onError: function(err) {
        console.error('שגיאה בתהליך התשלום:', err);
        alert('אירעה שגיאה, נסה שוב.');
    }
}).render('#paypal-button-container');

</script>

</body>
</html>
