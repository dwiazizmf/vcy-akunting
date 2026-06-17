<script>
    import AppLayout from '../../Layouts/AppLayout.svelte';
    import { router } from '@inertiajs/svelte';
    import { Button } from '$lib/components/ui/button';
    import { Input } from '$lib/components/ui/input';
    import * as Table from '$lib/components/ui/table';
    import Pagination from '../../Components/Pagination.svelte';

    export let vendors = [];
    export let pagination = { total: 0, perPage: 10, currentPage: 1, lastPage: 1, from: 0, to: 0 };
    export let filters = { search: '', per_page: 10 };

    let search = filters.search || '';
    let perPage = filters.per_page || 10;
    
    let debounceTimer;

    function handleFilterChange() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            router.get('/vendors', { search, per_page: perPage }, {
                preserveState: true,
                replace: true
            });
        }, 300);
    }

    function onGoToPage(page) {
        router.get('/vendors', { search, per_page: perPage, page }, {
            preserveState: true,
            replace: true
        });
    }

    function createVendor() {
        router.get('/vendors/create');
    }

    function editVendor(id) {
        router.get(`/vendors/${id}/edit`);
    }

    function deleteVendor(id) {
        if (confirm('Are you sure you want to delete this vendor?')) {
            router.delete(`/vendors/${id}`);
        }
    }
</script>

<AppLayout title="Vendors">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Vendors</h1>
            <Button on:click={createVendor} class="bg-teal-700 hover:bg-teal-800 text-white">Add Vendor</Button>
        </div>

        <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden flex flex-col">
            <!-- Filter Bar -->
            <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row gap-4 justify-between items-center bg-slate-50/50">
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <Input type="text" placeholder="Search Vendor..." bind:value={search} on:input={handleFilterChange} class="w-full sm:w-64" />
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <span class="text-sm text-slate-500">Show</span>
                    <select bind:value={perPage} on:change={handleFilterChange} class="h-9 w-20 rounded-md border border-slate-200 bg-white px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-slate-950">
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
                            <Table.Head class="font-semibold text-slate-600">Code</Table.Head>
                            <Table.Head class="font-semibold text-slate-600">Name</Table.Head>
                            <Table.Head class="font-semibold text-slate-600">NPWP</Table.Head>
                            <Table.Head class="font-semibold text-slate-600">Phone</Table.Head>
                            <Table.Head class="font-semibold text-slate-600 text-right">Actions</Table.Head>
                        </Table.Row>
                    </Table.Header>
                    <Table.Body>
                        {#if vendors.length === 0}
                            <Table.Row>
                                <Table.Cell colspan="5" class="text-center py-8 text-slate-500">No vendors found.</Table.Cell>
                            </Table.Row>
                        {:else}
                            {#each vendors as vendor}
                                <Table.Row class="hover:bg-slate-50/50 transition-colors">
                                    <Table.Cell class="py-2 text-sm">{vendor.vendor_code || '-'}</Table.Cell>
                                    <Table.Cell class="py-2 text-sm font-medium">{vendor.name}</Table.Cell>
                                    <Table.Cell class="py-2 text-sm text-slate-500">{vendor.npwp || '-'}</Table.Cell>
                                    <Table.Cell class="py-2 text-sm text-slate-500">{vendor.phone || '-'}</Table.Cell>
                                    <Table.Cell class="py-2 text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button variant="outline" size="sm" class="h-8 cursor-pointer" on:click={() => editVendor(vendor.id)}>Edit</Button>
                                            <Button variant="destructive" size="sm" class="h-8 cursor-pointer" on:click={() => deleteVendor(vendor.id)}>Delete</Button>
                                        </div>
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
