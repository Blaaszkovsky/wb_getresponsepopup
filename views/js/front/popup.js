(function () {
    'use strict';

    var popup = document.getElementById('wb-gr-popup');
    if (!popup) {
        return;
    }

    var config = {
        ajaxUrl: popup.getAttribute('data-ajax-url'),
        token: popup.getAttribute('data-token'),
        triggerType: popup.getAttribute('data-trigger-type'),
        triggerValue: parseInt(popup.getAttribute('data-trigger-value'), 10) || 5,
        cookieClosedMin: parseInt(popup.getAttribute('data-cookie-closed-min'), 10) || 10080,
        cookieSubscribedMin: parseInt(popup.getAttribute('data-cookie-subscribed-min'), 10) || 43200,
        discountEnabled: popup.getAttribute('data-discount-enabled') === '1'
    };

    // Dual persistence (cookie + localStorage) so the popup state survives
    // intermediaries that may strip cookies (Cloudflare cache rules, "Bypass cache on cookie",
    // privacy modes, etc.) and persists across page navigations on the same origin.
    var isSecure = (window.location.protocol === 'https:');

    function readCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : null;
    }

    function writeCookie(name, value, minutes) {
        var seconds = Math.max(60, Math.floor(minutes * 60));
        var date = new Date();
        date.setTime(date.getTime() + seconds * 1000);
        var parts = [
            name + '=' + encodeURIComponent(value),
            'expires=' + date.toUTCString(),
            'max-age=' + seconds,
            'path=/',
            'SameSite=Lax'
        ];
        if (isSecure) {
            parts.push('Secure');
        }
        document.cookie = parts.join('; ');
    }

    function readStorage(name) {
        try {
            var raw = window.localStorage.getItem(name);
            if (!raw) {
                return null;
            }
            var entry = JSON.parse(raw);
            if (!entry || typeof entry !== 'object') {
                return null;
            }
            if (typeof entry.e === 'number' && entry.e < Date.now()) {
                window.localStorage.removeItem(name);
                return null;
            }
            return entry.v != null ? String(entry.v) : null;
        } catch (err) {
            return null;
        }
    }

    function writeStorage(name, value, minutes) {
        try {
            window.localStorage.setItem(name, JSON.stringify({
                v: value,
                e: Date.now() + Math.max(60000, Math.floor(minutes * 60000))
            }));
        } catch (err) {
            // localStorage unavailable (quota, Safari ITP, private mode) — cookie still carries the state.
        }
    }

    function persistState(name, value, minutes) {
        writeCookie(name, value, minutes);
        writeStorage(name, value, minutes);
    }

    function hasState(name) {
        return readCookie(name) !== null || readStorage(name) !== null;
    }

    // Reconcile: if either store has it, mirror to the other so it survives the next request.
    function reconcile(name, minutes) {
        var cookieValue = readCookie(name);
        var storageValue = readStorage(name);
        if (cookieValue !== null && storageValue === null) {
            writeStorage(name, cookieValue, minutes);
        } else if (storageValue !== null && cookieValue === null) {
            writeCookie(name, storageValue, minutes);
        }
    }

    reconcile('wb_gr_closed', config.cookieClosedMin);
    reconcile('wb_gr_subscribed', config.cookieSubscribedMin);

    // Don't show if either store indicates closed or subscribed
    if (hasState('wb_gr_closed') || hasState('wb_gr_subscribed')) {
        return;
    }

    var shown = false;

    function showPopup() {
        if (shown) {
            return;
        }
        shown = true;
        popup.style.display = 'flex';
        // Force reflow for transition
        popup.offsetHeight;
        popup.classList.add('wb-gr-popup--visible');
    }

    function hidePopup() {
        popup.classList.remove('wb-gr-popup--visible');
        setTimeout(function () {
            popup.style.display = 'none';
        }, 300);
    }

    function closePopup() {
        persistState('wb_gr_closed', '1', config.cookieClosedMin);
        hidePopup();
    }

    // Close button
    var closeBtn = popup.querySelector('.wb-gr-popup__close');
    if (closeBtn) {
        closeBtn.addEventListener('click', closePopup);
    }

    // Close on overlay click
    var overlay = popup.querySelector('.wb-gr-popup__overlay');
    if (overlay) {
        overlay.addEventListener('click', closePopup);
    }

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && shown) {
            closePopup();
        }
    });

    // Setup trigger
    switch (config.triggerType) {
        case 'time':
            setTimeout(showPopup, config.triggerValue * 1000);
            break;

        case 'exit':
            document.addEventListener('mouseleave', function (e) {
                if (e.clientY < 0) {
                    showPopup();
                }
            }, { once: true });
            break;

        case 'idle':
            var idleTimer;
            var resetIdle = function () {
                clearTimeout(idleTimer);
                idleTimer = setTimeout(showPopup, config.triggerValue * 1000);
            };
            ['mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(function (evt) {
                document.addEventListener(evt, resetIdle, { passive: true });
            });
            resetIdle();
            break;

        case 'scroll':
            var scrollHandler = function () {
                var scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
                if (scrollHeight <= 0) {
                    return;
                }
                var scrolled = (window.scrollY / scrollHeight) * 100;
                if (scrolled >= config.triggerValue) {
                    showPopup();
                    window.removeEventListener('scroll', scrollHandler);
                }
            };
            window.addEventListener('scroll', scrollHandler, { passive: true });
            break;

        default:
            setTimeout(showPopup, 5000);
    }

    // Form submission
    var form = document.getElementById('wb-gr-popup-form');
    if (!form) {
        return;
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var errorEl = popup.querySelector('.wb-gr-popup__error');
        var submitBtn = form.querySelector('.wb-gr-popup__submit');
        var submitText = form.querySelector('.wb-gr-popup__submit-text');
        var submitLoading = form.querySelector('.wb-gr-popup__submit-loading');

        // Basic validation
        var email = form.querySelector('input[name="email"]');
        var consent = form.querySelector('input[name="consent"]');

        if (!email || !email.value || !email.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            errorEl.textContent = 'Please enter a valid email address.';
            errorEl.style.display = 'block';
            return;
        }

        if (!consent || !consent.checked) {
            errorEl.textContent = 'You must agree to receive marketing communications.';
            errorEl.style.display = 'block';
            return;
        }

        // Hide error, show loading
        errorEl.style.display = 'none';
        submitBtn.disabled = true;
        submitText.style.display = 'none';
        submitLoading.style.display = 'inline';

        // Build form data
        var formData = new FormData(form);
        formData.append('action', 'subscribe');
        formData.append('token', config.token);
        formData.append('ajax', '1');

        fetch(config.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                persistState('wb_gr_subscribed', '1', config.cookieSubscribedMin);

                // Show success state
                var formContent = popup.querySelector('.wb-gr-popup__content--form');
                var successContent = popup.querySelector('.wb-gr-popup__content--success');

                if (formContent) {
                    formContent.style.display = 'none';
                }
                if (successContent) {
                    successContent.style.display = 'block';

                    if (data.discount_code) {
                        var discountBlock = successContent.querySelector('.wb-gr-popup__discount');
                        var codeEl = successContent.querySelector('.wb-gr-popup__discount-code');
                        if (discountBlock && codeEl) {
                            codeEl.textContent = data.discount_code;
                            discountBlock.style.display = 'block';
                        }
                    }
                }
            } else {
                errorEl.textContent = data.message || 'An error occurred. Please try again.';
                errorEl.style.display = 'block';
                submitBtn.disabled = false;
                submitText.style.display = 'inline';
                submitLoading.style.display = 'none';
            }
        })
        .catch(function () {
            errorEl.textContent = 'A connection error occurred. Please try again.';
            errorEl.style.display = 'block';
            submitBtn.disabled = false;
            submitText.style.display = 'inline';
            submitLoading.style.display = 'none';
        });
    });
})();
