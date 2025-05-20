document.addEventListener('DOMContentLoaded', function () {
    // Charger SweetAlert2
    const Swal = window.Swal;

    // Afficher/masquer le mot de passe
    const togglePassword = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('password');
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.setAttribute('name', type === 'password' ? 'eye-outline' : 'eye-off-outline');
        });
    }

    // Valider le formulaire de connexion
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            const isLocked = loginForm.querySelector('button[type="submit"]').hasAttribute('disabled');

            if (isLocked) {
                e.preventDefault();
                showLockoutCountdown();
            } else if (!email || !password) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Champs manquants',
                    text: 'Veuillez remplir l\'email et le mot de passe.',
                    confirmButtonColor: '#3b82f6',
                });
            }
        });
    }

    // Gérer les boutons sociaux
    const socialButtons = document.querySelectorAll('.social-btn');
    socialButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            if (this.hasAttribute('disabled')) {
                e.preventDefault();
                showLockoutCountdown();
            } else {
                const provider = this.getAttribute('data-provider');
                Swal.fire({
                    icon: 'info',
                    title: `Connexion avec ${provider}`,
                    text: `La connexion via ${provider} n\'est pas encore implémentée.`,
                    confirmButtonColor: '#3b82f6',
                });
            }
        });
    });

    // Afficher les erreurs PHP avec SweetAlert2
    const errorMessage = document.getElementById('error-message');
    if (errorMessage) {
        const isLockoutError = errorMessage.innerText.includes('Trop de tentatives');
        if (isLockoutError) {
            showLockoutCountdown();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Erreur de connexion',
                html: errorMessage.innerHTML,
                confirmButtonColor: '#3b82f6',
            });
        }
    }

    // Fonction pour afficher le compte à rebours de blocage
    function showLockoutCountdown() {
        let remaining = parseInt(document.getElementById('error-message')?.innerText.match(/dans (\d+) secondes/)?.[1] || 10);
        if (remaining <= 0) return;

        Swal.fire({
            icon: 'error',
            title: 'Compte bloqué',
            text: `Trop de tentatives. Réessayez dans ${remaining} secondes.`,
            confirmButtonColor: '#3b82f6',
            showConfirmButton: false,
            timer: remaining * 1000,
            didOpen: () => {
                const content = Swal.getHtmlContainer();
                const timerInterval = setInterval(() => {
                    remaining--;
                    if (remaining <= 0) {
                        clearInterval(timerInterval);
                        // Réactiver le formulaire
                        document.querySelectorAll('#login-form input, #login-form button').forEach(el => el.removeAttribute('disabled'));
                        document.querySelectorAll('.social-btn').forEach(el => el.removeAttribute('disabled'));
                        Swal.close();
                    } else {
                        content.querySelector('p').textContent = `Trop de tentatives. Réessayez dans ${remaining} secondes.`;
                    }
                }, 1000);
            }
        });
    }
});