<script>
  import AppLayout from '../../../Layouts/AppLayout.svelte';
  import CoaSelect from '../../../Components/CoaSelect.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Card from '$lib/components/ui/card';
  import * as Table from '$lib/components/ui/table';
  import { Plus, Building2, Pencil, Trash2, X, Save } from 'lucide-svelte';
  import { showToast } from '../../../Stores/toast.js';
  import { showConfirm } from '../../../Stores/confirmStore.js';

  export let banks = [];
  export let accounts = [];
  export let errors = {};
  export let companies = [];
  export let active_company_id = null;

  let showModal = false;
  let isEditing = false;
  let form = { id: null, name: '', type: 'bank', bank_name: '', account_number: '', account_id: null, is_default: false, enabled: true };

  function openCreate() {
    form = { id: null, name: '', type: 'bank', bank_name: '', account_number: '', account_id: null, is_default: false, enabled: true };
    isEditing = false;
    showModal = true;
  }

  function openEdit(bank) {
    form = { ...bank, account_id: bank.account_id };
    isEditing = true;
    showModal = true;
  }

  function save() {
    if (!form.name) { showToast('Nama bank wajib diisi', 'error'); return; }
    if (!form.account_id) { showToast('Pilih akun COA untuk bank ini', 'error'); return; }

    const url = isEditing ? `/settings/bank-accounts/${form.id}` : '/settings/bank-accounts';
    const method = isEditing ? 'put' : 'post';

    router[method](url, form, {
      preserveScroll: true,
      onSuccess: () => {
        showToast(isEditing ? 'Bank account diperbarui!' : 'Bank account ditambahkan!', 'success');
        showModal = false;
      },
      onError: (e) => showToast(Object.values(e)[0] || 'Gagal menyimpan.', 'error'),
    });
  }

  async function remove(id) {
    if (await showConfirm('Hapus bank account ini?')) {
      router.delete(`/settings/bank-accounts/${id}`, {
        preserveScroll: true,
        onSuccess: () => showToast('Bank account dihapus.', 'success'),
      });
    }
  }
</script>

