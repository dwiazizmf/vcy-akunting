<script>
    import AppLayout from '../../../Layouts/AppLayout.svelte';
    import { router } from '@inertiajs/svelte';
    import { Button } from '$lib/components/ui/button';
    import { Input } from '$lib/components/ui/input';
    import * as Table from '$lib/components/ui/table';
    import Pagination from '../../../Components/Pagination.svelte';
    import { Plus, Search } from 'lucide-svelte';
    import { showConfirm } from '../../../Stores/confirmStore.js';
    import { showToast } from '../../../Stores/toast.js';

    export let payments = [];
    export let pagination = { total: 0, perPage: 10, currentPage: 1, lastPage: 1, from: 0, to: 0 };
    export let filters = { search: '', per_page: 10 };

    let search = filters.search || '';
    let perPage = filters.per_page || 10;
    let debounceTimer;
    let selectedIds = [];

    function toggleAll(e) {
        if (e.target.checked) {
            selectedIds = payments.filter(p => p.status === 'draft').map(p => p.id);
        } else {
            selectedIds = [];
        }
    }

    function fetchData() {
        router.get('/expense-payments', { search, per_page: perPage }, {
            preserveState: true,
            replace: true
        });
    }

    function handleSearch() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchData, 300);
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
</script>

<AppLayout title="Bill Payments">
    <div class="p-6 max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Bill Payments</h1>
                <p class="text-sm text-slate-500 mt-1">Manage bulk payments for vendor bills.</p>
            </div>
            <a href="/expense-payments/create">
                <Button class="bg-teal-600 hover:bg-teal-700 text-white gap-2">
                    <Plus class="h-4 w-4" />
                    New Payment
                </Button>
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center gap-4 bg-slate-50/50">
                <div class="flex items-center gap-2 w-full sm:w-auto self-start sm:self-auto">
                    {#if selectedIds.length > 0}
                        <Button variant="outline" class="border-teal-600 text-teal-700 hover:bg-teal-50" on:click={bulkPost}>
                            Bulk Post ({selectedIds.length})
                        </Button>
                    {/if}
                    <div class="relative w-full sm:w-64">
                        <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                        <Input bind:value={search} on:input={handleSearch} placeholder="Search by payment number or vendor..." class="pl-9 h-9 w-full bg-white border-slate-200" />
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <Table.Root>
                    <Table.Header class="bg-slate-50 border-y border-slate-200">
                        <Table.Row>
                            <Table.Head class="w-10 text-center">
                                <input type="checkbox" on:change={toggleAll} class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300" />
                            </Table.Head>
                            <Table.Head class="font-semibold text-slate-600">Payment #</Table.Head>
                            <Table.Head class="font-semibold text-slate-600">Date</Table.Head>
                            <Table.Head class="font-semibold text-slate-600">Perusahaan</Table.Head>
                            <Table.Head class="font-semibold text-slate-600">Vendor</Table.Head>
                            <Table.Head class="font-semibold text-slate-600">Category</Table.Head>
                            <Table.Head class="font-semibold text-slate-600">Bank Account</Table.Head>
                            <Table.Head class="font-semibold text-slate-600">Status</Table.Head>
                            <Table.Head class="font-semibold text-slate-600 text-right">Amount Paid</Table.Head>
                            <Table.Head class="font-semibold text-slate-600 text-right">Actions</Table.Head>
                        </Table.Row>
                    </Table.Header>
                    <Table.Body>
                        {#if payments.length === 0}
                            <Table.Row>
                                <Table.Cell colspan="10" class="h-24 text-center text-slate-500">
                                    No payments found.
                                </Table.Cell>
                            </Table.Row>
                        {:else}
                            {#each payments as payment}
                                <Table.Row class="hover:bg-slate-50/50 transition-colors {selectedIds.includes(payment.id) ? 'bg-teal-50/30' : ''}">
                                    <Table.Cell class="text-center">
                                        {#if payment.status === 'draft'}
                                            <input type="checkbox" bind:group={selectedIds} value={payment.id} class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300" />
                                        {/if}
                                    </Table.Cell>
                                    <Table.Cell class="font-medium text-slate-900">{payment.payment_number}</Table.Cell>
                                    <Table.Cell class="text-slate-600">
                                        {new Date(payment.payment_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
                                    </Table.Cell>
                                    <Table.Cell class="py-2.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200/60 whitespace-nowrap">
                                            {payment.company_name}
                                        </span>
                                    </Table.Cell>
                                    <Table.Cell class="font-medium text-slate-700">{payment.vendor?.name || '-'}</Table.Cell>
                                    <Table.Cell class="text-slate-600">
                                        <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-medium">{payment.payment_category?.name || '-'}</span>
                                    </Table.Cell>
                                    <Table.Cell class="text-slate-600">{payment.account?.name || '-'}</Table.Cell>
                                    <Table.Cell>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium {payment.status === 'posted' ? 'bg-emerald-100 text-emerald-800' : 'bg-yellow-100 text-yellow-800'}">
                                            {payment.status ? payment.status.toUpperCase() : 'DRAFT'}
                                        </span>
                                    </Table.Cell>
                                    <Table.Cell class="text-right font-semibold text-slate-800 tabular-nums">
                                        Rp {parseFloat(payment.total_amount).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </Table.Cell>
                                    <Table.Cell class="text-right">
                                        {#if payment.status !== 'posted'}
                                            <Button variant="outline" size="sm" class="h-7 text-xs border-teal-600 text-teal-700 hover:bg-teal-50 cursor-pointer" on:click={() => postPayment(payment.id)}>Post</Button>
                                        {/if}
                                    </Table.Cell>
                                </Table.Row>
                            {/each}
                        {/if}
                    </Table.Body>
                </Table.Root>
            </div>
            
            <Pagination {pagination} {onGoToPage} />
        </div>
    </div>
</AppLayout>
