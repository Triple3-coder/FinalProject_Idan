$(document).ready(function() {
    // מעדכן את הסיכום של המחירים כאשר המשתמש בוחר שירות
    $('.service-select').change(function() {
        let total = 0;

        // עבור כל שירות שנבחר, מוסיף את המחיר לסיכום
        $('.service-select:checked').each(function() {
            const price = parseFloat($(this).closest('.service').data('price'));
            // בדיקה אם מספר תקין
            if (!isNaN(price)) {
                total += price;
            }
        });

        // מציג את המחיר הכולל
        $('#total-price').text(total);
        // אם יש לפחות שירות אחד שנבחר, מוסיפים מחלקה
        if (total > 0) {
            $('#total-price').addClass('highlight');
        } else {
            $('#total-price').removeClass('highlight');
        }
    });

        // הוספת פונקציה ללחיצה על כפתור ההזמנה
        $('#checkout-button').click(function() {
            if (parseFloat($('#total-price').text()) > 0) {
                window.location.href = 'summary.html'; // העברת למשתמש לדף סיכום
            } else {
                // אם אין סכום, הקפצת הודעת אישור
                if (confirm('לא נבחר שום שירות. האם אתה בטוח שאתה רוצה להמשיך לסיכום ההזמנה?')) {
                    window.location.href = 'summary.html'; // עובר לדף סיכום
                }
            }
        });

});