document.addEventListener('DOMContentLoaded', function () {
    var sidebar = document.querySelector('.zulu-sidebar');
    var toggle = document.querySelector('.zulu-sidebar-toggle button');
    if (!sidebar || !toggle) {
        return;
    }

    toggle.addEventListener('click', function () {
        sidebar.classList.toggle('is-collapsed');
    });
});
