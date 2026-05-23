document.addEventListener('DOMContentLoaded', function () {
    function createError(el, msg) {
        removeError(el);
        el.classList.add('field-error');
        el.setAttribute('aria-invalid', 'true');
        var id = el.id || ('fv_' + Math.random().toString(36).slice(2, 9));
        el.id = id;
        var hint = document.createElement('div');
        hint.className = 'error-text';
        hint.id = id + '_error';
        hint.textContent = msg;
        hint.setAttribute('role', 'alert');
        el.parentNode && el.parentNode.appendChild(hint);
        el.setAttribute('aria-describedby', hint.id);
    }

    function removeError(el) {
        el.classList.remove('field-error');
        el.removeAttribute('aria-invalid');
        var desc = el.getAttribute('aria-describedby');
        if (desc) {
            var prev = document.getElementById(desc);
            if (prev && prev.parentNode) prev.parentNode.removeChild(prev);
            el.removeAttribute('aria-describedby');
        }
    }

    function validateField(el) {
        removeError(el);
        if (el.hasAttribute('required')) {
            if (!el.value || el.value.trim() === '') {
                createError(el, 'This field is required.');
                return false;
            }
        }
        var type = el.getAttribute('type');
        if (type === 'email' && el.value) {
            var re = /^\S+@\S+\.\S+$/;
            if (!re.test(el.value)) {
                createError(el, 'Please enter a valid email address.');
                return false;
            }
        }
        if (el.hasAttribute('minlength')) {
            var min = parseInt(el.getAttribute('minlength'), 10);
            if (el.value.length < min) {
                createError(el, 'Minimum ' + min + ' characters required.');
                return false;
            }
        }
        return true;
    }

    function attach(form) {
        var fields = Array.from(form.querySelectorAll('input,textarea,select'));
        fields.forEach(function (f) {
            f.addEventListener('input', function () { validateField(f); });
            f.addEventListener('blur', function () { validateField(f); });
        });

        form.addEventListener('submit', function (ev) {
            var valid = true;
            fields.forEach(function (f) { if (!validateField(f)) valid = false; });
            if (!valid) {
                ev.preventDefault();
                var firstError = form.querySelector('.field-error');
                if (firstError) firstError.focus();
            }
        });
    }

    var forms = document.querySelectorAll('form[data-validate="true"]');
    forms.forEach(function (f) { attach(f); });
});
