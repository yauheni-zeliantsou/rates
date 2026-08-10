const OAUTH_CLIENT_ID = 'web-frontend';
const ACCESS_TOKEN_KEY = 'access_token';

const form = document.querySelector('.login-form');
const errorMessage = document.querySelector('.login-form__error');

if (form) {
    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const formData = new FormData(form);
        const body = new URLSearchParams({
            grant_type: 'password',
            client_id: OAUTH_CLIENT_ID,
            username: formData.get('username') ?? '',
            password: formData.get('password') ?? '',
        });

        errorMessage.style.display = 'none';

        fetch('/oauth/token', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body,
        })
            .then(async (response) => {
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error ?? 'invalid_grant');
                }

                localStorage.setItem(ACCESS_TOKEN_KEY, data.access_token);
                window.location.href = 'rates.html';
            })
            .catch(() => {
                errorMessage.textContent = 'Invalid username or password.';
                errorMessage.style.display = 'block';
            });
    });
}
