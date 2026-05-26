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
    Plus, Upload, Download, Filter, Search, MoreHorizontal,
    Eye, Pencil, Trash2, Users, CheckCircle2, XCircle, DollarSign,
    ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, ChevronUp, ChevronDown, ChevronsUpDown
  } from 'lucide-svelte';

  // ================================================
  // PROPS DARI LARAVEL
  // ================================================
  export let customers   = [];
  export let pagination  = { total: 0, perPage: 25, currentPage: 1, lastPage: 1, from: 0, to: 0 };
  export let stats       = { total: 0, active: 0, inactive: 0, totalUnpaid: 0 };
  export let filters     = { search: '', status: '', per_page: 25 };

  // ================================================
  // STATE LOKAL
  // ================================================
  let filterOpen   = false;
  let search       = filters.search  || '';
  let status       = filters.status  || '';
  let perPage      = filters.per_page || 25;

  let searchTimeout;

  // ================================================
  // SERVER-SIDE NAVIGATION
  // ================================================
  function applyFilter() {
    router.get('/customers', { search, status, per_page: perPage, page: 1 }, {
      preserveState: true, preserveScroll: true, replace: true
    });
  }

  function resetFilter() {
    search = ''; status = '';
    applyFilter();
  }

  function goToPage(page) {
    if (page < 1 || page > pagination.lastPage) return;
    router.get('/customers', { search, status, per_page: perPage, page }, {
      preserveState: true, preserveScroll: true, replace: true
    });
  }

  function changePerPage(e) {
    perPage = parseInt(e.target.value);
    applyFilter();
  }

  function handleSearchInput() {
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

  $: hasActiveFilter = search || status;

  // Format currency Rupiah
  function formatRp(val) {
    if (!val && val !== 0) return 'Rp0,00';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2 }).format(val);
  }
</script>

