<script>
    import AppLayout from '../../../Layouts/AppLayout.svelte';
    import { router } from '@inertiajs/svelte';
    import { Button } from '$lib/components/ui/button';
    import { Input } from '$lib/components/ui/input';
    import * as Table from '$lib/components/ui/table';
    import Pagination from '../../../Components/Pagination.svelte';
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
    
    let debounceTimer;
    let expandedRows = [];
    let selectedIds = [];

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

    function handleFilterChange() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            router.get('/expenses', { search, status, date_from: dateFrom, date_to: dateTo, per_page: perPage }, {
                preserveState: true,
                replace: true
            });
        }, 300);
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
</script>

<AppLayout title="Expenses">
    <div class="p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Expenses & Bills</h1>
                <p class="text-sm text-slate-500">Manage your vendor bills and direct expenses.</p>
            </div>
            <Button on:click={createExpense} class="bg-teal-700 hover:bg-teal-800 text-white cursor-pointer shadow-sm gap-2">
                <Plus size={16} /> Create Expense
            </Button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Bills</p>
                <p class="text-2xl font-bold text-slate-800">{stats.total}</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Draft</p>
                <p class="text-2xl font-bold text-yellow-600">{stats.draft}</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Posted</p>
                <p class="text-2xl font-bold text-teal-600">{stats.posted}</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Amount (Valid)</p>
                <p class="text-xl font-bold text-slate-800">Rp {stats.totalAmount.toLocaleString()}</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden flex flex-col">
            <!-- Filter Bar -->
            <div class="p-4 border-b border-slate-100 flex flex-col lg:flex-row gap-4 justify-between items-center bg-slate-50/50">
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                    <Input type="text" placeholder="Search Number or Vendor..." bind:value={search} on:input={handleFilterChange} class="w-full sm:w-64" />
                    
                    <select bind:value={status} on:change={handleFilterChange} class="h-9 w-full sm:w-40 rounded-md border border-slate-200 bg-white px-3 py-1 text-sm shadow-sm">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="posted">Posted</option>
                        <option value="void">Void</option>
                    </select>

                    <Input type="date" bind:value={dateFrom} on:change={handleFilterChange} class="w-full sm:w-auto" />
                    <span class="text-slate-400">-</span>
                    <Input type="date" bind:value={dateTo} on:change={handleFilterChange} class="w-full sm:w-auto" />
                </div>
                
                <div class="flex items-center gap-2 w-full sm:w-auto self-start sm:self-auto">
                    {#if selectedIds.length > 0}
                        <Button variant="outline" class="border-teal-600 text-teal-700 hover:bg-teal-50" on:click={bulkPost}>
                            Bulk Post ({selectedIds.length})
                        </Button>
                    {/if}
                    <span class="text-sm text-slate-500 ml-2">Show</span>
                    <select bind:value={perPage} on:change={handleFilterChange} class="h-9 w-20 rounded-md border border-slate-200 bg-white px-3 py-1 text-sm shadow-sm">
                        <option value={10}>10</option>
                        <option value={25}>25</option>
                        <option value={50}>50</option>
                        <option value={100}>100</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto flex-1">
                <Table.Root>
                    <Table.Header class="bg-slate-50">
                        <Table.Row>
                            <Table.Head class="w-10 text-center">
                                <input type="checkbox" on:change={toggleAll} class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300" />
                            </Table.Head>
                            <Table.Head class="w-10"></Table.Head>
                            <Table.Head class="font-semibold text-slate-600">Date</Table.Head>
                            <Table.Head class="font-semibold text-slate-600">Number</Table.Head>
                            <Table.Head class="font-semibold text-slate-600">Perusahaan</Table.Head>
                            <Table.Head class="font-semibold text-slate-600">Vendor</Table.Head>
                            <Table.Head class="font-semibold text-slate-600">Type</Table.Head>
                            <Table.Head class="font-semibold text-slate-600">Status</Table.Head>
                            <Table.Head class="font-semibold text-slate-600">Payment</Table.Head>
                            <Table.Head class="font-semibold text-slate-600 text-right">Amount</Table.Head>
                            <Table.Head class="font-semibold text-slate-600 text-right">Actions</Table.Head>
                        </Table.Row>
                    </Table.Header>
                    <Table.Body>
                        {#if expenses.length === 0}
                            <Table.Row>
                                <Table.Cell colspan="10" class="text-center py-8 text-slate-500">No expenses found matching the criteria.</Table.Cell>
                            </Table.Row>
                        {:else}
                            {#each expenses as expense}
                                <Table.Row class="hover:bg-slate-50/50 transition-colors group cursor-pointer {selectedIds.includes(expense.id) ? 'bg-teal-50/30' : ''}" on:click={() => toggleRow(expense.id)}>
                                    <Table.Cell class="py-2.5 text-center" on:click={(e) => e.stopPropagation()}>
                                        {#if expense.expense_status_code === 'draft'}
                                            <input type="checkbox" bind:group={selectedIds} value={expense.id} class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-slate-300" />
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
                                    <Table.Cell class="py-2.5 text-sm">{new Date(expense.expense_date).toLocaleDateString('id-ID')}</Table.Cell>
                                    <Table.Cell class="py-2.5 text-sm font-semibold text-slate-800">{expense.expense_number}</Table.Cell>
                                    <Table.Cell class="py-2.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200/60 whitespace-nowrap">
                                            {expense.company_name}
                                        </span>
                                    </Table.Cell>
                                    <Table.Cell class="py-2.5 text-sm">{expense.vendor_name || '-'}</Table.Cell>
                                    <Table.Cell class="py-2.5 text-sm">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium {expense.is_direct_expense ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-orange-50 text-orange-700 border border-orange-200'}">
                                            {expense.is_direct_expense ? 'Direct' : 'Bill'}
                                        </span>
                                    </Table.Cell>
                                    <Table.Cell class="py-2.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium {expense.expense_status_code === 'posted' ? 'bg-emerald-100 text-emerald-800' : expense.expense_status_code === 'void' ? 'bg-slate-100 text-slate-600' : 'bg-yellow-100 text-yellow-800'}">
                                            {expense.expense_status_code.toUpperCase()}
                                        </span>
                                    </Table.Cell>
                                    <Table.Cell class="py-2.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium {expense.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : expense.payment_status === 'partial' ? 'bg-blue-100 text-blue-800' : 'bg-rose-100 text-rose-800'}">
                                            {expense.payment_status.toUpperCase()}
                                        </span>
                                    </Table.Cell>
                                    <Table.Cell class="py-2.5 text-sm font-semibold text-right">Rp {parseFloat(expense.grand_total).toLocaleString('id-ID')}</Table.Cell>
                                    <Table.Cell class="py-2.5 text-right">
                                        <div class="flex justify-end gap-2" on:click|stopPropagation>
                                            {#if expense.expense_status_code === 'draft'}
                                                <Button variant="outline" size="sm" class="h-7 text-xs border-teal-600 text-teal-700 hover:bg-teal-50 cursor-pointer" on:click={() => postExpense(expense.id)}>Post</Button>
                                            {/if}
                                            <Button variant="outline" size="sm" class="h-7 text-xs cursor-pointer" on:click={() => viewExpense(expense.id)}>Edit</Button>
                                        </div>
                                    </Table.Cell>
                                </Table.Row>
                                
                                <!-- EXPANDABLE ROW CONTENT -->
                                {#if expandedRows.includes(expense.id)}
                                    <Table.Row class="bg-slate-50/80 hover:bg-slate-50/80">
                                        <Table.Cell colspan="10" class="p-0 border-b border-slate-200">
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
                </Table.Root>
            </div>
            
            <Pagination {pagination} {onGoToPage} />
        </div>
    </div>
</AppLayout>
