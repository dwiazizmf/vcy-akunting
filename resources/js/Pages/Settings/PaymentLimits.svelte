<script>
    import AppLayout from '../../Layouts/AppLayout.svelte';
    import { router, useForm } from '@inertiajs/svelte';
    import { Button } from '$lib/components/ui/button';
    import { Input } from '$lib/components/ui/input';
    import * as Table from '$lib/components/ui/table';
    import Pagination from '../../Components/Pagination.svelte';
    import CoaSelect from '../../Components/CoaSelect.svelte';

    export let tab = 'categories';
    export let categories = { data: [], links: [] };
    export let limits = { data: [], links: [] };
    export let allCategories = [];
    export let accounts = [];
    export let filters = { search: '', per_page: 10 };

    let search = filters.search || '';
    let perPage = filters.per_page || 10;
    let debounceTimer;

    // Modals state
    let showCategoryModal = false;
    let showLimitModal = false;
    let isEditing = false;
    let editingId = null;

    // Forms
    const categoryForm = useForm({
        code: '',
        name: ''
    });

    const limitForm = useForm({
        account_id: '',
        payment_category_id: '',
        limit_amount: ''
    });

    function switchTab(newTab) {
        tab = newTab;
        search = '';
        fetchData();
    }

    function handleFilterChange() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetchData();
        }, 300);
    }

    function onGoToPage(page) {
        router.get('/settings/payment-limits', { tab, search, per_page: perPage, page }, {
            preserveState: true,
            replace: true
        });
    }

    function fetchData() {
        router.get('/settings/payment-limits', { tab, search, per_page: perPage }, {
            preserveState: true,
            replace: true
        });
    }

    // Category Actions
    function openCategoryModal(category = null) {
        if (category) {
            isEditing = true;
            editingId = category.id;
            $categoryForm.code = category.code;
            $categoryForm.name = category.name;
        } else {
            isEditing = false;
            editingId = null;
            $categoryForm.reset();
            $categoryForm.clearErrors();
        }
        showCategoryModal = true;
    }

    function saveCategory() {
        if (isEditing) {
            $categoryForm.put(`/settings/payment-categories/${editingId}`, {
                onSuccess: () => { showCategoryModal = false; fetchData(); }
            });
        } else {
            $categoryForm.post('/settings/payment-categories', {
                onSuccess: () => { showCategoryModal = false; fetchData(); }
            });
        }
    }

    function deleteCategory(id) {
        if (confirm('Are you sure you want to delete this category?')) {
            router.delete(`/settings/payment-categories/${id}`, {
                onSuccess: () => fetchData()
            });
        }
    }

    // Limit Actions
    function openLimitModal(limit = null) {
        if (limit) {
            isEditing = true;
            editingId = limit.id;
            $limitForm.account_id = limit.account_id;
            $limitForm.payment_category_id = limit.payment_category_id;
            $limitForm.limit_amount = limit.limit_amount;
        } else {
            isEditing = false;
            editingId = null;
            $limitForm.reset();
            $limitForm.clearErrors();
        }
        showLimitModal = true;
    }

    function saveLimit() {
        if (isEditing) {
            $limitForm.put(`/settings/payment-limits/${editingId}`, {
                onSuccess: () => { showLimitModal = false; fetchData(); }
            });
        } else {
            $limitForm.post('/settings/payment-limits', {
                onSuccess: () => { showLimitModal = false; fetchData(); }
            });
        }
    }

    function deleteLimit(id) {
        if (confirm('Are you sure you want to delete this limit?')) {
            router.delete(`/settings/payment-limits/${id}`, {
                onSuccess: () => fetchData()
            });
        }
    }
</script>

