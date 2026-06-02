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
    Search, Filter, Plus, MoreHorizontal, Eye, Pencil, Trash2,
    ChevronDown, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, FileText
  } from 'lucide-svelte';

  // ================================================
  // PROPS FROM LARAVEL
  // ================================================
  export let kwitansis = [];
  export let pagination = { total: 0, perPage: 25, currentPage: 1, lastPage: 1, from: 0, to: 0 };
  export let filters = { customer_name: '', tanggal_report: '', per_page: 25 };

  // ================================================
  // LOCAL STATE
  // ================================================
  let customerName = filters.customer_name || '';
  let tanggalReport = filters.tanggal_report || '';
  let perPage = filters.per_page || 25;
  let searchTimeout;

  // ================================================
  // ACTIONS
  // ================================================
  function applyFilter() {
    router.get('/kwitansi', {
      customer_name: customerName,
      tanggal_report: tanggalReport,
      per_page: perPage,
      page: 1
    }, {
      preserveState: true, preserveScroll: true, replace: true
    });
  }

  function resetFilter() {
    customerName = '';
    tanggalReport = '';
    applyFilter();
  }

  function goToPage(page) {
    if (page < 1 || page > pagination.lastPage) return;
    router.get('/kwitansi', {
      customer_name: customerName,
      tanggal_report: tanggalReport,
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

  function createKwitansi() {
    alert('Membuka form pembuatan Kwitansi baru.');
  }

  // Pagination pages helper
  $: pageNumbers = (() => {
    const total = pagination.lastPage;
    const cur   = pagination.currentPage;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const pages = new Set([1, total, cur]);
    if (cur > 1) pages.add(cur - 1);
    if (cur < total) pages.add(cur + 1);
    return [...pages].sort((a, b) => a - b);
  })();

  $: hasActiveFilter = customerName || tanggalReport;
</script>

<AppLayout>
  <!-- Page Header -->
  <div class="flex flex-col gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 flex items-center gap-2.5">
        <span class="inline-flex items-center justify-center h-9 w-9 rounded-xl bg-teal-700 text-white shadow-sm">
          <FileText class="h-5 w-5" />
        </span>
        List Kwitansi
      </h1>
      <p class="text-sm text-muted-foreground mt-1 ml-11.5">
        Kelola dan cetak bukti pembayaran kwitansi manual
      </p>
    </div>

    <!-- Action Bar / Create Button -->
    <div class="flex items-center gap-2">
      <Button
        variant="outline"
        size="sm"
        class="bg-white hover:bg-slate-50 border-slate-200 text-slate-700 flex items-center gap-1.5 shadow-sm text-xs font-semibold"
        on:click={createKwitansi}
      >
        <Plus class="h-3.5 w-3.5 text-slate-550" />
        Create Kwitansi
      </Button>
    </div>
  </div>

  <!-- Filters & Toolbar Card -->
  <Card.Root class="mb-6 bg-white border-slate-100 shadow-sm overflow-hidden">
    <div class="flex flex-col lg:flex-row lg:items-center gap-3 p-4">
      <div class="flex flex-wrap items-center gap-3 flex-1">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Search:</span>
        
        <!-- Customer Name Search -->
        <div class="relative w-48">
          <Search class="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" />
          <Input
            type="text"
            placeholder="Customer Name"
            bind:value={customerName}
            on:input={handleInputChange}
            class="pl-8 bg-white border-slate-200 h-8.5 text-xs placeholder:text-slate-400"
          />
        </div>

        <!-- Tanggal Report Search -->
        <div class="relative w-44">
          <Search class="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" />
          <Input
            type="text"
            placeholder="Tanggal Report"
            bind:value={tanggalReport}
            on:input={handleInputChange}
            class="pl-8 bg-white border-slate-200 h-8.5 text-xs placeholder:text-slate-400"
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
            <Table.Head class="w-16 text-center font-bold text-slate-700 text-[11px] uppercase tracking-wider py-3">#</Table.Head>
            <Table.Head class="font-bold text-teal-700 text-[11px] uppercase tracking-wider py-3 cursor-pointer hover:text-teal-800 w-36">
              <span class="flex items-center gap-1">Tanggal Report <ChevronDown class="h-3 w-3" /></span>
            </Table.Head>
            <Table.Head class="font-bold text-teal-700 text-[11px] uppercase tracking-wider py-3 cursor-pointer hover:text-teal-800 w-44">
              <span class="flex items-center gap-1">No Faktur <ChevronDown class="h-3 w-3" /></span>
            </Table.Head>
            <Table.Head class="font-bold text-teal-700 text-[11px] uppercase tracking-wider py-3 cursor-pointer hover:text-teal-800">
              <span class="flex items-center gap-1">Alamat <ChevronDown class="h-3 w-3" /></span>
            </Table.Head>
            <Table.Head class="font-bold text-teal-700 text-[11px] uppercase tracking-wider py-3 cursor-pointer hover:text-teal-800">
              <span class="flex items-center gap-1">Keterangan <ChevronDown class="h-3 w-3" /></span>
            </Table.Head>
            <Table.Head class="font-bold text-teal-700 text-[11px] uppercase tracking-wider py-3 cursor-pointer hover:text-teal-800 w-48">
              <span class="flex items-center gap-1">No Kwitansi <ChevronDown class="h-3 w-3" /></span>
            </Table.Head>
            <Table.Head class="font-bold text-teal-700 text-[11px] uppercase tracking-wider py-3 cursor-pointer hover:text-teal-800 w-48">
              <span class="flex items-center gap-1">Terima Dari <ChevronDown class="h-3 w-3" /></span>
            </Table.Head>
            <Table.Head class="font-bold text-slate-600 text-[11px] uppercase tracking-wider py-3 text-right pr-6 w-24">Actions</Table.Head>
          </Table.Row>
        </Table.Header>
        <Table.Body>
          {#each kwitansis as k, idx (k.id)}
            <Table.Row class="transition-colors duration-100 border-slate-100 hover:bg-slate-50/30 align-middle">
              <!-- Index Number -->
              <Table.Cell class="py-3 text-center text-xs text-slate-400 font-medium">
                {pagination.from + idx}
              </Table.Cell>

              <!-- Tanggal Report -->
              <Table.Cell class="py-3 text-xs text-slate-600 font-semibold whitespace-nowrap">
                {k.tanggal_report}
              </Table.Cell>

              <!-- No Faktur -->
              <Table.Cell class="py-3 text-xs text-slate-800 font-mono">
                {k.no_faktur}
              </Table.Cell>

              <!-- Alamat (Wrapped text) -->
              <Table.Cell class="py-3 text-xs text-slate-700 whitespace-normal break-words max-w-[280px] leading-relaxed">
                {k.alamat}
              </Table.Cell>

              <!-- Keterangan (Wrapped text) -->
              <Table.Cell class="py-3 text-xs text-slate-600 whitespace-normal break-words max-w-[200px] leading-relaxed">
                {k.keterangan}
              </Table.Cell>

              <!-- No Kwitansi -->
              <Table.Cell class="py-3 text-xs text-slate-800 font-mono font-semibold">
                {k.no_kwitansi}
              </Table.Cell>

              <!-- Terima Dari -->
              <Table.Cell class="py-3 text-xs text-slate-755 font-bold">
                {k.terima_dari}
              </Table.Cell>

              <!-- Actions Dropdown -->
              <Table.Cell class="py-3 text-right pr-5">
                <DropdownMenu.Root>
                  <DropdownMenu.Trigger asChild let:builder>
                    <Button builders={[builder]} variant="ghost" size="icon" class="h-7 w-7 text-slate-400 hover:text-slate-700 rounded-md">
                      <MoreHorizontal class="h-4 w-4" />
                    </Button>
                  </DropdownMenu.Trigger>
                  <DropdownMenu.Content align="end" class="w-40 bg-white shadow-lg border-slate-100">
                    <DropdownMenu.Item class="text-xs gap-2 cursor-pointer" on:click={() => router.visit(`/kwitansi/${k.id}`)}>
                      <Eye class="h-3.5 w-3.5 text-slate-400" />
                      View Detail
                    </DropdownMenu.Item>
                    <DropdownMenu.Item class="text-xs gap-2 cursor-pointer" on:click={() => router.visit(`/kwitansi/${k.id}/edit`)}>
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
          {/each}

          {#if kwitansis.length === 0}
            <Table.Row class="hover:bg-transparent">
              <Table.Cell colspan="8" class="text-center py-16 text-slate-400 text-sm">
                Tidak ada data Kwitansi yang sesuai filter.
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
