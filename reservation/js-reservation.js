$(document).ready(function () {
    const dateFormat = 'dd/mm/yy';
    let unavailableDates = [];

    // הגדרת Datepicker
    $('#start-date, #end-date').datepicker({
        dateFormat: dateFormat,
        onSelect: function () {
            const startDate = $('#start-date').val();
            const endDate = $('#end-date').val();

            // עדכון מספר הימים והסכום הכולל
            updateTotalDaysAndAmount(startDate, endDate);
        }
    });

    // שליחה לבדיקת תאריכים זמינים
    $.getJSON('get_unavailable_dates.php', function (data) {
        unavailableDates = data;
        $("#start-date, #end-date").datepicker("option", "beforeShowDay", function (date) {
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

    // שליחה של הימים והמשך הזמנה
    $('#submit').on('click', function (e) {
        e.preventDefault();
        const startDate = $('#start-date').val();
        const endDate = $('#end-date').val();
        if (startDate && endDate) {
            if (!isValidDateRange(startDate, endDate)) {
                $('#message').text('תאריך הסיום חייב להיות אחרי תאריך ההתחלה.');
                return;
            }
            $.post('reservation.php', {
                start_date: startDate,
                end_date: endDate
            }, function (response) {
                if (response.success) {
                    window.location.href = "../services/services.html";
                } else {
                    $('#message').text(response.error || "שגיאה בלתי צפויה");
                }
            }, 'json');
        } else {
            $('#message').text('אנא מלא את כל השדות.');
        }
    });

    function isValidDateRange(startDate, endDate) {
        if (!startDate || !endDate) return false;
        const start = new Date(startDate.split('/').reverse().join('-'));
        const end = new Date(endDate.split('/').reverse().join('-'));
        return start <= end;
    }

    // פונקציה משולבת לעדכון מספר הימים והסכום הכולל
    function updateTotalDaysAndAmount(startDate, endDate) {
        const dailyRate = 50;
        let daysDiff = 0;

        // אם אין תאריכים, אפס את התצוגה
        if (!startDate || !endDate) {
            $('#total-days').text('0 ימים');
            $('#totalAmount').text('0 ש"ח');
            return;
        }

        // המרת התאריכים לפורמט Date
        const startParts = startDate.split('/');
        const endParts = endDate.split('/');
        const start = new Date(startParts[2], startParts[1] - 1, startParts[0]);
        const end = new Date(endParts[2], endParts[1] - 1, endParts[0]);

        // בדיקת תקינות התאריכים
        if (isNaN(start.getTime()) || isNaN(end.getTime())) {
            $('#total-days').text('0 ימים');
            $('#totalAmount').text('0 ש"ח');
            return;
        }

        // חישוב מספר הימים
        const timeDiff = end - start;
        daysDiff = Math.floor(timeDiff / (1000 * 60 * 60 * 24));

        if (daysDiff < 0) {
            $('#total-days').text('0 ימים');
            $('#totalAmount').text('0 ש"ח');
            $('#message').text('תאריך הסיום חייב להיות אחרי תאריך ההתחלה.');
            $('#end-date').val(''); // איפוס תאריך הסיום
            return;
        }

        // עדכון התצוגה
        $('#total-days').text(`${daysDiff} ימים`);
        const totalAmount = daysDiff * dailyRate;
        $('#totalAmount').text(`${totalAmount.toFixed(2)} ש"ח`);
        $('#message').text(''); // ניקוי הודעות שגיאה
    }
});