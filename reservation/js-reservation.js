class Reservation {
    constructor(startDate, endDate) {
        this.startDate = new Date(startDate.split('/').reverse().join('-'));
        this.endDate = new Date(endDate.split('/').reverse().join('-'));
    }

    getDateRange() {
        let dates = [];
        let currentDate = new Date(this.startDate);
        while (currentDate <= this.endDate) {
            dates.push(this.formatDate(currentDate));
            currentDate.setDate(currentDate.getDate() + 1);
        }
        return dates;
    }

    formatDate(date) {
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0'); // חודשים מתחילים מ-0
        const year = date.getFullYear();
        return `${day}/${month}/${year}`; // פורמט יום/חודש/שנה
    }
}

$(document).ready(function() {
    const dateFormat = 'dd/mm/yy';

    $('#start-date').datepicker({
        dateFormat: dateFormat,
        onSelect: function() {
            const startDate = $('#start-date').val();
            const endDate = $('#end-date').val();
            if (endDate && !isValidDateRange(startDate, endDate)) {
                $('#end-date').val('');
            }
        }
    });

    $('#end-date').datepicker({
        dateFormat: dateFormat
    });

    $('#submit').on('click', function() {
        const startDate = $('#start-date').val();
        const endDate = $('#end-date').val();
        
        if (startDate && endDate) {
            const reservation = new Reservation(startDate, endDate);
            const dateRange = reservation.getDateRange();
            $('#message').text(`הזמנה בוצעה לתאריכים: ${dateRange.join(', ')}`);

            // עדכון לוח השנה
            updateCalendar(dateRange);
        } else {
            $('#message').text('אנא מלא את כל השדות.');
        }
    });

    function isValidDateRange(startDate, endDate) {
        const start = new Date(startDate.split('/').reverse().join('-'));
        const end = new Date(endDate.split('/').reverse().join('-'));
        return start <= end;
    }

    function updateCalendar(dates) {
        $('#calendar').empty(); // ניקוי התוכן הקודם בלוח השנה
        const today = new Date();
        const daysInMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();

        // לולאת עבור כל יום בחודש הנוכחי
        for (let day = 1; day <= daysInMonth; day++) {
            const dateToCheck = new Date(today.getFullYear(), today.getMonth(), day);
            const formattedDate = new Reservation(dateToCheck.toLocaleDateString('he-IL'), dateToCheck.toLocaleDateString('he-IL')).formatDate(dateToCheck);
            const isReserved = dates.includes(formattedDate); // בדוק אם התאריך מסומן

            // הוספה של כל יום בלוח השנה
            $('#calendar').append(`
                <div class="day${isReserved ? ' reserved' : ''}">
                    ${day}${isReserved ? ' ✔' : ''}
                </div>
            `);
        }
    }
});
