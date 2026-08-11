function escapeHtml(value) {
    return String(value).replace(
        /[&<>"']/g,
        (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch],
    );
}

function groupByDate(rates) {
    const groups = new Map();

    for (const rate of rates) {
        if (!groups.has(rate.date)) groups.set(rate.date, []);
        groups.get(rate.date).push(rate);
    }

    return [...groups.entries()]
        .sort(([dateA], [dateB]) => dateB.localeCompare(dateA))
        .map(([date, currencies]) => ({
            date,
            currencies: [...currencies].sort((a, b) => a.currency_code.localeCompare(b.currency_code)),
        }));
}

function renderGroup({ date, currencies }) {
    const rows = currencies
        .map(
            ({ currency_code, currency_name, nominal, value }) => `
        <tr>
          <td>${escapeHtml(currency_code)}</td>
          <td>${escapeHtml(currency_name)}</td>
          <td>${nominal}</td>
          <td>${value.toFixed(4)}</td>
        </tr>
      `,
        )
        .join('');

    return `
    <div class="results__group">
      <div class="results__group-header">
        <span class="results__date">${date}</span>
        <span class="results__count">${currencies.length} currencies</span>
      </div>
      <table class="results__table">
        <thead>
          <tr>
            <th>Code</th>
            <th>Currency</th>
            <th>Nominal</th>
            <th>Rate, ₽</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
    </div>
  `;
}

export function renderResults(container, rates, currencyCodes) {
    const currencyLabel = currencyCodes.length ? currencyCodes.join(', ') : 'all currencies';
    const groups = groupByDate(rates);

    if (groups.length === 0) {
        container.innerHTML = `<p class="rates-filter__hint">No rates found for the selected period.</p>`;
        return;
    }

    container.innerHTML = `
    <div class="results__summary">
      <span>Dates found: ${groups.length} · ${escapeHtml(currencyLabel)}</span>
    </div>
    ${groups.map(renderGroup).join('')}
  `;
}

export function renderLoading(container) {
    container.innerHTML = `
    <div class="results__loading">
      <span class="spinner" aria-hidden="true"></span>
      <span>Loading rates…</span>
    </div>
  `;
}

export function renderError(container, error) {
    container.innerHTML = `<p class="rates-filter__hint" style="color: var(--color-danger, #c0392b);">${escapeHtml(error.message ?? 'Something went wrong.')}</p>`;
}
