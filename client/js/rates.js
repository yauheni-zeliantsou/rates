import { getAllCurrencies, getRates } from './api.js';
import { initCurrencySelect } from './currency-select.js';
import { renderResults, renderError, renderLoading } from './render-results.js';

const currencySelect = document.querySelector('.currency-select');
const form = document.querySelector('.rates-filter');
const resultsContainer = document.querySelector('.results');
const submitButton = form?.querySelector('.rates-filter__submit');

if (currencySelect) {
    getAllCurrencies().then((currencies) => {
        initCurrencySelect(currencySelect, currencies);
    });
}

if (form) {
    form.addEventListener('submit', (event) => {
        event.preventDefault();

        if (!form.reportValidity()) return;

        const formData = new FormData(form);
        const date = formData.get('date') || null;
        const days = formData.get('days');
        const currencyCodes = formData.getAll('currency_codes[]');

        submitButton.disabled = true;
        renderLoading(resultsContainer);

        getRates({ date, days, currencyCodes })
            .then((rates) => {
                renderResults(resultsContainer, rates, currencyCodes);
            })
            .catch((error) => {
                renderError(resultsContainer, error);
            })
            .finally(() => {
                submitButton.disabled = false;
            });
    });
}
