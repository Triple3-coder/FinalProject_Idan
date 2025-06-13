$(document).ready(function() {
    // אתחול משתנים
    let selectedServices = [];
    let totalPrice = 0;
    
    // טיפול בבחירת שירות
    $('.service-select').on('change', function() {
        const serviceContainer = $(this).closest('.service');
        const serviceType = $(this).val();
        const servicePrice = parseInt(serviceContainer.attr('data-price'));
        
        if ($(this).is(':checked')) {
            // הוספת אנימציה כשנבחר
            serviceContainer.addClass('service-selected selected-animation');
            setTimeout(() => {
                serviceContainer.removeClass('selected-animation');
            }, 500);
            
            // הוספה למערך השירותים הנבחרים
            selectedServices.push({
                type: serviceType,
                price: servicePrice
            });
        } else {
            // הסרה מהעיצוב והמערך
            serviceContainer.removeClass('service-selected');
            
            // מצא את האינדקס של השירות במערך ומחק אותו
            selectedServices = selectedServices.filter(service => service.type !== serviceType);
        }
        
        // עדכון סכום המחיר
        updateTotalPrice();
    });
    
    // פונקציה לעדכון מחיר
    function updateTotalPrice() {
        totalPrice = 0;
        
        // חישוב המחיר הכולל
        selectedServices.forEach(service => {
            totalPrice += service.price;
        });
        
        // עדכון הצגת המחיר
        $('#total-price').text(totalPrice);
        $('#hidden-total-price').val(totalPrice);
    }
    
    // טיפול בשליחת הטופס
    $('#services-form').on('submit', function(e) {
        e.preventDefault();
        
        // הוסף אפקט טעינה
        $('.container').addClass('loading');
        $('#loading-spinner').show();
        
        // שליחת הנתונים לשרת
        $.ajax({
            type: 'POST',
            url: 'save_services.php',
            data: {
                selected_services: JSON.stringify(selectedServices),
                total_additional_price: totalPrice
            },
            dataType: 'json',
            success: function(response) {
                console.log("התגובה התקבלה:", response);
                
                if (response.success) {
                    // הפניה לדף הסיכום
                    window.location.href = 'summary.php';
                } else {
                    alert('אירעה שגיאה: ' + response.message);
                    $('.container').removeClass('loading');
                    $('#loading-spinner').hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('שגיאה בשמירת השירותים:', error);
                console.error('תוכן התגובה:', xhr.responseText);
                alert('אירעה שגיאה בתקשורת עם השרת. אנא נסה שוב מאוחר יותר.');
                $('.container').removeClass('loading');
                $('#loading-spinner').hide();
            }
        });
    });
    
    // בדיקה אם נבחרו שירותים
    $('#checkout-button').on('click', function(e) {
    // אם אין שירותים נבחרים
    if (selectedServices.length === 0) {
        e.preventDefault(); // מונע את הפעולה כברירת מחדל
        var continueWithoutServices = confirm('לא נבחרו שירותים. אתה בטוח שברצונך להמשיך ללא שירותים נוספים?');
        if (continueWithoutServices) {
            // ממשיך את ההגשה גם אם לא נבחרו שירותים
            $('#services-form').submit();
        }
        // אם לא אישר, לא עושה כלום והמשתמש יכול לבחור מחדש
        return false;
    }
    
    // אם נבחרו שירותים, הגש את הטופס
    $('#services-form').submit();
    });
});