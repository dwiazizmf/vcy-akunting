<script>
  import AppLayout from '../../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Table from '$lib/components/ui/table';
  import { Plus, Search, Eye, RefreshCw, CreditCard, ChevronDown, ChevronRight, Trash2 } from 'lucide-svelte';
  import { showConfirm } from '../../../Stores/confirmStore.js';
  import { showToast } from '../../../Stores/toast.js';

  import FilterPanel from '../../../Components/FilterPanel.svelte';
  import TableCard from '../../../Components/TableCard.svelte';
  import ColumnToggle from '../../../Components/ColumnToggle.svelte';
  import EmptyState from '../../../Components/EmptyState.svelte';

  export let payments = [];
  export let pagination = { total: 0, perPage: 10, currentPage: 1, lastPage: 1, from: 0, to: 0 };
  export let filters = { search: '', per_page: 10 };
  export let companies = [];
  export let active_company_id = null;

  let search = filters.search || '';
  let perPage = filters.per_page || 10;
  let expandedRows = [];

  function toggleRowExpansion(id) {
    expandedRows = expandedRows.includes(id)
      ? expandedRows.filter(rId => rId !== id)
      : [...expandedRows, id];
  }

  // ================================================
  // DEFINISI KOLOM 
  // ================================================
  let columns = [
      { key: 'payment_number', label: 'No. Kwitansi', visible: true },
      { key: 'company',        label: 'Perusahaan',   visible: true },
      { key: 'paid_at',        label: 'Tgl Bayar',    visible: true },
      { key: 'amount',         label: 'Jumlah',       visible: true },
      { key: 'method',         label: 'Metode',       visible: true },
      { key: 'bank',           label: 'Bank / Kas',   visible: true },
      { key: 'reference',      label: 'Referensi',    visible: false },
      { key: 'status',         label: 'Status',       visible: true },
  ];

  $: colVisible = Object.fromEntries(columns.map(c => [c.key, c.visible]));

  function applyFilter() {
    router.get('/payments', { search, per_page: perPage, page: 1 }, { preserveState: true, replace: true });
  }

  function resetFilter() {
    search = '';
    applyFilter();
  }

  function onChangePerPage(e) {
    perPage = parseInt(e.target.value);
    applyFilter();
  }

  function onGoToPage(page) {
    if (page < 1 || page > pagination.lastPage) return;
    router.get('/payments', { search, per_page: perPage, page }, { preserveState: true, preserveScroll: true, replace: true });
  }

  async function unpostPayment(id) {
    if (await showConfirm('Batalkan posting payment ini? Jurnal akuntansi yang terkait akan dihapus.')) {
      router.post(`/payments/${id}/unpost`, {}, {
        preserveScroll: true,
        onSuccess: () => showToast('Posting payment berhasil dibatalkan!', 'success'),
        onError: (e) => showToast(Object.values(e)[0] || 'Gagal unpost payment.', 'error'),
      });
    }
  }

  async function deletePayment(id, number) {
    if (await showConfirm(`Hapus pembayaran ${number} secara permanen? Data yang dihapus tidak bisa dikembalikan.`)) {
      router.delete(`/payments/${id}`, {
        preserveScroll: true,
        onSuccess: () => showToast('Pembayaran berhasil dihapus.', 'success'),
        onError: (e) => showToast(Object.values(e)[0] || 'Gagal menghapus payment.', 'error'),
      });
    }
  }

  const methodLabel = { cash: 'Tunai', transfer: 'Transfer', giro: 'Giro', cheque: 'Cek' };
  const methodColor = {
    cash: 'bg-amber-50 text-amber-700',
    transfer: 'bg-blue-50 text-blue-700',
    giro: 'bg-purple-50 text-purple-700',
    cheque: 'bg-slate-50 text-slate-600',
  };

  $: hasActiveFilter = !!(search);
</script>

