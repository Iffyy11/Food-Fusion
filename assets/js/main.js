(function () {
    'use strict';

    function apiUrl(path) {
        var meta = document.querySelector('meta[name="ff-api-base"]');
        var base = meta ? meta.getAttribute('content') || '' : '';
        base = String(base).trim();
        if (base && base.slice(-1) !== '/') {
            base += '/';
        }
        return base + String(path).replace(/^\//, '');
    }

    function readApiResponse(response) {
        return response.text().then(function (text) {
            var data = {};
            if (text) {
                try {
                    data = JSON.parse(text);
                } catch (ignore) {
                    var snippet = text.replace(/\s+/g, ' ').trim().slice(0, 200);
                    return {
                        ok: false,
                        status: response.status,
                        data: {
                            message:
                                'The server did not return JSON (status ' +
                                response.status +
                                '). Often this means a PHP or database error. Details: ' +
                                (snippet || '(empty body)')
                        }
                    };
                }
            }
            return { ok: response.ok, status: response.status, data: data };
        });
    }

    function showToast(message, type) {
        var stack = document.getElementById('toast-stack');
        if (!stack || !message) return;
        var t = document.createElement('div');
        t.className = 'toast toast-' + (type || 'info');
        t.setAttribute('role', 'status');
        t.textContent = message;
        stack.appendChild(t);
        requestAnimationFrame(function () {
            t.classList.add('toast-show');
        });
        setTimeout(function () {
            t.classList.remove('toast-show');
            setTimeout(function () {
                t.remove();
            }, 280);
        }, 4200);
    }
    window.showToast = showToast;

    /* Theme + status bar (mobile) */
    function setThemeColorMeta(theme) {
        var m = document.querySelector('meta[name="theme-color"]');
        if (!m) return;
        m.setAttribute('content', theme === 'dark' ? '#1c1917' : '#c2410c');
    }
    function applyTheme(theme) {
        var root = document.documentElement;
        if (theme !== 'dark' && theme !== 'light') theme = 'light';
        root.setAttribute('data-theme', theme);
        setThemeColorMeta(theme);
        try {
            localStorage.setItem('ff-theme', theme);
        } catch (e) {}
    }
    setThemeColorMeta(document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');
    var themeBtn = document.getElementById('themeToggle');
    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            applyTheme(next);
        });
    }

    /* Scroll-to-top */
    var scrollFab = document.getElementById('scrollTopFab');
    function toggleScrollFab() {
        if (!scrollFab) return;
        scrollFab.classList.toggle('is-visible', window.scrollY > 400);
    }
    if (scrollFab) {
        window.addEventListener('scroll', toggleScrollFab, { passive: true });
        toggleScrollFab();
        scrollFab.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* Print recipe cards */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-print');
        if (!btn) return;
        var id = btn.getAttribute('data-print-target');
        var block = id ? document.getElementById(id) : null;
        if (!block) return;
        var title = block.querySelector('h3');
        var method = block.querySelector('.print-area');
        var w = window.open('', '_blank');
        if (!w) return;
        var html =
            '<!DOCTYPE html><html><head><title>Recipe</title><style>body{font-family:system-ui;padding:1.5rem;max-width:40rem;}h1{font-size:1.25rem;}</style></head><body><h1>' +
            (title ? title.textContent : 'Recipe') +
            '</h1>' +
            (method ? method.innerHTML : '') +
            '</body></html>';
        w.document.write(html);
        w.document.close();
        w.focus();
        w.print();
        w.close();
    });

    /* URL toasts */
    try {
        var params = new URLSearchParams(window.location.search);
        if (params.get('sent') === '1' && window.location.pathname.indexOf('contact.php') !== -1) {
            showToast('Message sent — we will get back to you soon.', 'success');
            params.delete('sent');
            var qs = params.toString();
            var clean = window.location.pathname + (qs ? '?' + qs : '');
            window.history.replaceState({}, '', clean);
        }
    } catch (e) {}

    var navToggle = document.getElementById('navToggle');
    var nav = document.getElementById('site-nav');
    var navBackdrop = document.getElementById('navBackdrop');
    function setMobileNavOpen(open) {
        if (!nav || !navToggle) return;
        nav.classList.toggle('open', open);
        navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        navToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        document.body.classList.toggle('nav-open', open);
        if (navBackdrop) {
            navBackdrop.classList.toggle('is-visible', open);
            navBackdrop.setAttribute('aria-hidden', open ? 'false' : 'true');
        }
    }
    if (navToggle && nav) {
        navToggle.addEventListener('click', function () {
            setMobileNavOpen(!nav.classList.contains('open'));
        });
        if (navBackdrop) {
            navBackdrop.addEventListener('click', function () {
                setMobileNavOpen(false);
            });
        }
        nav.querySelectorAll('a[href]').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.matchMedia('(max-width: 900px)').matches) {
                    setMobileNavOpen(false);
                }
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && nav.classList.contains('open')) {
                setMobileNavOpen(false);
            }
        });
        window.addEventListener(
            'resize',
            function () {
                if (!window.matchMedia('(max-width: 900px)').matches) {
                    setMobileNavOpen(false);
                }
            },
            { passive: true }
        );
    }

    var banner = document.getElementById('cookieBanner');
    if (banner && !localStorage.getItem('ff_cookie_ok')) {
        banner.hidden = false;
        banner.removeAttribute('hidden');
        banner.setAttribute('aria-hidden', 'false');
    }
    var accept = document.getElementById('acceptCookies');
    if (accept && banner) {
        accept.addEventListener('click', function () {
            localStorage.setItem('ff_cookie_ok', '1');
            banner.hidden = true;
            banner.setAttribute('aria-hidden', 'true');
            showToast('Cookie preference saved.', 'success');
        });
    }

    function initCarousel(rootSelector, slideSelector, dotSelector) {
        var root = document.querySelector(rootSelector);
        if (!root) return;
        var slides = root.querySelectorAll(slideSelector);
        var dots = root.querySelectorAll(dotSelector);
        if (!slides.length) return;
        var current = 0;
        function show(i) {
            current = (i + slides.length) % slides.length;
            slides.forEach(function (s, j) {
                s.classList.toggle('active', j === current);
            });
            dots.forEach(function (d, j) {
                d.classList.toggle('active', j === current);
            });
        }
        dots.forEach(function (d) {
            d.addEventListener('click', function () {
                show(parseInt(d.getAttribute('data-slide'), 10));
            });
        });
        setInterval(function () {
            show(current + 1);
        }, 7000);
    }

    initCarousel('#eventsCarousel', '.event-slide', '.slider-dots .dot');

    function wireModal(openBtn, modal, closeSel, onOpen) {
        if (!openBtn || !modal) return;
        openBtn.addEventListener('click', function () {
            modal.hidden = false;
            document.body.classList.add('modal-open');
            if (onOpen) onOpen();
        });
        modal.querySelectorAll(closeSel).forEach(function (el) {
            el.addEventListener('click', function () {
                modal.hidden = true;
                document.body.classList.remove('modal-open');
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) {
                modal.hidden = true;
                document.body.classList.remove('modal-open');
            }
        });
    }

    var joinModal = document.getElementById('joinUsModal');
    wireModal(document.getElementById('openJoinUs'), joinModal, '[data-close-join]', function () {
        var f = document.getElementById('joinUsForm');
        if (f) f.querySelector('input[name="first_name"]').focus();
    });

    var joinForm = document.getElementById('joinUsForm');
    var joinMsg = document.getElementById('joinUsMsg');
    if (joinForm) {
        joinForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(joinForm);
            var payload = {
                first_name: fd.get('first_name'),
                last_name: fd.get('last_name'),
                email: fd.get('email'),
                password: fd.get('password'),
                password_confirm: fd.get('password')
            };
            fetch(apiUrl('register.php'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify(payload)
            })
                .then(readApiResponse)
                .then(function (res) {
                    if (!joinMsg) return;
                    joinMsg.hidden = false;
                    var msg =
                        (res.data && res.data.message) ||
                        (res.ok ? 'Success.' : 'Request failed (HTTP ' + res.status + ').');
                    joinMsg.textContent = msg;
                    joinMsg.className = 'notice ' + (res.ok ? 'success' : 'error');
                    if (res.ok) {
                        joinForm.reset();
                        showToast('Account created — you can log in.', 'success');
                    }
                })
                .catch(function (err) {
                    if (joinMsg) {
                        joinMsg.hidden = false;
                        joinMsg.className = 'notice error';
                        joinMsg.textContent =
                            (err && err.message) ||
                            'Could not reach the server. Use http://localhost (not file://), ensure PHP is running, and run setup.php if the database is new.';
                    }
                });
        });
    }

    var suModal = document.getElementById('signUpNowModal');
    wireModal(document.getElementById('openSignUpNow'), suModal, '[data-close-su]', function () {
        var f = document.getElementById('signUpNowForm');
        if (f) f.querySelector('input[name="email"]').focus();
    });

    var suForm = document.getElementById('signUpNowForm');
    var suMsg = document.getElementById('signUpNowMsg');
    if (suForm) {
        suForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(suForm);
            fetch(apiUrl('newsletter_signup.php'), {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            })
                .then(readApiResponse)
                .then(function (res) {
                    if (!suMsg) return;
                    suMsg.hidden = false;
                    var msg =
                        (res.data && res.data.message) ||
                        (res.ok ? 'Success.' : 'Request failed (HTTP ' + res.status + ').');
                    suMsg.textContent = msg;
                    suMsg.className = 'notice ' + (res.ok ? 'success' : 'error');
                    if (res.ok) {
                        suForm.reset();
                        showToast('You are on the list — thanks!', 'success');
                    }
                })
                .catch(function (err) {
                    if (suMsg) {
                        suMsg.hidden = false;
                        suMsg.className = 'notice error';
                        suMsg.textContent =
                            (err && err.message) ||
                            'Could not reach the server. Check PHP/MySQL and open the site over http:// not file://.';
                    }
                });
        });
    }

})();
