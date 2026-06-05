<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Table from '$lib/components/ui/table';
  import {
    Plus, Search, FileText, CheckCircle, XCircle, ArrowRight
  } from 'lucide-svelte';
  import { showToast } from '../../Stores/toast.js';
  import { showConfirm } from '../../Stores/confirmStore.js';
  
  import Pagination from '../../Components/Pagination.svelte';

  export let journals = { data: [], links: [] };
  export let filters = { search: '', status: '' };

  let search = filters.search || '';
  let status = filters.status || '';

  let searchTimeout;
  function handleSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      applyFilters();
    }, 300);
  }

  function applyFilters() {
    router.get('/journals', { 
      search, 
      status 
    }, {
      preserveState: true,
      replace: true
    });
  }

  function goToPage(page) {
    if (page === journals.current_page) return;
    router.get('/journals', { 
      search, 
      status,
      page 
    }, {
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
</script>

<svelte:head>
  <title>Jurnal Umum - VCY Accounting</title>
</svelte:head>

<AppLayout>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Jurnal Umum</h2>
        <p class="text-sm text-slate-500">Catat dan kelola transaksi manual</p>
      </div>
      <Button href="/journals/create" class="bg-teal-600 hover:bg-teal-700 text-white gap-2">
        <Plus size={16} /> Buat Jurnal
      </Button>
    </div>

    <!-- Filter Panel -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row gap-4">
      <div class="relative w-full sm:w-72">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
        <Input 
          type="text" 
          placeholder="Cari no. jurnal, deskripsi..." 
          class="pl-9 h-10 w-full"
          bind:value={search}
          on:input={handleSearch}
        />
      </div>
      
      <div class="w-full sm:w-64">
        <select 
          class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
          bind:value={status}
          on:change={applyFilters}
        >
          <option value="">Semua Status</option>
          <option value="draft">Draft</option>
          <option value="posted">Posted</option>
          <option value="void">Void</option>
        </select>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <Table.Root>
          <Table.Header class="bg-slate-50 border-b border-slate-100">
            <Table.Row>
              <Table.Head class="w-[150px] font-semibold text-slate-700">No. Jurnal</Table.Head>
              <Table.Head class="w-[120px] font-semibold text-slate-700">Tanggal</Table.Head>
              <Table.Head class="font-semibold text-slate-700">Keterangan</Table.Head>
              <Table.Head class="w-[150px] text-right font-semibold text-slate-700">Total Transaksi</Table.Head>
              <Table.Head class="w-[100px] text-center font-semibold text-slate-700">Status</Table.Head>
              <Table.Head class="w-[100px] text-right font-semibold text-slate-700">Aksi</Table.Head>
            </Table.Row>
          </Table.Header>
          <Table.Body>
            {#each journals.data as journal}
              <Table.Row class="hover:bg-slate-50 transition-colors group cursor-pointer" on:click={() => router.get(`/journals/${journal.id}`)}>
                <Table.Cell class="font-medium text-slate-900 py-3">
                  <div class="flex items-center gap-2">
                    <FileText size={14} class="text-slate-400" />
                    {journal.journal_number}
                  </div>
                </Table.Cell>
                <Table.Cell class="text-slate-600 py-3">
                  {new Date(journal.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}
                </Table.Cell>
                <Table.Cell class="py-3">
                  <p class="text-slate-800 line-clamp-1">{journal.description}</p>
                  {#if journal.reference}
                    <p class="text-xs text-slate-500 mt-0.5">Ref: {journal.reference}</p>
                  {/if}
                </Table.Cell>
                <Table.Cell class="text-right font-medium text-slate-800 py-3">
                  {formatCurrency(journal.total_debit)}
                </Table.Cell>
                <Table.Cell class="text-center py-3">
                  {#if journal.status === 'posted'}
                    <span class="inline-flex items-center px-2 py-1 rounded-full bg-teal-50 text-teal-700 text-xs font-medium ring-1 ring-inset ring-teal-600/20">
                      Posted
                    </span>
                  {:else if journal.status === 'draft'}
                    <span class="inline-flex items-center px-2 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-medium ring-1 ring-inset ring-amber-500/20">
                      Draft
                    </span>
                  {:else}
                    <span class="inline-flex items-center px-2 py-1 rounded-full bg-red-50 text-red-700 text-xs font-medium ring-1 ring-inset ring-red-600/20">
                      Void
                    </span>
                  {/if}
                </Table.Cell>
                <Table.Cell class="text-right py-3">
                  <div on:click|stopPropagation class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    {#if journal.status === 'draft'}
                      <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500 hover:text-teal-600" title="Post Journal" on:click={() => updateStatus(journal.id, 'posted')}>
                        <CheckCircle size={16} />
                      </Button>
                    {/if}
                    {#if journal.status !== 'void'}
                      <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500 hover:text-red-600" title="Void Journal" on:click={() => updateStatus(journal.id, 'void')}>
                        <XCircle size={16} />
                      </Button>
                    {/if}
                    <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500 hover:text-blue-600" title="Detail" on:click={() => router.get(`/journals/${journal.id}`)}>
                      <ArrowRight size={16} />
                    </Button>
                  </div>
                </Table.Cell>
              </Table.Row>
            {:else}
              <Table.Row>
                <Table.Cell colspan={6} class="h-32 text-center text-slate-500">
                  Tidak ada data jurnal yang ditemukan.
                </Table.Cell>
              </Table.Row>
            {/each}
          </Table.Body>
        </Table.Root>
      </div>
      
      {#if journals.last_page > 1}
        <div class="border-t border-slate-100">
          <Pagination 
            pagination={{ currentPage: journals.current_page, lastPage: journals.last_page }}
            onGoToPage={(p) => goToPage(p)}
          />
        </div>
      {/if}
    </div>
  </div>
</AppLayout>
