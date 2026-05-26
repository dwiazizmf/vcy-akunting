<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Card from '$lib/components/ui/card';
  import * as Table from '$lib/components/ui/table';
  import * as DropdownMenu from '$lib/components/ui/dropdown-menu';
  import { cn } from '$lib/utils.js';
  import {
    Search, Filter, Download, MoreHorizontal, Eye, Pencil, Trash2,
    ChevronDown, ChevronUp, FileText, ChevronLeft, ChevronRight,
    ChevronsLeft, ChevronsRight, Hash, Layers, Receipt, User
  } from 'lucide-svelte';

  // ================================================
  // PROPS DARI LARAVEL
  // ================================================
  export let receipts   = [];
  export let pagination = { total: 0, perPage: 25, currentPage: 1, lastPage: 1, from: 0, to: 0 };
  export let filters    = { order_number: '', customer_name: '', tanggal_kirim: '', per_page: 25 };

  // ================================================
  // STATE LOKAL
  // ================================================
  let orderNumber   = filters.order_number   || '';
  let customerName  = filters.customer_name  || '';
  let tanggalKirim  = filters.tanggal_kirim  || '';
  let perPage       = filters.per_page       || 25;

  let selectedRows = [];
  let expandedRows = []; // Row IDs that are expanded
  let searchTimeout;

  // ================================================
  // ACTIONS
  // ================================================
  function toggleRowExpansion(id) {
    if (expandedRows.includes(id)) {
      expandedRows = expandedRows.filter(rId => rId !== id);
    } else {
      expandedRows = [...expandedRows, id];
    }
  }

  function toggleSelectRow(id) {
    selectedRows = selectedRows.includes(id)
      ? selectedRows.filter(rId => rId !== id)
      : [...selectedRows, id];
  }

  function toggleSelectAll() {
    const ids = receipts.map(r => r.id);
    const allSelected = ids.every(id => selectedRows.includes(id));
    selectedRows = allSelected ? selectedRows.filter(id => !ids.includes(id)) : [...new Set([...selectedRows, ...ids])];
  }

  function applyFilter() {
    router.get('/tanda-terima', {
      order_number: orderNumber,
      customer_name: customerName,
      tanggal_kirim: tanggalKirim,
      per_page: perPage,
      page: 1
    }, {
      preserveState: true, preserveScroll: true, replace: true
    });
  }

  function resetFilter() {
    orderNumber = ''; customerName = ''; tanggalKirim = '';
    applyFilter();
  }

  function goToPage(page) {
    if (page < 1 || page > pagination.lastPage) return;
    router.get('/tanda-terima', {
      order_number: orderNumber,
      customer_name: customerName,
      tanggal_kirim: tanggalKirim,
      per_page: perPage,
      page
    }, {
      preserveState: true, preserveScroll: true, replace: true
    });
  }

  function changePerPage(e) {
    perPage = parseInt(e.target.value);
    applyFilter();
  }

  function handleInputChange() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilter(), 300);
  }

  // Pagination pages
  $: pageNumbers = (() => {
    const total = pagination.lastPage;
    const cur   = pagination.currentPage;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const pages = new Set([1, total, cur]);
    if (cur > 1) pages.add(cur - 1);
    if (cur < total) pages.add(cur + 1);
    return [...pages].sort((a, b) => a - b);
  })();

  $: hasActiveFilter = orderNumber || customerName || tanggalKirim;

  // Status mapping
  const statusColor = {
    'Draft': 'bg-amber-100 text-amber-800 border-amber-250',
    'draft': 'bg-amber-100 text-amber-800 border-amber-250',
    'Paid': 'bg-emerald-100 text-emerald-805 border-emerald-250',
    'Sent': 'bg-blue-100 text-blue-805 border-blue-200'
  };

  // Helper functions to compute aggregates dynamically
  function countTotalOrders(receipt) {
    if (!receipt.invoices) return 0;
    return receipt.invoices.reduce((sum, inv) => sum + (inv.orders ? inv.orders.length : 0), 0);
  }

  function getUniqueCustomerNames(receipt) {
    if (!receipt.invoices) return '–';
    const customers = [...new Set(receipt.invoices.map(i => i.customer_name).filter(Boolean))];
    return customers.length > 0 ? customers.join(', ') : '–';
  }
