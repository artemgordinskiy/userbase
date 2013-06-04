var clearAlerts = function() {
    var alertsDiv;
    alertsDiv = $('.alert');
    if(alertsDiv.length > 0) {
        alertsDiv.slideUp();
    }
};

setTimeout(function() {
    clearAlerts();
}, 5000);