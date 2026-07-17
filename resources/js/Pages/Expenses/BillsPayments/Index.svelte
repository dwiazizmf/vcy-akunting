<script>
    import AppLayout from '../../../Layouts/AppLayout.svelte';
    import { router } from '@inertiajs/svelte';
    import { Button } from '$lib/components/ui/button';
    import { Input } from '$lib/components/ui/input';
    import * as Table from '$lib/components/ui/table';
    import { Plus, Search } from 'lucide-svelte';
    import { showConfirm } from '../../../Stores/confirmStore.js';
    import { showToast } from '../../../Stores/toast.js';

    import FilterPanel from '../../../Components/FilterPanel.svelte';
    import TableCard from '../../../Components/TableCard.svelte';
    import ColumnToggle from '../../../Components/ColumnToggle.svelte';
    import EmptyState from '../../../Components/EmptyState.svelte';

    export let payments = [];
    export let pagination = { total: 0, perPage: 10, currentPage: 1, lastPage: 1, from: 0, to: 0 };
    export let filters = { search: '', per_page: 10 };

    let search = filters.search || '';
    let perPage = filters.per_page || 10;
    let selectedIds = [];

    // ================================================
    // DEFINISI KOLOM 
    // ================================================
    let columns = [
        { key: 'number',      label: 'Payment #',   visible: true },
        { key: 'date',        label: 'Date',        visible: true },
        { key: 'company',     label: 'Perusahaan',  visible: true },
        { key: 'vendor',      label: 'Vendor',      visible: true },
        { key: 'category',    label: 'Category',    visible: true },
        { key: 'bank',        label: 'Bank Account',visible: true },
        { key: 'status',      label: 'Status',      visible: true },
        { key: 'amount',      label: 'Amount Paid', visible: true },
    ];

    $: colVisible = Object.fromEntries(columns.map(c => [c.key, c.visible]));

    function toggleAll(e) {
        if (e.target.checked) {
            selectedIds = payments.filter(p => p.status === 'draft').map(p => p.id);
        } else {
            selectedIds = [];
        }
    }

    function applyFilter() {
        router.get('/expense-payments', { search, per_page: perPage, page: 1 }, {
            preserveState: true,
            replace: true
        });
    }

    function resetFilter() {
        search = '';
        applyFilter();
    }

    function changePerPage(e) {
        perPage = parseInt(e.target.value);
        applyFilter();
    }

    function onGoToPage(page) {
        router.get('/expense-payments', { search, per_page: perPage, page }, {
            preserveState: true,
            replace: true
        });
    }

    async function postPayment(id) {
        if (await showConfirm('Are you sure you want to post this payment to ledger?')) {
            router.post(`/expense-payments/${id}/post`, {}, {
                preserveScroll: true,
                onSuccess: () => showToast('Payment posted successfully!', 'success'),
                onError: (e) => showToast(Object.values(e)[0] || 'Failed to post payment.', 'error')
            });
        }
    }

    async function unpostPayment(id) {
        if (await showConfirm('Are you sure you want to unpost this payment? The related journal will be deleted.')) {
            router.post(`/expense-payments/${id}/unpost`, {}, {
                preserveScroll: true,
                onSuccess: () => showToast('Payment unposted successfully!', 'success'),
                onError: (e) => showToast(Object.values(e)[0] || 'Failed to unpost payment.', 'error')
            });
        }
    }

    async function bulkPost() {
        if (selectedIds.length === 0) return;
        if (await showConfirm(`Are you sure you want to post ${selectedIds.length} payments to ledger?`)) {
            router.post('/expense-payments/bulk-post', { ids: selectedIds }, {
                preserveScroll: true,
                onSuccess: () => {
                    showToast(`${selectedIds.length} payments posted successfully!`, 'success');
                    selectedIds = [];
                },
                onError: (e) => showToast(Object.values(e)[0] || 'Failed to bulk post payments.', 'error')
            });
        }
    }

    async function voidPayment(id) {
        if (await showConfirm('Are you sure you want to void this payment? This action cannot be undone.')) {
            router.delete(`/expense-payments/${id}`, {
                preserveScroll: true,
                onSuccess: () => showToast('Payment voided successfully!', 'success'),
                onError: (e) => showToast(Object.values(e)[0] || 'Failed to void payment.', 'error')
            });
        }
    }

    $: hasActiveFilter = !!(search);
</script>

