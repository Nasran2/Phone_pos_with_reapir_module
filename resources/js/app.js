function initializeWorkspace() {
    document.querySelectorAll('.global-search').forEach(root => {
        if (root.dataset.ready) return;
        root.dataset.ready = 'true';
        const input = root.querySelector('input'), panel = root.querySelector('.search-results');
        let timer, controller, generation = 0;
        const close = () => { panel.classList.add('hidden'); input.setAttribute('aria-expanded', 'false'); };
        const message = text => { panel.replaceChildren(); const p = document.createElement('p'); p.className = 'p-3 text-sm text-slate-500'; p.textContent = text; panel.append(p); panel.classList.remove('hidden'); input.setAttribute('aria-expanded', 'true'); };
        input.addEventListener('input', () => {
            clearTimeout(timer); controller?.abort(); const version = ++generation;
            if (input.value.trim().length < 2) { close(); return; }
            message('Searching…');
            timer = setTimeout(async () => {
                controller = new AbortController();
                try {
                    const response = await fetch(root.dataset.searchUrl + '?' + new URLSearchParams({q: input.value.trim()}), {headers: {Accept: 'application/json'}, signal: controller.signal});
                    if (!response.ok) throw new Error();
                    const data = await response.json();
                    if (version !== generation) return;
                    message(data.results.length ? '' : 'No matching results.');
                    data.results.forEach(result => {
                        const link = document.createElement('a'); link.href = result.url;
                        link.className = 'block rounded-lg p-3 text-sm hover:bg-blue-50 focus:bg-blue-50';
                        link.textContent = result.type + ' · ' + result.label; panel.append(link);
                    });
                } catch (error) { if (error.name !== 'AbortError') message('Search unavailable. Please try again.'); }
            }, 300);
        });
        root.addEventListener('keydown', event => {
            if (event.key === 'Escape') { close(); input.focus(); }
            const links = [...panel.querySelectorAll('a')];
            if (['ArrowDown', 'ArrowUp'].includes(event.key) && links.length) {
                event.preventDefault(); const current = links.indexOf(document.activeElement);
                links[(current + (event.key === 'ArrowDown' ? 1 : links.length - 1)) % links.length].focus();
            }
        });
        document.addEventListener('click', event => { if (!root.contains(event.target)) close(); });
    });
    // Compact transaction cards retain every column and its actions on a phone.
    document.querySelectorAll('.report-shell table, .dashboard-content table').forEach(table => {
        const labels = [...table.querySelectorAll('thead th')].map(th => th.textContent.trim());
        if (!labels.length || table.querySelector('tbody [rowspan]')) return;
        table.classList.add('mobile-record-table');
        table.querySelectorAll('tbody tr').forEach(row => {
            [...row.children].forEach((cell, index) => { if (!cell.hasAttribute('colspan')) cell.dataset.label = labels[index] || ''; });
        });
    });
    document.querySelectorAll('.report-filters').forEach(details => { if (matchMedia('(max-width: 767px)').matches) details.open = false; });
    document.querySelectorAll('label').forEach((label, index) => {
        if (label.htmlFor || label.querySelector('input, select, textarea')) return;
        const control = label.nextElementSibling;
        if (control?.matches('input, select, textarea')) {
            control.id ||= 'workspace-field-' + index;
            label.htmlFor = control.id;
        }
    });
    document.querySelectorAll('select').forEach(select => {
        if (select.options.length < 20 || select.dataset.searchable || select.hasAttribute('x-model') || select.hasAttribute('x-model.number') || select.multiple) return;
        select.dataset.searchable = 'true';
        const search = document.createElement('input'); search.type = 'search'; search.placeholder = 'Search options…';
        search.className = 'w-full rounded-lg border border-slate-200 mb-2';
        search.setAttribute('aria-label', 'Search ' + (select.labels?.[0]?.textContent.trim() || 'options'));
        const options = [...select.options].map(option => option.cloneNode(true));
        search.addEventListener('input', () => {
            const selected = select.value, term = search.value.toLowerCase();
            select.replaceChildren(...options.filter(option => !option.value || option.value === selected || option.textContent.toLowerCase().includes(term)).map(option => option.cloneNode(true)));
            select.value = selected;
        });
        select.before(search);
    });
    document.querySelectorAll('input[type=number]').forEach(input => { input.inputMode = input.step && input.step !== '1' ? 'decimal' : 'numeric'; });
}
window.shareInvoice = async function(invoice) {
    if (!invoice?.print_url) return;
    const shareUrl = invoice.share_url || invoice.print_url;
    try {
        if (navigator.share) { await navigator.share({title: 'Invoice ' + invoice.invoice_no, url: shareUrl}); return; }
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(shareUrl);
            await Swal.fire({icon: 'success', text: 'Invoice link copied.'});
        } else {
            await Swal.fire({title: 'Share invoice', input: 'text', inputValue: shareUrl, inputAttributes: {readonly: true}, text: 'Copy this link to share the invoice.'});
        }
    } catch (error) { if (error.name !== 'AbortError') await Swal.fire({icon: 'error', text: 'Unable to share. Open the invoice and copy its address.'}); }
};
document.addEventListener('DOMContentLoaded', initializeWorkspace);
document.addEventListener('livewire:navigated', initializeWorkspace);