<AppLayout>
  <!-- Page Header -->
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 flex items-center gap-2.5">
        <span class="inline-flex items-center justify-center h-9 w-9 rounded-xl bg-teal-700 text-white shadow-sm">
          <Users class="h-5 w-5" />
        </span>
        Customers
      </h1>
      <p class="text-sm text-muted-foreground mt-1 ml-11.5">
        {#if hasActiveFilter}
          Menampilkan <strong class="text-teal-700">{pagination.total}</strong> dari <strong class="text-slate-700">{stats.total}</strong> total customer
        {:else}
          <strong class="text-slate-700">{stats.total}</strong> total customer terdaftar
        {/if}
      </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <Button variant="outline" size="sm" class="bg-white shadow-sm text-slate-600 border-slate-200 hover:bg-slate-50 flex items-center gap-1.5">
        <Upload class="h-4 w-4" />
        Import
      </Button>
      <Button variant="outline" size="sm" class="bg-white shadow-sm text-slate-600 border-slate-200 hover:bg-slate-50 flex items-center gap-1.5">
        <Download class="h-4 w-4" />
        Export
      </Button>
      <Button
        size="sm"
        class="bg-teal-700 hover:bg-teal-800 text-white shadow-sm flex items-center gap-1.5 font-semibold"
        on:click={() => router.visit('/customers/create')}
      >
        <Plus class="h-4 w-4" />
        Add New
      </Button>
    </div>
  </div>

  <!-- Stats Grid -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <Card.Root class="bg-white shadow-sm border-slate-100 hover:shadow-md transition-shadow">
      <Card.Content class="flex items-center gap-4 p-5">
        <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
          <Users class="h-5 w-5" />
        </div>
        <div>
          <div class="text-2xl font-bold text-slate-900">{(stats.total || 0).toLocaleString('id-ID')}</div>
          <div class="text-xs font-medium text-slate-500 mt-0.5">Total Customer</div>
        </div>
      </Card.Content>
    </Card.Root>

    <Card.Root class="bg-white shadow-sm border-slate-100 hover:shadow-md transition-shadow">
      <Card.Content class="flex items-center gap-4 p-5">
        <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600">
          <CheckCircle2 class="h-5 w-5" />
        </div>
        <div>
          <div class="text-2xl font-bold text-slate-900">{(stats.active || 0).toLocaleString('id-ID')}</div>
          <div class="text-xs font-medium text-slate-500 mt-0.5">Aktif</div>
        </div>
      </Card.Content>
    </Card.Root>

    <Card.Root class="bg-white shadow-sm border-slate-100 hover:shadow-md transition-shadow">
      <Card.Content class="flex items-center gap-4 p-5">
        <div class="p-3 rounded-xl bg-red-50 text-red-500">
          <XCircle class="h-5 w-5" />
        </div>
        <div>
          <div class="text-2xl font-bold text-slate-900">{(stats.inactive || 0).toLocaleString('id-ID')}</div>
          <div class="text-xs font-medium text-slate-500 mt-0.5">Non-Aktif</div>
        </div>
      </Card.Content>
    </Card.Root>

    <Card.Root class="bg-white shadow-sm border-slate-100 hover:shadow-md transition-shadow">
      <Card.Content class="flex items-center gap-4 p-5">
        <div class="p-3 rounded-xl bg-amber-50 text-amber-600">
          <DollarSign class="h-5 w-5" />
        </div>
        <div>
          <div class="text-lg font-bold text-slate-900 leading-tight">{formatRp(stats.totalUnpaid || 0)}</div>
          <div class="text-xs font-medium text-slate-500 mt-0.5">Total Piutang</div>
        </div>
      </Card.Content>
    </Card.Root>
  </div>

  <!-- Search & Filter Bar -->
  <Card.Root class="mb-6 bg-white border-slate-100 shadow-sm overflow-hidden">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4">
      <!-- Search inline -->
      <div class="relative flex-1 max-w-sm">
        <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
        <Input
          type="text"
          placeholder="Type to search..."
          bind:value={search}
          on:input={handleSearchInput}
          on:keydown={(e) => e.key === 'Enter' && applyFilter()}
          class="pl-9 bg-white border-slate-200 h-9 text-sm"
        />
      </div>

      <!-- Filter toggle -->
      <button
        class="flex items-center gap-1.5 h-9 px-3 rounded-md border border-slate-200 bg-white text-sm text-slate-600 hover:bg-slate-50 transition-colors shadow-sm font-medium"
        on:click={() => filterOpen = !filterOpen}
      >
        <Filter class="h-4 w-4 text-slate-400" />
        Filter
        {#if hasActiveFilter}
          <span class="px-1.5 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-800 rounded-full border border-amber-200">Aktif</span>
        {/if}
      </button>

      <div class="sm:ml-auto flex items-center gap-2">
        <span class="text-xs text-slate-500">Show:</span>
        <select value={perPage} on:change={changePerPage} class="h-9 rounded-md border border-slate-200 bg-white px-2 text-xs outline-none shadow-sm">
          <option value={10}>10</option>
          <option value={25}>25</option>
          <option value={50}>50</option>
          <option value={100}>100</option>
        </select>
      </div>
    </div>

    <!-- Collapsible Filter Panel -->
    {#if filterOpen}
      <div class="px-4 pb-4 border-t border-slate-100 pt-3 bg-slate-50/30">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Status</label>
            <select bind:value={status} class="flex h-9 w-full rounded-md border border-slate-200 bg-white px-3 py-1 text-sm shadow-sm outline-none focus:border-teal-500">
              <option value="">Semua Status</option>
              <option value="active">Enabled</option>
              <option value="inactive">Disabled</option>
            </select>
          </div>
        </div>
        <div class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-100">
          <Button size="sm" class="bg-teal-700 hover:bg-teal-800 text-white flex items-center gap-1.5" on:click={applyFilter}>
            <Search class="h-3.5 w-3.5" />
            Cari
          </Button>
          {#if hasActiveFilter}
            <Button variant="ghost" size="sm" class="text-slate-500 hover:text-slate-700" on:click={resetFilter}>Reset Filter</Button>
          {/if}
        </div>
      </div>
    {/if}
  </Card.Root>

  <!-- Table Card -->
  <Card.Root class="bg-white border-slate-100 shadow-sm overflow-hidden">
    <!-- Toolbar -->
    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-slate-50/20">
      <span class="text-xs text-slate-500">
        Menampilkan <strong class="text-slate-700">{pagination.from || 0}–{pagination.to || 0}</strong> dari
        <strong class="text-slate-700">{(pagination.total || 0).toLocaleString('id-ID')}</strong> hasil
        {#if hasActiveFilter}<span class="text-slate-400">(difilter dari {(stats.total || 0).toLocaleString('id-ID')})</span>{/if}
      </span>
    </div>

    <!-- Table -->
    <div class="w-full overflow-x-auto">
      <Table.Root>
        <Table.Header class="bg-slate-50/50">
          <Table.Row class="hover:bg-transparent border-slate-100">
            <Table.Head class="font-bold text-teal-700 text-xs py-2.5 cursor-pointer hover:text-teal-800 whitespace-nowrap">
              <span class="flex items-center gap-1">Name <ChevronsUpDown class="h-3 w-3 opacity-60" /></span>
            </Table.Head>
            <Table.Head class="font-bold text-teal-700 text-xs py-2.5 cursor-pointer hover:text-teal-800 whitespace-nowrap">
              <span class="flex items-center gap-1">Email <ChevronsUpDown class="h-3 w-3 opacity-60" /></span>
            </Table.Head>
            <Table.Head class="font-bold text-teal-700 text-xs py-2.5 cursor-pointer hover:text-teal-800 whitespace-nowrap">
              <span class="flex items-center gap-1">Phone <ChevronsUpDown class="h-3 w-3 opacity-60" /></span>
            </Table.Head>
            <Table.Head class="font-bold text-teal-700 text-xs py-2.5 cursor-pointer hover:text-teal-800 text-right whitespace-nowrap">
              <span class="flex items-center justify-end gap-1">Unpaid <ChevronsUpDown class="h-3 w-3 opacity-60" /></span>
            </Table.Head>
            <Table.Head class="font-bold text-teal-700 text-xs py-2.5 cursor-pointer hover:text-teal-800 whitespace-nowrap">
              <span class="flex items-center gap-1">Status <ChevronsUpDown class="h-3 w-3 opacity-60" /></span>
            </Table.Head>
            <Table.Head class="font-bold text-slate-600 text-xs py-2.5 text-right pr-4">Actions</Table.Head>
          </Table.Row>
        </Table.Header>
        <Table.Body>
          {#each customers as cust (cust.id)}
            <Table.Row class="hover:bg-slate-50/60 transition-colors duration-100 border-slate-100">
              <Table.Cell class="py-1.5 font-semibold">
                <a href="/customers/{cust.id}" class="text-teal-600 hover:text-teal-700 hover:underline text-sm">
                  {cust.name}
                </a>
              </Table.Cell>
              <Table.Cell class="py-1.5 text-xs text-slate-500">
                {#if cust.email}
                  {cust.email}
                {:else}
                  <span class="text-slate-300">–</span>
                {/if}
              </Table.Cell>
              <Table.Cell class="py-1.5 text-xs text-slate-500">
                {#if cust.phone}
                  {cust.phone}
                {:else}
                  <span class="text-slate-300">–</span>
                {/if}
              </Table.Cell>
              <Table.Cell class="py-1.5 text-xs text-right font-medium text-slate-700 whitespace-nowrap">
                {formatRp(cust.unpaid || 0)}
              </Table.Cell>
              <Table.Cell class="py-1.5">
                {#if cust.is_active}
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-600 text-white shadow-sm">
                    Enabled
                  </span>
                {:else}
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-red-500 text-white shadow-sm">
                    Disabled
                  </span>
                {/if}
              </Table.Cell>
              <Table.Cell class="py-1.5 text-right pr-3">
                <DropdownMenu.Root>
                  <DropdownMenu.Trigger asChild let:builder>
                    <Button builders={[builder]} variant="ghost" size="icon" class="h-7 w-7 text-slate-400 hover:text-slate-700 rounded-md">
                      <MoreHorizontal class="h-4 w-4" />
                    </Button>
                  </DropdownMenu.Trigger>
                  <DropdownMenu.Content align="end" class="w-44 shadow-lg border-slate-100">
                    <DropdownMenu.Item class="text-xs gap-2 cursor-pointer" on:click={() => router.visit(`/customers/${cust.id}`)}>
                      <Eye class="h-3.5 w-3.5 text-slate-400" />
                      Lihat Detail
                    </DropdownMenu.Item>
                    <DropdownMenu.Item class="text-xs gap-2 cursor-pointer" on:click={() => router.visit(`/customers/${cust.id}/edit`)}>
                      <Pencil class="h-3.5 w-3.5 text-slate-400" />
                      Edit
                    </DropdownMenu.Item>
                    <DropdownMenu.Separator />
                    <DropdownMenu.Item class="text-xs gap-2 cursor-pointer text-red-600 focus:text-red-700 focus:bg-red-50">
                      <Trash2 class="h-3.5 w-3.5" />
                      Hapus
                    </DropdownMenu.Item>
                  </DropdownMenu.Content>
                </DropdownMenu.Root>
              </Table.Cell>
            </Table.Row>
          {/each}

          {#if customers.length === 0}
            <Table.Row class="hover:bg-transparent">
              <Table.Cell colspan="6" class="text-center py-16 text-slate-400 text-sm">
                <div class="flex flex-col items-center gap-3">
                  <Users class="h-10 w-10 text-slate-200" />
                  <span>Tidak ada data customer yang sesuai</span>
                  {#if hasActiveFilter}
                    <Button variant="outline" size="sm" class="text-xs" on:click={resetFilter}>Reset Filter</Button>
                  {/if}
                </div>
              </Table.Cell>
            </Table.Row>
          {/if}
        </Table.Body>
      </Table.Root>
    </div>

    <!-- Pagination Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 border-t border-slate-100 gap-3 bg-slate-50/20">
      <span class="text-xs text-slate-500">
        Halaman <strong>{pagination.currentPage || 1}</strong> dari <strong>{pagination.lastPage || 1}</strong>
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
