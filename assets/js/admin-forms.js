document.addEventListener('DOMContentLoaded', function () {
    function toggleRow(button) {
        var targetId = button.getAttribute('data-target');
        if (!targetId) return;
        var row = document.getElementById(targetId);
        if (!row) return;

        var isHidden = row.hasAttribute('hidden');
        
        // If we are opening a form, and it's a form-drawer, close others.
        if (isHidden && row.classList.contains('form-drawer')) {
            document.querySelectorAll('.form-drawer').forEach(function(drawer) {
                if (drawer !== row && !drawer.hasAttribute('hidden')) {
                    drawer.setAttribute('hidden', '');
                    drawer.setAttribute('aria-hidden', 'true');
                    // Find the button that toggles this drawer and update its state if needed
                    var otherButton = document.querySelector('[data-toggle="edit-form"][data-target="' + drawer.id + '"]');
                    if (otherButton) {
                        otherButton.setAttribute('aria-expanded', 'false');
                    }
                }
            });
        }

        if (isHidden) {
            row.removeAttribute('hidden');
            row.setAttribute('aria-hidden', 'false');
            button.setAttribute('aria-expanded', 'true');
            
            // Smooth scroll to the form so the user knows it opened
            setTimeout(function() {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 50);
        } else {
            row.setAttribute('hidden', '');
            row.setAttribute('aria-hidden', 'true');
            button.setAttribute('aria-expanded', 'false');
        }
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-toggle="edit-form"]');
        if (!button) return;
        event.preventDefault();
        toggleRow(button);
    });
});
