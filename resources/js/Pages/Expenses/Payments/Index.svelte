<script>
  import AppLayout from '../../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Card from '$lib/components/ui/card';
  import * as Table from '$lib/components/ui/table';
  import { Search, Eye, Plus, CreditCard, ArrowLeft, ArrowRight } from 'lucide-svelte';
  import { showToast } from '../../../Stores/toast.js';

  export let payments = [];
  export let pagination = { total: 0, perPage: 10, currentPage: 1, lastPage: 1 };
  export let filters = { search: '', per_page: 10 };
  export let companies = [];
  export let active_company_id = null;

  let search = filters.search || '';
  let perPage = filters.per_page || 10;

  function applyFilter() {
    router.get('/payments', { search, per_page: perPage, page: 1 }, { preserveState: true, replace: true });
  }

  function goToPage(page) {
    if (page < 1 || page > pagination.lastPage) return;
    router.get('/payments', { search, per_page: perPage, page }, { preserveState: true, replace: true });
  }

  const methodLabel = { cash: 'Tunai', transfer: 'Transfer', giro: 'Giro', cheque: 'Cek' };
  const methodColor = {
    cash: 'bg-amber-50 text-amber-700',
    transfer: 'bg-blue-50 text-blue-700',
    giro: 'bg-purple-50 text-purple-700',
    cheque: 'bg-slate-50 text-slate-600',
  };
</script>

<AppLayout title="Daftar Pembayaran">
  <div class="max-w-6xl mx-auto px-4 py-8 space-y-6">

    <!-- Header -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
          <CreditCard class="h-6 w-6 text-teal-600" /> Pembayaran
        </h1>
        <p class="text-sm text-slate-500 mt-1">{pagination.total} bukti penerimaan kas</p>
      </div>
      <Button class="w-full md:w-auto bg-teal-600 hover:bg-teal-700 text-white flex items-center gap-2" on:click={() => router.visit('/payments/create')}>
        <Plus class="h-4 w-4" /> Catat Pembayaran
      </Button>
    </div>

    <!-- Filter Bar -->
    <div class="flex items-center gap-3">
      <div class="relative flex-1 max-w-sm">
        <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
        <Input bind:value={search} placeholder="Cari nomor / customer..." class="pl-9 h-9" on:keydown={(e) => e.key === 'Enter' && applyFilter()} />
      </div>
      <Button variant="outline" class="h-9 text-sm" on:click={applyFilter}>Cari</Button>
      <select bind:value={perPage} on:change={applyFilter} class="h-9 px-3 rounded-md border border-slate-200 text-sm">
        {#each [10,25,50] as n}<option value={n}>{n} / halaman</option>{/each}
      </select>
    </div>

    <!-- Table -->
    <Card.Root class="shadow-sm border border-slate-200/60 overflow-hidden">
      <Table.Root>
        <Table.Header class="bg-slate-50">
          <Table.Row>
            <Table.Head class="text-xs font-bold uppercase text-slate-500">No. Kwitansi</Table.Head>
            <Table.Head class="text-xs font-bold uppercase text-slate-500">Customer</Table.Head>
            <Table.Head class="text-xs font-bold uppercase text-slate-500">Tgl Bayar</Table.Head>
            <Table.Head class="text-xs font-bold uppercase text-slate-500 text-right">Jumlah</Table.Head>
            <Table.Head class="text-xs font-bold uppercase text-slate-500">Metode</Table.Head>
            <Table.Head class="text-xs font-bold uppercase text-slate-500">Bank / Kas</Table.Head>
            <Table.Head class="text-xs font-bold uppercase text-slate-500">Referensi</Table.Head>
            <Table.Head class="w-16"></Table.Head>
          </Table.Row>
        </Table.Header>
        <Table.Body>
          {#each payments as p}
            <Table.Row class="hover:bg-slate-50/50">
              <Table.Cell class="font-mono text-sm font-semibold text-teal-700">{p.payment_number}</Table.Cell>
              <Table.Cell class="font-medium text-slate-800">{p.customer_name}</Table.Cell>
              <Table.Cell class="text-slate-600 text-sm">{p.paid_at}</Table.Cell>
              <Table.Cell class="text-right font-semibold text-slate-800">
                Rp {Number(p.total_amount).toLocaleString('id-ID')}
              </Table.Cell>
              <Table.Cell>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium {methodColor[p.payment_method] || ''}">
                  {methodLabel[p.payment_method] || p.payment_method}
                </span>
              </Table.Cell>
              <Table.Cell class="text-slate-500 text-sm">{p.bank_name}</Table.Cell>
              <Table.Cell class="text-slate-400 text-xs font-mono">{p.reference || '-'}</Table.Cell>
              <Table.Cell>
                <button class="p-1.5 rounded-md text-slate-400 hover:text-teal-600 hover:bg-teal-50 transition" on:click={() => router.visit(`/payments/${p.id}`)} title="Lihat Detail">
                  <Eye class="h-4 w-4" />
                </button>
              </Table.Cell>
            </Table.Row>
          {:else}
            <Table.Row>
              <Table.Cell colspan={8} class="text-center py-12 text-slate-400">
                Belum ada pembayaran. Klik "Catat Pembayaran" untuk mulai.
              </Table.Cell>
            </Table.Row>
          {/each}
        </Table.Body>
      </Table.Root>
    </Card.Root>

    <!-- Pagination -->
    {#if pagination.lastPage > 1}
      <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500">
          Halaman {pagination.currentPage} dari {pagination.lastPage} ({pagination.total} total)
        </p>
        <div class="flex gap-2">
          <Button variant="outline" size="sm" disabled={pagination.currentPage <= 1} on:click={() => goToPage(pagination.currentPage - 1)}>
            <ArrowLeft class="h-4 w-4" />
          </Button>
          <Button variant="outline" size="sm" disabled={pagination.currentPage >= pagination.lastPage} on:click={() => goToPage(pagination.currentPage + 1)}>
            <ArrowRight class="h-4 w-4" />
          </Button>
        </div>
      </div>
    {/if}
  </div>
</AppLayout>