<AppLayout title="Payment Limits">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Payment Limits Settings</h1>
            {#if tab === 'categories'}
                <Button on:click={() => openCategoryModal()} class="bg-teal-700 hover:bg-teal-800 text-white shadow-sm">+ Add Category</Button>
            {:else}
                <Button on:click={() => openLimitModal()} class="bg-teal-700 hover:bg-teal-800 text-white shadow-sm">+ Add Limit</Button>
            {/if}
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden flex flex-col">
            <!-- Tabs Header -->
            <div class="flex border-b border-slate-200">
                <button 
                    class="px-6 py-3 font-semibold text-sm transition-colors {tab === 'categories' ? 'text-teal-700 border-b-2 border-teal-700 bg-teal-50/30' : 'text-slate-500 hover:bg-slate-50'}"
                    on:click={() => switchTab('categories')}>
                    Payment Categories
                </button>
                <button 
                    class="px-6 py-3 font-semibold text-sm transition-colors {tab === 'limits' ? 'text-teal-700 border-b-2 border-teal-700 bg-teal-50/30' : 'text-slate-500 hover:bg-slate-50'}"
                    on:click={() => switchTab('limits')}>
                    Limit COA Category
                </button>
            </div>

            <!-- Filter Bar -->
            <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row gap-4 justify-between items-center bg-slate-50/50">
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <Input type="text" placeholder="Search..." bind:value={search} on:input={handleFilterChange} class="w-full sm:w-64 bg-white" />
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <span class="text-sm text-slate-500">Show</span>
                    <select bind:value={perPage} on:change={handleFilterChange} class="h-9 w-20 rounded-md border border-slate-200 bg-white px-3 py-1 text-sm shadow-sm transition-colors focus:ring-2 focus:ring-teal-500">
                        <option value={10}>10</option>
                        <option value={25}>25</option>
                        <option value={50}>50</option>
                        <option value={100}>100</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto flex-1">
                {#if tab === 'categories'}
                    <Table.Root>
                        <Table.Header class="bg-slate-50">
                            <Table.Row>
                                <Table.Head class="font-semibold text-slate-600">Kode</Table.Head>
                                <Table.Head class="font-semibold text-slate-600">Name</Table.Head>
                                <Table.Head class="font-semibold text-slate-600 text-right">Actions</Table.Head>
                            </Table.Row>
                        </Table.Header>
                        <Table.Body>
                            {#if categories.data.length === 0}
                                <Table.Row><Table.Cell colspan="3" class="text-center py-8 text-slate-500">No categories found.</Table.Cell></Table.Row>
                            {:else}
                                {#each categories.data as category}
                                    <Table.Row class="hover:bg-slate-50/50 transition-colors">
                                        <Table.Cell class="py-2.5 text-sm font-medium">{category.code}</Table.Cell>
                                        <Table.Cell class="py-2.5 text-sm">{category.name}</Table.Cell>
                                        <Table.Cell class="py-2.5 text-right">
                                            <div class="flex justify-end gap-2">
                                                <Button variant="outline" size="sm" class="h-8 cursor-pointer" on:click={() => openCategoryModal(category)}>Edit</Button>
                                                <Button variant="destructive" size="sm" class="h-8 cursor-pointer bg-rose-500 hover:bg-rose-600" on:click={() => deleteCategory(category.id)}>Delete</Button>
                                            </div>
                                        </Table.Cell>
                                    </Table.Row>
                                {/each}
                            {/if}
                        </Table.Body>
                    </Table.Root>
                {:else}
                    <Table.Root>
                        <Table.Header class="bg-slate-50">
                            <Table.Row>
                                <Table.Head class="font-semibold text-slate-600">COA Code</Table.Head>
                                <Table.Head class="font-semibold text-slate-600">COA Name</Table.Head>
                                <Table.Head class="font-semibold text-slate-600">Limits (Rp)</Table.Head>
                                <Table.Head class="font-semibold text-slate-600">Payment Category</Table.Head>
                                <Table.Head class="font-semibold text-slate-600 text-right">Actions</Table.Head>
                            </Table.Row>
                        </Table.Header>
                        <Table.Body>
                            {#if limits.data.length === 0}
                                <Table.Row><Table.Cell colspan="5" class="text-center py-8 text-slate-500">No limits found.</Table.Cell></Table.Row>
                            {:else}
                                {#each limits.data as limit}
                                    <Table.Row class="hover:bg-slate-50/50 transition-colors">
                                        <Table.Cell class="py-2.5 text-sm font-medium">{limit.account?.code || '-'}</Table.Cell>
                                        <Table.Cell class="py-2.5 text-sm">{limit.account?.name || '-'}</Table.Cell>
                                        <Table.Cell class="py-2.5 text-sm font-semibold tabular-nums text-slate-800">{parseFloat(limit.limit_amount).toLocaleString('id-ID')}</Table.Cell>
                                        <Table.Cell class="py-2.5 text-sm"><span class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded text-xs font-medium">{limit.payment_category?.name || '-'}</span></Table.Cell>
                                        <Table.Cell class="py-2.5 text-right">
                                            <div class="flex justify-end gap-2">
                                                <Button variant="outline" size="sm" class="h-8 cursor-pointer" on:click={() => openLimitModal(limit)}>Edit</Button>
                                                <Button variant="destructive" size="sm" class="h-8 cursor-pointer bg-rose-500 hover:bg-rose-600" on:click={() => deleteLimit(limit.id)}>Delete</Button>
                                            </div>
                                        </Table.Cell>
                                    </Table.Row>
                                {/each}
                            {/if}
                        </Table.Body>
                    </Table.Root>
                {/if}
            </div>
            
            <Pagination pagination={tab === 'categories' ? categories : limits} {onGoToPage} />
        </div>
    </div>

    <!-- Category Modal -->
    {#if showCategoryModal}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 border border-slate-200">
                <h2 class="text-xl font-bold mb-4 text-slate-800">{isEditing ? 'Edit Category' : 'Create Category'}</h2>
                <form on:submit|preventDefault={saveCategory} class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Kode</label>
                        <Input bind:value={$categoryForm.code} required placeholder="e.g., Pindah Buku" class="focus:ring-teal-500" />
                        {#if $categoryForm.errors.code}<p class="text-xs text-rose-500 mt-1">{$categoryForm.errors.code}</p>{/if}
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Name</label>
                        <Input bind:value={$categoryForm.name} required placeholder="e.g., Limit Transaksi Pindah Buku" class="focus:ring-teal-500" />
                        {#if $categoryForm.errors.name}<p class="text-xs text-rose-500 mt-1">{$categoryForm.errors.name}</p>{/if}
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <Button variant="outline" type="button" class="cursor-pointer" on:click={() => showCategoryModal = false}>Cancel</Button>
                        <Button type="submit" class="bg-teal-700 hover:bg-teal-800 text-white cursor-pointer" disabled={$categoryForm.processing}>Save</Button>
                    </div>
                </form>
            </div>
        </div>
    {/if}

    <!-- Limit Modal -->
    {#if showLimitModal}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 border border-slate-200">
                <h2 class="text-xl font-bold mb-4 text-slate-800">{isEditing ? 'Edit Limit' : 'Create Limit'}</h2>
                {#if $limitForm.errors.error}
                    <div class="bg-rose-50 text-rose-600 p-3 rounded-md mb-4 text-sm font-medium border border-rose-200">
                        {$limitForm.errors.error}
                    </div>
                {/if}
                <form on:submit|preventDefault={saveLimit} class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">COA Bank/Kas</label>
                        <CoaSelect bind:value={$limitForm.account_id} options={accounts} placeholder="-- Select Account --" />
                        {#if $limitForm.errors.account_id}<p class="text-xs text-rose-500 mt-1">{$limitForm.errors.account_id}</p>{/if}
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Payment Category</label>
                        <select bind:value={$limitForm.payment_category_id} class="w-full h-10 border border-slate-300 rounded-md px-3 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500" required>
                            <option value="">-- Select Category --</option>
                            {#each allCategories as cat}
                                <option value={cat.id}>{cat.name}</option>
                            {/each}
                        </select>
                        {#if $limitForm.errors.payment_category_id}<p class="text-xs text-rose-500 mt-1">{$limitForm.errors.payment_category_id}</p>{/if}
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Limit Amount (Rp)</label>
                        <Input type="number" bind:value={$limitForm.limit_amount} required min="0" step="0.01" class="focus:ring-teal-500" />
                        {#if $limitForm.errors.limit_amount}<p class="text-xs text-rose-500 mt-1">{$limitForm.errors.limit_amount}</p>{/if}
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <Button variant="outline" type="button" class="cursor-pointer" on:click={() => showLimitModal = false}>Cancel</Button>
                        <Button type="submit" class="bg-teal-700 hover:bg-teal-800 text-white cursor-pointer" disabled={$limitForm.processing}>Save</Button>
                    </div>
                </form>
            </div>
        </div>
    {/if}
</AppLayout>
