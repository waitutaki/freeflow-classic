document.addEventListener('DOMContentLoaded', function () {
    var forms = document.querySelectorAll('.js-perm-toggle');
    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
                .then(function (response) { return response.text(); })
                .then(function (text) {
                    if (text.trim() !== 'OK') {
                        return;
                    }
                    var stateInput = form.querySelector('input[name=\"state\"]');
                    var currentState = form.getAttribute('data-current-state') || '0';
                    var newState = currentState === '1' ? '0' : '1';
                    form.setAttribute('data-current-state', newState);
                    if (stateInput) {
                        stateInput.value = newState === '1' ? '0' : '1';
                    }
                    var cell = form.closest('td');
                    if (cell) {
                        var state = cell.querySelector('.perm-state');
                        if (state) {
                            var stateIcon = state.querySelector('i');
                            var enabledLabel = form.getAttribute('data-label-enabled') || '';
                            var disabledLabel = form.getAttribute('data-label-disabled') || '';
                            if (newState === '1') {
                                state.classList.remove('text-danger');
                                state.classList.add('text-success');
                                if (stateIcon) {
                                    stateIcon.classList.remove('fa-circle-xmark');
                                    stateIcon.classList.add('fa-circle-check');
                                }
                                state.setAttribute('aria-label', enabledLabel);
                            } else {
                                state.classList.remove('text-success');
                                state.classList.add('text-danger');
                                if (stateIcon) {
                                    stateIcon.classList.remove('fa-circle-check');
                                    stateIcon.classList.add('fa-circle-xmark');
                                }
                                state.setAttribute('aria-label', disabledLabel);
                            }
                        }
                    }
                    var button = form.querySelector('button');
                    if (button) {
                        var ariaLabel = newState === '1' ? (button.getAttribute('data-label-revoke') || '') : (button.getAttribute('data-label-allow') || '');
                        button.setAttribute('aria-label', ariaLabel);
                    }
                })
                .catch(function () {
                    return;
                });
        });
    });

    var mediaButtons = document.querySelectorAll('[data-media-action]');
    mediaButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var action = button.getAttribute('data-media-action');
            var path = button.getAttribute('data-media-path');
            if (!path) {
                return;
            }
            if (action === 'insert') {
                if (window.opener && window.opener.mediaPickerCallback) {
                    window.opener.mediaPickerCallback({ url: path, title: '' }, {});
                    window.close();
                } else if (window.opener) {
                    window.opener.ffMediaPickerPath = path;
                    window.close();
                }
                return;
            }
            if (action === 'featured') {
                if (window.opener) {
                    window.opener.ffFeaturedImage = path;
                    window.close();
                }
            }
        });
    });

    var documentButtons = document.querySelectorAll('[data-document-action]');
    documentButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var action = button.getAttribute('data-document-action');
            var path = button.getAttribute('data-document-path');
            var name = button.getAttribute('data-document-name') || '';
            var docId = button.getAttribute('data-document-id');
            var logUrl = button.getAttribute('data-document-log-url') || '';
            var csrf = button.getAttribute('data-document-csrf') || '';
            if (!path) {
                return;
            }
            if (action === 'insert') {
                if (logUrl && docId && csrf) {
                    var logData = new FormData();
                    logData.append('csrf_token', csrf);
                    logData.append('document_id', docId);
                    fetch(logUrl, { method: 'POST', body: logData }).catch(function () {
                        return;
                    });
                }
                if (window.opener && window.opener.freewriteDocumentCallback) {
                    window.opener.freewriteDocumentCallback(path, name);
                    window.close();
                } else if (window.opener && window.opener.documentPickerCallback) {
                    window.opener.documentPickerCallback({ url: path, name: name }, {});
                    window.close();
                } else if (window.opener) {
                    window.opener.ffDocumentLink = path;
                    window.close();
                }
            }
        });
    });

    var userForm = document.querySelector('#user-form');
    if (userForm) {
        var firstName = userForm.querySelector('#name_first');
        var lastName = userForm.querySelector('#name_last');
        var email = userForm.querySelector('#email');
        var username = userForm.querySelector('#username');
        var reserved = ['admin', 'administrator', 'webmaster', 'superadmin'];

        function sha1Hex(input) {
            if (!window.crypto || !window.crypto.subtle || !window.TextEncoder) {
                return Promise.resolve('');
            }
            var encoder = new TextEncoder();
            return window.crypto.subtle.digest('SHA-1', encoder.encode(input))
                .then(function (buffer) {
                    var bytes = new Uint8Array(buffer);
                    var hex = '';
                    bytes.forEach(function (byte) {
                        hex += ('0' + byte.toString(16)).slice(-2);
                    });
                    return hex;
                })
                .catch(function () {
                    return '';
                });
        }

        function isValidUsername(value) {
            if (value.length < 8) {
                return false;
            }
            if (!/^[a-z][a-z0-9_-]*$/.test(value)) {
                return false;
            }
            var normalized = value.replace(/[_-]/g, '');
            return !reserved.some(function (term) {
                return normalized.indexOf(term) !== -1;
            });
        }

        function buildBase(first, last) {
            var base = (first + last).toLowerCase().replace(/[^a-z0-9]/g, '');
            if (base === '' || !/^[a-z]/.test(base)) {
                base = 'u' + base;
            }
            var normalized = base.replace(/[_-]/g, '');
            reserved.forEach(function (term) {
                if (normalized.indexOf(term) !== -1) {
                    base = 'user';
                }
            });
            return base;
        }

        function updateUsernamePreview() {
            if (!username || username.value.trim() !== '') {
                return;
            }
            var first = firstName ? firstName.value.trim() : '';
            var last = lastName ? lastName.value.trim() : '';
            var emailValue = email ? email.value.trim().toLowerCase() : '';
            var base = buildBase(first, last);
            var emailLocal = emailValue.split('@')[0] || '';
            var seed = first + last + emailLocal;

            sha1Hex(seed).then(function (hash) {
                if (!hash) {
                    return;
                }
                var suffix = hash.slice(0, 4);
                var candidate = base + '-' + suffix;
                var finalize = function (value) {
                    if (!isValidUsername(value)) {
                        value = 'user-' + suffix;
                    }
                    if (isValidUsername(value)) {
                        username.value = value;
                    }
                };
                if (candidate.length < 8) {
                    sha1Hex(candidate).then(function (hash2) {
                        var extra = hash2 ? hash2.slice(0, 8 - candidate.length) : '';
                        finalize(candidate + extra);
                    });
                } else {
                    finalize(candidate);
                }
            });
        }

        [firstName, lastName, email].forEach(function (field) {
            if (!field) {
                return;
            }
            field.addEventListener('blur', updateUsernamePreview);
        });
    }

    var featuredButtons = document.querySelectorAll('[data-featured-button]');
    var featuredInput = document.querySelector('[data-featured-input]');
    var featuredPreview = document.querySelector('[data-featured-preview]');

    function applyFeaturedImage() {
        if (!window.ffFeaturedImage || !featuredInput) {
            return;
        }
        featuredInput.value = window.ffFeaturedImage;
        if (featuredPreview) {
            featuredPreview.innerHTML = '<img class="img-fluid" src="' + window.ffFeaturedImage + '" alt="">';
        }
        window.ffFeaturedImage = null;
    }

    featuredButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var url = button.getAttribute('data-featured-url');
            if (!url) {
                return;
            }
            window.open(url, 'ffMediaPicker', 'width=1100,height=760');
        });
    });

    window.addEventListener('focus', function () {
        applyFeaturedImage();
    });
});
