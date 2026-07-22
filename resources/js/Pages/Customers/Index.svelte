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
    Plus, Upload, Download, Search, MoreHorizontal,
    Eye, Pencil, Trash2, Users, CheckCircle2, XCircle, DollarSign
  } from 'lucide-svelte';
  
  import FilterPanel from '../../Components/FilterPanel.svelte';
  import TableCard from '../../Components/TableCard.svelte';
  import ColumnToggle from '../../Components/ColumnToggle.svelte';
  import EmptyState from '../../Components/EmptyState.svelte';
  import { showToast } from '../../Stores/toast.js';
  import { showConfirm } from '../../Stores/confirmStore.js';

  export let customers   = [];
  export let accounts    = [];
  export let pagination  = { total: 0, perPage: 25, currentPage: 1, lastPage: 1, from: 0, to: 0 };
  export let stats       = { total: 0, active: 0, inactive: 0, totalUnpaid: 0 };
  export let filters     = { search: '', status: '', per_page: 25 };

  let search       = filters.search  || '';
  let status       = filters.status  || '';
  let perPage      = Number(filters.per_page) || 25;

  let columns = [
    { key: 'name', label: 'Name', visible: true },
    { key: 'company', label: 'Perusahaan', visible: true },
    { key: 'address', label: 'Alamat', visible: true },
    { key: 'npwp', label: 'NPWP', visible: true },
    { key: 'reference', label: 'Referensi', visible: true },
    { key: 'unpaid', label: 'Unpaid', visible: true },
    { key: 'status', label: 'Status', visible: true },
  ];
  $: colVisible = Object.fromEntries(columns.map(c => [c.key, c.visible]));

  function applyFilter() {
    router.get('/customers', { search, status, per_page: perPage, page: 1 }, {
      preserveState: true, preserveScroll: true, replace: true
    });
  }

  function resetFilter() {
    search = ''; status = '';
    applyFilter();
  }

  $: hasActiveFilter = !!(search || status);

  function formatRp(val) {
    if (!val && val !== 0) return 'Rp0,00';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2 }).format(val);
  }

  function openCreateModal() {
    router.visit('/customers/create');
  }

  function openEditModal(customer) {
    router.visit(`/customers/${customer.id}/edit`);
  }

  async function deleteCustomer(id) {
    if (await showConfirm('Apakah Anda yakin ingin menghapus customer ini?')) {
      router.delete(`/customers/${id}`, {
        onSuccess: () => showToast('Customer berhasil dihapus', 'success'),
        onError: () => showToast('Gagal menghapus customer', 'error')
      });
    }
  }
</script>

<svelte:head>
  <title>Customers - VCY Accounting</title>
