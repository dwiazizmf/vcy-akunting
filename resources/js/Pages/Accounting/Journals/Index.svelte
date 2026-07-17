<script>
  import AppLayout from '../../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Table from '$lib/components/ui/table';
  import { Plus, Search, FileText, CheckCircle, XCircle, ArrowRight } from 'lucide-svelte';
  import { showToast } from '../../../Stores/toast.js';
  import { showConfirm } from '../../../Stores/confirmStore.js';
  
  import FilterPanel from '../../../Components/FilterPanel.svelte';
  import TableCard from '../../../Components/TableCard.svelte';
  import ColumnToggle from '../../../Components/ColumnToggle.svelte';
  import EmptyState from '../../../Components/EmptyState.svelte';

  export let journals = { data: [], current_page: 1, last_page: 1, total: 0, per_page: 10, from: 0, to: 0 };
  export let filters = { search: '', status: '', per_page: 10 };

  let search = filters.search || '';
  let status = filters.status || '';
  let perPage = filters.per_page || 10;

  // ================================================
  // DEFINISI KOLOM 
  // ================================================
  let columns = [
      { key: 'no',          label: 'No. Jurnal',      visible: true },
      { key: 'date',        label: 'Tanggal',         visible: true },
      { key: 'company',     label: 'Perusahaan',      visible: true },
      { key: 'description', label: 'Keterangan',      visible: true },
      { key: 'total',       label: 'Total Transaksi', visible: true },
      { key: 'status',      label: 'Status',          visible: true },
  ];

  $: colVisible = Object.fromEntries(columns.map(c => [c.key, c.visible]));

  function applyFilters() {
    router.get('/journals', { search, status, per_page: perPage, page: 1 }, {
      preserveState: true,
      replace: true
    });
  }

  function resetFilter() {
    search = ''; status = '';
    applyFilters();
  }

  function changePerPage(e) {
    perPage = parseInt(e.target.value);
    applyFilters();
  }

  function goToPage(page) {
    if (page === journals.current_page) return;
    router.get('/journals', { search, status, per_page: perPage, page }, {
      preserveState: true,
      replace: true
    });
  }

  async function updateStatus(id, newStatus) {
    if (await showConfirm(`Apakah Anda yakin ingin menandai jurnal ini sebagai ${newStatus.toUpperCase()}?`)) {
      router.patch(`/journals/${id}/status`, { status: newStatus }, {
        preserveScroll: true,
        onSuccess: () => {
          showToast(`Jurnal berhasil di-${newStatus}`, 'success');
        },
        onError: () => {
          showToast(`Gagal mengupdate status jurnal`, 'error');
        }
      });
    }
  }

  function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  }

  $: hasActiveFilter = !!(search || status);
  $: paginationProps = { 
    total: journals.total || 0, 
    perPage: journals.per_page || 10, 
    currentPage: journals.current_page || 1, 
    lastPage: journals.last_page || 1, 
    from: journals.from || 0, 
    to: journals.to || 0 
  };
</script>

<svelte:head>
  <title>Jurnal Umum - VCY Accounting</title>
</svelte:head>