<AppLayout title="Daftar Pembayaran">
  <div class="max-w-7xl mx-auto px-4 py-8 space-y-6">

    <!-- Header -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
          <CreditCard class="h-6 w-6 text-teal-600" /> Pembayaran
        </h1>
        <p class="text-sm text-slate-500 mt-1">{pagination.total} bukti penerimaan kas</p>
      </div>
      <Button class="w-full md:w-auto bg-teal-600 hover:bg-teal-700 text-white flex items-center gap-2 shadow-sm cursor-pointer" on:click={() => router.visit('/payments/create')}>
        <Plus class="h-4 w-4" /> Catat Pembayaran
      </Button>
    </div>

    <FilterPanel {hasActiveFilter} onApply={applyFilter} onReset={resetFilter}>
      <svelte:fragment slot="inputs">
        <div>
          <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Pencarian</label>
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={14} />
            <Input bind:value={search} on:keydown={(e) => e.key === 'Enter' && applyFilter()} placeholder="Cari nomor / customer..." class="pl-9 bg-white border-slate-200" />
          </div>
        </div>
      </svelte:fragment>
    </FilterPanel>

    <TableCard 
      {pagination}
      {perPage}
      {hasActiveFilter}
      {onChangePerPage}
      {onGoToPage}
      statsTotal={pagination.total}
    >
      <svelte:fragment slot="toolbar-actions">
        <ColumnToggle bind:columns />
      </svelte:fragment>

      <Table.Header class="bg-slate-50">
        <Table.Row>
          <Table.Head class="w-8"></Table.Head>
          {#if colVisible.payment_number}<Table.Head class="text-xs font-bold uppercase text-slate-500">No. Kwitansi</Table.Head>{/if}
          {#if colVisible.company}<Table.Head class="text-xs font-bold uppercase text-slate-500">Perusahaan</Table.Head>{/if}
          {#if colVisible.paid_at}<Table.Head class="text-xs font-bold uppercase text-slate-500">Tgl Bayar</Table.Head>{/if}
          {#if colVisible.amount}<Table.Head class="text-xs font-bold uppercase text-slate-500 text-right">Jumlah</Table.Head>{/if}
          {#if colVisible.method}<Table.Head class="text-xs font-bold uppercase text-slate-500">Metode</Table.Head>{/if}
          {#if colVisible.bank}<Table.Head class="text-xs font-bold uppercase text-slate-500">Bank / Kas</Table.Head>{/if}
          {#if colVisible.reference}<Table.Head class="text-xs font-bold uppercase text-slate-500">Referensi</Table.Head>{/if}
          {#if colVisible.status}<Table.Head class="text-xs font-bold uppercase text-slate-500">Status</Table.Head>{/if}
          <Table.Head class="w-24 text-right text-xs font-bold uppercase text-slate-500">Aksi</Table.Head>
        </Table.Row>
      </Table.Header>
      
      <Table.Body>
        {#if payments.length === 0}
          <Table.Row>
            <Table.Cell colspan={10} class="p-0 border-b-0">
              <EmptyState
                icon={CreditCard}
                title="Belum ada pembayaran"
                description={hasActiveFilter ? 'Tidak ada pembayaran yang cocok dengan filter pencarian Anda.' : 'Klik "Catat Pembayaran" untuk mulai mencatat penerimaan kas.'}
                actionLabel={hasActiveFilter ? 'Reset Filter' : 'Catat Pembayaran'}
                onAction={hasActiveFilter ? resetFilter : () => router.visit('/payments/create')}
              />
            </Table.Cell>
          </Table.Row>
        {:else}
          {#each payments as p}
            <Table.Row class="hover:bg-slate-50/50">
              <Table.Cell class="w-8 text-center px-2">
                <button 
                  on:click={() => toggleRowExpansion(p.id)} 
                  class="p-1 rounded hover:bg-slate-200/60 text-slate-400 hover:text-slate-600 transition"
                  title="Lihat Detail Invoice"
                >
                  {#if expandedRows.includes(p.id)}
                    <ChevronDown class="h-4 w-4 text-teal-600" />
                  {:else}
                    <ChevronRight class="h-4 w-4" />
                  {/if}
                </button>
              </Table.Cell>
              {#if colVisible.payment_number}
                <Table.Cell class="font-mono text-sm font-semibold text-teal-700">{p.payment_number}</Table.Cell>
              {/if}
              {#if colVisible.company}
                <Table.Cell>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200/60 whitespace-nowrap">
                    {p.company_name}
                  </span>
                </Table.Cell>
              {/if}
              {#if colVisible.paid_at}
                <Table.Cell class="text-slate-600 text-sm">{p.paid_at}</Table.Cell>
              {/if}
              {#if colVisible.amount}
                <Table.Cell class="text-right font-semibold text-slate-800">
                  Rp {Number(p.total_amount).toLocaleString('id-ID')}
                </Table.Cell>
              {/if}
              {#if colVisible.method}
                <Table.Cell>
                  <span class="px-2 py-0.5 rounded-full text-xs font-medium {methodColor[p.payment_method] || ''}">
                    {methodLabel[p.payment_method] || p.payment_method}
                  </span>
                </Table.Cell>
              {/if}
              {#if colVisible.bank}
                <Table.Cell class="text-slate-500 text-sm">{p.bank_name}</Table.Cell>
              {/if}
              {#if colVisible.reference}
                <Table.Cell class="text-slate-400 text-xs font-mono">{p.reference || '-'}</Table.Cell>
              {/if}
              {#if colVisible.status}
                <Table.Cell>
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium {p.status === 'posted' ? 'bg-teal-100 text-teal-800' : 'bg-slate-100 text-slate-800'}">
                    {(p.status || 'draft').toUpperCase()}
                  </span>
                </Table.Cell>
              {/if}
              <Table.Cell class="flex items-center gap-1 justify-end">
                {#if p.status === 'posted'}
                  <button class="p-1.5 rounded-md text-orange-400 hover:text-orange-600 hover:bg-orange-50 transition {p.is_locked ? 'opacity-50 cursor-not-allowed' : ''}" disabled={p.is_locked} on:click={() => { if(!p.is_locked) unpostPayment(p.id); }} title={p.is_locked ? 'Periode Terkunci' : 'Unpost Payment'}>
                    <RefreshCw class="h-4 w-4" />
                  </button>
                {/if}
                <button class="p-1.5 rounded-md text-slate-400 hover:text-red-600 hover:bg-red-50 transition {p.is_locked ? 'opacity-50 cursor-not-allowed' : ''}" disabled={p.is_locked} on:click={() => { if(!p.is_locked) deletePayment(p.id, p.payment_number); }} title={p.is_locked ? 'Periode Terkunci' : 'Hapus Payment'}>
                  <Trash2 class="h-4 w-4" />
                </button>
                <button class="p-1.5 rounded-md text-slate-400 hover:text-teal-600 hover:bg-teal-50 transition" on:click={() => router.visit(`/payments/${p.id}`)} title="Lihat Detail">
                  <Eye class="h-4 w-4" />
                </button>
                {#if p.is_locked}
                  <div class="ml-1 flex items-center gap-1 text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200" title="Periode sudah terkunci">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                  </div>
                {/if}
              </Table.Cell>
            </Table.Row>

            <!-- Expanded Detail Row -->
            {#if expandedRows.includes(p.id)}
              <Table.Row class="bg-slate-50/70 border-b border-slate-200">
                <Table.Cell colspan={10} class="p-4 bg-slate-50/50">
                  <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm w-full space-y-4">
                    <div class="text-[12px] font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2 border-b border-slate-100 pb-3">
                      <CreditCard class="h-4 w-4 text-teal-600" /> Rincian Transaksi ({p.payment_number})
                    </div>
                    
                    <div class="overflow-hidden border border-slate-100 rounded-lg">
                      <table class="w-full text-xs text-left text-slate-600">
                        <thead class="bg-slate-50 text-slate-500 font-semibold uppercase text-[10px]">
                          <tr>
                            <th class="py-2 px-4">Deskripsi</th>
                            <th class="py-2 px-4 text-center">Keterangan</th>
                            <th class="py-2 px-4 text-right">Penambahan</th>
                            <th class="py-2 px-4 text-right">Pengurangan</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                          <!-- Invoices -->
                          {#if p.paid_invoices && p.paid_invoices.length > 0}
                            {#each p.paid_invoices as inv}
                              <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-2.5 px-4">
                                  <div class="flex flex-col">
                                    <a href="/invoices/{inv.invoice_id}" class="font-medium text-teal-700 hover:underline">
                                      {inv.invoice_text}
                                    </a>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                      <span class="text-[10px] text-slate-500 font-medium">{inv.customer_name}</span>
                                      <span class="text-[10px] text-slate-400">• Pelunasan Invoice</span>
                                    </div>
                                  </div>
                                </td>
                                <td class="py-2.5 px-4 text-center text-slate-500">Tgl: {inv.invoice_date}</td>
                                <td class="py-2.5 px-4 text-right font-medium text-slate-800">
                                  Rp {Number(inv.amount).toLocaleString('id-ID')}
                                </td>
                                <td class="py-2.5 px-4 text-right text-slate-400">-</td>
                              </tr>
                            {/each}
                          {/if}

                          <!-- Adjustments -->
                          {#if p.adjustments && p.adjustments.length > 0}
                            {#each p.adjustments as adj}
                              <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-2.5 px-4">
                                  <div class="flex flex-col">
                                    <span class="font-medium text-slate-700">{adj.account_name}</span>
                                    <span class="text-[10px] text-slate-400">Penyesuaian COA</span>
                                  </div>
                                </td>
                                <td class="py-2.5 px-4 text-center text-slate-500 max-w-[150px] truncate" title={adj.description}>
                                  {adj.description || '-'}
                                </td>
                                {#if adj.type === 'addition'}
                                  <td class="py-2.5 px-4 text-right font-medium text-slate-800">
                                    Rp {Number(adj.amount).toLocaleString('id-ID')}
                                  </td>
                                  <td class="py-2.5 px-4 text-right text-slate-400">-</td>
                                {:else}
                                  <td class="py-2.5 px-4 text-right text-slate-400">-</td>
                                  <td class="py-2.5 px-4 text-right font-medium text-slate-800">
                                    Rp {Number(adj.amount).toLocaleString('id-ID')}
                                  </td>
                                {/if}
                              </tr>
                            {/each}
                          {/if}
                        </tbody>
                        <tfoot class="bg-slate-50 border-t border-slate-200">
                          <tr>
                            <td colspan="2" class="py-3 px-4 text-right font-bold text-slate-700 text-sm">TOTAL DIBAYARKAN</td>
                            <td colspan="2" class="py-3 px-4 text-right font-bold text-teal-700 text-sm">
                              Rp {Number(p.total_amount).toLocaleString('id-ID')}
                            </td>
                          </tr>
                        </tfoot>
                      </table>
                    </div>

                  </div>
                </Table.Cell>
              </Table.Row>
            {/if}
          {/each}
        {/if}
      </Table.Body>
    </TableCard>

  </div>
</AppLayout>
