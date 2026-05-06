(function () {
    if (typeof window.stackedThankyou === 'undefined') {
        return;
    }
    var statusEl = document.getElementById('stacked-status');
    if (!statusEl) {
        return;
    }
    var ajaxUrl = window.stackedThankyou.ajaxUrl;
    var attempts = 0;
    var maxAttempts = 12;
    var interval = setInterval(function () {
        attempts++;
        if (attempts > maxAttempts) {
            clearInterval(interval);
            statusEl.innerHTML = '<p>Payment confirmation is taking longer than expected. Your order will update automatically once payment is confirmed.</p>';
            return;
        }
        var xhr = new XMLHttpRequest();
        xhr.open('GET', ajaxUrl);
        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.paid) {
                        clearInterval(interval);
                        statusEl.innerHTML = '<p><strong>Payment confirmed!</strong></p>';
                        setTimeout(function () { location.reload(); }, 1500);
                    }
                } catch (e) {}
            }
        };
        xhr.send();
    }, 5000);
})();
