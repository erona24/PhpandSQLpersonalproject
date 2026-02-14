$(document).ready(function() {
    $('.sign_up_btn a').click(function(e) {
        e.preventDefault(); 
        $('.sign_in').hide(); 
        $('.sign_up').show(); 
    });


    $('.sign_in_btn a').click(function(e) {
        e.preventDefault(); 
        $('.sign_up').hide(); 
        $('.sign_in').show();
    });
});