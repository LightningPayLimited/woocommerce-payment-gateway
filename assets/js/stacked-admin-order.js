(function () {
    var btn = document.getElementById('stacked-check-status');
    if (!btn) {
        return;
    }
    var errorEl = document.getElementById('stacked-error');
    var statusEl = document.getElementById('stacked-admin-status');

    btn.addEventListener('click', function () {
        btn.disabled = true;
        btn.textContent = 'Checking...';
        if (errorEl) {
            errorEl.style.display = 'none';
        }
        var xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxurl);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.success && data.data.paid) {
                    if (statusEl) {
                        statusEl.textContent = 'Paid';
                    }
                    btn.textContent = 'Payment Confirmed';
                    setTimeout(function () { location.reload(); }, 1500);
                } else if (data.success && !data.data.paid) {
                    btn.textContent = 'Not Yet Paid';
                    setTimeout(function () {
                        btn.textContent = 'Check Payment Status';
                        btn.disabled = false;
                    }, 2000);
                } else {
                    var msg = (data.data && data.data.message) ? data.data.message : 'Unknown error';
                    if (errorEl) {
                        errorEl.textContent = msg;
                        errorEl.style.display = 'block';
                    }
                    btn.textContent = 'Check Payment Status';
                    btn.disabled = false;
                }
            } catch (e) {
                if (errorEl) {
                    errorEl.textContent = 'Invalid response from server (HTTP ' + xhr.status + ')';
                    errorEl.style.display = 'block';
                }
                btn.textContent = 'Check Payment Status';
                btn.disabled = false;
            }
        };
        xhr.onerror = function () {
            if (errorEl) {
                errorEl.textContent = 'Network error - could not reach server.';
                errorEl.style.display = 'block';
            }
            btn.textContent = 'Check Payment Status';
            btn.disabled = false;
        };
        xhr.send('action=stacked_check_status&order_id=' + btn.dataset.orderId + '&nonce=' + btn.dataset.nonce);
    });
})();
