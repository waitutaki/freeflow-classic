document.addEventListener('DOMContentLoaded', function () {
    var usernameInput = document.getElementById('username');
    var statusEl = document.getElementById('ffcms-username-status');
    if (!usernameInput || !statusEl) {
        return;
    }

    var timer = null;
    usernameInput.addEventListener('input', function () {
        clearTimeout(timer);
        statusEl.textContent = '';
        var value = usernameInput.value.trim();
        if (value === '') {
            return;
        }
        timer = setTimeout(function () {
            fetch('/check-username?u=' + encodeURIComponent(value))
                .then(function (response) { return response.text(); })
                .then(function (text) {
                    statusEl.textContent = text;
                })
                .catch(function () {
                    statusEl.textContent = '';
                });
        }, 300);
    });
});
