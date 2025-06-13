$(document).ready(function() {
    // אתחול משתנים - מערך השירותים שנבחרו וסכום כולל
    let selectedServices = [];
    let totalPrice = 0;
    
    // טיפול במרכיב בחירה של שירות עבור צ'קבוקס 
    $('.service-select').on('change', function() {
        const serviceContainer = $(this).closest('.service');
        const serviceType = $(this).val();
        const servicePrice = parseInt(serviceContainer.attr('data-price'));
        
        if ($(this).is(':checked')) {
            // אם שירות נבחר, מפעיל את פעילות האנימציה
            serviceContainer.addClass('service-selected selected-animation');
            setTimeout(() => {
                serviceContainer.removeClass('selected-animation');
            }, 500);
            
            // הוספה למערך השירותים הנבחרים, סוג ומחיר
            selectedServices.push({
                type: serviceType,
                price: servicePrice
            });
        } else {
            // אם השירות הוסר, מסירים את העיצובים
            serviceContainer.removeClass('service-selected');
            
            // מעדכן מערך להסרת השירות שהוסר
            selectedServices = selectedServices.filter(service => service.type !== serviceType);
        }
        
        // עדכון סכום המחיר
        updateTotalPrice();
    });
    
    // פונקציה לעדכון מחיר
    function updateTotalPrice() {
        totalPrice = 0;
        
        // חישוב המחיר משירותים שנבחרו
        selectedServices.forEach(service => {
            totalPrice += service.price;
        });
        
        // עדכון הצגת המחיר
        $('#total-price').text(totalPrice);
        $('#hidden-total-price').val(totalPrice);
    }
    
    // טיפול בהגשת הטופס
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
                    //  הפניה לדף הסיכום, במידה והפעולה הצליחה
                    window.location.href = 'summary.php';
                } else {
                    alert('אירעה שגיאה: ' + response.message);
                    $('.container').removeClass('loading');
                    $('#loading-spinner').hide();
                }
            },
            error: function(xhr, status, error) {//טיפול בשגיאות תקשורת עם השרת
                console.error('שגיאה בשמירת השירותים:', error);
                console.error('תוכן התגובה:', xhr.responseText);
                alert('אירעה שגיאה בתקשורת עם השרת. אנא נסה שוב מאוחר יותר.');
                $('.container').removeClass('loading');
                $('#loading-spinner').hide();
            }
        });
    });
    
    // טיפול בלחיצה על כפתור בחירת השירות צ'קבוקס
    $('#checkout-button').on('click', function(e) {
    // בדיקה אם נבחרו שירותים
    if (selectedServices.length === 0) {
        e.preventDefault(); // מונע את ההגשה אם אין שירותים נבחרים ואז מקבל הודעה אם להמשיך ללא שירותים
        var continueWithoutServices = confirm('לא נבחרו שירותים. אתה בטוח שברצונך להמשיך ללא שירותים נוספים?');
        if (continueWithoutServices) {
            //במידה ומאשר ללא שירותים נוספים, תתבצע שליחה של הטופס
            $('#services-form').submit();
        }
        // אם לא אישר, לא עושה כלום והמשתמש יכול לבחור מחדש
        return false;
    }
    
    // אם נבחרו שירותים, הגש את הטופס
    $('#services-form').submit();
    });
});