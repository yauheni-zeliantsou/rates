export function initCurrencySelect(root, currencies) {
    const trigger = root.querySelector('.currency-select__trigger');
    const panel = root.querySelector('.currency-select__panel');

    panel.innerHTML = currencies
        .map(
            ({ code, name }) => `
        <label class="currency-select__option">
          <input type="checkbox" name="currency_codes[]" value="${code}">
          <span class="currency-select__code">${code}</span>
          <span class="currency-select__name">${name}</span>
        </label>
      `,
        )
        .join('');

    const checkboxes = [...panel.querySelectorAll('input[type="checkbox"]')];

    function updateTrigger() {
        const selected = checkboxes.filter((cb) => cb.checked).map((cb) => cb.value);
        trigger.textContent = selected.length ? selected.join(', ') : 'All currencies';
    }

    function openPanel() {
        panel.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
    }

    function closePanel() {
        panel.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
    }

    trigger.addEventListener('click', (event) => {
        event.stopPropagation();
        panel.hidden ? openPanel() : closePanel();
    });

    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateTrigger));

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) closePanel();
    });
}