<AppLayout>
  <div class="p-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <div>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Jurnal Umum</h2>
        <p class="text-sm text-slate-500">Catat dan kelola transaksi manual</p>
      </div>
      <Button href="/journals/create" class="bg-teal-600 hover:bg-teal-700 text-white shadow-sm gap-2">
        <Plus size={16} /> Buat Jurnal
      </Button>
    </div>

    <!-- Filter Panel -->
    <FilterPanel {hasActiveFilter} onApply={applyFilters} onReset={resetFilter}>
      <svelte:fragment slot="inputs">
        <div>
          <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Pencarian</label>
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={14} />
            <Input type="text" placeholder="No. jurnal, deskripsi..." bind:value={search} on:keydown={(e) => e.key === 'Enter' && applyFilters()} class="pl-9 bg-white border-slate-200" />
          </div>
        </div>
        <div>
          <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Status</label>
          <select bind:value={status} on:change={applyFilters} class="flex h-9 w-full rounded-md border border-slate-200 bg-white px-3 py-1 text-sm shadow-inner outline-none focus:border-teal-500 cursor-pointer">
            <option value="">Semua Status</option>
            <option value="draft">Draft</option>
            <option value="posted">Posted</option>
            <option value="void">Void</option>
          </select>
        </div>
      </svelte:fragment>
    </FilterPanel>

    <!-- Table -->
    <TableCard pagination={paginationProps} {perPage} {hasActiveFilter} statsTotal={journals.total} onChangePerPage={changePerPage} onGoToPage={goToPage}>
      <svelte:fragment slot="toolbar-actions">
          <ColumnToggle bind:columns={columns} />
      </svelte:fragment>

      <Table.Header class="bg-slate-50/60">
        <Table.Row class="hover:bg-transparent border-b border-slate-100">
          {#if colVisible['no']}          <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">No. Jurnal</Table.Head>{/if}
          {#if colVisible['date']}        <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Tanggal</Table.Head>{/if}
          {#if colVisible['company']}     <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Perusahaan</Table.Head>{/if}
          {#if colVisible['description']} <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">Keterangan</Table.Head>{/if}
          {#if colVisible['total']}       <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider text-right">Total Transaksi</Table.Head>{/if}
          {#if colVisible['status']}      <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider text-center">Status</Table.Head>{/if}
          <Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider text-right">Aksi</Table.Head>
        </Table.Row>
      </Table.Header>
      <Table.Body>
        {#if journals.data.length === 0}
          <Table.Row>
            <Table.Cell colspan="7" class="p-0">
              <EmptyState title="Tidak ada Jurnal" description="Belum ada data jurnal yang sesuai dengan kriteria pencarian Anda." />
            </Table.Cell>
          </Table.Row>
        {:else}
          {#each journals.data as journal}
            <Table.Row class="hover:bg-slate-50/50 transition-colors group cursor-pointer" on:click={() => router.get(`/journals/${journal.id}`)}>
              {#if colVisible['no']}
                <Table.Cell class="font-medium text-slate-900 py-3">
                  <div class="flex items-center gap-2">
                    <FileText size={14} class="text-slate-400" />
                    {journal.journal_number}
                  </div>
                </Table.Cell>
              {/if}
              {#if colVisible['date']}
                <Table.Cell class="text-slate-600 py-3 text-sm">
                  {new Date(journal.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}
                </Table.Cell>
              {/if}
              {#if colVisible['company']}
                <Table.Cell class="py-3">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200/60 whitespace-nowrap">
                    {journal.company_name}
                  </span>
                </Table.Cell>
              {/if}
              {#if colVisible['description']}
                <Table.Cell class="py-3">
                  <p class="text-sm text-slate-800 line-clamp-1">{journal.description}</p>
                  {#if journal.reference}
                    <p class="text-[10px] text-slate-500 mt-0.5 uppercase tracking-wider">Ref: {journal.reference}</p>
                  {/if}
                </Table.Cell>
              {/if}
              {#if colVisible['total']}
                <Table.Cell class="text-right font-medium text-slate-800 py-3">
                  {formatCurrency(journal.total_debit)}
                </Table.Cell>
              {/if}
              {#if colVisible['status']}
                <Table.Cell class="text-center py-3">
                  {#if journal.status === 'posted'}
                    <span class="inline-flex items-center px-2 py-1 rounded text-[11px] font-medium bg-teal-50 text-teal-700 border border-teal-200">
                      POSTED
                    </span>
                  {:else if journal.status === 'draft'}
                    <span class="inline-flex items-center px-2 py-1 rounded text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200">
                      DRAFT
                    </span>
                  {:else}
                    <span class="inline-flex items-center px-2 py-1 rounded text-[11px] font-medium bg-red-50 text-red-700 border border-red-200">
                      VOID
                    </span>
                  {/if}
                </Table.Cell>
              {/if}
              <Table.Cell class="text-right py-3">
                <div on:click|stopPropagation class="flex items-center justify-end gap-1 {journal.is_locked ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'} transition-opacity">
                  {#if journal.status === 'draft'}
                    <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500 hover:text-teal-600 {journal.is_locked ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}" disabled={journal.is_locked} title={journal.is_locked ? 'Periode Terkunci' : 'Post Journal'} on:click={() => { if(!journal.is_locked) updateStatus(journal.id, 'posted'); }}>
                      <CheckCircle size={16} />
                    </Button>
                  {/if}
                  {#if journal.status !== 'void'}
                    <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500 hover:text-red-600 {journal.is_locked ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}" disabled={journal.is_locked} title={journal.is_locked ? 'Periode Terkunci' : 'Void Journal'} on:click={() => { if(!journal.is_locked) updateStatus(journal.id, 'void'); }}>
                      <XCircle size={16} />
                    </Button>
                  {/if}
                  <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500 hover:text-blue-600 cursor-pointer" title="Detail" on:click={() => router.get(`/journals/${journal.id}`)}>
                    <ArrowRight size={16} />
                  </Button>
                  {#if journal.is_locked}
                    <div class="ml-1 flex items-center gap-1 text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200" title="Periode sudah terkunci">
                      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                  {/if}
                </div>
              </Table.Cell>
            </Table.Row>
          {/each}
        {/if}
      </Table.Body>
    </TableCard>
  </div>
</AppLayout>
