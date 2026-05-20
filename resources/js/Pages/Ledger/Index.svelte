<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Card from '$lib/components/ui/card';
  import * as Table from '$lib/components/ui/table';
  import { cn } from '$lib/utils.js';

  export let ledgerData = [];

  let currentPage = 1;
  let pageSize = 10;
  let selectedAccount = '11101 - Kas Besar';

  const transactions = ledgerData.length > 0 ? ledgerData : [
    { id: 1, date: '2026-05-01', account: '11101 - Kas Besar', description: 'Saldo Awal (Brought Forward)', debit: 150000000, credit: 0, balance: 150000000 },
    { id: 2, date: '2026-05-03', account: '11101 - Kas Besar', description: 'Pembayaran Piutang PT Maju Jaya', debit: 25000005, credit: 0, balance: 175000005 },
    { id: 3, date: '2026-05-05', account: '11101 - Kas Besar', description: 'Biaya Listrik, Air & Internet', debit: 0, credit: 4500000, balance: 170500005 },
    { id: 4, date: '2026-05-07', account: '11101 - Kas Besar', description: 'Pembelian Meja Kerja Kantor', debit: 0, credit: 12000000, balance: 158500005 },
    { id: 5, date: '2026-05-09', account: '11101 - Kas Besar', description: 'Penerimaan Invoice PT Inbisco', debit: 45000000, credit: 0, balance: 203500005 },
    { id: 6, date: '2026-05-11', account: '11101 - Kas Besar', description: 'Biaya Operasional Kapal', debit: 0, credit: 8750000, balance: 194750005 },
    { id: 7, date: '2026-05-14', account: '11101 - Kas Besar', description: 'Pembayaran dari PT Primarasa', debit: 15200000, credit: 0, balance: 209950005 },
    { id: 8, date: '2026-05-16', account: '11101 - Kas Besar', description: 'Pembelian ATK & Supplies', debit: 0, credit: 2300000, balance: 207650005 },
  ];

  const fmt = (n) => n.toLocaleString('id-ID', { style:'currency', currency:'IDR', minimumFractionDigits:0 });

  // Reset page if page size changes
  $: {
    if (pageSize) {
      currentPage = 1;
    }
  }

  $: totalPages = Math.ceil(transactions.length / pageSize) || 1;
  $: paginatedItems = transactions.slice((currentPage - 1) * pageSize, currentPage * pageSize);

  $: pageNumbers = (() => {
    if (totalPages <= 7) return Array.from({ length: totalPages }, (_, i) => i + 1);
    const pages = new Set([1, totalPages, currentPage]);
    if (currentPage > 1) pages.add(currentPage - 1);
    if (currentPage < totalPages) pages.add(currentPage + 1);
    return [...pages].sort((a, b) => a - b);
  })();

  function goToPage(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
  }

  const totalDebit = transactions.reduce((s, t) => s + t.debit, 0);
  const totalCredit = transactions.reduce((s, t) => s + t.credit, 0);
  const lastBalance = transactions[transactions.length - 1]?.balance ?? 0;
</script>