</svelte:head>

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
      <p class="text-sm text-slate-500 mt-1 ml-11.5">
        Kelola data pelanggan dan piutang.
      </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <Button
        size="sm"
        class="bg-teal-700 hover:bg-teal-800 text-white shadow-sm flex items-center gap-1.5 font-semibold"
        on:click={openCreateModal}
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

  <FilterPanel 
    hasActiveFilter={hasActiveFilter}
    onApply={applyFilter}
    onReset={resetFilter}
  >
    <svelte:fragment slot="inputs">
      <div class="col-span-1 sm:col-span-2 md:col-span-3 lg:col-span-4">
        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Cari (Nama/Perusahaan)</label>
        <div class="relative">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
          <Input 
            type="text" 
            placeholder="Cari customer..." 
            class="pl-9 h-9 w-full bg-white border-slate-200"
            bind:value={search}
            on:keydown={(e) => e.key === 'Enter' && applyFilter()}
          />
        </div>
      </div>
      <div class="col-span-1 sm:col-span-2 lg:col-span-2">
        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Status</label>
        <select bind:value={status} class="flex h-9 w-full rounded-md border border-slate-200 bg-white px-3 py-1 text-sm shadow-inner outline-none focus:border-teal-500 cursor-pointer">
          <option value="">Semua Status</option>
          <option value="active">Aktif</option>
          <option value="inactive">Nonaktif</option>
        </select>
      </div>
    </svelte:fragment>
  </FilterPanel>

  <TableCard 
    {pagination}
    {perPage}
    hasActiveFilter={hasActiveFilter}
    onChangePerPage={(e) => { perPage = Number(e.target.value); applyFilter(); }}
    onGoToPage={(p) => { router.get('/customers', { search, status, per_page: perPage, page: p }, { preserveState: true, replace: true }) }}
  >
    <div slot="toolbar-actions">
      <ColumnToggle bind:columns={columns} />
    </div>
    <Table.Header class="bg-slate-50/80 border-b border-slate-200">
      <Table.Row class="hover:bg-transparent">
        {#if colVisible.name}<Table.Head class="font-semibold text-slate-700">Name</Table.Head>{/if}
        {#if colVisible.company}<Table.Head class="font-semibold text-slate-700">Perusahaan</Table.Head>{/if}
        {#if colVisible.address}<Table.Head class="font-semibold text-slate-700 min-w-[200px]">Alamat</Table.Head>{/if}
        {#if colVisible.npwp}<Table.Head class="font-semibold text-slate-700">NPWP</Table.Head>{/if}
        {#if colVisible.reference}<Table.Head class="font-semibold text-slate-700">Referensi</Table.Head>{/if}
        {#if colVisible.unpaid}<Table.Head class="text-right font-semibold text-slate-700">Unpaid</Table.Head>{/if}
        {#if colVisible.status}<Table.Head class="text-center font-semibold text-slate-700">Status</Table.Head>{/if}
        <Table.Head class="text-right font-semibold text-slate-700">Aksi</Table.Head>
      </Table.Row>
    </Table.Header>
    <Table.Body>
      {#if customers.length === 0}
        <Table.Row>
          <Table.Cell colspan={Object.keys(colVisible).filter(k=>colVisible[k]).length + 1} class="p-0 border-b-0">
            <EmptyState 
              icon={Users}
              title="Tidak ada data customer"
              description="Belum ada customer terdaftar atau tidak cocok dengan filter Anda."
            />
          </Table.Cell>
        </Table.Row>
      {:else}
        {#each customers as cust (cust.id)}
          <Table.Row class="hover:bg-slate-50/60 transition-colors border-b border-slate-100">
            {#if colVisible.name}
              <Table.Cell class="py-3 font-semibold">
                <a href="/customers/{cust.id}" class="text-teal-600 hover:text-teal-700 hover:underline text-sm">
                  {cust.name}
                </a>
              </Table.Cell>
            {/if}
            {#if colVisible.company}
              <Table.Cell class="py-3 text-sm text-slate-600">
                {cust.company_name || '-'}
              </Table.Cell>
            {/if}
            {#if colVisible.address}
              <Table.Cell class="py-3 text-sm text-slate-600">
                {cust.address || '-'}
              </Table.Cell>
            {/if}
            {#if colVisible.npwp}
              <Table.Cell class="py-3 text-sm text-slate-600">
                {cust.npwp || '-'}
              </Table.Cell>
            {/if}
            {#if colVisible.reference}
              <Table.Cell class="py-3 text-sm text-slate-600">
                {cust.reference || '-'}
              </Table.Cell>
            {/if}
            {#if colVisible.unpaid}
              <Table.Cell class="py-3 text-right font-medium text-slate-700">
                {formatRp(cust.unpaid || 0)}
              </Table.Cell>
            {/if}
            {#if colVisible.status}
              <Table.Cell class="py-3 text-center">
                {#if cust.is_active}
                  <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-xs font-medium ring-1 ring-inset ring-emerald-600/20">
                    Aktif
                  </span>
                {:else}
                  <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-50 text-slate-600 text-xs font-medium ring-1 ring-inset ring-slate-500/20">
                    Nonaktif
                  </span>
                {/if}
              </Table.Cell>
            {/if}
            <Table.Cell class="py-3 text-right">
              <DropdownMenu.Root>
                <DropdownMenu.Trigger asChild let:builder>
                  <Button builders={[builder]} variant="ghost" size="icon" class="h-8 w-8 text-slate-400 hover:text-slate-700 rounded-md">
                    <MoreHorizontal class="h-4 w-4" />
                  </Button>
                </DropdownMenu.Trigger>
                <DropdownMenu.Content align="end" class="w-40 shadow-lg border-slate-100">
                  <DropdownMenu.Item class="text-xs gap-2 cursor-pointer" on:click={() => router.visit(`/customers/${cust.id}`)}>
                    <Eye class="h-3.5 w-3.5 text-slate-400" />
                    Detail
                  </DropdownMenu.Item>
                  <DropdownMenu.Item class="text-xs gap-2 cursor-pointer" on:click={() => openEditModal(cust)}>
                    <Pencil class="h-3.5 w-3.5 text-slate-400" />
                    Edit
                  </DropdownMenu.Item>
                  <DropdownMenu.Separator />
                  <DropdownMenu.Item class="text-xs gap-2 cursor-pointer text-red-600 focus:text-red-700 focus:bg-red-50" on:click={() => deleteCustomer(cust.id)}>
                    <Trash2 class="h-3.5 w-3.5" />
                    Hapus
                  </DropdownMenu.Item>
                </DropdownMenu.Content>
              </DropdownMenu.Root>
            </Table.Cell>
          </Table.Row>
        {/each}
      {/if}
    </Table.Body>
  </TableCard>
</AppLayout>
