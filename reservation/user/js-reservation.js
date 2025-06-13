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
        onSelect: function() {
            validateAndUpdateDates();
        },
        onClose: function() {
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

    /*
    // מניעת הקלדה ידנית בשדות התאריך
    $('#start-date, #end-date').on('keydown paste', function(e) {
        // מאפשר רק מקשי חזרה, delete, tab ומקשי חצים
        const allowedKeys = [8, 9, 46, 37, 38, 39, 40];
        if (allowedKeys.indexOf(e.keyCode) === -1) {
            e.preventDefault();
        }
    });
*/
    // בדיקה נוספת כאשר השדה מאבד פוקוס
    $('#start-date, #end-date').on('blur', function() {
        validateAndUpdateDates();
    });

    // שליחת הימים שבחר המשתמש
    $('#submit').on('click', function(e) {
        e.preventDefault();
        const startDate = $('#start-date').val();
        const endDate = $('#end-date').val();
        const totalPrice = parseFloat($('#total-price').text().replace(/[^0-9.-]+/g, ''));
        
        $('#message').text('מעבד את ההזמנה...').removeClass('error-message').addClass('processing-message');
        
        if (startDate && endDate) {
            // Log values for debugging
            console.log("Submitting reservation:", {
                startDate: startDate,
                endDate: endDate,
                totalPrice: totalPrice
            });
            
            // בדיקה עבור תאריכים קורא לפונקציה והקובץ בדיקות
            checkReservationConflict(startDate, endDate, totalPrice);

        } else {
            $('#message').text('אנא מלא את כל השדות.');
            $('#message').addClass('error-message');
        }
    });

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
    //מעלה את בדיקת התאריכים הזמינים
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
            
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error('Failed to load unavailable dates:', textStatus, errorThrown);
            console.error('Response:', jqXHR.responseText);
            unavailableDates = [];
        });
    }
    //בדיקת התאריכים אם יש תפיסות הזמנה קודמת באותם תאריכים
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
                    
                    // המשך לדף services.php 
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
    //ולידצית בחירת תאריכים לפי פורמט
    function validateAndUpdateDates() {
        const startDate = $('#start-date').val();
        const endDate = $('#end-date').val();
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        // בדיקת פורמט ותקינות התאריכים
        let isStartValid = true;
        let isEndValid = true;

        if (startDate) {
            const startParts = startDate.split("/");
            if (startParts.length === 3) {
                const startDateObj = new Date(startParts[2], startParts[1] - 1, startParts[0]);
                if (isNaN(startDateObj.getTime()) || startDateObj < today) {
                    $('#start-date').val('');
                    isStartValid = false;
                    $('#message').text('תאריך התחלה לא תקין או בעבר').addClass('error-message');
                } else if (unavailableDates.includes(startDate)) {
                    $('#start-date').val('');
                    isStartValid = false;
                    $('#message').text('תאריך ההתחלה אינו זמין').addClass('error-message');
                }
            } else {
                $('#start-date').val('');
                isStartValid = false;
            }
        }

        if (endDate) {
            const endParts = endDate.split("/");
            if (endParts.length === 3) {
                const endDateObj = new Date(endParts[2], endParts[1] - 1, endParts[0]);
                if (isNaN(endDateObj.getTime()) || endDateObj < today) {
                    $('#end-date').val('');
                    isEndValid = false;
                    $('#message').text('תאריך סיום לא תקין או בעבר').addClass('error-message');
                } else if (unavailableDates.includes(endDate)) {
                    $('#end-date').val('');
                    isEndValid = false;
                    $('#message').text('תאריך הסיום אינו זמין').addClass('error-message');
                }
            } else {
                $('#end-date').val('');
                isEndValid = false;
            }
        }

        // בדיקה אם תאריך סיום לפני תאריך התחלה
        if (startDate && endDate && isStartValid && isEndValid) {
            if (!isValidDateRange(startDate, endDate)) {
                $('#end-date').val('');
                $('#message').text('תאריך היציאה חייב להיות לאחר תאריך הכניסה').addClass('error-message');
                isEndValid = false;
            }
        }

        // עדכון הסיכום רק אם התאריכים תקינים
        if (isStartValid && isEndValid) {
            $('#message').text('').removeClass('error-message');
            updateBookingSummary($('#start-date').val(), $('#end-date').val());
        } else {
            updateBookingSummary('', '');
        }
    }

    //פונקציה אם התאריכים בטווח הנכון לפי התחלה וסיום
    function isValidDateRange(startDate, endDate) {
        const start = new Date(startDate.split('/').reverse().join('-'));
        const end = new Date(endDate.split('/').reverse().join('-'));
        return start <= end;
    }

    // פונקציה שמעדכנת את כמות הימים והמחיר הכולל
    function updateBookingSummary(start, end) {
        // איפוס הודעות שגיאה
        $('#message').text('').removeClass('error-message');
        
        // לבדוק אם אחד התאריכים לא קיים
        if (!start || !end) {
            $('#total-days').text('0 ימים');
            $('#total-price').text('0 ₪');
            $('#total-price-value').val(0); // עדכון של שדה מוסתר
            $('.price-breakdown').hide();
            return;
        }
    
        // המרה לתאריכים בסדר הנכון יום-חודש-שנה
        const startParts = start.split("/");
        const endParts = end.split("/");
        const startDate = new Date(startParts[2], startParts[1] - 1, startParts[0]);
        const endDate = new Date(endParts[2], endParts[1] - 1, endParts[0]);
        
        console.log("Start Date:", startDate);
        console.log("End Date:", endDate);

        // בדיקת תאריכים תקינים
        if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
            $('#total-days').text('0 ימים');
            $('#total-price').text('0 ₪');
            $('#total-price-value').val(0); // עדכון של שדה מוסתר
            $('.price-breakdown').hide();
            return;
        }
    
        // חישוב ההפרש בין התאריכים
        const timeDiff = endDate - startDate;
        const dayDiff = Math.floor(timeDiff / (1000 * 3600 * 24)) + 1; // +1 כי כולל את יום ההגעה
        
        console.log("Time Difference (ms):", timeDiff);
        console.log("Days calculated:", dayDiff);
        
        // עדכון תצוגת הימים והמחיר
        if (dayDiff >= 1) {
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
        } else {
            $('#total-days').text('0 ימים');
            $('#total-price').text('0 ₪');
            $('#total-price-value').val(0); // עדכון של שדה מוסתר
            $('.price-breakdown').hide();
        }
    }
});