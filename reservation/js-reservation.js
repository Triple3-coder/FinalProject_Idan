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
        dateFormat: dateFormat,
        onSelect: function() {
            const startDate = $('#start-date').val();
            if (startDate && !isValidDateRange(startDate, $(this).val())) {
                alert("תאריך היציאה חייב להיות לאחר תאריך הכניסה.");
                $(this).val(''); // מחק את תאריך היציאה אם הוא לא תקין
            }
        }
    });

    let selectedDates = []; // משתנה גלובלי לשמירת התאריכים שנבחרו
    $('#submit').on('click', function() {//סיכום הזמנה
        const startDate = $('#start-date').val();
        const endDate = $('#end-date').val();
        
        if (startDate && endDate) {
            // פורמט של התאריכים
            const start = new Date(startDate.split('/').reverse().join('-'));
            const end = new Date(endDate.split('/').reverse().join('-'));
    
            // חישוב מספר הימים בטווח
            const totalDays = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1; // +1 כדי לכלול את התאריך הראשון
            $('#total-days').text(totalDays + ' ימים'); // עדכון התצוגה
    
            const reservation = new Reservation(startDate, endDate);
            const dateRange = reservation.getDateRange();
            selectedDates = dateRange; // שמירה של התאריכים שנבחרו
    
            // הצגת הכותרת ותאריכים שנבחרו
            $('#selectedDatesHeader').show();
            
            // עדכון לוח השנה
            updateCalendar(dateRange);

            //הוספה של פעולות לדף הבא
            fetch('reservation.php', { 
                method: 'POST',
                body: formData
            })
            //לאחר שיש הצלחה הוא עובר פה לדף הבא
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    setTimeout(function() {
                        $('#message').text('ההזמנה בוצעה בהצלחה!');
                        $('#message').show();
                        //window.location.href = 'services/services.html';
                    }, 2000);
                } else {
                    // הצגת הודעת שגיאה
                    $('#message').text('יש בעיה בהזמנה!'); 
                    $('#message').show();
                }
            })
            .catch(error => {
                document.getElementById('message').innerText = 'אירעה שגיאה, אנא נסה שוב מאוחר יותר.';
                document.getElementById('message').style.display = 'block';
            });

        } else {
            $('#message').text('אנא מלא את כל השדות.');
            // מחיקת התאריכים שנבחרו והכותרת
            $('#selectedDatesHeader').hide();
            $('#total-days').text('0 ימים'); // לא נוסף ימים אם השדות ריקים
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
        const month = today.getMonth(); // החודש הנוכחי
        const year = today.getFullYear(); // השנה הנוכחית
        
        // שמות הימים
        const daysOfWeek = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'];
        const headerRow = $('<div class="header-row"></div>');
        daysOfWeek.forEach(day => {
            headerRow.append(`<div class="day-header">${day}</div>`);
        });
        $('#calendar').append(headerRow);
    
        // קביעת מספר הימים בחודש הנוכחי
        const daysInMonth = new Date(year, month + 1, 0).getDate(); // הוספת 1 לקבלת החודש הבא ומקבלת את היום האחרון
        
        // הוספה של 'תאים ריקים' עד שהחודש מתחיל
        const firstDayOfMonth = new Date(year, month, 1).getDay(); // יום בשבוע של הראשון בחודש
        for (let i = 0; i < firstDayOfMonth; i++) {
            $('#calendar').append('<div class="empty-day"></div>');
        }
    
        // לולאת עבור כל יום בחודש הנוכחי
        for (let day = 1; day <= daysInMonth; day++) {
            const dateToCheck = new Date(year, month, day);
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

    const totalDays = dateRange.length; // טווח ימים שנבחר
    $('#total-days').text(totalDays + ' ימים'); // עדכון מספר ימים
});
