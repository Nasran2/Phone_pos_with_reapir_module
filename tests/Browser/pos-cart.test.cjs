const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const item = { product_id: 12, name: 'Test Phone', stock: 18, quantity: 1, selling_price: 350, discount_type: 'fixed', discount_amount: 50 };

// Execute the actual POS methods with browser storage and checkout responses mocked.
// These tests never submit a sale to the application or its database.
function loadPOS(mode, storage = new Map(), options = {}) {
    const template = fs.readFileSync(path.join(__dirname, '../../resources/views/pos', `${mode}.blade.php`), 'utf8');
    const factory = mode === 'retail' ? 'retailPosApp' : 'posApp';
    let script = template.slice(template.indexOf(`function ${factory}()`)).split('</script>')[0];
    const settings = { taxPercentage: 0, taxMode: 'exclusive', enableTax: false, enableDiscount: true, currencySymbol: 'Rs.', decimalPlaces: 2 };
    const data = {
        products: [], categories: [], customers: [{ id: 1, name: 'Walk-in Customer' }, { id: 2, name: 'Customer' }],
        paymentMethods: [], holdSales: [], resumeHold: null, editSale: null,
        posSettings: settings, sourceType: 'store', activeLorryId: null, activeTripId: null,
    };
    script = script.replace(/@json\(([^\n()]*)\)/g, (_, expression) => {
        const setting = expression.match(/\$posSettings\['([^']+)'\]/);
        const variable = expression.match(/^\$(\w+)/);
        assert.ok(variable, `Unhandled Blade expression: ${expression}`);
        return JSON.stringify(setting ? settings[setting[1]] : data[variable[1]]);
    }).replace(/\{\{([^]*?)\}\}/g, (_, expression) => expression.includes('$activeStoreId') ? '1' : expression.includes('$sourceType') ? 'store' : '');

    const alerts = [];
    const requests = [];
    const location = { href: `http://pos.test/pos/${mode}?store_id=1&edit_sale_id=42&hold_id=8` };
    const printWindow = { closed: false, close() { this.closed = true; }, location: { replace(url) { options.onPrint?.(url, storage); } } };
    const context = vm.createContext({
        console, URL, setTimeout, setInterval() {},
        localStorage: { getItem(key) { return storage.get(key) ?? null; }, setItem(key, value) { storage.set(key, String(value)); } },
        document: { getElementById() { return { focus() {} }; } },
        window: {
            location, addEventListener() {}, open() { return printWindow; },
            history: { state: null, replaceState(_state, _title, url) { location.href = String(url); } },
        },
        Swal: { fire(message) { alerts.push(message); return Promise.resolve({ isConfirmed: Boolean(options.confirmClear) }); } },
        async fetch(url, config) {
            if (config?.method === 'POST') {
                requests.push(JSON.parse(config.body));
                if (options.networkFailure) throw new Error('Network unavailable');
                return { headers: { get() { return 'application/json'; } }, async json() {
                    return options.rejectCheckout ? { success: false, message: 'Stock unavailable' } : {
                        success: true, invoice_no: 'TEST-001', print_url: '/invoice/1', share_url: '/invoice/1', due_amount: 0, change: 0,
                    };
                } };
            }
            return { ok: true, async json() { return { products: [], customers: data.customers }; } };
        },
    });
    vm.runInContext(`${script}\nglobalThis.app = ${factory}();`, context);
    const app = context.app;
    app.$nextTick = callback => callback();
    app.init();
    return { app, storage, alerts, requests, location, printWindow };
}

function draft(mode, options) {
    const result = loadPOS(mode, new Map(), options);
    result.app.selectedCustomerId = 2;
    result.app.cart = [structuredClone(item)];
    result.app.recalculateCartTotals();
    return result;
}

async function settle(app) {
    for (let attempt = 0; attempt < 20; attempt++) {
        await new Promise(resolve => setImmediate(resolve));
        if (!app.checkoutBusy) return;
    }
    assert.fail('Checkout did not finish');
}

