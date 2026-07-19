<script>
  import AppLayout from '../../../Layouts/AppLayout.svelte';
  import { router, useForm } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Table from '$lib/components/ui/table';
  import { Plus, Search, Pencil, Trash2, Save, X } from 'lucide-svelte';
  import { showToast } from '../../../Stores/toast.js';
  import { showConfirm } from '../../../Stores/confirmStore.js';
  
  import FilterPanel from '../../../Components/FilterPanel.svelte';
  import TableCard from '../../../Components/TableCard.svelte';
  import ColumnToggle from '../../../Components/ColumnToggle.svelte';
  import EmptyState from '../../../Components/EmptyState.svelte';

  export let accounts = [];
  export let pagination = { total: 0, perPage: 25, currentPage: 1, lastPage: 1, from: 0, to: 0 };
  export let categories = [];
  export let types = [];
  export let parentAccounts = [];
  export let filters = { search: '', category: '', per_page: 25 };

  let search = filters.search || '';
  let category = filters.category || (categories.length > 0 ? categories[0] : 'Asset');
  let perPage = Number(filters.per_page) || 25;

  let isModalOpen = false;
  let isEditing = false;
  let editingId = null;

  const form = useForm({
    type_id: '',
    parent_id: '',
    code: '',
    name: '',
    description: '',
    enabled: true
  });

  // Columns definition
  let columns = [
    { key: 'code', label: 'Kode', visible: true },
    { key: 'name', label: 'Nama Akun', visible: true },
    { key: 'type', label: 'Tipe', visible: true },
    { key: 'parent', label: 'Induk', visible: true },
    { key: 'status', label: 'Status', visible: true },
  ];
  $: colVisible = Object.fromEntries(columns.map(c => [c.key, c.visible]));

  function applyFilter() {
    router.get('/accounts', { search, category, per_page: perPage, page: 1 }, { preserveState: true, replace: true });
  }

  function resetFilter() {
    search = '';
    applyFilter();
  }

  function selectCategory(cat) {
    category = cat;
    applyFilter();
  }

  function openCreateModal() {
    isEditing = false;
    editingId = null;
    $form.type_id = '';
    $form.parent_id = '';
    $form.code = '';
    $form.name = '';
    $form.description = '';
    $form.enabled = true;
    isModalOpen = true;
  }

  function openEditModal(account) {
    isEditing = true;
    editingId = account.id;
    $form.type_id = account.type_id;
    $form.parent_id = account.parent_id;
    $form.code = account.code;
    $form.name = account.name;
    $form.description = account.description;
    $form.enabled = account.enabled;
    isModalOpen = true;
  }

  function submitForm() {
    if (isEditing) {
      $form.put(`/accounts/${editingId}`, {
        onSuccess: () => {
          isModalOpen = false;
          showToast('Akun berhasil diperbarui', 'success');
        },
        onError: () => {
          showToast('Gagal memperbarui akun', 'error');
        }
      });
    } else {
      $form.post('/accounts', {
        onSuccess: () => {
          isModalOpen = false;
          showToast('Akun berhasil dibuat', 'success');
        },
        onError: () => {
          showToast('Gagal membuat akun', 'error');
        }
      });
    }
  }

  async function deleteAccount(id) {
    if (await showConfirm('Apakah Anda yakin ingin menghapus akun ini?')) {
      router.delete(`/accounts/${id}`, {
        onSuccess: () => {
          showToast('Akun berhasil dihapus', 'success');
        },
        onError: () => {
          showToast('Gagal menghapus akun', 'error');
        }
      });
    }
  }
</script>

<svelte:head>
  <title>Chart of Accounts - VCY Accounting</title>
</svelte:head>

