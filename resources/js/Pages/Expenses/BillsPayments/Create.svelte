<script>
    import AppLayout from '../../../Layouts/AppLayout.svelte';
    import { useForm, router } from '@inertiajs/svelte';
    import { Button } from '$lib/components/ui/button';
    import { Input } from '$lib/components/ui/input';
    import CoaSelect from '../../../Components/CoaSelect.svelte';
    import SearchableSelect from '../../../Components/SearchableSelect.svelte';
    import { ArrowLeft, Plus, Trash2, Loader2 } from 'lucide-svelte';
    import { showToast } from '../../../Stores/toast.js';

    export let vendors = [];
    export let categories = [];
    export let accounts = [];
    export let taxes = [];
    export let unpaidExpenses = [];
    export let selectedVendorId = null;

    let isLoading = false;

    const form = useForm({
        payment_date: new Date().toISOString().split('T')[0],
        vendor_id: selectedVendorId || '',
        payment_category_id: '',
        account_id: '',
        notes: '',
        lines: [],
        taxes: []
    });

    // When vendor changes, fetch their unpaid bills
    function onVendorChange(event) {
        const vid = event.target ? event.target.value : event.detail?.value;
        if (!vid) return;
        
        $form.vendor_id = vid;
        router.reload({
            data: { vendor_id: vid },
            only: ['unpaidExpenses', 'selectedVendorId'],
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                // Initialize lines with empty amounts
                $form.lines = unpaidExpenses.map(exp => ({
                    expense_id: exp.id,
                    expense_number: exp.expense_number,
                    expense_date: exp.expense_date,
                    grand_total: exp.grand_total,
                    amount_paid: 0,
                    selected: false
                }));
            }
        });
    }

    // Taxes
    function addTax() {
        $form.taxes = [...$form.taxes, { tax_id: '' }];
    }
    function removeTax(index) {
        $form.taxes = $form.taxes.filter((_, i) => i !== index);
    }

    // Auto-fill amount paid when a bill is checked
    function toggleLine(index) {
        if ($form.lines[index].selected) {
            $form.lines[index].amount_paid = $form.lines[index].grand_total;
        } else {
            $form.lines[index].amount_paid = 0;
        }
    }

    $: totalSelected = $form.lines.filter(l => l.selected).reduce((sum, l) => sum + parseFloat(l.amount_paid || 0), 0);
    
    $: totalTax = $form.taxes.reduce((sum, t) => {
        if (!t.tax_id) return sum;
        const tax = taxes.find(tx => tx.id == t.tax_id);
        if (!tax) return sum;
        return sum + (tax.type === 'fixed' ? parseFloat(tax.rate) : totalSelected * (parseFloat(tax.rate) / 100));
    }, 0);

    $: netPayment = totalSelected - totalTax;

    function submit() {
        // Filter out unselected lines
        const submission = { ...$form };
        submission.lines = submission.lines.filter(l => l.selected && l.amount_paid > 0);

        if (submission.lines.length === 0) {
            showToast('Silakan pilih minimal 1 tagihan untuk dibayar.', 'error');
            return;
        }

        isLoading = true;
        router.post('/expense-payments', submission, {
            onFinish: () => isLoading = false,
            onSuccess: () => showToast('Pembayaran berhasil disimpan.', 'success'),
            onError: (err) => {
                console.error(err);
                if (err.error) {
                    showToast(err.error, 'error');
                } else {
                    showToast(Object.values(err)[0] || 'Terjadi kesalahan.', 'error');
                }
            }
        });
    }
</script>

