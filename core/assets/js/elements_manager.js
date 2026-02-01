document.addEventListener('DOMContentLoaded', function () {
    var select = document.querySelector('[data-element-type="1"]');
    if (!select) {
        return;
    }

    function updateSections() {
        var option = select.options[select.selectedIndex];
        var key = option ? option.getAttribute('data-type-key') : '';
        document.querySelectorAll('[data-element-settings]').forEach(function (section) {
            var sectionKey = section.getAttribute('data-element-settings');
            section.classList.toggle('d-none', sectionKey !== key);
        });
    }

    select.addEventListener('change', updateSections);
    updateSections();

    var mediaPickerButtons = document.querySelectorAll('[data-media-picker-button]');
    var mediaPickerTarget = null;

    function applyMediaPicker() {
        if (!window.ffMediaPickerPath || !mediaPickerTarget) {
            return;
        }
        var input = document.getElementById(mediaPickerTarget);
        if (input) {
            input.value = window.ffMediaPickerPath;
        }
        window.ffMediaPickerPath = null;
        mediaPickerTarget = null;
    }

    mediaPickerButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var target = button.getAttribute('data-media-picker-target');
            if (!target) {
                return;
            }
            mediaPickerTarget = target;
            var url = '/admin/media/picker';
            window.open(url, 'ffMediaPicker', 'width=1100,height=760');
        });
    });

    window.addEventListener('focus', function () {
        applyMediaPicker();
    });
});
