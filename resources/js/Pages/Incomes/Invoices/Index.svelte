<script>
  import AppLayout from '../../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Card from '$lib/components/ui/card';
  import * as Table from '$lib/components/ui/table';
  import {
    Plus, Search, Pencil, Trash2, ArrowLeft, ArrowRight, XCircle, MoreVertical, Building2, Calendar, Download, RefreshCw, FileText, SendHorizonal
  } from 'lucide-svelte';
  import { showToast } from '../../../Stores/toast.js';
  import { showConfirm } from '../../../Stores/confirmStore.js';
  import { cn } from '$lib/utils.js';

  import Pagination from '../../../Components/Pagination.svelte';
  import ColumnToggle from '../../../Components/ColumnToggle.svelte';
  import StatsGrid from '../../../Components/StatsGrid.svelte';
  import FilterPanel from '../../../Components/FilterPanel.svelte';
  import TableCard from '../../../Components/TableCard.svelte';

  // ================================================
  // PROPS DARI LARAVEL
  // ================================================
  export let invoices   = [];
  export let pagination = { total: 0, perPage: 10, currentPage: 1, lastPage: 1, from: 0, to: 0 };
  export let stats      = { total: 0, draft: 0, sent: 0, paid: 0, totalAmount: 0 };
  export let filters    = { search: '', status: '', kapal: '', date_from: '', date_to: '', isPosted: '', per_page: 10 };

  // ================================================
  // STATE LOKAL
  // ================================================
  let filterOpen      = false;
  let selectedRows    = [];
  let expandedRows    = [];      // Row IDs yang di-expand

  let search   = filters.search    || '';
  let status   = filters.status    || '';
  let kapal    = filters.kapal     || '';
  let dateFrom = filters.date_from || '';
  let dateTo   = filters.date_to   || '';
  let isPosted = filters.isPosted  || '';
  let perPage  = filters.per_page  || 10;

  // ================================================
  // DEFINISI KOLOM — setiap kolom bisa di-show/hide
  // ================================================
  let columns = [
    { key: 'no',            label: 'No',            visible: true  },
    { key: 'number',        label: 'Number',         visible: true  },
    { key: 'orderNumber',   label: 'Order Num',      visible: true  },
    { key: 'coa',           label: 'COA',            visible: true  },
    { key: 'company',       label: 'Perusahaan',     visible: true  },
    { key: 'customer',      label: 'Customer',       visible: true  },
    { key: 'amount',        label: 'Amount',         visible: true  },
    { key: 'namaKapal',     label: 'Kapal',          visible: true  },
    { key: 'tglKapal',      label: 'Tgl Kapal',      visible: true  },
    { key: 'invDate',       label: 'Inv Date',       visible: true  },
    { key: 'dueDate',       label: 'Due Date',       visible: true  },
    { key: 'dokKirim',      label: 'Dok Kirim',      visible: true  },
    { key: 'titipInt',      label: 'Titip Int.',     visible: true  },
    { key: 'bpb',           label: 'BPB',            visible: true  },
    { key: 'status',        label: 'Status',         visible: true  },
    { key: 'statusPayment', label: 'Stat. Payment',  visible: true  },
    { key: 'statCr',        label: 'Stat Cr.',       visible: true  },
    { key: 'faktur',        label: 'Faktur',         visible: true  },
    { key: 'notes',         label: 'Notes',          visible: false },
  ];

  // Reactive lookup: Svelte tracks perubahan `columns` dan update template otomatis
  $: colVisible = Object.fromEntries(columns.map(c => [c.key, c.visible]));

  // ================================================
  // EXPAND / COLLAPSE ROW
  // ================================================
  function toggleRowExpansion(id) {
    expandedRows = expandedRows.includes(id)
      ? expandedRows.filter(rId => rId !== id)
      : [...expandedRows, id];
  }

  // ================================================
  // VOID INVOICE
  // ================================================
  async function voidInvoice(id) {
    if (await showConfirm('Are you sure you want to void this invoice?')) {
      router.delete(`/invoices/${id}`, { 
        preserveScroll: true,
        onSuccess: () => {
          showToast('Invoice berhasil divoid/dihapus.', 'success');
        },
        onError: () => {
          showToast('Gagal membatalkan invoice.', 'error');
        }
      });
    }
  }

  function editInvoice(id) {
    router.visit(`/invoices/${id}/edit`);
  }

  async function postInvoice(id) {
    if (await showConfirm('Posting invoice ini ke jurnal akuntansi? Proses ini tidak dapat dibatalkan.')) {
      router.post(`/invoices/${id}/post`, {}, {
        preserveScroll: true,
        onSuccess: () => showToast('Invoice berhasil diposting ke jurnal!', 'success'),
        onError: (e) => showToast(Object.values(e)[0] || 'Gagal posting invoice.', 'error'),
      });
    }
  }

  async function unpostInvoice(id) {
    if (await showConfirm('Batalkan posting invoice ini? Jurnal akuntansi yang terkait akan dihapus.')) {
      router.post(`/invoices/${id}/unpost`, {}, {
        preserveScroll: true,
        onSuccess: () => showToast('Posting invoice berhasil dibatalkan!', 'success'),
        onError: (e) => showToast(Object.values(e)[0] || 'Gagal unpost invoice.', 'error'),
      });
    }
  }

  async function bulkPostInvoices() {
    if (await showConfirm(`Posting ${selectedRows.length} invoice terpilih ke jurnal akuntansi? Proses ini tidak dapat dibatalkan.`)) {
      router.post('/invoices/bulk-post', { ids: selectedRows }, {
        preserveScroll: true,
        onSuccess: () => {
          showToast(`${selectedRows.length} invoice berhasil diposting!`, 'success');
          selectedRows = [];
        },
        onError: (e) => showToast(Object.values(e)[0] || 'Gagal bulk posting.', 'error')
      });
    }
  }

  function printInvoices() {
    if (selectedRows.length === 0) {
      showToast('Pilih minimal satu invoice untuk dicetak', 'error');
      return;
    }
    const ids = selectedRows.join(',');
    window.open(`/invoices/print?ids=${ids}`, '_blank');
  }

  // ================================================
  // SERVER-SIDE NAVIGATION
  // ================================================
  function applyFilter() {
    router.get('/invoices', {
      search, status, kapal, isPosted,
      date_from: dateFrom,
      date_to: dateTo,
      per_page: perPage,
      page: 1,
    }, { preserveState: true, preserveScroll: true, replace: true });
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

  $: hasActiveFilter = search || status || kapal || dateFrom || dateTo || isPosted;

  const statusColor = {
    draft: 'bg-slate-50 text-slate-600 border-slate-200/60',
    sent:  'bg-sky-50 text-sky-700 border-sky-200/60',
    paid:  'bg-emerald-50 text-emerald-700 border-emerald-200/60',
  };

  const paymentColor = {
    draft: 'bg-slate-100 text-slate-500 border-slate-200',
    sent:  'bg-amber-50 text-amber-700 border-amber-200',
    paid:  'bg-emerald-100 text-emerald-800 border-emerald-200',
  };

</script>

<AppLayout fullWidth={true}>
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
        <Button variant="outline" size="sm" class="bg-teal-50 text-teal-700 border-teal-200 shadow-sm cursor-pointer" on:click={bulkPostInvoices}>
          {selectedRows.length} terpilih · Bulk Post
        </Button>
        <Button variant="destructive" size="sm" class="shadow-sm cursor-pointer">
          {selectedRows.length} terpilih · Hapus
        </Button>
        <Button variant="outline" size="sm" class="bg-indigo-50 text-indigo-700 border-indigo-200 shadow-sm cursor-pointer flex items-center gap-1" on:click={printInvoices}>
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
          Print {selectedRows.length} Invoice
        </Button>
      {/if}
      <Button variant="outline" size="sm" class="bg-white shadow-sm cursor-pointer">Print Tanda Terima</Button>
      <Button variant="outline" size="sm" class="bg-white shadow-sm cursor-pointer">Export</Button>
      <Button size="sm" class="bg-teal-700 hover:bg-teal-800 text-white shadow-sm flex items-center gap-1.5 cursor-pointer" on:click={() => router.visit('/invoices/create')}>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Create Invoice
      </Button>
    </div>
  </div>

  <!-- Stats Grid -->
  <StatsGrid {stats} />

  <!-- Collapsible Filter Card -->
  <FilterPanel {hasActiveFilter} onApply={applyFilter} onReset={resetFilter}>
    <svelte:fragment slot="inputs">
      <div>
        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Cari (Customer/No/Order)</label>
        <Input bind:value={search} type="text" placeholder="Cari..." on:keydown={(e) => e.key === 'Enter' && applyFilter()} class="bg-white border-slate-200" />
      </div>
      <div>
        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Status Posting</label>
        <select bind:value={isPosted} on:change={applyFilter} class="flex h-9 w-full rounded-md border border-slate-200 bg-white px-3 py-1 text-sm shadow-inner outline-none focus:border-teal-500 cursor-pointer">
          <option value="">Semua</option>
          <option value="0">Belum Diposting</option>
          <option value="1">Sudah Diposting</option>
        </select>
      </div>
      <div>
        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Status</label>
        <select bind:value={status} class="flex h-9 w-full rounded-md border border-slate-200 bg-white px-3 py-1 text-sm shadow-inner outline-none focus:border-teal-500 cursor-pointer">
          <option value="">Semua Status</option>
          <option>draft</option>
          <option>sent</option>
          <option>paid</option>
        </select>
      </div>
      <div>
        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Nama Kapal</label>
        <Input bind:value={kapal} type="text" placeholder="Nama kapal..." on:keydown={(e) => e.key === 'Enter' && applyFilter()} class="bg-white border-slate-200" />
      </div>
      <div>
        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Mulai Tanggal</label>
        <Input bind:value={dateFrom} type="date" class="bg-white border-slate-200 cursor-pointer" />
      </div>
      <div>
        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Sampai Tanggal</label>
        <Input bind:value={dateTo} type="date" class="bg-white border-slate-200 cursor-pointer" />
      </div>
      <div>
        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">COA</label>
        <Input type="text" placeholder="Nomor COA..." class="bg-white border-slate-200" />
      </div>
    </svelte:fragment>
  </FilterPanel>

  <!-- Table Card -->
  <TableCard {pagination} {perPage} {hasActiveFilter} statsTotal={stats.total} onChangePerPage={changePerPage} onGoToPage={goToPage}>
    <svelte:fragment slot="toolbar-actions">
      <ColumnToggle bind:columns={columns} />
    </svelte:fragment>
        <Table.Header class="bg-slate-50/60">
          <Table.Row class="hover:bg-transparent border-b border-slate-100">
            <!-- Fixed: expand, checkbox, aksi -->
            <Table.Head class="w-8"></Table.Head>
            <Table.Head class="w-8"><input type="checkbox" on:change={toggleAll} class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 cursor-pointer" /></Table.Head>
            <Table.Head class="w-8"></Table.Head>
            <!-- Dynamic columns -->
            {#if colVisible['no']}           <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">No</Table.Head>{/if}
            {#if colVisible['number']}       <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Number</Table.Head>{/if}
            {#if colVisible['orderNumber']}  <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider w-[280px]">Order Num</Table.Head>{/if}
            {#if colVisible['coa']}          <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">COA</Table.Head>{/if}
            {#if colVisible['company']}      <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Perusahaan</Table.Head>{/if}
            {#if colVisible['customer']}     <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider whitespace-nowrap">Customer</Table.Head>{/if}
            {#if colVisible['amount']}       <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Amount</Table.Head>{/if}
            {#if colVisible['namaKapal']}    <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Kapal</Table.Head>{/if}
            {#if colVisible['tglKapal']}     <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider whitespace-nowrap">Tgl Kapal</Table.Head>{/if}
            {#if colVisible['invDate']}      <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider whitespace-nowrap">Inv Date</Table.Head>{/if}
            {#if colVisible['dueDate']}      <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider whitespace-nowrap">Due Date</Table.Head>{/if}
            {#if colVisible['dokKirim']}     <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider whitespace-nowrap">Dok Kirim</Table.Head>{/if}
            {#if colVisible['titipInt']}     <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider whitespace-nowrap">Titip Int.</Table.Head>{/if}
            {#if colVisible['bpb']}          <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">BPB</Table.Head>{/if}
            {#if colVisible['status']}       <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Status</Table.Head>{/if}
            {#if colVisible['statusPayment']}<Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider whitespace-nowrap">Stat. Payment</Table.Head>{/if}
            {#if colVisible['statCr']}       <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider whitespace-nowrap">Stat Cr.</Table.Head>{/if}
            {#if colVisible['faktur']}       <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Faktur</Table.Head>{/if}
            {#if colVisible['notes']}        <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Notes</Table.Head>{/if}
          </Table.Row>
        </Table.Header>
        <Table.Body>
          {#each invoices as inv (inv.id)}
            {@const isExpanded = expandedRows.includes(inv.id)}
            {@const statusKey  = inv.status?.toLowerCase()}

            <!-- ===== MAIN ROW ===== -->
            <Table.Row class={cn(
              "transition-colors duration-150 border-b border-slate-50",
              isExpanded ? "bg-teal-50/20" : "",
              selectedRows.includes(inv.id) && "bg-teal-50/40 hover:bg-teal-50/60"
            )}>

              <!-- Expand toggle -->
              <Table.Cell class="py-2 text-center w-8">
                <button
                  type="button"
                  on:click={() => toggleRowExpansion(inv.id)}
                  class="p-1 rounded-md text-slate-400 hover:text-teal-700 hover:bg-slate-100 transition-colors cursor-pointer"
                  title={isExpanded ? 'Sembunyikan' : 'Detail Dokumen & Jurnal'}
                >
                  {#if isExpanded}
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                  {:else}
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                  {/if}
                </button>
              </Table.Cell>

              <!-- Checkbox -->
              <Table.Cell class="w-8">
                <input type="checkbox" checked={selectedRows.includes(inv.id)} on:change={() => toggleRow(inv.id)} class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 cursor-pointer" />
              </Table.Cell>

              <!-- Aksi (Post, Edit & Void) -->
              <Table.Cell class="w-24 whitespace-nowrap">
                <div class="flex items-center gap-1">
                  {#if inv.status !== 'void'}
                    {#if inv.status === 'draft'}
                      <Button variant="ghost" size="icon" class="h-6 w-6 text-teal-500 hover:text-teal-700 hover:bg-teal-50 rounded-md {inv.is_locked ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}" on:click={() => { if(inv.is_locked) { showToast('Tidak dapat memproses: Periode Akuntansi telah dikunci.', 'error'); } else { postInvoice(inv.id); } }} title={inv.is_locked ? "Periode Terkunci" : "Post Invoice ke Jurnal"}>
                        <SendHorizonal class="h-3.5 w-3.5" />
                      </Button>
                    {:else if inv.status === 'posted'}
                      <Button variant="ghost" size="icon" class="h-6 w-6 text-orange-500 hover:text-orange-700 hover:bg-orange-50 rounded-md {inv.statusPayment !== 'unpaid' || inv.is_locked ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}" on:click={() => { if(inv.is_locked) { showToast('Tidak dapat memproses: Periode Akuntansi telah dikunci.', 'error'); } else if(inv.statusPayment !== 'unpaid') { showToast('Tidak bisa Unpost: Terdapat pembayaran aktif. Harap batalkan pembayaran (di menu Penerimaan) terlebih dahulu.', 'error'); } else { unpostInvoice(inv.id); } }} title={inv.is_locked ? "Periode Terkunci" : (inv.statusPayment !== 'unpaid' ? "Terdapat pembayaran aktif" : "Unpost Invoice")}>
                        <RefreshCw class="h-3.5 w-3.5" />
                      </Button>
                    {/if}
                    <Button variant="ghost" size="icon" class="h-6 w-6 text-slate-400 hover:text-teal-700 rounded-md {inv.statusPayment !== 'unpaid' || inv.is_locked ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}" on:click={() => { if(inv.is_locked) { showToast('Tidak dapat memproses: Periode Akuntansi telah dikunci.', 'error'); } else if(inv.statusPayment !== 'unpaid') { showToast('Tidak bisa Edit: Terdapat pembayaran aktif. Harap batalkan pembayaran terlebih dahulu.', 'error'); } else { editInvoice(inv.id); } }} title={inv.is_locked ? "Periode Terkunci" : (inv.statusPayment !== 'unpaid' ? "Terdapat pembayaran aktif" : "Edit Invoice")}>
                      <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </Button>
                    <Button variant="ghost" size="icon" class="h-6 w-6 text-slate-400 hover:text-red-600 rounded-md {inv.statusPayment !== 'unpaid' || inv.is_locked ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}" on:click={() => { if(inv.is_locked) { showToast('Tidak dapat memproses: Periode Akuntansi telah dikunci.', 'error'); } else if(inv.statusPayment !== 'unpaid') { showToast('Tidak bisa Void: Terdapat pembayaran aktif. Harap batalkan pembayaran terlebih dahulu.', 'error'); } else { voidInvoice(inv.id); } }} title={inv.is_locked ? "Periode Terkunci" : (inv.statusPayment !== 'unpaid' ? "Terdapat pembayaran aktif" : "Void Invoice")}>
                      <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </Button>
                  {:else}
                    <span class="text-[10px] font-bold text-red-500 uppercase">VOIDED</span>
                  {/if}
                  
                  {#if inv.is_locked}
                    <div class="ml-1 flex items-center gap-1 text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200" title="Periode sudah terkunci">
                      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                      <span class="text-[9px] font-bold uppercase">Locked</span>
                    </div>
                  {/if}
                </div>
              </Table.Cell>

              {#if colVisible['no']}
                <Table.Cell class="font-mono text-xs text-slate-400">{inv.no}</Table.Cell>
              {/if}

              {#if colVisible['number']}
                <Table.Cell>
                  <a href="#inv-{inv.number}" class="text-teal-600 hover:text-teal-700 hover:underline font-semibold whitespace-nowrap cursor-pointer">
                    {inv.invoiceText || inv.number}
                  </a>
                </Table.Cell>
              {/if}

              {#if colVisible['orderNumber']}
                <Table.Cell class="text-xs text-slate-600 whitespace-normal break-words w-[280px]">{inv.orderNumber || '-'}</Table.Cell>
              {/if}

              {#if colVisible['coa']}
                <Table.Cell class="text-center">
                  {#if inv.coa && inv.coa !== '-'}
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-mono font-medium bg-slate-100 text-slate-700 border border-slate-200/80">{inv.coa}</span>
                  {:else}
                    <span class="text-slate-300 text-xs">-</span>
                  {/if}
                </Table.Cell>
              {/if}

              {#if colVisible['company']}
                <Table.Cell>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200/60 whitespace-nowrap">
                    {inv.company_name}
                  </span>
                </Table.Cell>
              {/if}

              {#if colVisible['customer']}
                <Table.Cell class="text-xs font-medium text-slate-900 whitespace-nowrap" title={inv.customer}>{inv.customer}</Table.Cell>
              {/if}

              {#if colVisible['amount']}
                <Table.Cell class="font-semibold text-slate-900 whitespace-nowrap">{inv.amount}</Table.Cell>
              {/if}

              {#if colVisible['namaKapal']}
                <Table.Cell class="text-xs text-slate-600 whitespace-nowrap">{inv.namaKapal || '-'}</Table.Cell>
              {/if}

              {#if colVisible['tglKapal']}
                <Table.Cell class="text-xs text-slate-500 whitespace-nowrap">{inv.tglKapBerangkat}</Table.Cell>
              {/if}

              {#if colVisible['invDate']}
                <Table.Cell class="text-xs text-slate-500 whitespace-nowrap">{inv.invoiceDate}</Table.Cell>
              {/if}

              {#if colVisible['dueDate']}
                <Table.Cell class="text-xs text-slate-500 whitespace-nowrap">{inv.dueDate}</Table.Cell>
              {/if}

              {#if colVisible['dokKirim']}
                <Table.Cell class="whitespace-nowrap">
                  {#if inv.noDokumenKirim && inv.noDokumenKirim !== '-'}
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-200/60">
                      <span class="h-1 w-1 rounded-full bg-blue-500"></span>
                      {inv.noDokumenKirim}
                    </span>
                  {:else}
                    <span class="text-slate-300 text-xs">-</span>
                  {/if}
                </Table.Cell>
              {/if}

              {#if colVisible['titipInt']}
                <Table.Cell class="whitespace-nowrap">
                  {#if inv.noTitipInternal && inv.noTitipInternal !== '-'}
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-mono font-medium bg-violet-50 text-violet-700 border border-violet-200/60">
                      <span class="h-1 w-1 rounded-full bg-violet-500"></span>
                      {inv.noTitipInternal}
                    </span>
                  {:else}
                    <span class="text-slate-300 text-xs">-</span>
                  {/if}
                </Table.Cell>
              {/if}

              {#if colVisible['bpb']}
                <Table.Cell>
                  {#if inv.bpb === 'Ya'}
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                      <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                      BPB
                    </span>
                  {:else}
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-50 text-slate-400 border border-slate-200/50">Tidak</span>
                  {/if}
                </Table.Cell>
              {/if}

              {#if colVisible['status']}
                <Table.Cell>
                  <span class={cn("px-2.5 py-0.5 rounded-full text-[11px] font-semibold border whitespace-nowrap", statusColor[statusKey] || 'bg-slate-50 text-slate-600 border-slate-200/60')}>
                    {inv.status}
                  </span>
                </Table.Cell>
              {/if}

              <!-- STATUS PAYMENT -->
              {#if colVisible['statusPayment']}
                <Table.Cell>
                  {#if inv.statusPayment === 'paid'}
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border bg-emerald-100 text-emerald-800 border-emerald-200 whitespace-nowrap">
                      <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                      Lunas
                    </span>
                  {:else if inv.statusPayment === 'partial'}
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border bg-amber-50 text-amber-700 border-amber-200 whitespace-nowrap">
                      Partial
                    </span>
                  {:else}
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border bg-slate-100 text-slate-500 border-slate-200 whitespace-nowrap">
                      Belum
                    </span>
                  {/if}
                </Table.Cell>
              {/if}

              {#if colVisible['statCr']}
                <Table.Cell class="text-xs text-slate-500">{inv.statusCreated || '-'}</Table.Cell>
              {/if}

              {#if colVisible['faktur']}
                <Table.Cell class="whitespace-nowrap">
                  {#if inv.faktur && inv.faktur !== '-'}
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-mono font-medium bg-amber-50 text-amber-800 border border-amber-200/60">
                      <svg class="h-3 w-3 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                      {inv.faktur}
                    </span>
                  {:else}
                    <span class="text-slate-300 font-mono text-[11px]">-</span>
                  {/if}
                </Table.Cell>
              {/if}

              {#if colVisible['notes']}
                <Table.Cell class="text-xs text-slate-500 max-w-[150px] truncate" title={inv.notes}>{inv.notes || '-'}</Table.Cell>
              {/if}
            </Table.Row>

            <!-- ===== EXPANDED / COLLAPSE ROW ===== -->
            {#if isExpanded}
              <Table.Row class="bg-slate-50/20 hover:bg-slate-50/20">
                <Table.Cell colspan="25" class="p-0">
                  <div class="mx-4 my-3 space-y-4">

                    <!-- ── SUMMARY CARDS ── -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                      <!-- Total Items -->
                      <div class="bg-white border border-sky-100 rounded-xl px-4 py-3 shadow-sm flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg bg-sky-50 flex items-center justify-center shrink-0">
                          <svg class="h-4 w-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <div>
                          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Jumlah Item</p>
                          <p class="text-lg font-extrabold text-slate-800">{inv.items?.length ?? 0}</p>
                        </div>
                      </div>
                      <!-- Grand Total Amount -->
                      <div class="bg-white border border-teal-100 rounded-xl px-4 py-3 shadow-sm flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg bg-teal-50 flex items-center justify-center shrink-0">
                          <svg class="h-4 w-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <div class="min-w-0">
                          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Amount</p>
                          <p class="text-sm font-extrabold text-teal-700 truncate">{inv.amount}</p>
                        </div>
                      </div>
                      <!-- Invoice Date -->
                      <div class="bg-white border border-indigo-100 rounded-xl px-4 py-3 shadow-sm flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0">
                          <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Invoice Date</p>
                          <p class="text-sm font-bold text-slate-700">{inv.invoiceDate}</p>
                        </div>
                      </div>
                      <!-- Payment Status -->
                      <div class="bg-white border border-amber-100 rounded-xl px-4 py-3 shadow-sm flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                          <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status</p>
                          <span class={cn(
                            "text-[11px] font-bold px-2 py-0.5 rounded-full border",
                            statusKey === 'paid'  ? 'bg-emerald-100 text-emerald-800 border-emerald-200' :
                            statusKey === 'sent'  ? 'bg-amber-50 text-amber-700 border-amber-200' :
                            'bg-slate-100 text-slate-600 border-slate-200'
                          )}>{inv.status}</span>
                        </div>
                      </div>
                    </div>

                    <!-- ── TABLE 0: Invoice Items ── -->
                    {#if inv.items && inv.items.length > 0}
                    <div class="bg-white border border-slate-150 rounded-xl shadow-sm overflow-hidden">
                      <div class="flex items-center gap-2 px-4 py-2.5 bg-sky-50/70 border-b border-sky-100/60">
                        <svg class="h-3.5 w-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <span class="text-[11px] font-bold text-sky-800 uppercase tracking-wider">Invoice Items</span>
                        <span class="ml-auto text-[10px] text-sky-500">{inv.items.length} items</span>
                      </div>
                      <table class="w-full text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase tracking-wider border-b border-slate-100">
                          <tr>
                            <th class="px-4 py-2 text-left w-12">No.</th>
                            <th class="px-4 py-2 text-left">Nama Item</th>
                            <th class="px-4 py-2 text-right w-24">Qty</th>
                            <th class="px-4 py-2 text-right w-40">Harga Satuan</th>
                            <th class="px-4 py-2 text-right w-40">Total</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 bg-white">
                          {#each inv.items as item, idx}
                            <tr class="hover:bg-sky-50/20 transition-colors">
                              <td class="px-4 py-2.5 text-slate-400 font-mono text-center">{idx + 1}</td>
                              <td class="px-4 py-2.5 font-medium text-slate-800">{item.name}</td>
                              <td class="px-4 py-2.5 text-right font-mono">{item.quantity}</td>
                              <td class="px-4 py-2.5 text-right font-mono">Rp {(parseFloat(item.price)||0).toLocaleString('id-ID')}</td>
                              <td class="px-4 py-2.5 text-right font-mono font-semibold">Rp {(parseFloat(item.total)||0).toLocaleString('id-ID')}</td>
                            </tr>
                          {/each}
                        </tbody>
                      </table>
                    </div>
                    {/if}

                  <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 items-start">
                    <!-- ── TABLE 1: List Dokumen ── -->
                    <div class="bg-white border border-slate-150 rounded-xl shadow-sm overflow-hidden">
                      <div class="flex items-center gap-2 px-4 py-2.5 bg-teal-50/70 border-b border-teal-100/60">
                        <svg class="h-3.5 w-3.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="text-[11px] font-bold text-teal-800 uppercase tracking-wider">List Dokumen — Invoice {inv.invoiceText || inv.number}</span>
                        <span class="ml-auto text-[10px] text-teal-500">{inv.documents ? inv.documents.length : 0} dokumen</span>
                      </div>
                      <table class="w-full text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase tracking-wider border-b border-slate-100">
                          <tr>
                            <th class="px-4 py-2 text-left w-8">No.</th>
                            <th class="px-4 py-2 text-left">Nama Dokumen</th>
                            <th class="px-4 py-2 text-left w-36">Tanggal</th>
                            <th class="px-4 py-2 text-left w-28">No. Titip Int.</th>
                            <th class="px-4 py-2 text-left w-24">Status</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 bg-white">
                          {#if inv.documents && inv.documents.length > 0}
                            {#each inv.documents as dok, idx}
                              <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-4 py-2.5 text-slate-400 font-mono">{idx + 1}</td>
                                <td class="px-4 py-2.5 font-medium text-slate-800">
                                  <div class="flex items-center gap-2">
                                    <svg class="h-3.5 w-3.5 text-teal-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    {dok.nama}
                                  </div>
                                </td>
                                <td class="px-4 py-2.5 text-slate-500">{dok.tgl}</td>
                                <td class="px-4 py-2.5">
                                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-mono font-semibold bg-violet-50 text-violet-700 border border-violet-200/60">
                                    <span class="h-1 w-1 rounded-full bg-violet-500"></span>
                                    {dok.noTitip}
                                  </span>
                                </td>
                                <td class="px-4 py-2.5">
                                  <span class={cn(
                                    "inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border",
                                    dok.status === 'Terkirim' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-600 border-slate-200'
                                  )}>
                                    {#if dok.status === 'Terkirim'}
                                      <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    {/if}
                                    {dok.status}
                                  </span>
                                </td>
                              </tr>
                            {/each}
                          {:else}
                            <tr>
                              <td colspan="5" class="px-4 py-6 text-center text-slate-400 italic">Belum ada dokumen yang terhubung.</td>
                            </tr>
                          {/if}
                        </tbody>
                      </table>
                    </div>

                    <!-- ── TABLE 2: Jurnal Pembayaran ── -->
                    <div class="bg-white border border-slate-150 rounded-xl shadow-sm overflow-hidden">
                      <div class="flex items-center gap-2 px-4 py-2.5 bg-indigo-50/70 border-b border-indigo-100/60">
                        <!-- receipt/payment icon -->
                        <svg class="h-3.5 w-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span class="text-[11px] font-bold text-indigo-800 uppercase tracking-wider">Jurnal Pembayaran</span>
                        <span class="ml-auto">
                          {#if inv.statusPayment === 'paid'}
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                              <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                              Lunas
                            </span>
                          {:else if inv.statusPayment === 'partial'}
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                              <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                              Dibayar Sebagian (Partial)
                            </span>
                          {:else}
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                              Belum Lunas
                            </span>
                          {/if}
                        </span>
                      </div>
                      <table class="w-full text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase tracking-wider border-b border-slate-100">
                          <tr>
                            <th class="px-4 py-2 text-left w-8">No.</th>
                            <th class="px-4 py-2 text-left w-36">No. Jurnal</th>
                            <th class="px-4 py-2 text-left w-36">Tanggal</th>
                            <th class="px-4 py-2 text-right w-40">Total</th>
                            <th class="px-4 py-2 text-left">Keterangan</th>
                            <th class="px-4 py-2 text-left w-24">Status</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 bg-white">
                          {#if inv.payments && inv.payments.length > 0}
                            {#each inv.payments as jrn, idx}
                              <tr class="hover:indigo-50/20 transition-colors">
                                <td class="px-4 py-2.5 text-slate-400 font-mono">{idx + 1}</td>
                                <td class="px-4 py-2.5 font-mono font-semibold text-indigo-700">
                                  <div class="flex items-center gap-1.5">
                                    <svg class="h-3 w-3 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                    {jrn.noJurnal}
                                  </div>
                                </td>
                                <td class="px-4 py-2.5 text-slate-500">{jrn.tgl}</td>
                                <td class="px-4 py-2.5 text-right font-semibold text-slate-800 font-mono">Rp {(parseFloat(jrn.total)||0).toLocaleString('id-ID')}</td>
                                <td class="px-4 py-2.5 text-slate-600">{jrn.keterangan}</td>
                                <td class="px-4 py-2.5">
                                  {#if jrn.status === 'paid'}
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                      <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                      Lunas
                                    </span>
                                  {:else if jrn.status === 'void'}
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">
                                      Batal / Void
                                    </span>
                                  {:else}
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                      {jrn.status}
                                    </span>
                                  {/if}
                                </td>
                              </tr>
                            {/each}
                          {:else}
                            <tr>
                              <td colspan="6" class="px-4 py-6 text-center text-slate-400 italic">Belum ada riwayat pembayaran.</td>
                            </tr>
                          {/if}
                        </tbody>
                        <tfoot class="bg-indigo-50/40 border-t border-indigo-100 font-bold text-xs">
                          {#if true}
                            {@const totalPaid = (inv.payments || []).reduce((s, j) => s + (j.status === 'void' ? 0 : (parseFloat(j.total)||0)), 0)}
                            {@const remaining = (inv.amount_raw || 0) - totalPaid}
                            <tr>
                              <td colspan="3" class="px-4 py-2 text-slate-500 uppercase text-[10px] tracking-wider">Total Pembayaran</td>
                              <td class="px-4 py-2 text-right font-mono text-indigo-800">
                                Rp {totalPaid.toLocaleString('id-ID')}
                              </td>
                              <td colspan="2" class="px-4 py-2"></td>
                            </tr>
                            <tr class="border-t border-indigo-100">
                              <td colspan="3" class="px-4 py-2 text-slate-500 uppercase text-[10px] tracking-wider">Sisa Pembayaran</td>
                              <td class="px-4 py-2 text-right font-mono text-rose-600">
                                Rp {remaining.toLocaleString('id-ID')}
                              </td>
                              <td colspan="2" class="px-4 py-2"></td>
                            </tr>
                          {/if}
                        </tfoot>
                      </table>
                    </div>

                  </div>
                </Table.Cell>
              </Table.Row>
            {/if}
          {/each}

          {#if invoices.length === 0}
            <Table.Row class="hover:bg-transparent">
              <Table.Cell colspan="25" class="text-center py-12 text-slate-400 text-sm">
                Tidak ada data yang sesuai filter
              </Table.Cell>
            </Table.Row>
          {/if}
        </Table.Body>
  </TableCard>

</AppLayout>