<AppLayout>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Chart of Accounts</h2>
        <p class="text-sm text-slate-500">Kelola daftar akun akuntansi perusahaan</p>
      </div>
      <div class="flex items-center gap-3">
        <ColumnToggle bind:columns={columns} />
        <Button on:click={openCreateModal} class="bg-teal-600 hover:bg-teal-700 text-white gap-2">
          <Plus size={16} /> Tambah Akun
        </Button>
      </div>
    </div>

    <!-- Category Tabs -->
    <div class="border-b border-slate-200">
      <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
        {#each categories as cat}
          <button 
            on:click={() => selectCategory(cat)}
            class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors {category === cat ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'}"
          >
            {cat}
          </button>
        {/each}
      </nav>
    </div>

    <FilterPanel 
      hasActiveFilter={search !== ''}
      onApply={applyFilter}
      onReset={resetFilter}
    >
      <div slot="inputs" class="relative w-full sm:w-72">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
        <Input 
          type="text" 
          placeholder="Cari kode atau nama akun..." 
          class="pl-9 h-9 w-full bg-white border-slate-200"
          bind:value={search}
          on:keydown={(e) => e.key === 'Enter' && applyFilter()}
        />
      </div>
    </FilterPanel>

    <TableCard 
      {pagination}
      {perPage}
      hasActiveFilter={search !== ''}
      onChangePerPage={(e) => { perPage = e.target.value; applyFilter(); }}
      onGoToPage={(p) => { router.get('/accounts', { search, category, per_page: perPage, page: p }, { preserveState: true, replace: true }) }}
    >
      <Table.Header class="bg-slate-50/80 border-b border-slate-200">
        <Table.Row class="hover:bg-transparent">
              {#if colVisible.code}<Table.Head class="w-[150px] font-semibold text-slate-700">Kode</Table.Head>{/if}
              {#if colVisible.name}<Table.Head class="font-semibold text-slate-700">Nama Akun</Table.Head>{/if}
              {#if colVisible.type}<Table.Head class="w-[200px] font-semibold text-slate-700">Tipe</Table.Head>{/if}
              {#if colVisible.parent}<Table.Head class="w-[200px] font-semibold text-slate-700">Induk</Table.Head>{/if}
              {#if colVisible.status}<Table.Head class="w-[100px] text-center font-semibold text-slate-700">Status</Table.Head>{/if}
              <Table.Head class="w-[100px] text-right font-semibold text-slate-700">Aksi</Table.Head>
            </Table.Row>
          </Table.Header>
          <Table.Body>
            {#if accounts.length === 0}
              <Table.Row>
                <Table.Cell colspan={6} class="p-0 border-b-0">
                  <EmptyState 
                    icon={Search}
                    title="Tidak ada data akun"
                    description="Akun akuntansi untuk kategori {category} belum tersedia atau tidak cocok dengan pencarian Anda."
                  />
                </Table.Cell>
              </Table.Row>
            {:else}
              {#each accounts as account}
              <Table.Row class="hover:bg-slate-50/60 transition-colors group border-b border-slate-100">
                
                {#if colVisible.code}
                  <Table.Cell class="font-medium text-slate-900 py-3">
                    {#if account.parent_id}
                      <span class="pl-4 text-slate-400">-</span> {account.code}
                    {:else}
                      {account.code}
                    {/if}
                  </Table.Cell>
                {/if}

                {#if colVisible.name}
                  <Table.Cell class="py-3">
                    <div class="flex items-center gap-2">
                      <span class={account.parent_id ? 'text-slate-600' : 'font-medium text-slate-800'}>
                        {account.name}
                      </span>
                      {#if account.system}
                        <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-[10px] font-medium border border-slate-200">
                          System
                        </span>
                      {/if}
                    </div>
                  </Table.Cell>
                {/if}

                {#if colVisible.type}
                  <Table.Cell class="text-slate-600 py-3 text-sm">{account.type_name}</Table.Cell>
                {/if}

                {#if colVisible.parent}
                  <Table.Cell class="text-slate-500 py-3 text-sm">{account.parent_name}</Table.Cell>
                {/if}

                {#if colVisible.status}
                  <Table.Cell class="text-center py-3">
                    {#if account.enabled}
                      <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-teal-50 text-teal-700 text-xs font-medium ring-1 ring-inset ring-teal-600/20">
                        Aktif
                      </span>
                    {:else}
                      <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-50 text-slate-600 text-xs font-medium ring-1 ring-inset ring-slate-500/20">
                        Nonaktif
                      </span>
                    {/if}
                  </Table.Cell>
                {/if}

                <Table.Cell class="text-right py-3">
                  <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500 hover:text-teal-600 hover:bg-teal-50 rounded-full" on:click={() => openEditModal(account)}>
                      <Pencil size={14} />
                    </Button>
                    {#if !account.system}
                      <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-full" on:click={() => deleteAccount(account.id)}>
                        <Trash2 size={14} />
                      </Button>
                    {/if}
                  </div>
                </Table.Cell>
              </Table.Row>
            {/each}
          {/if}
        </Table.Body>
      </TableCard>
  </div>

  <!-- Modal Form -->
  {#if isModalOpen}
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
      <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" on:click={() => isModalOpen = false}></div>
      
      <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg border border-slate-200 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
          <h3 class="text-lg font-semibold text-slate-800">
            {isEditing ? 'Edit Akun' : 'Tambah Akun Baru'}
          </h3>
          <button on:click={() => isModalOpen = false} class="text-slate-400 hover:text-slate-600 transition-colors">
            <X size={20} />
          </button>
        </div>
        
        <form on:submit|preventDefault={submitForm} class="p-6 space-y-5">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <label for="type_id" class="text-sm font-medium leading-none">Tipe Akun</label>
              <select 
                id="type_id"
                class="flex h-10 w-full items-center justify-between rounded-md border border-slate-300 bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 disabled:cursor-not-allowed disabled:opacity-50"
                bind:value={$form.type_id}
                required
              >
                <option value="" disabled>Pilih Tipe...</option>
                {#each types as type}
                  <option value={type.id}>{type.name}</option>
                {/each}
              </select>
              {#if $form.errors.type_id}<p class="text-xs text-red-500">{$form.errors.type_id}</p>{/if}
            </div>

            <div class="space-y-2">
              <label for="parent_id" class="text-sm font-medium leading-none">Sub-akun Dari (Opsional)</label>
              <select 
                id="parent_id"
                class="flex h-10 w-full items-center justify-between rounded-md border border-slate-300 bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 disabled:cursor-not-allowed disabled:opacity-50"
                bind:value={$form.parent_id}
              >
                <option value="">Tidak Ada (Akun Utama)</option>
                {#each parentAccounts as parent}
                  {#if !isEditing || parent.id !== editingId}
                    <option value={parent.id}>{parent.code} - {parent.name}</option>
                  {/if}
                {/each}
              </select>
            </div>
          </div>

          <div class="space-y-2">
            <label for="code" class="text-sm font-medium leading-none">Kode Akun</label>
            <Input id="code" bind:value={$form.code} placeholder="Misal: 1-1010" required class="border-slate-300 focus:border-teal-500 focus:ring-teal-500" />
            {#if $form.errors.code}<p class="text-xs text-red-500">{$form.errors.code}</p>{/if}
          </div>

          <div class="space-y-2">
            <label for="name" class="text-sm font-medium leading-none">Nama Akun</label>
            <Input id="name" bind:value={$form.name} placeholder="Misal: Kas Kecil" required class="border-slate-300 focus:border-teal-500 focus:ring-teal-500" />
            {#if $form.errors.name}<p class="text-xs text-red-500">{$form.errors.name}</p>{/if}
          </div>
          
          <div class="space-y-2">
            <label for="description" class="text-sm font-medium leading-none">Keterangan (Opsional)</label>
            <Input id="description" bind:value={$form.description} placeholder="Keterangan fungsi akun..." class="border-slate-300 focus:border-teal-500 focus:ring-teal-500" />
          </div>

          <div class="flex items-center gap-2 pt-2">
            <input type="checkbox" id="enabled" bind:checked={$form.enabled} class="rounded border-slate-300 text-teal-600 focus:ring-teal-600" />
            <label for="enabled" class="text-sm font-medium cursor-pointer">Akun Aktif</label>
          </div>

          <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
            <Button type="button" variant="outline" class="border-slate-200" on:click={() => isModalOpen = false}>Batal</Button>
            <Button type="submit" disabled={$form.processing} class="bg-teal-600 hover:bg-teal-700 text-white gap-2">
              <Save size={16} /> Simpan Akun
            </Button>
          </div>
        </form>
      </div>
    </div>
  {/if}
</AppLayout>