</script>

<AppLayout>
  <!-- Page Header -->
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 flex items-center gap-2.5">
        <span class="inline-flex items-center justify-center h-9 w-9 rounded-xl bg-teal-700 text-white shadow-sm">
          <FileText class="h-5 w-5" />
        </span>
        List Tanda Terima
      </h1>
      <p class="text-sm text-muted-foreground mt-1 ml-11.5">
        Kelola berkas tanda terima fisik yang berisikan berbagai customer & order
      </p>
    </div>
  </div>

  <!-- Filters & Toolbar Card -->
  <Card.Root class="mb-6 bg-white border-slate-100 shadow-sm overflow-hidden">
    <div class="flex flex-col lg:flex-row lg:items-center gap-3 p-4">
      <div class="flex flex-wrap items-center gap-3 flex-1">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Search:</span>
        
        <!-- Order Number Search -->
        <div class="relative w-44">
          <Search class="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" />
          <Input
            type="text"
            placeholder="Order Number Search"
            bind:value={orderNumber}
            on:input={handleInputChange}
            class="pl-8 bg-white border-slate-200 h-8.5 text-xs placeholder:text-slate-400"
          />
        </div>

        <!-- Customer Name Search -->
        <div class="relative w-48">
          <Search class="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" />
          <Input
            type="text"
            placeholder="Customer Name Search"
            bind:value={customerName}
            on:input={handleInputChange}
            class="pl-8 bg-white border-slate-200 h-8.5 text-xs placeholder:text-slate-400"
          />
        </div>

        <!-- Tanggal Kirim -->
        <div class="relative w-40">
          <Input
            type="text"
            placeholder="Tanggal Kirim"
            bind:value={tanggalKirim}
            on:input={handleInputChange}
            class="bg-white border-slate-200 h-8.5 text-xs placeholder:text-slate-400"
          />
        </div>

        <!-- Action Buttons -->
        <button
          class="flex items-center gap-1.5 h-8.5 px-3 rounded-md border border-slate-200 bg-white text-xs text-slate-600 hover:bg-slate-50 transition-colors shadow-sm font-semibold"
          on:click={applyFilter}
        >
          <Filter class="h-3.5 w-3.5 text-slate-400" />
          Filter
        </button>

        <button
          class="flex items-center gap-1.5 h-8.5 px-3 rounded-md border border-slate-200 bg-white text-xs text-slate-600 hover:bg-slate-50 transition-colors shadow-sm font-semibold"
        >
          <Download class="h-3.5 w-3.5 text-slate-400" />
          Export
        </button>

        {#if hasActiveFilter}
          <Button variant="ghost" size="sm" class="text-xs h-8.5" on:click={resetFilter}>Reset</Button>
        {/if}
      </div>

      <div class="flex items-center gap-2 lg:ml-auto">
        <span class="text-xs text-slate-500">Show:</span>
        <select value={perPage} on:change={changePerPage} class="h-8.5 rounded-md border border-slate-200 bg-white px-2 text-xs outline-none shadow-sm">
          <option value={10}>10</option>
          <option value={25}>25</option>
          <option value={50}>50</option>
          <option value={100}>100</option>
        </select>
      </div>
    </div>
  </Card.Root>

  <!-- Table Card -->
  <Card.Root class="bg-white border-slate-100 shadow-sm overflow-hidden">
    <div class="w-full overflow-x-auto">
      <Table.Root>
        <Table.Header class="bg-slate-50/40">
          <Table.Row class="hover:bg-transparent border-slate-150">
            <!-- Toggle expansion col spacer -->
            <Table.Head class="w-10"></Table.Head>
            <Table.Head class="w-12 text-center py-3">
              <input
                type="checkbox"
                checked={receipts.length > 0 && receipts.map(r => r.id).every(id => selectedRows.includes(id))}
                on:change={toggleSelectAll}
                class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 cursor-pointer h-4 w-4"
              />
            </Table.Head>
            <Table.Head class="font-bold text-slate-700 text-[11px] uppercase tracking-wider py-3 w-16 text-center">ID</Table.Head>
            <Table.Head class="font-bold text-teal-700 text-[11px] uppercase tracking-wider py-3 cursor-pointer hover:text-teal-800 w-36">
              <span class="flex items-center gap-1">Tanggal Kirim <ChevronDown class="h-3 w-3" /></span>
            </Table.Head>
            <Table.Head class="font-bold text-slate-700 text-[11px] uppercase tracking-wider py-3 w-40">No Dokumen</Table.Head>
            <Table.Head class="font-bold text-teal-700 text-[11px] uppercase tracking-wider py-3 cursor-pointer hover:text-teal-800">
              <span class="flex items-center gap-1">Customer Name <ChevronDown class="h-3 w-3" /></span>
            </Table.Head>
            <Table.Head class="font-bold text-slate-700 text-[11px] uppercase tracking-wider py-3 text-center w-28">Total Invoices</Table.Head>
            <Table.Head class="font-bold text-slate-700 text-[11px] uppercase tracking-wider py-3 text-center w-28">Total Orders</Table.Head>
            <Table.Head class="font-bold text-slate-600 text-[11px] uppercase tracking-wider py-3 text-right pr-4 w-20">Actions</Table.Head>
          </Table.Row>
        </Table.Header>
        <Table.Body>
          {#each receipts as receipt (receipt.id)}
            {@const isExpanded = expandedRows.includes(receipt.id)}
            {@const totalOrders = countTotalOrders(receipt)}
            {@const customerNames = getUniqueCustomerNames(receipt)}
            <!-- Main Row -->
            <Table.Row class={cn(
              "transition-colors duration-100 border-slate-100 align-middle",
              isExpanded ? "bg-slate-50/20" : "hover:bg-slate-50/30"
            )}>
              <!-- Toggle Expansion Button -->
              <Table.Cell class="py-2.5 text-center">
                <button
                  type="button"
                  on:click={() => toggleRowExpansion(receipt.id)}
                  class="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                  title={isExpanded ? "Sembunyikan Detail" : "Tampilkan Detail"}
                >
                  {#if isExpanded}
                    <ChevronDown class="h-4 w-4" />
                  {:else}
                    <ChevronRight class="h-4 w-4" />
                  {/if}
                </button>
              </Table.Cell>

              <!-- Checkbox -->
              <Table.Cell class="py-2.5 text-center">
                <input
                  type="checkbox"
                  checked={selectedRows.includes(receipt.id)}
                  on:change={() => toggleSelectRow(receipt.id)}
                  class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 cursor-pointer h-4 w-4"
                />
              </Table.Cell>

              <!-- ID -->
              <Table.Cell class="py-2.5 text-center text-xs font-mono text-slate-500">
                {receipt.id}
              </Table.Cell>

              <!-- Tanggal Kirim -->
              <Table.Cell class="py-2.5 text-xs text-slate-600 font-medium whitespace-nowrap">
                {receipt.tanggal_kirim}
              </Table.Cell>

              <!-- No Dokumen -->
              <Table.Cell class="py-2.5 text-xs text-slate-800 font-semibold">
                {receipt.no_dokumen}
              </Table.Cell>

              <!-- Customer Name (Joined) -->
              <Table.Cell class="py-2.5 text-xs text-slate-700 font-medium">
                {customerNames}
              </Table.Cell>

              <!-- Total Invoices Badge -->
              <Table.Cell class="py-2.5 text-center">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-700 border border-teal-200">
                  <Layers class="h-3 w-3 opacity-60" />
                  {receipt.invoices ? receipt.invoices.length : 0} Invoices
                </span>
              </Table.Cell>

              <!-- Total Orders Badge -->
              <Table.Cell class="py-2.5 text-center">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                  <Hash class="h-3 w-3 opacity-60" />
                  {totalOrders} Orders
                </span>
              </Table.Cell>

              <!-- Actions Dropdown -->
              <Table.Cell class="py-2.5 text-right pr-3">
                <DropdownMenu.Root>
                  <DropdownMenu.Trigger asChild let:builder>
                    <Button builders={[builder]} variant="ghost" size="icon" class="h-7 w-7 text-slate-400 hover:text-slate-700 rounded-md">
                      <MoreHorizontal class="h-4 w-4" />
                    </Button>
                  </DropdownMenu.Trigger>
                  <DropdownMenu.Content align="end" class="w-40 bg-white shadow-lg border-slate-100">
                    <DropdownMenu.Item class="text-xs gap-2 cursor-pointer" on:click={() => router.visit(`/tanda-terima/${receipt.id}`)}>
                      <Eye class="h-3.5 w-3.5 text-slate-400" />
                      View Detail
                    </DropdownMenu.Item>
                    <DropdownMenu.Item class="text-xs gap-2 cursor-pointer" on:click={() => router.visit(`/tanda-terima/${receipt.id}/edit`)}>
                      <Pencil class="h-3.5 w-3.5 text-slate-400" />
                      Edit
                    </DropdownMenu.Item>
                    <DropdownMenu.Separator class="bg-slate-100" />
                    <DropdownMenu.Item class="text-xs gap-2 cursor-pointer text-red-600 focus:text-red-700 focus:bg-red-50">
                      <Trash2 class="h-3.5 w-3.5" />
                      Delete
                    </DropdownMenu.Item>
                  </DropdownMenu.Content>
                </DropdownMenu.Root>
              </Table.Cell>
            </Table.Row>

            <!-- Expanded Details Row (Unified Sub-table with Customer column) -->
            {#if isExpanded}
              <Table.Row class="bg-slate-50/20 hover:bg-slate-50/20">
                <Table.Cell colspan="9" class="p-4 border-t border-slate-100">
                  <div class="bg-white border border-slate-150 rounded-xl p-5 shadow-sm space-y-4">
                    
                    <!-- Sub-header -->
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                      <h4 class="text-xs font-bold text-teal-800 uppercase tracking-wider flex items-center gap-1.5">
                        <Layers class="h-4 w-4 opacity-70" />
                        Detail Relasi Customer, Invoice & Order ({receipt.invoices ? receipt.invoices.length : 0} Invoices, {totalOrders} Orders)
                      </h4>
                    </div>

                    <!-- Unified Hierarchical Sub-table with Customer Col -->
                    <div class="border border-slate-150 rounded-lg overflow-hidden bg-slate-50/30">
                      <table class="w-full text-xs">
                        <thead class="bg-slate-100/80 text-slate-600 font-bold text-[10px] uppercase tracking-wider">
                          <tr class="border-b border-slate-150">
                            <th class="px-3 py-2 text-left w-12">No.</th>
                            <th class="px-3 py-2 text-left w-1/4">Customer Name</th>
                            <th class="px-3 py-2 text-left w-1/4">Invoice Number</th>
                            <th class="px-3 py-2 text-left">Order Numbers</th>
                            <th class="px-3 py-2 text-left w-1/5">Status Inv</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-150 bg-white">
                          {#each receipt.invoices as inv, idx}
                            <tr class="hover:bg-slate-50/50 transition-colors align-top">
                              <!-- No -->
                              <td class="px-3 py-2.5 text-slate-400 font-normal">{idx + 1}</td>
                              
                              <!-- Customer Name -->
                              <td class="px-3 py-2.5 font-medium text-slate-800">
                                <div class="flex items-center gap-1">
                                  <User class="h-3 w-3 text-slate-400" />
                                  {inv.customer_name}
                                </div>
                              </td>

                              <!-- Invoice Number -->
                              <td class="px-3 py-2.5 font-semibold text-teal-700 font-mono">
                                <a href="#inv-{inv.number}" class="hover:underline flex items-center gap-1">
                                  <Receipt class="h-3 w-3 opacity-60 text-slate-400" />
                                  {inv.number}
                                </a>
                              </td>
                              
                              <!-- Order Numbers -->
                              <td class="px-3 py-2.5 font-mono text-slate-700">
                                <div class="flex flex-col gap-1">
                                  {#if inv.orders && inv.orders.length > 0}
                                    {#each inv.orders as order}
                                      <span class="inline-flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400/60"></span>
                                        {order}
                                      </span>
                                    {/each}
                                  {:else}
                                    <span class="text-slate-300 italic text-[11px]">No orders</span>
                                  {/if}
                                </div>
                              </td>
                              
                              <!-- Status -->
                              <td class="px-3 py-2.5">
                                <span class={cn(
                                  "inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border capitalize shadow-sm",
                                  statusColor[inv.status] || 'bg-slate-100 text-slate-700 border-slate-200'
                                )}>
                                  {inv.status}
                                </span>
                              </td>
                            </tr>
                          {/each}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </Table.Cell>
              </Table.Row>
            {/if}
          {/each}

          {#if receipts.length === 0}
            <Table.Row class="hover:bg-transparent">
              <Table.Cell colspan="9" class="text-center py-16 text-slate-400 text-sm">
                Tidak ada data tanda terima yang sesuai filter.
              </Table.Cell>
            </Table.Row>
          {/if}
        </Table.Body>
      </Table.Root>
    </div>

    <!-- Pagination Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 border-t border-slate-100 gap-3 bg-slate-50/20">
      <span class="text-xs text-slate-500">
        Showing <strong class="text-slate-700">{pagination.from || 0} to {pagination.to || 0}</strong> of
        <strong class="text-slate-700">{(pagination.total || 0).toLocaleString()}</strong> entries
      </span>
      
      <div class="flex items-center gap-1">
        <Button variant="outline" size="icon" class="h-8 w-8 bg-white border-slate-200 text-slate-600 shadow-sm" disabled={pagination.currentPage <= 1} on:click={() => goToPage(1)}>
          <ChevronsLeft class="h-3.5 w-3.5" />
        </Button>
        <Button variant="outline" size="icon" class="h-8 w-8 bg-white border-slate-200 text-slate-600 shadow-sm" disabled={pagination.currentPage <= 1} on:click={() => goToPage(pagination.currentPage - 1)}>
          <ChevronLeft class="h-3.5 w-3.5" />
        </Button>

        {#each pageNumbers as p, i}
          {#if i > 0 && pageNumbers[i] - pageNumbers[i-1] > 1}
            <span class="px-2 text-slate-400 text-xs">…</span>
          {/if}
          <Button
            variant={p === pagination.currentPage ? 'default' : 'outline'}
            size="icon"
            class={cn("h-8 w-8 shadow-sm", p === pagination.currentPage ? "bg-teal-700 hover:bg-teal-800 text-white" : "bg-white border-slate-200 text-slate-700")}
            on:click={() => goToPage(p)}
          >
            {p}
          </Button>
        {/each}

        <Button variant="outline" size="icon" class="h-8 w-8 bg-white border-slate-200 text-slate-600 shadow-sm" disabled={pagination.currentPage >= pagination.lastPage} on:click={() => goToPage(pagination.currentPage + 1)}>
          <ChevronRight class="h-3.5 w-3.5" />
        </Button>
        <Button variant="outline" size="icon" class="h-8 w-8 bg-white border-slate-200 text-slate-600 shadow-sm" disabled={pagination.currentPage >= pagination.lastPage} on:click={() => goToPage(pagination.lastPage)}>
          <ChevronsRight class="h-3.5 w-3.5" />
        </Button>
      </div>
    </div>
  </Card.Root>
</AppLayout>
