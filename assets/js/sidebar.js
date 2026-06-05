document.addEventListener('DOMContentLoaded', function () {
    var body = document.body;
    var toggle = document.querySelector('.sidebar-toggle');
    var backdrop = document.querySelector('.sidebar-backdrop');
    var nav = document.querySelector('.top-nav');
    var storageKey = 'risksecure.nav.open';
    var mediaQuery = window.matchMedia('(max-width: 1150px)');

    function setExpanded(isOpen) {
        if (isOpen) {
            try { backdrop.hidden = false; } catch (e) {}
            window.requestAnimationFrame(function () {
                body.classList.add('sidebar-open');
            });
        } else {
            body.classList.remove('sidebar-open');
        }

        if (toggle) {
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            toggle.classList.toggle('open', isOpen);
        }
    }

    function openNav() {
        setExpanded(true);
        try { localStorage.setItem(storageKey, 'true'); } catch (e) {}
    }

    function closeNav() {
        setExpanded(false);
        try { localStorage.setItem(storageKey, 'false'); } catch (e) {}
    }

    function toggleNav() {
        if (body.classList.contains('sidebar-open')) {
            closeNav();
        } else {
            openNav();
        }
    }

    if (toggle) {
        toggle.addEventListener('click', toggleNav);
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeNav);
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && body.classList.contains('sidebar-open')) {
            closeNav();
            if (toggle) toggle.focus();
        }
    });

    // Close mobile nav when a link is clicked
    if (nav) {
        nav.addEventListener('click', function (event) {
            if (mediaQuery.matches && event.target.closest('a')) {
                closeNav();
            }
        });
    }

    // Dropdown menu logic
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

    // Responsive sync
    if (mediaQuery.addEventListener) {
        mediaQuery.addEventListener('change', function() {
            if (!mediaQuery.matches) closeNav();
        });
    }
});
