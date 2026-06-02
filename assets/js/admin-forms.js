document.addEventListener('DOMContentLoaded', function () {
    function toggleRow(button) {
        var targetId = button.getAttribute('data-target');
        if (!targetId) return;
        var row = document.getElementById(targetId);
        if (!row) return;

        var isHidden = row.hasAttribute('hidden');
        if (isHidden) {
            row.removeAttribute('hidden');
            row.setAttribute('aria-hidden', 'false');
            button.setAttribute('aria-expanded', 'true');
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
