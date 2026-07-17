<script>
    import AppLayout from '../../../Layouts/AppLayout.svelte';
    import { router } from '@inertiajs/svelte';
    import { Button } from '$lib/components/ui/button';
    import { Input } from '$lib/components/ui/input';
    import * as Table from '$lib/components/ui/table';
    import Pagination from '../../../Components/Pagination.svelte';
    import FilterPanel from '../../../Components/FilterPanel.svelte';
    import TableCard from '../../../Components/TableCard.svelte';
    import ColumnToggle from '../../../Components/ColumnToggle.svelte';
    import EmptyState from '../../../Components/EmptyState.svelte';
    import { ChevronDown, ChevronRight, Plus } from 'lucide-svelte';
    import { showConfirm } from '../../../Stores/confirmStore.js';
    import { showToast } from '../../../Stores/toast.js';

    export let expenses = [];
    export let pagination = { total: 0, perPage: 10, currentPage: 1, lastPage: 1, from: 0, to: 0 };
    export let filters = { search: '', status: '', date_from: '', date_to: '', per_page: 10 };
    export let stats = { total: 0, totalFiltered: 0, draft: 0, posted: 0, totalAmount: 0 };

    let search = filters.search || '';
    let status = filters.status || '';
    let dateFrom = filters.date_from || '';
    let dateTo = filters.date_to || '';
    let perPage = filters.per_page || 10;
    
    let expandedRows = [];
    let selectedIds = [];

    // ================================================
    // DEFINISI KOLOM 
    // ================================================
    let columns = [
        { key: 'date',        label: 'Date',        visible: true },
        { key: 'number',      label: 'Number',      visible: true },
        { key: 'company',     label: 'Perusahaan',  visible: true },
        { key: 'vendor',      label: 'Vendor',      visible: true },
        { key: 'type',        label: 'Type',        visible: true },
        { key: 'status',      label: 'Status',      visible: true },
        { key: 'payment',     label: 'Payment',     visible: true },
        { key: 'amount',      label: 'Amount',      visible: true },
    ];

    $: colVisible = Object.fromEntries(columns.map(c => [c.key, c.visible]));

    function toggleAll(e) {
        if (e.target.checked) {
            selectedIds = expenses.filter(exp => exp.expense_status_code === 'draft').map(exp => exp.id);
        } else {
            selectedIds = [];
        }
    }

    function toggleRow(id) {
        if (expandedRows.includes(id)) {
            expandedRows = expandedRows.filter(rowId => rowId !== id);
        } else {
            expandedRows = [...expandedRows, id];
        }
    }

    function applyFilter() {
        router.get('/expenses', { search, status, date_from: dateFrom, date_to: dateTo, per_page: perPage, page: 1 }, {
            preserveState: true,
            replace: true
        });
    }

    function resetFilter() {
        search = ''; status = ''; dateFrom = ''; dateTo = '';
        applyFilter();
    }

    function changePerPage(e) {
        perPage = parseInt(e.target.value);
        applyFilter();
    }

    function onGoToPage(page) {
        router.get('/expenses', { search, status, date_from: dateFrom, date_to: dateTo, per_page: perPage, page }, {
            preserveState: true,
            replace: true
        });
    }

    function createExpense() {
        router.get('/expenses/create');
    }

    function viewExpense(id) {
        router.get(`/expenses/${id}/edit`);
    }

    async function postExpense(id) {
        if (await showConfirm('Are you sure you want to post this expense to ledger?')) {
            router.post(`/expenses/${id}/post`, {}, {
                preserveScroll: true,
                onSuccess: () => showToast('Expense posted successfully!', 'success'),
                onError: (e) => showToast(Object.values(e)[0] || 'Failed to post expense.', 'error')
            });
        }
    }

    async function unpostExpense(id) {
        if (await showConfirm('Are you sure you want to unpost this expense? The related journal will be deleted.')) {
            router.post(`/expenses/${id}/unpost`, {}, {
                preserveScroll: true,
                onSuccess: () => showToast('Expense unposted successfully!', 'success'),
                onError: (e) => showToast(Object.values(e)[0] || 'Failed to unpost expense.', 'error')
            });
        }
    }

    async function voidExpense(id) {
        if (await showConfirm('Are you sure you want to void this expense? This action cannot be undone.')) {
            router.delete(`/expenses/${id}`, {
                preserveScroll: true,
                onSuccess: () => showToast('Expense voided successfully!', 'success'),
                onError: (e) => showToast(Object.values(e)[0] || 'Failed to void expense.', 'error')
            });
        }
    }

    async function bulkPost() {
        if (selectedIds.length === 0) return;
        if (await showConfirm(`Are you sure you want to post ${selectedIds.length} expenses to ledger?`)) {
            router.post('/expenses/bulk-post', { ids: selectedIds }, {
                preserveScroll: true,
                onSuccess: () => {
                    showToast(`${selectedIds.length} expenses posted successfully!`, 'success');
                    selectedIds = [];
                },
                onError: (e) => showToast(Object.values(e)[0] || 'Failed to bulk post expenses.', 'error')
            });
        }
    }

    $: hasActiveFilter = !!(search || status || dateFrom || dateTo);