<AppLayout>
  <!-- Page Header -->
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">General Ledger</h1>
      <p class="text-sm text-muted-foreground mt-1">Buku Besar · Detail Transaksi</p>
    </div>
    <div class="flex items-center gap-2">
      <Button variant="outline" size="sm" class="bg-white shadow-sm flex items-center gap-1.5">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Print
      </Button>
      <Button size="sm" class="bg-teal-700 hover:bg-teal-800 text-white shadow-sm flex items-center gap-1.5">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Export
      </Button>
    </div>
  </div>

  <!-- Stats Grid -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <Card.Root class="bg-white shadow-sm border-slate-100 hover:shadow-md transition">
      <Card.Content class="flex items-center gap-4 p-5">
        <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>
        </div>
        <div>
          <div class="text-lg font-bold text-emerald-600">{fmt(totalDebit)}</div>
          <div class="text-xs font-medium text-slate-500 mt-0.5">Total Debit</div>
        </div>
      </Card.Content>
    </Card.Root>

    <Card.Root class="bg-white shadow-sm border-slate-100 hover:shadow-md transition">
      <Card.Content class="flex items-center gap-4 p-5">
        <div class="p-3 rounded-xl bg-rose-50 text-rose-600">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/></svg>
        </div>
        <div>
          <div class="text-lg font-bold text-rose-600">{fmt(totalCredit)}</div>
          <div class="text-xs font-medium text-slate-500 mt-0.5">Total Kredit</div>
        </div>
      </Card.Content>
    </Card.Root>

    <Card.Root class="bg-white shadow-sm border-slate-100 hover:shadow-md transition">
      <Card.Content class="flex items-center gap-4 p-5">
        <div class="p-3 rounded-xl bg-teal-50 text-teal-600">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
        </div>
        <div>
          <div class="text-lg font-bold text-slate-900">{fmt(lastBalance)}</div>
          <div class="text-xs font-medium text-slate-500 mt-0.5">Saldo Akhir</div>
        </div>
      </Card.Content>
    </Card.Root>

    <Card.Root class="bg-white shadow-sm border-slate-100 hover:shadow-md transition">
      <Card.Content class="flex items-center gap-4 p-5">
        <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <div>
          <div class="text-lg font-bold text-slate-900">{transactions.length}</div>
          <div class="text-xs font-medium text-slate-500 mt-0.5">Transaksi</div>
        </div>
      </Card.Content>
    </Card.Root>
  </div>

  <!-- Filter Card -->
  <Card.Root class="mb-6 bg-white border-slate-100 shadow-sm overflow-hidden">
    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
      <div>
        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Akun COA</label>
        <select bind:value={selectedAccount} class="flex h-9 w-full rounded-md border border-slate-200 bg-white px-3 py-1 text-sm shadow-inner outline-none focus:border-teal-500">
          <option>11101 - Kas Besar</option>
          <option>11201 - Bank BCA</option>
          <option>11301 - Piutang Usaha</option>
        </select>
      </div>

      <div>
        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Tanggal Mulai</label>
        <Input type="date" class="bg-white border-slate-200" value="2026-05-01" />
      </div>

      <div>
        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Tanggal Akhir</label>
        <Input type="date" class="bg-white border-slate-200" value="2026-05-31" />
      </div>

      <div>
        <Button class="w-full bg-teal-700 hover:bg-teal-800 text-white shadow-sm">Tarik Data</Button>
      </div>
    </div>
  </Card.Root>

  <!-- Table Card -->
  <Card.Root class="bg-white border-slate-100 shadow-sm overflow-hidden">
    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 border-b border-slate-100 gap-3 bg-slate-50/20">
      <span class="text-xs text-slate-500">
        Menampilkan <strong class="text-slate-700">{paginatedItems.length}</strong> dari <strong class="text-slate-700">{transactions.length}</strong> transaksi
      </span>
      <div class="flex items-center gap-2">
        <span class="text-xs text-slate-500">Per halaman:</span>
        <select bind:value={pageSize} class="h-8 rounded-md border border-slate-200 bg-white px-2 py-1 text-xs outline-none">
          <option value={5}>5</option>
          <option value={10}>10</option>
          <option value={25}>25</option>
        </select>
      </div>
    </div>

    <!-- Table Container -->
    <div class="w-full overflow-x-auto">
      <Table.Root>
        <Table.Header class="bg-slate-50/50">
          <Table.Row class="hover:bg-transparent">
            <Table.Head class="font-bold text-slate-700 text-xs uppercase tracking-wider">Tanggal</Table.Head>
            <Table.Head class="font-bold text-slate-700 text-xs uppercase tracking-wider">Keterangan</Table.Head>
            <Table.Head class="font-bold text-slate-700 text-xs uppercase tracking-wider text-right">Debit</Table.Head>
            <Table.Head class="font-bold text-slate-700 text-xs uppercase tracking-wider text-right">Kredit</Table.Head>
            <Table.Head class="font-bold text-slate-700 text-xs uppercase tracking-wider text-right">Saldo Berjalan</Table.Head>
          </Table.Row>
        </Table.Header>
        <Table.Body>
          {#each paginatedItems as trx (trx.id)}
            <Table.Row class="transition-colors hover:bg-slate-50/50">
              <Table.Cell class="text-xs text-slate-500 whitespace-nowrap">{trx.date}</Table.Cell>
              <Table.Cell>
                <div class="font-medium text-slate-900 text-sm">{trx.description}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">{trx.account}</div>
              </Table.Cell>
              <Table.Cell class={cn("text-right font-medium whitespace-nowrap text-sm", trx.debit > 0 ? "text-emerald-600" : "text-slate-300")}>
                {trx.debit > 0 ? fmt(trx.debit) : '—'}
              </Table.Cell>
              <Table.Cell class={cn("text-right font-medium whitespace-nowrap text-sm", trx.credit > 0 ? "text-rose-500" : "text-slate-300")}>
                {trx.credit > 0 ? fmt(trx.credit) : '—'}
              </Table.Cell>
              <Table.Cell class="text-right font-bold text-slate-800 whitespace-nowrap text-sm">{fmt(trx.balance)}</Table.Cell>
            </Table.Row>
          {/each}
        </Table.Body>
        <Table.Footer class="bg-slate-50/55 font-bold border-t-2 border-slate-200">
          <Table.Row class="hover:bg-transparent">
            <Table.Cell colspan="2" class="text-slate-700 text-xs uppercase tracking-wider">Total</Table.Cell>
            <Table.Cell class="text-right text-emerald-600 text-sm">{fmt(totalDebit)}</Table.Cell>
            <Table.Cell class="text-right text-rose-600 text-sm">{fmt(totalCredit)}</Table.Cell>
            <Table.Cell class="text-right text-slate-900 text-sm">{fmt(lastBalance)}</Table.Cell>
          </Table.Row>
        </Table.Footer>
      </Table.Root>
    </div>

    <!-- Pagination -->
    {#if totalPages > 1}
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 border-t border-slate-100 gap-3 bg-slate-50/20">
        <span class="text-xs text-slate-500">
          Halaman <strong>{currentPage}</strong> dari <strong>{totalPages}</strong>
        </span>
        <div class="flex items-center gap-1">
          <Button variant="outline" size="icon" class="h-8 w-8 bg-white border-slate-200 text-slate-600 shadow-sm" disabled={currentPage === 1} on:click={() => goToPage(1)}>
            «
          </Button>
          <Button variant="outline" size="icon" class="h-8 w-8 bg-white border-slate-200 text-slate-600 shadow-sm" disabled={currentPage === 1} on:click={() => goToPage(currentPage - 1)}>
            ‹
          </Button>

          {#each pageNumbers as p, i}
            {#if i > 0 && pageNumbers[i] - pageNumbers[i-1] > 1}
              <span class="px-2 text-slate-400 text-xs">…</span>
            {/if}
            <Button
              variant={p === currentPage ? 'default' : 'outline'}
              size="icon"
              class={cn("h-8 w-8 shadow-sm", p === currentPage ? "bg-teal-700 hover:bg-teal-800 text-white" : "bg-white border-slate-200 text-slate-700")}
              on:click={() => goToPage(p)}
            >
              {p}
            </Button>
          {/each}

          <Button variant="outline" size="icon" class="h-8 w-8 bg-white border-slate-200 text-slate-600 shadow-sm" disabled={currentPage === totalPages} on:click={() => goToPage(currentPage + 1)}>
            ›
          </Button>
          <Button variant="outline" size="icon" class="h-8 w-8 bg-white border-slate-200 text-slate-600 shadow-sm" disabled={currentPage === totalPages} on:click={() => goToPage(totalPages)}>
            »
          </Button>
        </div>
      </div>
    {/if}
  </Card.Root>
</AppLayout>