<AppLayout title="Create Bill Payment">
    <div class="p-6 max-w-5xl mx-auto mb-20">
        <div class="flex items-center gap-4 mb-8">
            <a href="/expense-payments">
                <Button variant="outline" size="icon" class="h-9 w-9 bg-white cursor-pointer"><ArrowLeft class="h-4 w-4" /></Button>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">New Bill Payment</h1>
                <p class="text-sm text-slate-500 mt-1">Select a vendor and pay their outstanding bills.</p>
            </div>
        </div>

        {#if $form.errors.error}
            <div class="bg-rose-50 text-rose-700 p-4 rounded-xl mb-6 border border-rose-200 font-medium">
                {$form.errors.error}
            </div>
        {/if}

        <form on:submit|preventDefault={submit}>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Header Info -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 space-y-4">
                    <h3 class="font-bold text-slate-800 border-b pb-2 mb-4">Payment Details</h3>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Vendor <span class="text-rose-500">*</span></label>
                        <select value={$form.vendor_id} on:change={onVendorChange} class="w-full h-10 border border-slate-300 rounded-md px-3 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white" required>
                            <option value="">-- Select Vendor --</option>
                            {#each vendors as vendor}
                                <option value={vendor.id}>{vendor.name}</option>
                            {/each}
                        </select>
                        {#if $form.errors.vendor_id}<p class="text-xs text-rose-500 mt-1">{$form.errors.vendor_id}</p>{/if}
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Payment Date <span class="text-rose-500">*</span></label>
                        <Input type="date" bind:value={$form.payment_date} class="h-10 bg-white" required />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Notes</label>
                        <Input bind:value={$form.notes} class="h-10 bg-white" placeholder="Payment reference..." />
                    </div>
                </div>

                <!-- Limits & Account -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 space-y-4">
                    <h3 class="font-bold text-slate-800 border-b pb-2 mb-4">Account & Category</h3>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Payment Category <span class="text-rose-500">*</span></label>
                        <select bind:value={$form.payment_category_id} class="w-full h-10 border border-slate-300 rounded-md px-3 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white" required>
                            <option value="">-- Select Category --</option>
                            {#each categories as cat}
                                <option value={cat.id}>{cat.code} - {cat.name}</option>
                            {/each}
                        </select>
                        {#if $form.errors.payment_category_id}<p class="text-xs text-rose-500 mt-1">{$form.errors.payment_category_id}</p>{/if}
                    </div>

                    <div class="pt-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">COA Bank/Kas <span class="text-rose-500">*</span></label>
                        <CoaSelect bind:value={$form.account_id} options={accounts} placeholder="Select Bank/Kas..." />
                        {#if $form.errors.account_id}<p class="text-xs text-rose-500 mt-1">{$form.errors.account_id}</p>{/if}
                    </div>
                </div>
            </div>

            <!-- Bills List -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-6">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50 rounded-t-xl">
                    <h3 class="font-bold text-slate-800">Outstanding Bills</h3>
                    {#if !$form.vendor_id}
                        <p class="text-sm text-amber-600 mt-1">Select a vendor to see their unpaid bills.</p>
                    {/if}
                </div>

                <div class="overflow-x-auto min-h-[150px]">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-white border-b border-slate-200 text-slate-600">
                            <tr>
                                <th class="p-4 w-12 text-center">Pay</th>
                                <th class="p-4 font-semibold">Bill Number</th>
                                <th class="p-4 font-semibold">Date</th>
                                <th class="p-4 font-semibold text-right">Bill Total</th>
                                <th class="p-4 font-semibold text-right">Amount to Pay</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            {#if $form.vendor_id && $form.lines.length === 0}
                                <tr><td colspan="5" class="p-8 text-center text-slate-500">No outstanding bills for this vendor.</td></tr>
                            {:else}
                                {#each $form.lines as line, index}
                                    <tr class="hover:bg-slate-50/50 {line.selected ? 'bg-teal-50/30' : ''}">
                                        <td class="p-4 text-center">
                                            <input type="checkbox" bind:checked={line.selected} on:change={() => toggleLine(index)} class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300" />
                                        </td>
                                        <td class="p-4 font-medium text-slate-900">{line.expense_number}</td>
                                        <td class="p-4 text-slate-600">{new Date(line.expense_date).toLocaleDateString('id-ID')}</td>
                                        <td class="p-4 text-right text-slate-600 font-semibold">
                                            Rp {parseFloat(line.grand_total).toLocaleString('id-ID')}
                                        </td>
                                        <td class="p-4 text-right">
                                            <Input type="number" step="0.01" bind:value={line.amount_paid} disabled={!line.selected} class="w-32 h-9 text-right inline-block bg-white" required={line.selected} />
                                        </td>
                                    </tr>
                                {/each}
                            {/if}
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tax Deductions -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 mb-6">
                <div class="flex items-center justify-between border-b pb-2 mb-4">
                    <h3 class="font-bold text-slate-800">Taxes / Deductions (Optional)</h3>
                    <Button type="button" variant="outline" size="sm" on:click={addTax} class="h-8 gap-1"><Plus class="h-3 w-3" /> Add Tax</Button>
                </div>
                
                {#if $form.taxes.length === 0}
                    <p class="text-sm text-slate-500 italic">No taxes applied.</p>
                {:else}
                    <div class="space-y-3">
                        {#each $form.taxes as tax, index}
                            <div class="flex items-center gap-3">
                                <select bind:value={tax.tax_id} class="w-1/2 h-10 border border-slate-300 rounded-md px-3 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 bg-white" required>
                                    <option value="">-- Select Tax --</option>
                                    {#each taxes as t}
                                        <option value={t.id}>{t.name} ({t.type === 'percentage' ? t.rate + '%' : 'Rp ' + parseFloat(t.rate).toLocaleString('id-ID')})</option>
                                    {/each}
                                </select>
                                <Button type="button" variant="ghost" size="icon" class="text-rose-500 hover:text-rose-600 hover:bg-rose-50 h-10 w-10" on:click={() => removeTax(index)}>
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        {/each}
                    </div>
                {/if}
            </div>

            <!-- Payment Summary -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-6">
                <div class="bg-slate-50 p-6 rounded-xl flex flex-col items-end space-y-2">
                    <div class="flex items-center justify-between w-64 text-sm text-slate-600">
                        <span>Gross Payment:</span>
                        <span class="font-semibold text-slate-800">Rp {totalSelected.toLocaleString('id-ID', { minimumFractionDigits: 2 })}</span>
                    </div>
                    {#if totalTax > 0}
                        <div class="flex items-center justify-between w-64 text-sm text-rose-600">
                            <span>Tax Deductions:</span>
                            <span class="font-semibold">- Rp {totalTax.toLocaleString('id-ID', { minimumFractionDigits: 2 })}</span>
                        </div>
                    {/if}
                    <div class="flex items-center justify-between w-64 text-base pt-2 border-t border-slate-200">
                        <span class="font-bold text-slate-800">Net Transfer:</span>
                        <span class="font-bold text-teal-700 text-lg">Rp {netPayment.toLocaleString('id-ID', { minimumFractionDigits: 2 })}</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pb-8">
                <a href="/expense-payments">
                    <Button type="button" variant="outline" class="w-32 bg-white cursor-pointer">Cancel</Button>
                </a>
                <Button type="submit" disabled={isLoading} class="w-40 bg-teal-600 hover:bg-teal-700 text-white cursor-pointer gap-2">
                    {#if isLoading}<Loader2 class="h-4 w-4 animate-spin" />{:else}Save Payment{/if}
                </Button>
            </div>
        </form>
    </div>
</AppLayout>
