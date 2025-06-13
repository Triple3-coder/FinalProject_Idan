<?php include '../../header.php'; ?>
<!DOCTYPE html>
<html lang="he">
<head>
    <script src="https://www.paypal.com/sdk/js?client-id=AZsMEPgdZ8ij16miVWreeoc4U3GztW24GFo6a3HwYJV3Z6xJ7Lx7RVgYNgV0yts8wEWsQvvnowgUiWhr&currency=ILS"></script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>תשלום - פנסיון כלבים</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            direction: rtl;
            background: linear-gradient(120deg, #fefcea, #f1da36);
            margin: 0;
            padding: 0;
        }
        .container {
            width: 60%;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            margin-top: 50px;
        }
        h2 {
            text-align: center;
            color: #444;
            font-size: 2em;
            margin-bottom: 20px;
        }
        .button-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .btn {
            padding: 12px 20px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin: 10px;
            transition: 0.3s;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #28a745;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #218838;
        }
        form {
            display: none;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            font-size: 16px;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        input {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
        }
        .error {
            color: red;
            font-size: 14px;
            display: none;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>בחר שיטת תשלום</h2>
       
        <div id="paypal-button-container"></div>

        <form id="creditCardForm" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="fullName">שם מלא</label>
                <input type="text" id="fullName" maxlength="50" placeholder="הכנס את שמך המלא">
                <div class="error">שדה חובה</div>
            </div>
            <div class="form-group">
                <label for="idNumber">תעודת זהות</label>
                <input type="text" id="idNumber" maxlength="9" placeholder="הכנס תעודת זהות">
                <div class="error">הכנס תעודת זהות חוקית (9 ספרות)</div>
            </div>
            <div class="form-group">
                <label for="cardNumber">מספר כרטיס אשראי</label>
                <input type="text" id="cardNumber" maxlength="16" placeholder="הכנס מספר כרטיס (16 ספרות)">
                <div class="error">הכנס מספר כרטיס תקין</div>
            </div>
            <div class="form-group">
                <label for="expiry">תוקף הכרטיס (MM/YY)</label>
                <input type="text" id="expiry" maxlength="5" placeholder="לדוגמה: 12/25">
                <div class="error">הכנס תוקף תקין</div>
            </div>
            <div class="form-group">
                <label for="cvv">3 ספרות בגב הכרטיס (CVV)</label>
                <input type="text" id="cvv" maxlength="3" placeholder="לדוגמה: 123">
                <div class="error">הכנס קוד CVV תקין</div>
            </div>
            <div class="button-container">
                <button type="submit" class="btn btn-primary">שלם</button>
            </div>
        </form>
    </div>

    <div class="footer">
        <p>תודה שבחרתם בפנסיון הכלבים שלנו! נשמח להעניק לחברכם על ארבע את הטיפול המיטבי.</p>
    </div>

    <script>
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
        paypal.Buttons({
    createOrder: function(data, actions) {
        return actions.order.create({
            purchase_units: [{
                amount: {
                    value: '50.00' // עדכן את הסכום הרצוי
                }
            }]
        });
    },
    onApprove: function(data, actions) {
        return actions.order.capture().then(function(details) {
            alert('התשלום הצליח! תודה לך, ' + details.payer.name.given_name);
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
