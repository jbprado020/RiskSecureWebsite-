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
        body.classList.toggle('sidebar-open', isOpen);
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        backdrop.hidden = !isOpen;
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

    sidebar.addEventListener('click', function (event) {
        if (mediaQuery.matches && event.target.closest('a')) {
            closeSidebar();
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
});
