<script>
  import { createEventDispatcher } from 'svelte';
  import { useForm, router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import CoaSelect from '../../Components/CoaSelect.svelte';
  import { Save, Building2, AlignLeft, Hash, Link as LinkIcon, Briefcase, ArrowLeft } from 'lucide-svelte';
  import { cn } from '$lib/utils';

  export let customer = null;
  export let accounts = [];

  let form = useForm({
    name: '',
    address: '',
    npwp: '',
    reference: '',
    account_id: '',
    is_active: true
  });

  $: {
    if (customer) {
      $form.name = customer.name || '';
      $form.address = customer.address || '';
      $form.npwp = customer.npwp || '';
      $form.reference = customer.reference || '';
      $form.account_id = customer.account_id || '';
      $form.is_active = customer.is_active ?? true;
    }
  }

  function submit() {
    if (customer) {
      $form.put(`/customers/${customer.id}`, {
        preserveScroll: true
      });
    } else {
      $form.post('/customers', {
        preserveScroll: true
      });
    }
  }
</script>

<div class="space-y-6">
  <div class="flex items-center gap-4">
    <Button
      variant="outline"
      size="icon"
      class="h-8 w-8 bg-white"
      on:click={() => window.history.back()}
    >
      <ArrowLeft class="h-4 w-4" />
    </Button>
    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
      {customer ? 'Edit Customer' : 'Tambah Customer Baru'}
    </h1>
  </div>

  <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-2">
      <Building2 class="h-5 w-5 text-teal-600" />
      <h3 class="font-semibold text-slate-800">Informasi Umum</h3>
    </div>
    
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
          <!-- Name -->
          <div class="space-y-1.5">
            <label for="name" class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
              <Building2 class="w-3.5 h-3.5" />
              Nama Customer <span class="text-rose-500">*</span>
            </label>
            <Input 
              id="name" 
              bind:value={$form.name} 
              placeholder="Contoh: PT. Maju Bersama" 
              class="border-slate-200 focus:border-teal-500" 
            />
            {#if $form.errors.name}
              <p class="text-[10px] text-rose-500 font-medium">{$form.errors.name}</p>
            {/if}
          </div>

          <!-- Address -->
          <div class="space-y-1.5">
            <label for="address" class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
              <AlignLeft class="w-3.5 h-3.5" />
              Alamat
            </label>
            <textarea 
              id="address" 
              bind:value={$form.address} 
              placeholder="Alamat lengkap customer..." 
              class="w-full flex rounded-md border border-slate-200 bg-white px-3 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 min-h-[80px]" 
            ></textarea>
            {#if $form.errors.address}
              <p class="text-[10px] text-rose-500 font-medium">{$form.errors.address}</p>
            {/if}
          </div>

          <!-- NPWP -->
          <div class="space-y-1.5">
            <label for="npwp" class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
              <Hash class="w-3.5 h-3.5" />
              NPWP / Tax Number
            </label>
            <Input 
              id="npwp" 
              bind:value={$form.npwp} 
              placeholder="Contoh: 01.234.567.8-901.000" 
              class="border-slate-200 focus:border-teal-500" 
            />
            {#if $form.errors.npwp}
              <p class="text-[10px] text-rose-500 font-medium">{$form.errors.npwp}</p>
            {/if}
          </div>

          <!-- Reference -->
          <div class="space-y-1.5">
            <label for="reference" class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
              <LinkIcon class="w-3.5 h-3.5" />
              Referensi Internal
            </label>
            <Input 
              id="reference" 
              bind:value={$form.reference} 
              placeholder="Kode atau referensi internal" 
              class="border-slate-200 focus:border-teal-500" 
            />
            {#if $form.errors.reference}
              <p class="text-[10px] text-rose-500 font-medium">{$form.errors.reference}</p>
            {/if}
          </div>

          <!-- Account ID (COA) -->
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
              <Briefcase class="w-3.5 h-3.5" />
              COA Piutang <span class="text-rose-500">*</span>
            </label>
            <CoaSelect 
              bind:value={$form.account_id}
              options={accounts}
              placeholder="-- Pilih COA Piutang --"
            />
            {#if $form.errors.account_id}
              <p class="text-[10px] text-rose-500 font-medium">{$form.errors.account_id}</p>
            {/if}
          </div>

          <!-- Is Active -->
          <div class="flex items-center justify-between p-4 bg-slate-50/50 rounded-xl border border-slate-100 md:col-span-2">
            <div>
              <h4 class="text-sm font-semibold text-slate-800">Status Aktif</h4>
              <p class="text-xs text-slate-500 mt-0.5">Customer aktif dapat digunakan dalam transaksi.</p>
            </div>
            <button
              type="button"
              class={cn(
                "relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2",
                $form.is_active ? "bg-teal-500" : "bg-slate-300"
              )}
              on:click={() => $form.is_active = !$form.is_active}
            >
              <span
                class={cn(
                  "pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out",
                  $form.is_active ? "translate-x-5" : "translate-x-0"
                )}
              />
            </button>
          </div>
          
          <div class="md:col-span-2 pt-6">
            <Button
                class="w-full bg-teal-700 hover:bg-teal-800 text-white shadow-sm font-semibold flex items-center justify-center gap-2 h-12 text-base rounded-md"
                on:click={submit}
                disabled={$form.processing}
            >
                {#if $form.processing}
                    <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                    Menyimpan...
                {:else}
                    <Save class="h-5 w-5" />
                    Simpan Customer
                {/if}
            </Button>
          </div>
    </div>
  </div>
</div>
