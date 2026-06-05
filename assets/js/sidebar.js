document.addEventListener('DOMContentLoaded', function () {
    var body = document.body;
    var sidebar = document.querySelector('.nav-drawer');
    var toggle = document.querySelector('.sidebar-toggle');
    var closeBtn = document.querySelector('.drawer-close');
    var backdrop = document.querySelector('.sidebar-backdrop');
    var main = document.getElementById('main-content');
    var storageKey = 'risksecure.sidebar.open';

    if (!sidebar || !toggle || !backdrop || !main) {
        return;
    }

    function setExpanded(isOpen) {
        if (isOpen) {
            backdrop.hidden = false;
            window.requestAnimationFrame(function () {
                body.classList.add('sidebar-open');
            });
        } else {
            body.classList.remove('sidebar-open');
        }

        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        toggle.classList.toggle('open', isOpen);

        if (isOpen) {
            var firstLink = sidebar.querySelector('nav a');
            if (firstLink) {
                setTimeout(function () { firstLink.focus(); }, 120);
            }
        }
    }

    function openSidebar() {
        setExpanded(true);
    }

    function closeSidebar() {
        setExpanded(false);
    }

    function toggleSidebar() {
        if (body.classList.contains('sidebar-open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    toggle.addEventListener('click', toggleSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    backdrop.addEventListener('click', closeSidebar);
    
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && body.classList.contains('sidebar-open')) {
            closeSidebar();
            toggle.focus();
        }
    });

    sidebar.addEventListener('click', function (event) {
        if (event.target.closest('a')) {
            closeSidebar();
        }
    });

    backdrop.addEventListener('transitionend', function (ev) {
        if (ev.propertyName === 'opacity' && !body.classList.contains('sidebar-open')) {
            backdrop.hidden = true;
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