<AppLayout title="Master Bank & Kas">
  <div class="max-w-5xl mx-auto px-4 py-8 space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
          <Building2 class="h-6 w-6 text-teal-600" /> Master Bank & Kas
        </h1>
        <p class="text-sm text-slate-500 mt-1">Kelola rekening bank dan kas yang terhubung ke akun COA</p>
      </div>
      <Button class="bg-teal-600 hover:bg-teal-700 text-white flex items-center gap-2" on:click={openCreate}>
        <Plus class="h-4 w-4" /> Tambah Bank
      </Button>
    </div>

    <!-- Table -->
    <Card.Root class="shadow-sm border border-slate-200/60">
      <Table.Root>
        <Table.Header class="bg-slate-50">
          <Table.Row>
            <Table.Head class="text-xs font-bold uppercase text-slate-500">Nama</Table.Head>
            <Table.Head class="text-xs font-bold uppercase text-slate-500">Tipe</Table.Head>
            <Table.Head class="text-xs font-bold uppercase text-slate-500">Bank / Kas</Table.Head>
            <Table.Head class="text-xs font-bold uppercase text-slate-500">No. Rekening</Table.Head>
            <Table.Head class="text-xs font-bold uppercase text-slate-500">COA Account</Table.Head>
            <Table.Head class="text-xs font-bold uppercase text-slate-500 text-center">Default</Table.Head>
            <Table.Head class="text-xs font-bold uppercase text-slate-500 text-center">Status</Table.Head>
            <Table.Head class="w-20"></Table.Head>
          </Table.Row>
        </Table.Header>
        <Table.Body>
          {#each banks as bank}
            <Table.Row class="hover:bg-slate-50/50">
              <Table.Cell class="font-semibold text-slate-800">{bank.name}</Table.Cell>
              <Table.Cell>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium {bank.type === 'bank' ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700'}">
                  {bank.type === 'bank' ? 'Bank' : 'Kas'}
                </span>
              </Table.Cell>
              <Table.Cell class="text-slate-600">{bank.bank_name || '-'}</Table.Cell>
              <Table.Cell class="font-mono text-xs text-slate-500">{bank.account_number || '-'}</Table.Cell>
              <Table.Cell class="text-xs text-slate-600">{bank.account_name}</Table.Cell>
              <Table.Cell class="text-center">
                {#if bank.is_default}
                  <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-teal-50 text-teal-700">Default</span>
                {/if}
              </Table.Cell>
              <Table.Cell class="text-center">
                <span class="px-2 py-0.5 rounded-full text-xs font-medium {bank.enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'}">
                  {bank.enabled ? 'Aktif' : 'Nonaktif'}
                </span>
              </Table.Cell>
              <Table.Cell>
                <div class="flex items-center gap-1 justify-end">
                  <button class="p-1.5 rounded-md text-slate-400 hover:text-teal-600 hover:bg-teal-50 transition" on:click={() => openEdit(bank)} title="Edit">
                    <Pencil class="h-3.5 w-3.5" />
                  </button>
                  <button class="p-1.5 rounded-md text-slate-400 hover:text-red-600 hover:bg-red-50 transition" on:click={() => remove(bank.id)} title="Hapus">
                    <Trash2 class="h-3.5 w-3.5" />
                  </button>
                </div>
              </Table.Cell>
            </Table.Row>
          {:else}
            <Table.Row>
              <Table.Cell colspan={8} class="text-center py-10 text-slate-400">
                Belum ada bank/kas. Klik "Tambah Bank" untuk mulai.
              </Table.Cell>
            </Table.Row>
          {/each}
        </Table.Body>
      </Table.Root>
    </Card.Root>
  </div>

  <!-- Modal -->
  {#if showModal}
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
          <h2 class="text-lg font-bold text-slate-900">{isEditing ? 'Edit Bank Account' : 'Tambah Bank Account'}</h2>
          <button class="text-slate-400 hover:text-slate-600 p-1 rounded-md hover:bg-slate-100" on:click={() => showModal = false}>
            <X class="h-5 w-5" />
          </button>
        </div>

        <div class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2 space-y-1.5">
              <label class="text-xs font-bold text-slate-700 uppercase">Nama <span class="text-red-500">*</span></label>
              <Input bind:value={form.name} placeholder="Contoh: BCA Operasional" />
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700 uppercase">Tipe <span class="text-red-500">*</span></label>
              <select bind:value={form.type} class="w-full h-10 px-3 rounded-md border border-slate-200 bg-white text-sm outline-none focus:border-teal-500">
                <option value="bank">Bank</option>
                <option value="cash">Kas</option>
              </select>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700 uppercase">Nama Bank</label>
              <Input bind:value={form.bank_name} placeholder="BCA, Mandiri, dll" />
            </div>

            <div class="col-span-2 space-y-1.5">
              <label class="text-xs font-bold text-slate-700 uppercase">No. Rekening</label>
              <Input bind:value={form.account_number} placeholder="0123-456-789" />
            </div>

            <div class="col-span-2 space-y-1.5">
              <label class="text-xs font-bold text-slate-700 uppercase">Akun COA <span class="text-red-500">*</span></label>
              <CoaSelect bind:value={form.account_id} options={accounts} placeholder="Pilih Akun..." />
              <p class="text-xs text-slate-400">Pilih akun Aset Lancar (Kas & Bank) yang sesuai</p>
            </div>

            <div class="flex items-center gap-3">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" bind:checked={form.is_default} class="rounded border-slate-300 text-teal-600" />
                <span class="text-sm text-slate-600">Set sebagai default</span>
              </label>
            </div>

            <div class="flex items-center gap-3">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" bind:checked={form.enabled} class="rounded border-slate-300 text-teal-600" />
                <span class="text-sm text-slate-600">Aktif</span>
              </label>
            </div>
          </div>
        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2">
          <Button variant="ghost" on:click={() => showModal = false}>Batal</Button>
          <Button class="bg-teal-600 hover:bg-teal-700 text-white flex items-center gap-2" on:click={save}>
            <Save class="h-4 w-4" /> Simpan
          </Button>
        </div>
      </div>
    </div>
  {/if}
</AppLayout>
