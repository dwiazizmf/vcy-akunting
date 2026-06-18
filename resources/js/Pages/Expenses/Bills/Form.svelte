<script>
    import AppLayout from '../../../Layouts/AppLayout.svelte';
    import { useForm, router } from '@inertiajs/svelte';
    import { Button } from '$lib/components/ui/button';
    import { Input } from '$lib/components/ui/input';
    import CoaSelect from '../../../Components/CoaSelect.svelte';
    import { showToast } from '../../../Stores/toast.js';

    export let expense;
    export let isEdit;
    export let vendors = [];
    export let accounts = [];
    export let bankAccounts = [];
    export let taxes = [];

    const form = useForm({
        expense_number: expense.expense_number || '',
        vendor_id: expense.vendor_id || '',
        expense_date: expense.expense_date ? expense.expense_date.split('T')[0] : new Date().toISOString().split('T')[0],
        due_date: expense.due_date ? expense.due_date.split('T')[0] : '',
        is_direct_expense: expense.is_direct_expense || false,
        bank_account_id: expense.bank_account_id || '',
        notes: expense.notes || '',
        items: expense.items && expense.items.length > 0 ? expense.items : [{ account_id: '', description: '', amount: 0, tax_id: '', tax_amount: 0, total: 0 }]
    });

    // Reactive calculations
    $: {
        $form.items.forEach(item => {
            let baseAmount = parseFloat(item.amount) || 0;
            let taxAmount = 0;
            
            if (item.tax_id) {
                const selectedTax = taxes.find(t => t.id === parseInt(item.tax_id));
                if (selectedTax) {
                    taxAmount = selectedTax.type === 'fixed' ? parseFloat(selectedTax.rate) : baseAmount * (selectedTax.rate / 100);
                }
            }
            item.tax_amount = taxAmount;
            item.total = baseAmount + taxAmount;
        });
    }

    $: subtotal = $form.items.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0);
    $: totalTax = $form.items.reduce((sum, item) => sum + (parseFloat(item.tax_amount) || 0), 0);
    $: grandTotal = subtotal + totalTax;

    function addItem() {
        $form.items = [...$form.items, { account_id: '', description: '', amount: 0, tax_id: '', tax_amount: 0, total: 0 }];
    }

    function removeItem(index) {
        $form.items = $form.items.filter((_, i) => i !== index);
    }

    function submit() {
        const options = {
            onSuccess: () => showToast(`Expense ${isEdit ? 'updated' : 'created'} successfully!`, 'success'),
            onError: (err) => showToast(Object.values(err)[0] || 'Failed to save expense.', 'error')
        };
        if (isEdit) {
            $form.put(`/expenses/${expense.id}`, options);
        } else {
            $form.post('/expenses', options);
        }
    }
</script>

