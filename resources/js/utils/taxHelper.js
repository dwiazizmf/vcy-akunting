/**
 * Reusable helper untuk kalkulasi Hybrid JSONB Tax (seperti PPN, PPh, dll)
 * Dapat dipanggil di Svelte components.
 */

/**
 * Menghitung tax array untuk 1 item (baris).
 * 
 * @param {Number} basePrice - Harga satuan
 * @param {Number} qty - Jumlah barang
 * @param {Array} activeTaxes - Array of active tax objects dari backend { name, rate, type }
 * @param {Boolean} applyTax - Apakah checkbox Apply Tax global dicentang
 * @returns {Array} - Array of calculated tax details { name, rate, amount }
 */
export function calculateItemTax(basePrice, qty, activeTaxes, applyTax) {
    if (!applyTax || !activeTaxes || activeTaxes.length === 0) {
        return [];
    }

    const subtotal = Number(basePrice) * Number(qty);
    const taxDetails = [];

    activeTaxes.forEach(tax => {
        let amount = 0;
        if (tax.type === 'percentage') {
            amount = subtotal * (Number(tax.rate) / 100);
        } else if (tax.type === 'fixed') {
            amount = Number(tax.rate) * Number(qty);
        }

        taxDetails.push({
            name: tax.name,
            rate: Number(tax.rate),
            amount: amount
        });
    });

    return taxDetails;
}

/**
 * Merekapitulasi total tax per item dan total seluruh tax untuk header invoice.
 * 
 * @param {Array} items - Array of invoice items yang sudah punya tax_details dan subtotal
 * @returns {Object} - { total_item_subtotal, total_item_tax, grand_total, header_tax_details }
 */
export function calculateHeaderTotals(items) {
    let totalItemSubtotal = 0;
    let totalItemTax = 0;
    const headerTaxesMap = {};

    items.forEach(item => {
        // Asumsi item punya subtotal (base_price * qty)
        const subtotal = Number(item.base_price || item.price || 0) * Number(item.qty || item.quantity || 0);
        totalItemSubtotal += subtotal;

        let itemTaxTotal = 0;
        if (Array.isArray(item.tax_details)) {
            item.tax_details.forEach(tax => {
                const taxAmount = Number(tax.amount || 0);
                itemTaxTotal += taxAmount;

                // Akumulasi ke header
                if (!headerTaxesMap[tax.name]) {
                    headerTaxesMap[tax.name] = {
                        name: tax.name,
                        rate: tax.rate, // Asumsi rate sama untuk tax name yang sama
                        amount: 0
                    };
                }
                headerTaxesMap[tax.name].amount += taxAmount;
            });
        }
        totalItemTax += itemTaxTotal;
        
        // Simpan total tax di level item jika diperlukan (untuk kolom tax_amount)
        item.tax_amount = itemTaxTotal;
        item.total = subtotal + itemTaxTotal;
    });

    const header_tax_details = Object.values(headerTaxesMap);
    const grand_total = totalItemSubtotal + totalItemTax;

    return {
        total_item_subtotal: totalItemSubtotal,
        total_item_tax: totalItemTax,
        grand_total: grand_total,
        header_tax_details: header_tax_details
    };
}

/**
 * Menghitung ulang seluruh form data ketika ada perubahan (qty, harga, tax di-toggle).
 * Biasanya dipanggil via Svelte reactivity ($: recalculateAll(form, activeTaxes, applyTax)).
 * 
 * @param {Object} form - Objek useForm (dari Inertia)
 * @param {Array} activeTaxes - Data master taxes dari backend
 * @param {Boolean} applyTax - Toggle global apply tax
 */
export function recalculateAll(form, activeTaxes, applyTax) {
    if (!form.items || !Array.isArray(form.items)) return form;

    // 1. Recalculate item level taxes
    form.items.forEach(item => {
        // Sesuaikan dengan nama field harga & qty di form Anda. (Bisa base_price/price dan qty/quantity)
        const basePrice = item.price || item.base_price || 0;
        const qty = item.quantity || item.qty || 0;
        
        item.tax_details = calculateItemTax(basePrice, qty, activeTaxes, applyTax);
    });

    // 2. Recalculate header totals
    const totals = calculateHeaderTotals(form.items);

    // 3. Assign ke form object
    form.total_item_subtotal = totals.total_item_subtotal;
    form.total_item_tax = totals.total_item_tax;
    form.grand_total = totals.grand_total;
    form.header_tax_details = totals.header_tax_details;

    return form;
}
