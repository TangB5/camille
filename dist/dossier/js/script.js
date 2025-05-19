 document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('#login-form');
            const emailInput = document.querySelector('#email');
            const passwordInput = document.querySelector('#password');
            const errorDiv = document.querySelector('#error-message');
            const socialButtons = document.querySelectorAll('.social-btn');

            form.addEventListener('submit', (e) => {
                let error = '';

                // Vérification de l'email
                if (!emailInput.value.includes('@') || emailInput.value.length < 5) {
                    error = 'Veuillez entrer un email valide.';
                }
                // Vérification du mot de passe
                else if (passwordInput.value.length < 6) {
                    error = 'Le mot de passe doit contenir au moins 6 caractères.';
                }

                if (error) {
                    e.preventDefault();
                    if (errorDiv) {
                        errorDiv.textContent = error;
                        errorDiv.classList.remove('hidden');
                    }
                }
            });

            // Simulation des connexions sociales
            socialButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const provider = button.dataset.provider;
                    alert(`Connexion via ${provider} : Cette fonctionnalité nécessite une intégration ${provider === 'Apple' ? 'Sign in with Apple' : 'OAuth 2.0'}.`);
                });
            });
        });