<AppLayout title="Bill Payments">
    <div class="p-6 max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Bill Payments</h1>
                <p class="text-sm text-slate-500 mt-1">Manage bulk payments for vendor bills.</p>
            </div>
            <div class="flex items-center gap-2">
                {#if selectedIds.length > 0}
                    <Button variant="outline" class="border-teal-600 text-teal-700 hover:bg-teal-50 shadow-sm" on:click={bulkPost}>
                        Bulk Post ({selectedIds.length})
                    </Button>
                {/if}
                <a href="/expense-payments/create">
                    <Button class="bg-teal-600 hover:bg-teal-700 text-white gap-2 shadow-sm cursor-pointer">
                        <Plus class="h-4 w-4" />
                        New Payment
                    </Button>
                </a>
            </div>
        </div>

        <FilterPanel {hasActiveFilter} onApply={applyFilter} onReset={resetFilter}>
            <svelte:fragment slot="inputs">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Pencarian</label>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={14} />
                        <Input bind:value={search} on:keydown={(e) => e.key === 'Enter' && applyFilter()} placeholder="Number or vendor..." class="pl-9 bg-white border-slate-200" />
                    </div>
                </div>
            </svelte:fragment>
        </FilterPanel>

        <TableCard {pagination} {perPage} {hasActiveFilter} statsTotal={pagination.total} onChangePerPage={changePerPage} onGoToPage={onGoToPage}>
            <svelte:fragment slot="toolbar-actions">
                <ColumnToggle bind:columns={columns} />
            </svelte:fragment>

            <Table.Header class="bg-slate-50/60">
                <Table.Row class="hover:bg-transparent border-b border-slate-100">
                    <Table.Head class="w-8 text-center">
                        <input type="checkbox" on:change={toggleAll} class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300 cursor-pointer" />
                    </Table.Head>
                    {#if colVisible['number']}   <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Payment #</Table.Head>{/if}
                    {#if colVisible['date']}     <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Date</Table.Head>{/if}
                    {#if colVisible['company']}  <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Perusahaan</Table.Head>{/if}
                    {#if colVisible['vendor']}   <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Vendor</Table.Head>{/if}
                    {#if colVisible['category']} <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Category</Table.Head>{/if}
                    {#if colVisible['bank']}     <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Bank Account</Table.Head>{/if}
                    {#if colVisible['status']}   <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Status</Table.Head>{/if}
                    {#if colVisible['amount']}   <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider text-right">Amount Paid</Table.Head>{/if}
                    <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider text-right">Actions</Table.Head>
                </Table.Row>
            </Table.Header>
            <Table.Body>
                {#if payments.length === 0}
                    <Table.Row>
                        <Table.Cell colspan="10" class="p-0">
                            <EmptyState title="Tidak ada Pembayaran" description="Belum ada data pembayaran yang sesuai dengan kriteria pencarian Anda." />
                        </Table.Cell>
                    </Table.Row>
                {:else}
                    {#each payments as payment}
                        <Table.Row class="hover:bg-slate-50/50 transition-colors cursor-pointer {selectedIds.includes(payment.id) ? 'bg-teal-50/30' : ''}">
                            <Table.Cell class="text-center">
                                {#if payment.status === 'draft'}
                                    <input type="checkbox" bind:group={selectedIds} value={payment.id} class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300 cursor-pointer" />
                                {/if}
                            </Table.Cell>
                            {#if colVisible['number']}   <Table.Cell class="font-medium text-slate-900">{payment.payment_number}</Table.Cell>{/if}
                            {#if colVisible['date']}
                                <Table.Cell class="text-slate-600 text-sm">
                                    {new Date(payment.payment_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }
                                </Table.Cell>
                            {/if}
                            {#if colVisible['company']}
                                <Table.Cell class="py-2.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200/60 whitespace-nowrap">
                                        {payment.company_name}
                                    </span>
                                </Table.Cell>
                            {/if}
                            {#if colVisible['vendor']}   <Table.Cell class="font-medium text-slate-700">{payment.vendor?.name || '-'}</Table.Cell>{/if}
                            {#if colVisible['category']}
                                <Table.Cell class="text-slate-600">
                                    <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-medium border border-blue-100">{payment.payment_category?.name || '-'}</span>
                                </Table.Cell>
                            {/if}
                            {#if colVisible['bank']}     <Table.Cell class="text-slate-600 text-sm">{payment.account?.name || '-'}</Table.Cell>{/if}
                            {#if colVisible['status']}
                                <Table.Cell>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium {payment.status === 'posted' ? 'bg-emerald-100 text-emerald-800' : 'bg-yellow-100 text-yellow-800'}">
                                        {payment.status ? payment.status.toUpperCase() : 'DRAFT'}
                                    </span>
                                </Table.Cell>
                            {/if}
                            {#if colVisible['amount']}
                                <Table.Cell class="text-right font-semibold text-slate-800 tabular-nums">
                                    Rp {parseFloat(payment.total_amount).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                </Table.Cell>
                            {/if}
                            <Table.Cell class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    {#if payment.status !== 'void'}
                                        {#if payment.status !== 'posted'}
                                            <Button variant="outline" size="sm" class="h-7 text-xs border-teal-600 text-teal-700 hover:bg-teal-50 {payment.is_locked ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}" disabled={payment.is_locked} on:click={() => { if(!payment.is_locked) postPayment(payment.id); }} title={payment.is_locked ? "Periode Terkunci" : "Post"}>Post</Button>
                                        {:else}
                                            <Button variant="outline" size="sm" class="h-7 text-xs border-orange-500 text-orange-600 hover:bg-orange-50 {payment.is_locked ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}" disabled={payment.is_locked} on:click={() => { if(!payment.is_locked) unpostPayment(payment.id); }} title={payment.is_locked ? "Periode Terkunci" : "Unpost"}>Unpost</Button>
                                        {/if}
                                        <Button variant="outline" size="sm" class="h-7 text-xs border-red-500 text-red-600 hover:bg-red-50 {payment.status === 'posted' || payment.is_locked ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}" disabled={payment.status === 'posted' || payment.is_locked} title={payment.is_locked ? "Periode Terkunci" : (payment.status === 'posted' ? "Harap Unpost terlebih dahulu" : "Void Payment")} on:click={() => { if(payment.status !== 'posted' && !payment.is_locked) voidPayment(payment.id); }}>Void</Button>
                                    {:else}
                                        <span class="text-[10px] font-bold text-red-500 uppercase mt-1">VOIDED</span>
                                    {/if}

                                    {#if payment.is_locked}
                                        <div class="flex items-center justify-center gap-1 text-slate-400 bg-slate-100 px-2 py-0.5 rounded border border-slate-200" title="Periode sudah terkunci">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            <span class="text-[9px] font-bold uppercase">Locked</span>
                                        </div>
                                    {/if}
                                </div>
                            </Table.Cell>
                        </Table.Row>
                    {/each}
                {/if}
            </Table.Body>
        </TableCard>
    </div>
</AppLayout>