<AppLayout title={isEdit ? 'Edit Expense' : 'Create Expense'}>
    <div class="p-6 max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">{isEdit ? 'Edit Expense' : 'Create Expense'}</h1>
            <Button variant="outline" class="cursor-pointer" on:click={() => router.get('/expenses')}>Back to List</Button>
        </div>

        <form on:submit|preventDefault={submit} class="space-y-6">
            <!-- Header Section -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Expense Type</label>
                        <select bind:value={$form.is_direct_expense} class="w-full h-10 border border-slate-300 rounded-md px-3 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                            <option value={false}>Vendor Bill (Accounts Payable)</option>
                            <option value={true}>Direct Expense (Cash/Bank Out)</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-1">Direct expense will bypass AP and directly deduct your bank balance.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Vendor</label>
                        <select bind:value={$form.vendor_id} class="w-full h-10 border border-slate-300 rounded-md px-3 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">-- Select Vendor (Optional) --</option>
                            {#each vendors as vendor}
                                <option value={vendor.id}>{vendor.name}</option>
                            {/each}
                        </select>
                    </div>
                    
                    {#if $form.is_direct_expense}
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Pay From Account <span class="text-rose-500">*</span></label>
                        <select bind:value={$form.bank_account_id} class="w-full h-10 border border-slate-300 rounded-md px-3 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                            <option value="">-- Select Bank Account --</option>
                            {#each bankAccounts as ba}
                                <option value={ba.id}>{ba.name}</option>
                            {/each}
                        </select>
                    </div>
                    {/if}
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Expense Number <span class="text-rose-500">*</span></label>
                        <Input bind:value={$form.expense_number} class="h-10" required />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Date <span class="text-rose-500">*</span></label>
                            <Input type="date" bind:value={$form.expense_date} class="h-10" required />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Due Date</label>
                            <Input type="date" bind:value={$form.due_date} class="h-10" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Notes</label>
                        <Input bind:value={$form.notes} class="h-10" placeholder="Internal notes or description..." />
                    </div>
                </div>
            </div>

            <!-- Line Items Section -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Line Items</h3>
                <div class="overflow-visible rounded-lg border border-slate-200 mb-4 min-h-[300px]">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
                            <tr>
                                <th class="p-3 font-semibold min-w-[200px]">Description <span class="text-rose-500">*</span></th>
                                <th class="p-3 font-semibold min-w-[200px]">Account <span class="text-rose-500">*</span></th>
                                <th class="p-3 font-semibold w-40">Amount <span class="text-rose-500">*</span></th>
                                <th class="p-3 font-semibold w-40">Tax</th>
                                <th class="p-3 font-semibold w-40 text-right">Total</th>
                                <th class="p-3 w-12 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            {#each $form.items as item, index}
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-2">
                                        <Input bind:value={item.description} class="h-9 w-full" placeholder="Item description" required />
                                    </td>
                                    <td class="p-2">
                                        <CoaSelect bind:value={item.account_id} options={accounts} placeholder="Select Expense Account..." />
                                    </td>
                                    <td class="p-2">
                                        <Input type="number" step="0.01" bind:value={item.amount} class="h-9 w-full text-right" required />
                                    </td>
                                    <td class="p-2">
                                        <select bind:value={item.tax_id} class="w-full h-9 border border-slate-300 rounded-md px-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                            <option value="">No Tax</option>
                                            {#each taxes as tax}
                                                <option value={tax.id}>{tax.name} ({tax.type === 'percentage' ? tax.rate + '%' : 'Rp ' + parseFloat(tax.rate).toLocaleString('id-ID')})</option>
                                            {/each}
                                        </select>
                                    </td>
                                    <td class="p-2 text-right font-medium text-slate-700 bg-slate-50/50">
                                        Rp {item.total.toLocaleString('id-ID')}
                                    </td>
                                    <td class="p-2 text-center">
                                        {#if $form.items.length > 1}
                                            <button type="button" class="h-8 w-8 inline-flex justify-center items-center rounded-full text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" on:click={() => removeItem(index)}>
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        {/if}
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
                <Button type="button" variant="outline" class="text-sm font-semibold text-teal-700 border-teal-200 hover:bg-teal-50 cursor-pointer" on:click={addItem}>
                    + Add New Line
                </Button>
            </div>

            <!-- Summary Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
                <div class="w-full md:w-1/2">
                    <!-- Additional inputs can go here -->
                </div>
                <div class="w-full md:w-96 bg-slate-50 p-6 rounded-xl border border-slate-200">
                    <div class="flex justify-between items-center mb-2 text-slate-600 text-sm">
                        <span>Subtotal</span>
                        <span class="font-medium">Rp {subtotal.toLocaleString('id-ID')}</span>
                    </div>
                    <div class="flex justify-between items-center mb-4 text-slate-600 text-sm pb-4 border-b border-slate-200">
                        <span>Total Tax</span>
                        <span class="font-medium">Rp {totalTax.toLocaleString('id-ID')}</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-800">
                        <span class="text-lg font-bold">Grand Total</span>
                        <span class="text-xl font-bold text-teal-700">Rp {grandTotal.toLocaleString('id-ID')}</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-slate-200">
                <Button variant="outline" type="button" class="px-6 cursor-pointer" on:click={() => router.get('/expenses')}>Cancel</Button>
                <Button type="submit" class="bg-teal-700 hover:bg-teal-800 text-white px-8 cursor-pointer" disabled={$form.processing}>
                    {$form.processing ? 'Saving...' : 'Save Expense'}
                </Button>
            </div>
        </form>
    </div>
</AppLayout>
