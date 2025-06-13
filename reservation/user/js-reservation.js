$(document).ready(function() {
    const dateFormat = 'dd/mm/yy';
    let unavailableDates = [];
    let dailyPrice = 0;

    // טעינת המחיר היומי של הלינה
    loadDailyPrice();
    
    // טעינת תאריכים לא זמינים לפני התקנת datepicker
    loadUnavailableDates();

    $('#start-date, #end-date').datepicker({
        dateFormat: dateFormat,
        minDate: 0, // מונע בחירה של תאריכים עבר
        //כאן יש בדיקה על התאריכים
        onSelect: function() {
            validateAndUpdateDates();
        },
        beforeShowDay: function(date) {
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const formatted = `${day}/${month}/${year}`;
            
            if (unavailableDates.includes(formatted)) {
                return [false, "unavailable-date", "תאריך תפוס - אין מקומות זמינים"];
            }
            return [true, "", ""];
        }
    });

    // מניעת הקלדה ידנית בשדות התאריך
    $('#start-date, #end-date').on('keydown paste', function(e) {
        // מאפשר רק מקשי חזרה, delete, tab ומקשי חצים
        const allowedKeys = [8, 9, 46, 37, 38, 39, 40];
        if (allowedKeys.indexOf(e.keyCode) === -1) {
            e.preventDefault();
        }
    });

    // שליחה של הימים והמשך הזמנה - נתפס על ידי הקוד בדף reservation.php
    $('#submit').on('click', function(e) {
        e.preventDefault();
        const startDate = $('#start-date').val();
        const endDate = $('#end-date').val();
        const totalPrice = parseFloat($('#total-price').text().replace(/[^0-9.-]+/g, ''));
        
        // הודעה על קיום הזמנה והסרת עיצוב
        $('#message').text('מעבד את ההזמנה...').removeClass('error-message').addClass('processing-message');
        
        if (startDate && endDate) {
            // לוג עבורנו
            console.log("Submitting reservation:", {
                startDate: startDate,
                endDate: endDate,
                totalPrice: totalPrice
            });
            
            // בדיקה עבור תאריכים תקינים וזמינות תאריכים שכבר נבחרו בעבר ושליחה לעדכון מחיר
            checkReservationConflict(startDate, endDate, totalPrice);
        } else {
            //במידה וחסר תאריכים יש שגיאה
            $('#message').text('אנא מלא את כל השדות.');
            $('#message').addClass('error-message');
        }
    });
    //פונקציה לבדיקת המחיר של הלינה היומי
    function loadDailyPrice() {
        $.getJSON('get_daily_price.php', function(response) {
            if (response.success) {
                dailyPrice = parseFloat(response.price);
                console.log('Daily price loaded:', dailyPrice);
                
                // עדכון הסיכום אם התאריכים כבר נבחרו
                validateAndUpdateDates();
            } else {
                console.error('Error loading daily price:', response.error);
                dailyPrice = 0;
            }
        }).fail(function() {
            console.error('Failed to load daily price');
            dailyPrice = 0;
        });
    }
    //פונקציה בדיקת תאריכים זמינים
    function loadUnavailableDates() {
        console.log('Loading unavailable dates...');
        $.getJSON('get_unavailable_dates.php', function(data) {
            console.log('Response received:', data);
            
            if (Array.isArray(data)) {
                unavailableDates = data;
                console.log('Unavailable dates loaded:', unavailableDates);
            } else if (data && data.error) {
                console.error('Error loading unavailable dates:', data.error);
                unavailableDates = [];
            } else {
                console.error('Unexpected response format:', data);
                unavailableDates = [];
            }
            
            // עדכון ה-datepicker עם התאריכים החסומים
            $('#start-date, #end-date').datepicker('option', 'beforeShowDay', function(date) {
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                const formatted = `${day}/${month}/${year}`;
                
                if (unavailableDates.includes(formatted)) {
                    return [false, "unavailable-date", "תאריך תפוס - אין מקומות זמינים"];
                }
                return [true, "", ""];
            });
             $('#start-date, #end-date').datepicker('refresh');
            
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error('Failed to load unavailable dates:', textStatus, errorThrown);
            console.error('Response:', jqXHR.responseText);
            unavailableDates = [];
        });
    }
    //פונקציית בדיקה של טווח תאריכים אם נבחרו ותקינותם
    function checkReservationConflict(startDate, endDate, totalPrice) {
        $.ajax({
            type: "POST",
            url: 'check_reservation_conflict.php',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.hasConflict) {
                        $('#message').text('כבר יש לך הזמנה בתאריכים אלו. אנא בחר תאריכים אחרים.');
                        $('#message').addClass('error-message');
                    } else {
                        // אין חפיפה - ממשיכים עם ההזמנה - שמירה ב-SESSION בלבד
                        saveToSessionOnly(startDate, endDate, totalPrice);
                    }
                } else {
                    $('#message').text(response.error || "שגיאה בבדיקת חפיפות הזמנות");
                    $('#message').addClass('error-message');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response Text:', xhr.responseText);
                $('#message').text('שגיאה בתקשורת עם השרת');
                $('#message').addClass('error-message');
            }
        });
    }

    // פונקציה חדשה ששומרת רק ב-SESSION ולא בדאטה-בייס
    function saveToSessionOnly(startDate, endDate, totalPrice) {
        // שמירה ב-SESSION בלבד
        $.ajax({
            type: "POST",
            url: "reservation.php",
            data: {
                start_date: startDate,
                end_date: endDate,
                total_price: totalPrice
            },
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(result) {
                if (result.success) {
                    $('#message').text('הנתונים נשמרו בהצלחה').removeClass('error-message').addClass('success-message');
                    
                    // המשך לדף services.php אחרי שנייה
                    setTimeout(function() {
                        window.location.href = "../../services/user/services.php";
                    }, 1000);
                } else {
                    $('#message').text('שגיאה: ' + (result.message || 'שגיאה לא ידועה')).addClass('error-message');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error (Session):', status, error);
                console.error('Response Text:', xhr.responseText);
                $('#message').text('שגיאה בתקשורת עם השרת: ' + error).addClass('error-message');
            }
        });
    }
//פונקציית בדיקת תאריכים שנבחרו
function validateAndUpdateDates() {
    const startDate = $('#start-date').val();
    const endDate = $('#end-date').val();
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // בדיקת פורמט ותקינות התאריכים
    let isStartValid = true;
    let isEndValid = true;
    //אם תאריך הסיום לפני תאריך ההתחלה נקיים שגיאה ולא ניתן להמשיך הזמנה
    if(endDate<startDate)
    {
        console.log('תאריך סיום מוקדם מתאריך התחלה');
        $('.message').text('תאריך היציאה חייב להיות לאחר תאריך הכניסה').removeClass('success-message').addClass('error-message');
        isEndValid = false;
        $('#end-date').val('');//מניעה להמשך הזמנה מחיקת תאריך סיום
        return;
    }

    // בדיקה אם יש תאריכים חסומים בטווח התאריכים
    if (startDate && endDate && isStartValid && isEndValid) {
        let currentDate = new Date(startDate.split('/').reverse().join('-'));
        let endDateObj = new Date(endDate.split('/').reverse().join('-'));
        //לולאה שעוברת על תאריכים שבחר המשתמש
        while (currentDate <= endDateObj) {
            const day = String(currentDate.getDate()).padStart(2, '0');
            const month = String(currentDate.getMonth() + 1).padStart(2, '0');
            const year = currentDate.getFullYear();
            const formattedDate = `${day}/${month}/${year}`;

            if (unavailableDates.includes(formattedDate)) {
                $('.message').text('אחד או יותר מהתאריכים בתקופה שבחרת אינם זמינים.').addClass('error-message');
                updateBookingSummary('', ''); // עדכן את הסיכום
                return;
            }

            currentDate.setDate(currentDate.getDate() + 1);
        }
    }

    // עדכון הסיכום רק אם התאריכים תקינים
    if (isStartValid && isEndValid) {
        $('.message').text('').removeClass('error-message').removeClass('success-message');
        updateBookingSummary($('#start-date').val(), $('#end-date').val());
    } else {//במידה ולא תקין ימחק את התאריכים 
        updateBookingSummary('', '');
    }

}

    // פונקציה שמעדכנת את כמות הימים והמחיר הכולל של הזמנה
    function updateBookingSummary(start, end) {
        
        //לבדוק אם אחד התאריכים חסרים - ואיפוס הדף
        if (!start || !end) {
            $('#total-days').text('0 ימים');
            $('#total-price').text('0 ₪');
            $('#total-price-value').val(0); // עדכון של שדה מוסתר
            $('.price-breakdown').hide();
            $('#end-date').val(''); // עדכון תאריך סיום ריק שלא יוכל להמשיך בהזמנה
            return;
        }
    
        // המרה לתאריכים
        const startParts = start.split("/");
        const endParts = end.split("/");
        const startDate = new Date(startParts[2], startParts[1] - 1, startParts[0]);
        const endDate = new Date(endParts[2], endParts[1] - 1, endParts[0]);
        //לוג בדיקה עבורנו לתאריכים
        console.log("Start Date:", startDate);
        console.log("End Date:", endDate);

        // בדיקת אם אחד התאריכים חסרים שלא ימשיך הזמנה
        if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
            $('#total-days').text('0 ימים');
            $('#total-price').text('0 ₪');
            $('#total-price-value').val(0); // עדכון של שדה מוסתר
            $('.price-breakdown').hide();
            $('#end-date').val(''); // עדכון תאריך סיום ריק שלא יוכל להמשיך בהזמנה
            return;
        }
    
        // חישוב ההפרש בין התאריכים
        const timeDiff = endDate - startDate;
        const dayDiff = Math.floor(timeDiff / (1000 * 3600 * 24)) + 1; // +1 חישוב כמות ימים, כולל את יום ההגעה
        
        console.log("Time Difference (ms):", timeDiff);
        console.log("Days calculated:", dayDiff);
        
        // עדכון תצוגת הימים והמחיר
        if (dayDiff >= 1) {//אם מדובר ביום אחד ומעלה נעדכן מחיר וימים
            $('#total-days').text(dayDiff + ' ימים');
            
            if (dailyPrice > 0) {
                const totalPrice = dayDiff * dailyPrice;
                $('#total-price').text(totalPrice.toLocaleString() + ' ₪');
                $('#total-price-value').val(totalPrice); // עדכון של שדה מוסתר
                $('#daily-price').text(dailyPrice.toLocaleString() + ' ₪');
                $('.price-breakdown').show();
            } else {
                $('#total-price').text('טוען מחיר...');
                $('#total-price-value').val(0); // עדכון של שדה מוסתר
                $('.price-breakdown').hide();
            }
        } else {//במידה ויש בעיה או חוסר בימים מבצע איפוס
            $('#total-days').text('0 ימים');
            $('#total-price').text('0 ₪');
            $('#total-price-value').val(0); // עדכון של שדה מוסתר
            $('.price-breakdown').hide();
            $('#end-date').val(''); // עדכון תאריך סיום ריק שלא יוכל להמשיך בהזמנה
        }
    }
});