</script>

<AppLayout title="Expenses">
    <div class="p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Expenses & Bills</h1>
                <p class="text-sm text-slate-500">Manage your vendor bills and direct expenses.</p>
            </div>
            <div class="flex items-center gap-2">
                {#if selectedIds.length > 0}
                    <Button variant="outline" class="border-teal-600 text-teal-700 hover:bg-teal-50 shadow-sm" on:click={bulkPost}>
                        Bulk Post ({selectedIds.length})
                    </Button>
                {/if}
                <Button on:click={createExpense} class="bg-teal-700 hover:bg-teal-800 text-white cursor-pointer shadow-sm gap-2">
                    <Plus size={16} /> Create Expense
                </Button>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Total Bills</p>
                <p class="text-2xl font-bold text-slate-800">{stats.total}</p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Draft</p>
                <p class="text-2xl font-bold text-yellow-600">{stats.draft}</p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Posted</p>
                <p class="text-2xl font-bold text-teal-600">{stats.posted}</p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Total Amount (Valid)</p>
                <p class="text-xl font-bold text-slate-800">Rp {stats.totalAmount.toLocaleString()}</p>
            </div>
        </div>

        <FilterPanel {hasActiveFilter} onApply={applyFilter} onReset={resetFilter}>
            <svelte:fragment slot="inputs">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Search</label>
                    <Input type="text" placeholder="Number or Vendor..." bind:value={search} on:keydown={(e) => e.key === 'Enter' && applyFilter()} class="bg-white border-slate-200" />
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Status</label>
                    <select bind:value={status} on:change={applyFilter} class="flex h-9 w-full rounded-md border border-slate-200 bg-white px-3 py-1 text-sm shadow-inner outline-none focus:border-teal-500 cursor-pointer">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="posted">Posted</option>
                        <option value="void">Void</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">From Date</label>
                    <Input type="date" bind:value={dateFrom} class="bg-white border-slate-200 cursor-pointer" />
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">To Date</label>
                    <Input type="date" bind:value={dateTo} class="bg-white border-slate-200 cursor-pointer" />
                </div>
            </svelte:fragment>
        </FilterPanel>

        <TableCard {pagination} {perPage} {hasActiveFilter} statsTotal={stats.total} onChangePerPage={changePerPage} onGoToPage={onGoToPage}>
            <svelte:fragment slot="toolbar-actions">
                <ColumnToggle bind:columns={columns} />
            </svelte:fragment>
            
            <Table.Header class="bg-slate-50/60">
                <Table.Row class="hover:bg-transparent border-b border-slate-100">
                    <Table.Head class="w-8 text-center"><input type="checkbox" on:change={toggleAll} class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 cursor-pointer" /></Table.Head>
                    <Table.Head class="w-8"></Table.Head>
                    {#if colVisible['date']}    <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Date</Table.Head>{/if}
                    {#if colVisible['number']}  <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Number</Table.Head>{/if}
                    {#if colVisible['company']} <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Perusahaan</Table.Head>{/if}
                    {#if colVisible['vendor']}  <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Vendor</Table.Head>{/if}
                    {#if colVisible['type']}    <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Type</Table.Head>{/if}
                    {#if colVisible['status']}  <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Status</Table.Head>{/if}
                    {#if colVisible['payment']} <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Payment</Table.Head>{/if}
                    {#if colVisible['amount']}  <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider text-right">Amount</Table.Head>{/if}
                    <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider text-right">Actions</Table.Head>
                </Table.Row>
            </Table.Header>
            <Table.Body>
                {#if expenses.length === 0}
                    <Table.Row>
                        <Table.Cell colspan="11" class="p-0">
                            <EmptyState title="Tidak ada Expenses" description="Belum ada data expense yang sesuai dengan kriteria pencarian atau filter Anda." />
                        </Table.Cell>
                    </Table.Row>
                {:else}
                    {#each expenses as expense}
                        <Table.Row class="hover:bg-slate-50/50 transition-colors group cursor-pointer {selectedIds.includes(expense.id) ? 'bg-teal-50/30' : ''}" on:click={() => toggleRow(expense.id)}>
                            <Table.Cell class="py-2.5 text-center" on:click={(e) => e.stopPropagation()}>
                                {#if expense.expense_status_code === 'draft'}
                                    <input type="checkbox" bind:group={selectedIds} value={expense.id} class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300 cursor-pointer" />
                                {/if}
                            </Table.Cell>
                            <Table.Cell class="py-2.5" on:click={(e) => e.stopPropagation()}>
                                <button class="text-slate-400 hover:text-teal-600 transition-colors" on:click={() => toggleRow(expense.id)}>
                                    {#if expandedRows.includes(expense.id)}
                                        <ChevronDown size={18} />
                                    {:else}
                                        <ChevronRight size={18} />
                                    {/if}
                                </button>
                            </Table.Cell>
                            {#if colVisible['date']}
                                <Table.Cell class="py-2.5 text-sm">{new Date(expense.expense_date).toLocaleDateString('id-ID')}</Table.Cell>
                            {/if}
                            {#if colVisible['number']}
                                <Table.Cell class="py-2.5 text-sm font-semibold text-slate-800">{expense.expense_number}</Table.Cell>
                            {/if}
                            {#if colVisible['company']}
                                <Table.Cell class="py-2.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200/60 whitespace-nowrap">
                                        {expense.company_name}
                                    </span>
                                </Table.Cell>
                            {/if}
                            {#if colVisible['vendor']}
                                <Table.Cell class="py-2.5 text-sm">{expense.vendor_name || '-'}</Table.Cell>
                            {/if}
                            {#if colVisible['type']}
                                <Table.Cell class="py-2.5 text-sm">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium {expense.is_direct_expense ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-orange-50 text-orange-700 border border-orange-200'}">
                                        {expense.is_direct_expense ? 'Direct' : 'Bill'}
                                    </span>
                                </Table.Cell>
                            {/if}
                            {#if colVisible['status']}
                                <Table.Cell class="py-2.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium {expense.expense_status_code === 'posted' ? 'bg-emerald-100 text-emerald-800' : expense.expense_status_code === 'void' ? 'bg-slate-100 text-slate-600' : 'bg-yellow-100 text-yellow-800'}">
                                        {expense.expense_status_code.toUpperCase()}
                                    </span>
                                </Table.Cell>
                            {/if}
                            {#if colVisible['payment']}
                                <Table.Cell class="py-2.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium {expense.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : expense.payment_status === 'partial' ? 'bg-blue-100 text-blue-800' : 'bg-rose-100 text-rose-800'}">
                                        {expense.payment_status.toUpperCase()}
                                    </span>
                                </Table.Cell>
                            {/if}
                            {#if colVisible['amount']}
                                <Table.Cell class="py-2.5 text-sm font-semibold text-right">Rp {parseFloat(expense.grand_total).toLocaleString('id-ID')}</Table.Cell>
                            {/if}
                            <Table.Cell class="py-2.5 text-right">
                                <div class="flex items-center justify-end gap-2" on:click|stopPropagation>
                                    {#if expense.expense_status_code !== 'void'}
                                        {#if expense.expense_status_code === 'draft'}
                                            <Button variant="outline" size="sm" class="h-7 text-xs border-teal-600 text-teal-700 hover:bg-teal-50 {expense.is_locked ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}" on:click={() => { if(expense.is_locked) { showToast('Tidak dapat memproses: Periode Akuntansi telah dikunci.', 'error'); } else { postExpense(expense.id); } }} title={expense.is_locked ? "Periode Terkunci" : "Post"}>Post</Button>
                                        {:else if expense.expense_status_code === 'posted'}
                                            <Button variant="outline" size="sm" class="h-7 text-xs border-orange-500 text-orange-600 hover:bg-orange-50 {expense.payment_status !== 'unpaid' || expense.is_locked ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}" title={expense.is_locked ? "Periode Terkunci" : (expense.payment_status !== 'unpaid' ? "Terdapat pembayaran aktif" : "Unpost")} on:click={() => { if(expense.is_locked) { showToast('Tidak dapat memproses: Periode Akuntansi telah dikunci.', 'error'); } else if(expense.payment_status !== 'unpaid') { showToast('Tidak bisa Unpost: Terdapat pembayaran aktif. Harap batalkan pembayaran (di menu Pembayaran Bill) terlebih dahulu.', 'error'); } else { unpostExpense(expense.id); } }}>Unpost</Button>
                                        {/if}
                                        <Button variant="outline" size="sm" class="h-7 text-xs {expense.payment_status !== 'unpaid' || expense.expense_status_code !== 'draft' || expense.is_locked ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}" title={expense.is_locked ? "Periode Terkunci" : (expense.payment_status !== 'unpaid' ? "Terdapat pembayaran aktif" : (expense.expense_status_code !== 'draft' ? "Harap Unpost terlebih dahulu" : "Edit"))} on:click={() => { if(expense.is_locked) { showToast('Tidak dapat memproses: Periode Akuntansi telah dikunci.', 'error'); } else if(expense.expense_status_code !== 'draft') { showToast('Harap Unpost tagihan terlebih dahulu sebelum melakukan Edit.', 'error'); } else if(expense.payment_status !== 'unpaid') { showToast('Tidak bisa Edit: Terdapat pembayaran aktif. Harap batalkan pembayaran terlebih dahulu.', 'error'); } else { viewExpense(expense.id); } }}>Edit</Button>
                                        <Button variant="outline" size="sm" class="h-7 text-xs border-red-500 text-red-600 hover:bg-red-50 {expense.payment_status !== 'unpaid' || expense.expense_status_code !== 'draft' || expense.is_locked ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}" title={expense.is_locked ? "Periode Terkunci" : (expense.payment_status !== 'unpaid' ? "Terdapat pembayaran aktif" : (expense.expense_status_code !== 'draft' ? "Harap Unpost terlebih dahulu" : "Void"))} on:click={() => { if(expense.is_locked) { showToast('Tidak dapat memproses: Periode Akuntansi telah dikunci.', 'error'); } else if(expense.expense_status_code !== 'draft') { showToast('Harap Unpost tagihan terlebih dahulu sebelum melakukan Void.', 'error'); } else if(expense.payment_status !== 'unpaid') { showToast('Tidak bisa Void: Terdapat pembayaran aktif. Harap batalkan pembayaran terlebih dahulu.', 'error'); } else { voidExpense(expense.id); } }}>Void</Button>
                                    {:else}
                                        <span class="text-[10px] font-bold text-red-500 uppercase mt-1">VOIDED</span>
                                    {/if}

                                    {#if expense.is_locked}
                                        <div class="flex items-center justify-center gap-1 text-slate-400 bg-slate-100 px-2 py-0.5 rounded border border-slate-200" title="Periode sudah terkunci">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            <span class="text-[9px] font-bold uppercase">Locked</span>
                                        </div>
                                    {/if}
                                </div>
                            </Table.Cell>
                        </Table.Row>
                        
                        {#if expandedRows.includes(expense.id)}
                            <Table.Row class="bg-slate-50/80 hover:bg-slate-50/80">
                                <Table.Cell colspan="11" class="p-0 border-b border-slate-200">
                                    <div class="pl-12 pr-6 py-4">
                                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Line Items</h4>
                                        <div class="border border-slate-200 rounded-lg overflow-hidden bg-white">
                                            <table class="w-full text-sm">
                                                <thead class="bg-slate-100 border-b border-slate-200">
                                                    <tr>
                                                        <th class="py-2 px-3 text-left font-semibold text-slate-600">Description</th>
                                                        <th class="py-2 px-3 text-right font-semibold text-slate-600">Amount</th>
                                                        <th class="py-2 px-3 text-right font-semibold text-slate-600">Tax</th>
                                                        <th class="py-2 px-3 text-right font-semibold text-slate-600">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {#each expense.items as item}
                                                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50">
                                                            <td class="py-2 px-3">{item.description}</td>
                                                            <td class="py-2 px-3 text-right tabular-nums">Rp {parseFloat(item.amount).toLocaleString('id-ID')}</td>
                                                            <td class="py-2 px-3 text-right tabular-nums text-slate-500">Rp {parseFloat(item.tax_amount).toLocaleString('id-ID')}</td>
                                                            <td class="py-2 px-3 text-right font-medium tabular-nums">Rp {parseFloat(item.total).toLocaleString('id-ID')}</td>
                                                        </tr>
                                                    {/each}
                                                </tbody>
                                            </table>
                                        </div>
                                        {#if expense.notes}
                                            <div class="mt-3 text-sm text-slate-600 bg-yellow-50/50 p-2 rounded border border-yellow-100">
                                                <strong>Notes:</strong> {expense.notes}
                                            </div>
                                        {/if}
                                    </div>
                                </Table.Cell>
                            </Table.Row>
                        {/if}
                    {/each}
                {/if}
            </Table.Body>
        </TableCard>
    </div>
</AppLayout>
