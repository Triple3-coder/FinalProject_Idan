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
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    }
}

$(document).ready(function() {
    const dateFormat = 'dd/mm/yy';
    let unavailableDates = [];

    $('#start-date, #end-date').datepicker({
        dateFormat: dateFormat,
        onSelect: function() {
            const startDate = $('#start-date').val();
            const endDate = $('#end-date').val();
            if (startDate && endDate && !isValidDateRange(startDate, endDate)) {
                alert("תאריך היציאה חייב להיות לאחר תאריך הכניסה.");
                $('#end-date').val('');
            }
        }
    });

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
            $.post('reservation.php', {
                start_date: startDate,
                end_date: endDate
            }, function(response) {
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
        const start = new Date(startDate.split('/').reverse().join('-'));
        const end = new Date(endDate.split('/').reverse().join('-'));
        return start <= end;
    }
});
