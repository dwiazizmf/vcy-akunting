<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { router, useForm } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Table from '$lib/components/ui/table';
  import * as Select from '$lib/components/ui/select';
  import {
    Plus, Search, Pencil, Trash2, Save, X
  } from 'lucide-svelte';
  import { showToast } from '../../Stores/toast.js';
  import { showConfirm } from '../../Stores/confirmStore.js';
  
  import Pagination from '../../Components/Pagination.svelte';

  export let accounts = { data: [], links: [] };
  export let types = [];
  export let parentAccounts = [];
  export let filters = { search: '', type_id: '' };

  let search = filters.search || '';
  let type_id = filters.type_id || '';
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

  let searchTimeout;
  function handleSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      applyFilters();
    }, 300);
  }

  function goToPage(page) {
    if (page === accounts.current_page) return;
    router.get('/accounts', { ...filters, page }, {
      preserveState: true,
      replace: true
    });
  }

  function applyFilters() {
    router.get('/accounts', { 
      search, 
      type_id 
    }, {
      preserveState: true,
      replace: true
    });
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
        onError: (errors) => {
          showToast('Gagal memperbarui akun', 'error');
        }
      });
    } else {
      $form.post('/accounts', {
        onSuccess: () => {
          isModalOpen = false;
          showToast('Akun berhasil dibuat', 'success');
        },
        onError: (errors) => {
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
      <Button on:click={openCreateModal} class="bg-teal-600 hover:bg-teal-700 text-white gap-2">
        <Plus size={16} /> Tambah Akun
      </Button>
    </div>

    <!-- Filter Panel -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row gap-4">
      <div class="relative w-full sm:w-72">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
        <Input 
          type="text" 
          placeholder="Cari kode atau nama akun..." 
          class="pl-9 h-10 w-full"
          bind:value={search}
          on:input={handleSearch}
        />
      </div>
      
      <div class="w-full sm:w-64">
        <select 
          class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
          bind:value={type_id}
          on:change={applyFilters}
        >
          <option value="">Semua Tipe Akun</option>
          {#each types as type}
            <option value={type.id}>{type.name} ({type.category})</option>
          {/each}
        </select>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <Table.Root>
          <Table.Header class="bg-slate-50 border-b border-slate-100">
            <Table.Row>
              <Table.Head class="w-[120px] font-semibold text-slate-700">Kode</Table.Head>
              <Table.Head class="font-semibold text-slate-700">Nama Akun</Table.Head>
              <Table.Head class="font-semibold text-slate-700">Tipe</Table.Head>
              <Table.Head class="font-semibold text-slate-700">Kategori</Table.Head>
              <Table.Head class="w-[100px] text-center font-semibold text-slate-700">Status</Table.Head>
              <Table.Head class="w-[100px] text-right font-semibold text-slate-700">Aksi</Table.Head>
            </Table.Row>
          </Table.Header>
          <Table.Body>
            {#each accounts.data as account}
              <Table.Row class="hover:bg-slate-50 transition-colors group">
                <Table.Cell class="font-medium text-slate-900 py-2">
                  {#if account.parent_id}
                    <span class="pl-4 text-slate-500">-</span> {account.code}
                  {:else}
                    {account.code}
                  {/if}
                </Table.Cell>
                <Table.Cell class="py-2">
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
                <Table.Cell class="text-slate-600 py-2">{account.type?.name}</Table.Cell>
                <Table.Cell class="text-slate-600 py-2">{account.type?.category}</Table.Cell>
                <Table.Cell class="text-center py-2">
                  {#if account.enabled}
                    <span class="inline-flex items-center px-2 py-1 rounded-full bg-teal-50 text-teal-700 text-xs font-medium ring-1 ring-inset ring-teal-600/20">
                      Aktif
                    </span>
                  {:else}
                    <span class="inline-flex items-center px-2 py-1 rounded-full bg-slate-50 text-slate-600 text-xs font-medium ring-1 ring-inset ring-slate-500/20">
                      Nonaktif
                    </span>
                  {/if}
                </Table.Cell>
                <Table.Cell class="text-right py-2">
                  <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500 hover:text-teal-600" on:click={() => openEditModal(account)}>
                      <Pencil size={14} />
                    </Button>
                    {#if !account.system}
                      <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-500 hover:text-red-600 hover:bg-red-50" on:click={() => deleteAccount(account.id)}>
                        <Trash2 size={14} />
                      </Button>
                    {/if}
                  </div>
                </Table.Cell>
              </Table.Row>
            {:else}
              <Table.Row>
                <Table.Cell colspan={6} class="h-32 text-center text-slate-500">
                  Tidak ada data akun yang ditemukan.
                </Table.Cell>
              </Table.Row>
            {/each}
          </Table.Body>
        </Table.Root>
      </div>
      
      {#if accounts.last_page > 1}
        <div class="border-t border-slate-100">
          <Pagination 
            pagination={{ currentPage: accounts.current_page, lastPage: accounts.last_page }}
            onGoToPage={(p) => goToPage(p)}
          />
        </div>
      {/if}
    </div>
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
                class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
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
                class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
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
            <Input id="code" bind:value={$form.code} placeholder="Misal: 1-1010" required />
            {#if $form.errors.code}<p class="text-xs text-red-500">{$form.errors.code}</p>{/if}
          </div>

          <div class="space-y-2">
            <label for="name" class="text-sm font-medium leading-none">Nama Akun</label>
            <Input id="name" bind:value={$form.name} placeholder="Misal: Kas Kecil" required />
            {#if $form.errors.name}<p class="text-xs text-red-500">{$form.errors.name}</p>{/if}
          </div>
          
          <div class="space-y-2">
            <label for="description" class="text-sm font-medium leading-none">Keterangan (Opsional)</label>
            <Input id="description" bind:value={$form.description} placeholder="Keterangan fungsi akun..." />
          </div>

          <div class="flex items-center gap-2 pt-2">
            <input type="checkbox" id="enabled" bind:checked={$form.enabled} class="rounded border-slate-300 text-teal-600 focus:ring-teal-600" />
            <label for="enabled" class="text-sm font-medium cursor-pointer">Akun Aktif</label>
          </div>

          <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
            <Button type="button" variant="outline" on:click={() => isModalOpen = false}>Batal</Button>
            <Button type="submit" disabled={$form.processing} class="bg-teal-600 hover:bg-teal-700 text-white gap-2">
              <Save size={16} /> Simpan Akun
            </Button>
          </div>
        </form>
      </div>
    </div>
  {/if}
</AppLayout>
