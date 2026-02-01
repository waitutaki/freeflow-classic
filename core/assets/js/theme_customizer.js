document.addEventListener('DOMContentLoaded', function () {
    var container = document.querySelector('.ff-customizer');
    if (!container) {
        return;
    }

    var saveUrl = container.getAttribute('data-save-url') || '';
    var previewUrl = container.getAttribute('data-preview-url') || '';
    var themeKey = container.getAttribute('data-theme-key') || '';
    var context = container.getAttribute('data-context') || '';
    var autosaveToggle = document.getElementById('ff-autosave-toggle');
    var form = document.getElementById('theme-customizer-form');
    var inputs = container.querySelectorAll('.ff-customizer-input');
    var colorInputs = container.querySelectorAll('.ff-customizer-input[data-ff-color-input="1"]');
    var frame = container.querySelector('.ff-customizer-frame');
    var csrf = form ? form.querySelector('input[name="csrf_token"]') : null;

    function autosaveEnabled() {
        return autosaveToggle && autosaveToggle.checked;
    }

    function refreshPreview() {
        if (!frame || !previewUrl) {
            return;
        }
        var glue = previewUrl.indexOf('?') === -1 ? '?' : '&';
        frame.src = previewUrl + glue + 'rev=' + Date.now();
    }

    function sendAutosave(token, value) {
        if (!saveUrl || !csrf) {
            return;
        }
        var data = new FormData();
        data.append('csrf_token', csrf.value);
        data.append('theme_key', themeKey);
        data.append('context', context);
        data.append('token', token);
        data.append('value', value);
        fetch(saveUrl, { method: 'POST', body: data })
            .then(function (response) { return response.text(); })
            .then(function (text) {
                if (text.trim() === 'OK') {
                    refreshPreview();
                }
            })
            .catch(function () {
                return;
            });
    }

    inputs.forEach(function (input) {
        input.addEventListener('change', function () {
            if (!autosaveEnabled()) {
                return;
            }
            var token = input.getAttribute('data-token') || '';
            if (!token) {
                return;
            }
            sendAutosave(token, input.value);
        });
    });

    if (window.iro && colorInputs.length) {
        var activePicker = null;

        function closeAllPickers() {
            document.querySelectorAll('.IroColorPicker').forEach(function (p) {
                p.style.display = 'none';
            });
            activePicker = null;
        }

        document.addEventListener('click', function (e) {
            if (!e.target.closest('[data-ff-color-picker="1"]')) {
                closeAllPickers();
            }
        });

        colorInputs.forEach(function (input) {
            var pickerEl = input.parentElement ? input.parentElement.querySelector('[data-ff-color-picker="1"]') : null;
            if (!pickerEl) {
                return;
            }
            var colorValue = input.value || '#000000';
            var picker = null;
            try {
                picker = new window.iro.ColorPicker(pickerEl, {
                    width: 150,
                    color: colorValue,
                    layout: [
                        { component: window.iro.ui.Wheel, options: { wheelLightness: false } },
                        { component: window.iro.ui.Slider, options: { sliderType: 'alpha' } }
                    ]
                });
                // Position and hide the picker
                var pickerContainer = pickerEl.querySelector('.IroColorPicker');
                if (pickerContainer) {
                    pickerContainer.style.position = 'absolute';
                    pickerContainer.style.zIndex = '1000';
                    pickerContainer.style.top = '100%';
                    pickerContainer.style.left = '0';
                    pickerContainer.style.marginTop = '5px';
                    pickerContainer.style.display = 'none';
                }
            } catch (err) {
                return;
            }

            picker.on('color:change', function (color) {
                input.value = color.rgbaString;
                pickerEl.style.backgroundColor = color.rgbaString;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });

            input.addEventListener('input', function () {
                var nextValue = input.value.trim();
                if (!nextValue) {
                    return;
                }
                try {
                    picker.color.set(nextValue);
                    pickerEl.style.backgroundColor = nextValue;
                } catch (err) {
                    return;
                }
            });

            // Initial color
            pickerEl.style.backgroundColor = colorValue;

            // Toggle picker on click
            pickerEl.addEventListener('click', function (e) {
                e.stopPropagation();
                var pickerContainer = pickerEl.querySelector('.IroColorPicker');
                if (!pickerContainer) {
                    return;
                }
                if (activePicker && activePicker !== pickerContainer) {
                    closeAllPickers();
                }
                pickerContainer.style.display = pickerContainer.style.display === 'none' ? 'block' : 'none';
                if (pickerContainer.style.display === 'block') {
                    activePicker = pickerContainer;
                } else {
                    activePicker = null;
                }
            });
        });
    }

    if (autosaveToggle) {
        autosaveToggle.addEventListener('change', function () {
            if (autosaveEnabled()) {
                refreshPreview();
            }
        });
    }
});
