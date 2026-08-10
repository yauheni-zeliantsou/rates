const ACCESS_TOKEN_KEY = 'access_token';

function authHeaders() {
    const token = localStorage.getItem(ACCESS_TOKEN_KEY);
    return token ? { Authorization: `Bearer ${token}` } : {};
}

async function apiGet(path) {
    const response = await fetch(path, { headers: authHeaders() });

    if (response.status === 401) {
        localStorage.removeItem(ACCESS_TOKEN_KEY);
        window.location.href = '/index.html';
        throw new Error('Session expired, please sign in again.');
    }

    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.error ?? 'Request failed');
    }

    return data;
}

export function getAllCurrencies() {
    return apiGet('/api/currencies');
}

export function getRates({ date, days, currencyCodes }) {
    const params = new URLSearchParams();
    if (date) params.set('date', date);
    if (days) params.set('days', String(days));
    if (currencyCodes.length) params.set('currencies', currencyCodes.join(','));

    return apiGet(`/api/rates?${params.toString()}`);
}
