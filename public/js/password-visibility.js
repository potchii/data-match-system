/**
 * Password Visibility Toggle
 * Adds lock/unlock icon functionality to password fields
 */
document.addEventListener('DOMContentLoaded', function () {
    const passwordFields = document.querySelectorAll('input[type="password"][data-toggle-password]');

    passwordFields.forEach(field => {
        const wrapper = field.parentElement;
        const toggleBtn = document.createElement('button');

        toggleBtn.type = 'button';
        toggleBtn.className = 'password-toggle-btn';
        toggleBtn.innerHTML = '<i class="fas fa-lock"></i>';
        toggleBtn.setAttribute('aria-label', 'Toggle password visibility');
        toggleBtn.setAttribute('title', 'Show password');

        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const icon = this.querySelector('i');

            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-lock');
                icon.classList.add('fa-lock-open');
                this.setAttribute('title', 'Hide password');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-lock-open');
                icon.classList.add('fa-lock');
                this.setAttribute('title', 'Show password');
            }
        });

        wrapper.style.position = 'relative';
        wrapper.appendChild(toggleBtn);
    });
});