for (const mode of ['wholesale', 'retail']) {
    test(`${mode}: unfinished products survive refresh and leaving/returning`, () => {
        const { app, storage } = draft(mode);
        app.cart[0].quantity = 3;
        app.recalculateCartTotals();
        const refreshed = loadPOS(mode, storage);
        assert.equal(refreshed.app.cart.length, 1);
        assert.equal(refreshed.app.cart[0].quantity, 3);
        assert.equal(refreshed.app.cart[0].discount_amount, 50);
        assert.equal(loadPOS(mode, storage).app.cart.length, 1);
    });

    test(`${mode}: successful cash checkout starts an empty cart after returning`, async () => {
        const { app, storage, requests } = draft(mode);
        app.quickPay();
        await settle(app);
        assert.equal(requests.length, 1);
        assert.equal(requests[0].checkout_mode, 'standard');
        assert.equal(app.cart.length, 0);
        assert.equal(app.calculateGrandTotal(), 0);
        assert.deepEqual(JSON.parse(storage.get(`${mode}_cart_items`)), []);
        assert.equal(loadPOS(mode, storage).app.cart.length, 0);
        const receipt = mode === 'retail' ? app.successData : app.checkoutSuccessData;
        assert.equal(receipt.items.length, 1);
        assert.equal(receipt.items[0].product_id, item.product_id);
    });

    test(`${mode}: due checkout clears the saved cart before opening the print invoice`, async () => {
        let printed = false;
        const { app, storage, requests } = draft(mode, { onPrint(url, saved) {
            printed = true;
            assert.equal(url, '/invoice/1');
            assert.deepEqual(JSON.parse(saved.get(`${mode}_cart_items`)), []);
        } });
        app.printDueInvoice();
        await settle(app);
        assert.equal(requests[0].checkout_mode, 'due_print');
        assert.equal(printed, true);
        assert.equal(app.cart.length, 0);
        assert.equal(loadPOS(mode, storage).app.cart.length, 0);
    });

    for (const failure of ['rejectCheckout', 'networkFailure']) {
        test(`${mode}: ${failure} keeps the unfinished cart for retry`, async () => {
            const { app, storage, printWindow } = draft(mode, { [failure]: true });
            app.printDueInvoice();
            await settle(app);
            assert.equal(app.cart.length, 1);
            assert.equal(printWindow.closed, true);
            assert.equal(loadPOS(mode, storage).app.cart.length, 1);
        });
    }

    test(`${mode}: successful checkout removes completed edit/hold context`, async () => {
        const { app, location } = draft(mode);
        app.editSale = { id: 42 };
        app.resumeHold = { id: 8 };
        app.currentHoldId = 8;
        app.quickPay();
        await settle(app);
        assert.equal(app.editSale, null);
        assert.equal(app.resumeHold, null);
        assert.equal(app.currentHoldId, null);
        const url = new URL(location.href);
        assert.equal(url.searchParams.get('store_id'), '1');
        assert.equal(url.searchParams.has('edit_sale_id'), false);
        assert.equal(url.searchParams.has('hold_id'), false);
    });

    test(`${mode}: holding and recalling a sale keeps the queue separate from the active cart`, () => {
        const { app, storage } = draft(mode);
        app.holdCurrentSale();
        assert.equal(app.cart.length, 0);
        assert.equal(loadPOS(mode, storage).app.cart.length, 0);
        assert.equal(JSON.parse(storage.get(`${mode}_held_sales`)).length, 1);
        app.recallHeldSale(app.holdSales[0]);
        assert.equal(loadPOS(mode, storage).app.cart.length, 1);
        assert.equal(app.calculateGrandTotal(), 300);
        assert.deepEqual(JSON.parse(storage.get(`${mode}_held_sales`)), []);
    });
}

test('wholesale: New Sale persists the empty cart and resets totals', () => {
    const { app, storage } = draft('wholesale');
    app.resetPOSForNewSale();
    assert.equal(app.calculateGrandTotal(), 0);
    assert.equal(loadPOS('wholesale', storage).app.cart.length, 0);
});

test('wholesale: allocation list works for product selection, cart editing, and refreshed stock', () => {
    const { app } = loadPOS('wholesale');
    const allocation = { sale_id: 4, customer_name: 'Customer', invoice_no: 'WSL-004', invoice_url: '/sales/4', quantity: 5 };
    const product = { id: item.product_id, name: item.name, stock: 18, wholesale_price: 350, allocations: [allocation], variants: [] };
    app.products = [product];
    app.openProductFlow(product);
    assert.equal(app.selectedProductAllocations()[0].invoice_no, 'WSL-004');
    app.openEditItemModal(item, 0);
    assert.equal(app.selectedProductAllocations()[0].quantity, 5);
    app.products = [{ ...product, allocations: [] }];
    assert.equal(app.selectedProductAllocations().length, 0);
    app.closeProductModal();
    assert.equal(app.selectedProductAllocations().length, 0);
});
