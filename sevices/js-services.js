$(document).ready(function() {
    // מעדכן את הסיכום של המחירים כאשר המשתמש בוחר שירות
    function updateTotalPrice() {
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
        $('#hidden-total-price').val(total);
        // אם יש לפחות שירות אחד שנבחר, מוסיפים מחלקה
        if (total > 0) {
            $('#total-price').addClass('highlight');
        } else {
            $('#total-price').removeClass('highlight');
        }
    }

    // מעדכן את הסיכום של המחירים כאשר המשתמש בוחר באחד ה-checkboxים
    $('.service-select').change(updateTotalPrice);

    // הוספת אפשרות ללחוץ על כל ה-DIV כדי לסמן או לבטל את הסימון של ה-checkbox
    $('.service').click(function(e) {
        if (!$(e.target).is('input:checkbox')) {
            const checkbox = $(this).find('.service-select');
            checkbox.prop('checked', !checkbox.prop('checked'));
                updateTotalPrice();
            }
        });
    
    $('#services-form').submit(function(e) {
            const totalPrice = parseFloat($('#total-price').text());
            if (totalPrice === 0) {
                e.preventDefault();
                if (confirm('לא נבחר שום שירות. האם אתה בטוח שאתה רוצה להמשיך לסיכום ההזמנה?')) {
                    return true; // שלח את הטופס
                } else {
                    return false; // בטל את שליחת הטופס
                }
            }
        });
});
/* זה הכפתור הקודם שהיה כאן ברגע שלוחצים על הכפתור
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
*/
