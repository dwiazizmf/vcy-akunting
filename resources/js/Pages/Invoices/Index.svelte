<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Card from '$lib/components/ui/card';
  import * as Table from '$lib/components/ui/table';
  import { cn } from '$lib/utils.js';

  // ================================================
  // PROPS DARI LARAVEL
  // ================================================
  export let invoices   = [];
  export let pagination = { total: 0, perPage: 10, currentPage: 1, lastPage: 1, from: 0, to: 0 };
  export let stats      = { total: 0, draft: 0, sent: 0, paid: 0, totalAmount: 0 };
  export let filters    = { search: '', status: '', kapal: '', date_from: '', date_to: '', per_page: 10 };

  // ================================================
  // STATE LOKAL
  // ================================================
  let filterOpen   = false;
  let selectedRows = [];

  let search    = filters.search    || '';
  let status    = filters.status    || '';
  let kapal     = filters.kapal     || '';
  let dateFrom  = filters.date_from || '';
  let dateTo    = filters.date_to   || '';
  let perPage   = filters.per_page  || 10;

  // ================================================
  // SERVER-SIDE NAVIGATION
  // ================================================
  function applyFilter() {
    router.get('/invoices', {
      search, status, kapal,
      date_from: dateFrom,
      date_to: dateTo,
      per_page: perPage,
      page: 1,
    }, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  }

  function resetFilter() {
    search = ''; status = ''; kapal = ''; dateFrom = ''; dateTo = '';
    applyFilter();
  }

  function goToPage(page) {
    if (page < 1 || page > pagination.lastPage) return;
    router.get('/invoices', {
      search, status, kapal,
      date_from: dateFrom,
      date_to: dateTo,
      per_page: perPage,
      page,
    }, { preserveState: true, preserveScroll: true, replace: true });
  }

  function changePerPage(e) {
    perPage = parseInt(e.target.value);
    applyFilter();
  }

  // Pagination pages (tampilkan maks 5 nomor halaman)
  $: pageNumbers = (() => {
    const total = pagination.lastPage;
    const cur   = pagination.currentPage;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const pages = new Set([1, total, cur]);
    if (cur > 1) pages.add(cur - 1);
    if (cur < total) pages.add(cur + 1);
    return [...pages].sort((a, b) => a - b);
  })();

  // Row selection
  function toggleRow(id) {
    selectedRows = selectedRows.includes(id)
      ? selectedRows.filter(r => r !== id)
      : [...selectedRows, id];
  }
  function toggleAll() {
    const ids = invoices.map(i => i.id);
    const allSel = ids.every(id => selectedRows.includes(id));
    selectedRows = allSel ? selectedRows.filter(id => !ids.includes(id)) : [...new Set([...selectedRows, ...ids])];
  }

  $: hasActiveFilter = search || status || kapal || dateFrom || dateTo;

  const statusColor = {
    Draft: 'bg-slate-100 text-slate-800 border-slate-200',
    Sent: 'bg-amber-100 text-amber-850 border-amber-250',
    Paid: 'bg-emerald-100 text-emerald-850 border-emerald-250'
  };
</script>

<AppLayout>
  <!-- Page Header -->
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Invoices</h1>
      <p class="text-sm text-muted-foreground mt-1">
        {#if hasActiveFilter}
          Menampilkan <strong class="text-teal-700">{pagination.total}</strong> dari <strong class="text-slate-700">{stats.total}</strong> total invoice
        {:else}
          <strong class="text-slate-700">{stats.total}</strong> total invoice
        {/if}
      </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      {#if selectedRows.length > 0}
        <Button variant="destructive" size="sm" class="shadow-sm">
          {selectedRows.length} terpilih · Hapus
        </Button>
      {/if}
      <Button variant="outline" size="sm" class="bg-white shadow-sm">Print Tanda Terima</Button>
      <Button variant="outline" size="sm" class="bg-white shadow-sm">Export</Button>
      <Button size="sm" class="bg-teal-700 hover:bg-teal-800 text-white shadow-sm flex items-center gap-1.5" on:click={() => router.visit('/invoices/create')}>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Create Invoice
      </Button>
    </div>
  </div>

  <!-- Stats Grid -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <Card.Root class="bg-white shadow-sm border-slate-100 hover:shadow-md transition">
      <Card.Content class="flex items-center gap-4 p-5">
        <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div>
          <div class="text-2xl font-bold text-slate-900">{stats.total.toLocaleString()}</div>
          <div class="text-xs font-medium text-slate-500 mt-0.5">Total Invoices</div>
        </div>
      </Card.Content>
    </Card.Root>

    <Card.Root class="bg-white shadow-sm border-slate-100 hover:shadow-md transition">
      <Card.Content class="flex items-center gap-4 p-5">
        <div class="p-3 rounded-xl bg-slate-100 text-slate-600">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <div class="text-2xl font-bold text-slate-900">{stats.draft}</div>
          <div class="text-xs font-medium text-slate-500 mt-0.5">Draft</div>
        </div>
      </Card.Content>
    </Card.Root>

    <Card.Root class="bg-white shadow-sm border-slate-100 hover:shadow-md transition">
      <Card.Content class="flex items-center gap-4 p-5">
        <div class="p-3 rounded-xl bg-amber-50 text-amber-600">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        </div>
        <div>
          <div class="text-2xl font-bold text-slate-900">{stats.sent}</div>
          <div class="text-xs font-medium text-slate-500 mt-0.5">Sent</div>
        </div>
      </Card.Content>
    </Card.Root>

    <Card.Root class="bg-white shadow-sm border-slate-100 hover:shadow-md transition">
      <Card.Content class="flex items-center gap-4 p-5">
        <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <div class="text-2xl font-bold text-slate-900">{stats.paid}</div>
          <div class="text-xs font-medium text-slate-500 mt-0.5">Paid</div>
        </div>
      </Card.Content>
    </Card.Root>
  </div>

  <!-- Collapsible Filter Card -->
  <Card.Root class="mb-6 bg-white border-slate-100 shadow-sm overflow-hidden">
    <!-- Filter Toggle Header -->
    <button class="w-full flex items-center justify-between p-4 bg-slate-50/50 hover:bg-slate-50 transition text-left cursor-pointer" on:click={() => filterOpen = !filterOpen}>
      <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        <span>Filter & Pencarian</span>
        {#if hasActiveFilter}
          <span class="px-2 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-800 rounded-full border border-amber-250">Aktif</span>
        {/if}
      </div>
      <svg class={cn("h-4 w-4 text-slate-400 transition-transform duration-200", filterOpen && "rotate-180")} fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
      </svg>
    </button>

    <!-- Filter Content -->
    {#if filterOpen}
      <div class="p-4 border-t border-slate-100 bg-white space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Cari (Customer/No/Order)</label>
            <Input bind:value={search} type="text" placeholder="Cari..." on:keydown={(e) => e.key === 'Enter' && applyFilter()} class="bg-white border-slate-200" />
          </div>

          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Status</label>
            <select bind:value={status} class="flex h-9 w-full rounded-md border border-slate-200 bg-white px-3 py-1 text-sm shadow-inner outline-none focus:border-teal-500">
              <option value="">Semua Status</option>
              <option>Draft</option>
              <option>Sent</option>
              <option>Paid</option>
            </select>
          </div>

          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Nama Kapal</label>
            <Input bind:value={kapal} type="text" placeholder="Nama kapal..." on:keydown={(e) => e.key === 'Enter' && applyFilter()} class="bg-white border-slate-200" />
          </div>

          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Mulai Tanggal</label>
            <Input bind:value={dateFrom} type="date" class="bg-white border-slate-200" />
          </div>

          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Sampai Tanggal</label>
            <Input bind:value={dateTo} type="date" class="bg-white border-slate-200" />
          </div>

          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">COA</label>
            <Input type="text" placeholder="Nomor COA..." class="bg-white border-slate-200" />
          </div>
        </div>

        <div class="flex items-center justify-between pt-2 border-t border-slate-50">
          <div class="flex items-center gap-2">
            <Button size="sm" class="bg-teal-700 hover:bg-teal-800 text-white" on:click={applyFilter}>
              <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
              Cari
            </Button>
            {#if hasActiveFilter}
              <Button variant="ghost" size="sm" class="text-slate-500 hover:text-slate-700" on:click={resetFilter}>Reset Filter</Button>
            {/if}
          </div>
          <span class="text-xs text-slate-400 hidden sm:inline-block">💡 Tekan Enter untuk mencari cepat</span>
        </div>
      </div>
    {/if}
  </Card.Root>

  <!-- Table Card -->
  <Card.Root class="bg-white border-slate-100 shadow-sm overflow-hidden">
    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 border-b border-slate-100 gap-3 bg-slate-50/20">
      <span class="text-xs text-slate-500">
        Menampilkan <strong class="text-slate-700">{pagination.from}–{pagination.to}</strong> dari
        <strong class="text-slate-700">{pagination.total.toLocaleString()}</strong> hasil
        {#if hasActiveFilter}(difilter dari {stats.total.toLocaleString()}){/if}
      </span>
      <div class="flex items-center gap-2">
        <span class="text-xs text-slate-500">Per halaman:</span>
        <select value={perPage} on:change={changePerPage} class="h-8 rounded-md border border-slate-200 bg-white px-2 py-1 text-xs outline-none">
          <option value={10}>10</option>
          <option value={25}>25</option>
          <option value={50}>50</option>
          <option value={100}>100</option>
        </select>
      </div>
    </div>

    <!-- Table Container -->
    <div class="w-full overflow-x-auto">
      <Table.Root>
        <Table.Header class="bg-slate-50/50">
          <Table.Row class="hover:bg-transparent">
            <Table.Head class="w-10"><input type="checkbox" on:change={toggleAll} class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" /></Table.Head>
            <Table.Head class="font-bold text-slate-700 text-xs uppercase tracking-wider">No</Table.Head>
            <Table.Head class="font-bold text-slate-700 text-xs uppercase tracking-wider">Number</Table.Head>
            <Table.Head class="font-bold text-slate-700 text-xs uppercase tracking-wider">Order Number</Table.Head>
            <Table.Head class="font-bold text-slate-700 text-xs uppercase tracking-wider">Customer</Table.Head>
            <Table.Head class="font-bold text-slate-700 text-xs uppercase tracking-wider">Amount</Table.Head>
            <Table.Head class="font-bold text-slate-700 text-xs uppercase tracking-wider">Kapal</Table.Head>
            <Table.Head class="font-bold text-slate-700 text-xs uppercase tracking-wider">Invoice Date</Table.Head>
            <Table.Head class="font-bold text-slate-700 text-xs uppercase tracking-wider">Due Date</Table.Head>
            <Table.Head class="font-bold text-slate-700 text-xs uppercase tracking-wider">Status</Table.Head>
            <Table.Head class="font-bold text-slate-700 text-xs uppercase tracking-wider">Faktur</Table.Head>
            <Table.Head class="w-10"></Table.Head>
          </Table.Row>
        </Table.Header>
        <Table.Body>
          {#each invoices as inv (inv.id)}
            <Table.Row class={cn("transition-colors duration-150", selectedRows.includes(inv.id) && "bg-teal-50/40 hover:bg-teal-50/60")}>
              <Table.Cell><input type="checkbox" checked={selectedRows.includes(inv.id)} on:change={() => toggleRow(inv.id)} class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" /></Table.Cell>
              <Table.Cell class="font-mono text-xs text-slate-400">{inv.no}</Table.Cell>
              <Table.Cell><a href="#inv-{inv.number}" class="text-teal-600 hover:text-teal-700 hover:underline font-semibold">{inv.number}</a></Table.Cell>
              <Table.Cell class="text-xs text-slate-600">{inv.orderNumber}</Table.Cell>
              <Table.Cell class="max-w-[200px] truncate font-medium text-slate-900">{inv.customer}</Table.Cell>
              <Table.Cell class="font-semibold text-slate-900 whitespace-nowrap">{inv.amount}</Table.Cell>
              <Table.Cell class="text-xs text-slate-600">{inv.namaKapal}</Table.Cell>
              <Table.Cell class="text-xs text-slate-500 whitespace-nowrap">{inv.invoiceDate}</Table.Cell>
              <Table.Cell class="text-xs text-slate-500 whitespace-nowrap">{inv.dueDate}</Table.Cell>
              <Table.Cell>
                <span class={cn("px-2.5 py-0.5 rounded-full text-xs font-semibold border shadow-sm", statusColor[inv.status] || 'bg-slate-100 text-slate-800 border-slate-200')}>
                  {inv.status}
                </span>
              </Table.Cell>
              <Table.Cell class="text-xs text-slate-400">{inv.faktur}</Table.Cell>
              <Table.Cell>
                <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-400 hover:text-slate-700 rounded-md">
                  <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                </Button>
              </Table.Cell>
            </Table.Row>
          {/each}
          {#if invoices.length === 0}
            <Table.Row class="hover:bg-transparent">
              <Table.Cell colspan="12" class="text-center py-12 text-slate-400 text-sm">
                Tidak ada data yang sesuai filter
              </Table.Cell>
            </Table.Row>
          {/if}
        </Table.Body>
      </Table.Root>
    </div>

    <!-- Pagination Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 border-t border-slate-100 gap-3 bg-slate-50/20">
      <span class="text-xs text-slate-500">
        Halaman <strong>{pagination.currentPage}</strong> dari <strong>{pagination.lastPage.toLocaleString()}</strong>
      </span>
      <div class="flex items-center gap-1">
        <!-- First page -->
        <Button variant="outline" size="icon" class="h-8 w-8 bg-white border-slate-200 text-slate-600 shadow-sm" disabled={pagination.currentPage === 1} on:click={() => goToPage(1)}>
          «
        </Button>
        <!-- Prev page -->
        <Button variant="outline" size="icon" class="h-8 w-8 bg-white border-slate-200 text-slate-600 shadow-sm" disabled={pagination.currentPage === 1} on:click={() => goToPage(pagination.currentPage - 1)}>
          ‹
        </Button>

        <!-- Pages Numbers -->
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

        <!-- Next page -->
        <Button variant="outline" size="icon" class="h-8 w-8 bg-white border-slate-200 text-slate-600 shadow-sm" disabled={pagination.currentPage === pagination.lastPage} on:click={() => goToPage(pagination.currentPage + 1)}>
          ›
        </Button>
        <!-- Last page -->
        <Button variant="outline" size="icon" class="h-8 w-8 bg-white border-slate-200 text-slate-600 shadow-sm" disabled={pagination.currentPage === pagination.lastPage} on:click={() => goToPage(pagination.lastPage)}>
          »
        </Button>
      </div>
    </div>
  </Card.Root>
</AppLayout>
