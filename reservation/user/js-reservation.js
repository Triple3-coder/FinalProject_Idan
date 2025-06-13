$(document).ready(function() {
    const dateFormat = 'dd/mm/yy';
    let unavailableDates = [];
    let dailyRate = 1; //ברירת מחדל אם אין קריאה לשרת

    /*
    //משיכת מחיר לינה
    $(document).ready(function() {
        $.getJSON('getLodgePrice.php', function(data) {
                if (data && typeof data.price !== 'undefined') {
                    dailyRate = data.price;
                    $('#lodge_price').text(dailyRate + ' ש"ח');
                    }
            });
    });
*/
    $('#start-date, #end-date').datepicker({
        dateFormat: dateFormat,
        minDate: 0,
        onSelect: function(selectedDate) {
            const startDate = $('#start-date').datepicker('getDate');
            const endDate = $('#end-date').datepicker('getDate');
            
            if (startDate && endDate && startDate > endDate) {
                alert("תאריך היציאה חייב להיות לאחר תאריך הכניסה.");
                $('#end-date').val('');
            } else {
                updateBookingSummary();
            }
        }
    });

    function updateBookingSummary() {
        const startDate = $('#start-date').datepicker('getDate');
        const endDate = $('#end-date').datepicker('getDate');

        if (startDate && endDate) {
            const days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
            const totalAmount = days * dailyRate; // משתמש בערך הסטטי
            $('#total-days').text(days);
            $('#totalAmount').text(totalAmount);
        } else {
            $('#total-days').text('0');
            $('#totalAmount').text('0');
        }
    }
    //בדיקת התאריכים הזמינים
    $.getJSON('get_unavailable_dates.php', function(data) {
        unavailableDates = data;
        $("#start-date, #end-date").datepicker("option", "beforeShowDay", function(date) {
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const formatted = `${day}/${month}/${year}`;
            if (unavailableDates.includes(formatted)) {
                return [false, "unavailable-date", "תאריך תפוס"];
            }
            return [true, "", ""];
        });
    });

    $('#submit').on('click', function(e) {
        e.preventDefault();
        const startDate = $('#start-date').val();
        const endDate = $('#end-date').val();


        if (startDate && endDate) {
            $.post('reservationServerUpdate.php', {
                start_date: startDate,
                end_date: endDate,
            }, function(response) {
                if (response.success) {
                    window.location.href = "../../services/user/services.php";
                } else {
                    $('#message').text(response.error || "שגיאה בלתי צפויה");
                }
            }, 'json');
        } else {
            $('#message').text('אנא מלא את כל השדות.');
        }
    });

});
