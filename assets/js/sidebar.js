document.addEventListener('DOMContentLoaded', function () {
    var body = document.body;
    var sidebar = document.querySelector('.sidebar');
    var toggle = document.querySelector('.sidebar-toggle');
    var backdrop = document.querySelector('.sidebar-backdrop');
    var main = document.getElementById('main-content');
    var storageKey = 'risksecure.sidebar.open';
    var mediaQuery = window.matchMedia('(max-width: 1080px)');

    if (!sidebar || !toggle || !backdrop || !main) {
        return;
    }

    function setExpanded(isOpen) {
        // If opening, ensure backdrop is available for animation
        if (isOpen) {
            try { backdrop.hidden = false; } catch (e) { /* ignore */ }
            // small delay gives browser a chance to paint before transition
            window.requestAnimationFrame(function () {
                body.classList.add('sidebar-open');
            });
        } else {
            body.classList.remove('sidebar-open');
        }

        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        toggle.classList.toggle('open', isOpen);

        // Update accessible label and visible text
        try {
            toggle.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
            var txt = toggle.querySelector('.sidebar-toggle-text');
            if (txt) txt.textContent = isOpen ? 'Close' : 'Menu';
        } catch (err) {
            // ignore
        }

        // When opened, focus first link for keyboard users
        if (isOpen) {
            var firstLink = sidebar.querySelector('nav.sidebar-nav a');
            if (firstLink) {
                setTimeout(function () { firstLink.focus(); }, 120);
            }
        }
    }

    function openSidebar() {
        setExpanded(true);
        try {
            localStorage.setItem(storageKey, 'true');
        } catch (error) {
            // Ignore storage failures.
        }
    }

    function closeSidebar() {
        setExpanded(false);
        try {
            localStorage.setItem(storageKey, 'false');
        } catch (error) {
            // Ignore storage failures.
        }
    }

    function toggleSidebar() {
        if (body.classList.contains('sidebar-open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    function syncFromStorage() {
        if (!mediaQuery.matches) {
            closeSidebar();
            return;
        }

        var persisted = null;
        try {
            persisted = localStorage.getItem(storageKey);
        } catch (error) {
            persisted = null;
        }

        if (persisted === 'true') {
            openSidebar();
        } else {
            closeSidebar();
        }
    }

    toggle.addEventListener('click', toggleSidebar);
    backdrop.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && body.classList.contains('sidebar-open')) {
            closeSidebar();
            toggle.focus();
        }
    });

    // Keyboard arrow navigation within the sidebar nav (Up/Down/Home/End)
    sidebar.addEventListener('keydown', function (event) {
        var nav = sidebar.querySelector('nav.sidebar-nav');
        if (!nav) return;

        var links = Array.from(nav.querySelectorAll('a'));
        if (!links.length) return;

        var active = document.activeElement;
        var idx = links.indexOf(active);

        if (['ArrowDown', 'ArrowRight', 'ArrowUp', 'ArrowLeft', 'Home', 'End'].indexOf(event.key) === -1) {
            return;
        }

        // Only handle when focus is inside the nav
        if (idx === -1) return;

        event.preventDefault();

        if (event.key === 'ArrowDown' || event.key === 'ArrowRight') {
            var next = links[(idx + 1) % links.length];
            next.focus();
            return;
        }

        if (event.key === 'ArrowUp' || event.key === 'ArrowLeft') {
            var prev = links[(idx - 1 + links.length) % links.length];
            prev.focus();
            return;
        }

        if (event.key === 'Home') {
            links[0].focus();
            return;
        }

        if (event.key === 'End') {
            links[links.length - 1].focus();
            return;
        }
    });

    sidebar.addEventListener('click', function (event) {
        if (mediaQuery.matches && event.target.closest('a')) {
            closeSidebar();
        }
    });

    // Hide backdrop after its fade-out transition completes when closing
    backdrop.addEventListener('transitionend', function (ev) {
        if (ev.propertyName === 'opacity' && !body.classList.contains('sidebar-open')) {
            try { backdrop.hidden = true; } catch (e) { /* ignore */ }
        }
    });

    if (mediaQuery.addEventListener) {
        mediaQuery.addEventListener('change', syncFromStorage);
    } else {
        mediaQuery.addListener(syncFromStorage);
    }

    syncFromStorage();

    main.addEventListener('focus', function () {
        if (mediaQuery.matches) {
            closeSidebar();
        }
    });

    // Top-bar dropdowns logic
    var dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(function (dropdown) {
        var btn = dropdown.querySelector('[data-toggle="dropdown"]');
        var menu = dropdown.querySelector('.dropdown-menu');
        if (!btn || !menu) return;

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = !menu.hasAttribute('hidden');
            
            // Close all other dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(function(m) {
                if (m !== menu) {
                    m.setAttribute('hidden', '');
                    var otherBtn = m.parentElement.querySelector('[data-toggle="dropdown"]');
                    if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
                }
            });

            if (isOpen) {
                menu.setAttribute('hidden', '');
                btn.setAttribute('aria-expanded', 'false');
            } else {
                menu.removeAttribute('hidden');
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });

    document.addEventListener('click', function () {
        document.querySelectorAll('.dropdown-menu').forEach(function (m) {
            m.setAttribute('hidden', '');
            var b = m.parentElement.querySelector('[data-toggle="dropdown"]');
            if (b) b.setAttribute('aria-expanded', 'false');
        });
    });
});
