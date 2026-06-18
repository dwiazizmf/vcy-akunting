<script>
  import AppLayout from '../../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { cn } from '$lib/utils.js';
  import { onMount } from 'svelte';
  import {
    FileText, FileCheck2, FileSpreadsheet, FileWarning, Layers,
    ArrowLeft, Save, Trash2, Calendar, User, Phone, MapPin, 
    Search, AlertCircle, Info, Sparkles, Building, Lock
  } from 'lucide-svelte';

  // Invoices data passed from Controller
  export let invoices = [];
  export let errors = {};

  // ================================================
  // DOCUMENT CONFIGURATION OPTIONS
  // ================================================
  const docTypes = [
    { 
      value: 'tanda_terima', 
      label: 'Tanda Terima', 
      abbr: 'TT', 
      desc: '1 Customer, invoice tidak bisa dipakai di TT lain.',
      icon: FileText,
      color: 'from-blue-500 to-indigo-500',
      badgeColor: 'bg-blue-50 text-blue-700 border-blue-200'
    },
    { 
      value: 'tanda_terima_new', 
      label: 'Tanda Terima New', 
      abbr: 'TTN', 
      desc: '1 Customer, invoice BOLEH dipakai di TTN lain.',
      icon: FileCheck2,
      color: 'from-teal-500 to-emerald-500',
      badgeColor: 'bg-emerald-50 text-emerald-700 border-emerald-200'
    },
    { 
      value: 'surat_tagihan', 
      label: 'Surat Tagihan', 
      abbr: 'ST', 
      desc: '1 Customer, invoice tidak bisa dipakai di ST lain.',
      icon: FileWarning,
      color: 'from-amber-500 to-orange-500',
      badgeColor: 'bg-amber-50 text-amber-700 border-amber-200'
    },
    { 
      value: 'schedule_tukar_faktur', 
      label: 'Schedule Faktur', 
      abbr: 'SCH', 
      desc: 'Bisa campur banyak Customer, invoice unik.',
      icon: FileSpreadsheet,
      color: 'from-purple-500 to-fuchsia-500',
      badgeColor: 'bg-purple-50 text-purple-700 border-purple-200'
    },
    { 
      value: 'titip_internal', 
      label: 'Titip Internal', 
      abbr: 'TI', 
      desc: '1 Customer, invoice tidak bisa dipakai di TI lain.',
      icon: Layers,
      color: 'from-rose-500 to-pink-500',
      badgeColor: 'bg-rose-50 text-rose-700 border-rose-200'
    }
  ];

  // ================================================
  // FORM STATE
  // ================================================
  let form = {
    type: '',
    send_date: new Date().toISOString().substring(0, 10),
    up_person: '',
    customer_name: '',
    no_tlp: '',
    address: '',
    invoice_ids: []
  };

  let addedInvoices = [];
  let submitting = false;

  // Search logic for dropdown
  let searchQuery = '';
  let showDropdown = false;
  let searchInputContainer;

  // Determine locked customer
  $: selectedCustomerName = addedInvoices.length > 0 ? addedInvoices[0].customer_name : '';
  $: selectedCustomerAddress = addedInvoices.length > 0 ? addedInvoices[0].customer_address : '';

  // Lock logic
  $: {
    if (form.type && form.type !== 'schedule_tukar_faktur') {
      if (addedInvoices.length > 0) {
        form.customer_name = selectedCustomerName;
        // Auto-fill address only if empty
        if (!form.address) {
          form.address = selectedCustomerAddress;
        }
      } else {
        form.customer_name = '';
        form.address = '';
      }
    }
  }

  // Reactive filtering of invoices dropdown
  $: filteredInvoices = invoices.filter(inv => {
    if (!form.type) return false;

    // Filter out already selected invoices
    if (form.invoice_ids.includes(inv.id)) return false;

    // Filter out if not same customer (for non-SCH documents)
    if (form.type !== 'schedule_tukar_faktur' && selectedCustomerName) {
      if (inv.customer_name !== selectedCustomerName) return false;
    }

    // Filter out invoices already used in this type (except TTN)
    if (form.type !== 'tanda_terima_new') {
      if (inv.used_in_types && inv.used_in_types.includes(form.type)) {
        return false;
      }
    }

    return true;
  });

  $: searchResults = filteredInvoices.filter(inv => {
    const q = searchQuery.toLowerCase();
    return inv.invoice_number.toLowerCase().includes(q) || 
           inv.customer_name.toLowerCase().includes(q) ||
           inv.order_number.toLowerCase().includes(q);
  });

  function handleKeyDown(event) {
    if (event.key === 'Escape') {
      showDropdown = false;
    }
  }

  function handleClickOutside(event) {
    if (showDropdown && searchInputContainer && !searchInputContainer.contains(event.target)) {
      showDropdown = false;
    }
  }

  function selectType(value) {
    if (form.type !== value) {
      form.type = value;
      // Reset items and locked fields on type change
      addedInvoices = [];
      form.invoice_ids = [];
      form.customer_name = '';
      form.address = '';
      errors = {};
    }
  }

  function addInvoice(inv) {
    addedInvoices = [...addedInvoices, inv];
    form.invoice_ids = [...form.invoice_ids, inv.id];
    searchQuery = '';
    showDropdown = false;
  }

  function removeInvoice(id) {
    addedInvoices = addedInvoices.filter(inv => inv.id !== id);
    form.invoice_ids = form.invoice_ids.filter(iid => iid !== id);
  }

  function submit() {
    submitting = true;
    router.post('/documents', form, {
      onError: (e) => {
        errors = e;
        submitting = false;
      },
      onSuccess: () => {
        submitting = false;
      }
    });
  }
</script>

<svelte:window on:keydown={handleKeyDown} on:click={handleClickOutside} />

<AppLayout>
  <div class="max-w-6xl mx-auto space-y-8 pb-16">
    
    <!-- Top Action Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <button
          type="button"
          class="inline-flex items-center justify-center h-10 w-10 rounded-xl border border-slate-200 bg-white shadow-sm text-slate-500 hover:bg-slate-50 hover:text-slate-900 transition-all duration-200"
          on:click={() => router.visit('/tanda-terima')}
        >
          <ArrowLeft class="h-5 w-5" />
        </button>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-slate-950 flex items-center gap-2">
            Create Document
            <Sparkles class="h-5 w-5 text-yellow-500 fill-yellow-500" />
          </h1>
          <p class="text-sm text-slate-500 mt-1">Buat dokumen tanda terima, surat tagihan, dan lainnya dengan validasi otomatis.</p>
        </div>
      </div>
      
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="px-5 h-11 border border-slate-200 bg-white text-slate-700 font-semibold rounded-xl hover:bg-slate-50 transition-colors shadow-sm"
          on:click={() => router.visit('/tanda-terima')}
          disabled={submitting}
        >
          Batal
        </button>
        <button
          type="button"
          class="flex items-center justify-center gap-2 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white px-6 h-11 rounded-xl shadow-lg shadow-emerald-600/10 hover:shadow-emerald-600/20 transition-all font-semibold disabled:opacity-50"
          on:click={submit}
          disabled={submitting || !form.type || form.invoice_ids.length === 0}
        >
          <Save class="h-4 w-4" />
          {submitting ? 'Menyimpan...' : 'Simpan Dokumen'}
        </button>
      </div>
    </div>

    <!-- Alert Global Error -->
    {#if errors.invoice_ids}
      <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 flex gap-3 text-rose-900 animate-in fade-in slide-in-from-top-2">
        <AlertCircle class="h-5 w-5 text-rose-600 shrink-0 mt-0.5" />
        <div>
          <span class="font-bold text-sm">Gagal Menyimpan Dokumen:</span>
          <p class="text-xs text-rose-700 mt-1">{errors.invoice_ids}</p>
        </div>
      </div>
    {/if}

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      
      <!-- Left 2 Cols: Form Config and Invoices -->
      <div class="lg:col-span-2 space-y-8">
        
        <!-- STEP 1: SELECT TYPE -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm space-y-4">
          <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
            <span class="flex items-center justify-center h-6 w-6 rounded-full bg-teal-50 text-teal-700 text-xs font-black">1</span>
            Pilih Jenis Dokumen
          </h2>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            {#each docTypes as type}
              <button
                type="button"
                on:click={() => selectType(type.value)}
                class={cn(
                  "flex flex-col items-start p-4 rounded-xl border text-left transition-all duration-300 relative group cursor-pointer h-full",
                  form.type === type.value
                    ? "border-teal-500 bg-teal-50/20 ring-4 ring-teal-500/10"
                    : "border-slate-200 bg-white hover:border-teal-200 hover:bg-slate-50/30"
                )}
              >
                <div class="flex items-center justify-between w-full mb-3">
                  <div class={cn(
                    "h-9 w-9 rounded-lg flex items-center justify-center bg-gradient-to-br text-white",
                    type.color
                  )}>
                    <svelte:component this={type.icon} class="h-4.5 w-4.5" />
                  </div>
                  <span class={cn(
                    "px-2 py-0.5 text-[10px] font-bold border rounded-md uppercase tracking-wider",
                    type.badgeColor
                  )}>
                    {type.abbr}
                  </span>
                </div>
                <span class="font-bold text-slate-900 text-sm">{type.label}</span>
                <span class="text-xs text-slate-400 mt-1 leading-normal line-clamp-2">{type.desc}</span>
              </button>
            {/each}
          </div>
          {#if errors.type}
            <p class="text-xs text-rose-500 mt-1 font-medium">{errors.type}</p>
          {/if}
        </div>

        <!-- STEP 2: ADD INVOICES -->
        {#if form.type}
          <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <span class="flex items-center justify-center h-6 w-6 rounded-full bg-teal-50 text-teal-700 text-xs font-black">2</span>
                Pilih Invoice
              </h2>

              <!-- Info status customer -->
              {#if form.type !== 'schedule_tukar_faktur' && selectedCustomerName}
                <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 text-amber-800 border border-amber-200/60 rounded-full text-xs font-semibold">
                  <Lock class="h-3 w-3" />
                  Terkunci Customer: {selectedCustomerName}
                </div>
              {/if}
            </div>

            <!-- Auto-complete Search input -->
            <div class="space-y-2" bind:this={searchInputContainer}>
              <label class="text-xs font-bold text-slate-500 uppercase tracking-wider" for="search_inv">Cari Invoice</label>
              <div class="relative">
                <Search class="absolute left-3.5 top-3.5 h-4.5 w-4.5 text-slate-400" />
                <input
                  id="search_inv"
                  type="text"
                  placeholder="Ketik nomor invoice, PO (Order Number), atau nama customer..."
                  bind:value={searchQuery}
                  on:focus={() => showDropdown = true}
                  on:click={() => showDropdown = true}
                  on:input={() => showDropdown = true}
                  class="w-full h-12 pl-10 pr-4 rounded-xl border border-slate-200 outline-none transition-all duration-200 focus:bg-white focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 bg-slate-50/50 text-sm font-medium"
                />
                
                {#if showDropdown}
                  <!-- svelte-ignore a11y-no-static-element-interactions -->
                  <div 
                    class="absolute z-50 w-full mt-2 bg-white border border-slate-200/80 rounded-xl shadow-xl max-h-72 overflow-y-auto p-1.5 space-y-1"
                  >
                    {#each searchResults as inv}
                      <button
                        type="button"
                        on:click={() => addInvoice(inv)}
                        class="w-full text-left px-3.5 py-2.5 rounded-lg hover:bg-slate-50 transition-colors flex flex-col gap-1 cursor-pointer"
                      >
                        <div class="flex items-center justify-between">
                          <span class="font-bold text-slate-900 text-sm">{inv.invoice_number}</span>
                          {#if inv.order_number}
                            <span class="text-xs px-2 py-0.5 bg-slate-100 text-slate-600 rounded-md font-medium">PO: {inv.order_number}</span>
                          {/if}
                        </div>
                        <div class="flex items-center justify-between text-xs text-slate-500">
                          <span>{inv.customer_name}</span>
                        </div>
                      </button>
                    {:else}
                      <!-- svelte-ignore a11y-click-events-have-key-events -->
                      <!-- svelte-ignore a11y-no-static-element-interactions -->
                      <div 
                        on:click={() => showDropdown = false} 
                        class="py-8 text-center text-slate-400 text-xs cursor-pointer hover:bg-slate-50 transition-colors rounded-lg"
                        title="Klik untuk menutup"
                      >
                        {#if searchQuery}
                          Invoice tidak ditemukan. (Klik untuk menutup)
                        {:else}
                          Tidak ada invoice yang memenuhi kriteria untuk ditambahkan. (Klik untuk menutup)
                        {/if}
                      </div>
                    {/each}
                  </div>
                {/if}
              </div>
            </div>

            <!-- List Invoice Terpilih -->
            <div class="space-y-3">
              <div class="flex justify-between items-center">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Invoice Terpilih</span>
                <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md font-bold">{addedInvoices.length} Terpilih</span>
              </div>

              <div class="border border-slate-100 rounded-xl overflow-hidden">
                <table class="w-full text-left text-sm text-slate-600">
                  <thead class="bg-slate-50 text-xs uppercase text-slate-500 border-b border-slate-100">
                    <tr>
                      <th class="px-4 py-3 font-semibold">No. Invoice</th>
                      <th class="px-4 py-3 font-semibold">Customer</th>
                      <th class="px-4 py-3 font-semibold">PO Number</th>
                      <th class="px-4 py-3 font-semibold text-center w-20">Aksi</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    {#each addedInvoices as item (item.id)}
                      <tr class="hover:bg-slate-50/40 transition-colors">
                        <td class="px-4 py-3.5 font-bold text-slate-800">{item.invoice_number}</td>
                        <td class="px-4 py-3.5 text-xs font-semibold text-slate-600">{item.customer_name}</td>
                        <td class="px-4 py-3.5 text-xs text-slate-500">{item.order_number || '-'}</td>
                        <td class="px-4 py-3.5 text-center">
                          <button
                            type="button"
                            class="h-8 w-8 rounded-lg inline-flex items-center justify-center text-slate-400 hover:text-rose-500 hover:bg-rose-50 transition-colors"
                            on:click={() => removeInvoice(item.id)}
                          >
                            <Trash2 class="h-4 w-4" />
                          </button>
                        </td>
                      </tr>
                    {:else}
                      <tr>
                        <td colspan="4" class="py-12 text-center text-slate-400">
                          <div class="flex flex-col items-center justify-center">
                            <Info class="h-6 w-6 text-slate-300 mb-2" />
                            <p class="text-xs">Belum ada invoice yang dipilih.</p>
                          </div>
                        </td>
                      </tr>
                    {/each}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        {/if}
      </div>

      <!-- Right 1 Col: Info Penerima & Tanggal Kirim -->
      {#if form.type}
        <div class="space-y-6 animate-in fade-in slide-in-from-right-3">
          
          <!-- DATE CARD -->
          <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm space-y-4">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <Calendar class="h-4.5 w-4.5 text-teal-600" />
              Tanggal Kirim
            </h2>
            <div class="space-y-1">
              <input
                type="date"
                bind:value={form.send_date}
                class="w-full h-11 px-4 rounded-xl border border-slate-200 focus:outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 text-sm font-medium"
              />
              {#if errors.send_date}
                <p class="text-xs text-rose-500 font-medium">{errors.send_date}</p>
              {/if}
            </div>
          </div>

          <!-- RECIPIENT CARD -->
          <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm space-y-5">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <User class="h-4.5 w-4.5 text-teal-600" />
              Detail Dokumen
            </h2>

            <div class="space-y-4">
              <!-- Customer Name -->
              <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider" for="cust_name">Nama Customer</label>
                <div class="relative">
                  <Building class="absolute left-3.5 top-3.5 h-4 w-4 text-slate-400" />
                  <input
                    id="cust_name"
                    type="text"
                    bind:value={form.customer_name}
                    placeholder="Nama Customer"
                    class="w-full h-11 pl-10 pr-4 rounded-xl border border-slate-200 focus:outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 text-sm font-medium"
                  />
                </div>
                {#if errors.customer_name}
                  <p class="text-xs text-rose-500 font-medium">{errors.customer_name}</p>
                {/if}
              </div>

              <!-- UP Person -->
              <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider" for="up_p">UP Person</label>
                <div class="relative">
                  <User class="absolute left-3.5 top-3.5 h-4 w-4 text-slate-400" />
                  <input
                    id="up_p"
                    type="text"
                    bind:value={form.up_person}
                    placeholder="Contoh: Bapak Anas / Ibu Mayang"
                    class="w-full h-11 pl-10 pr-4 rounded-xl border border-slate-200 focus:outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 text-sm font-medium"
                  />
                </div>
                {#if errors.up_person}
                  <p class="text-xs text-rose-500 font-medium">{errors.up_person}</p>
                {/if}
              </div>

              <!-- No Tlp -->
              <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider" for="phone">No. Telepon</label>
                <div class="relative">
                  <Phone class="absolute left-3.5 top-3.5 h-4 w-4 text-slate-400" />
                  <input
                    id="phone"
                    type="text"
                    bind:value={form.no_tlp}
                    placeholder="No. Telepon Customer"
                    class="w-full h-11 pl-10 pr-4 rounded-xl border border-slate-200 focus:outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 text-sm font-medium"
                  />
                </div>
                {#if errors.no_tlp}
                  <p class="text-xs text-rose-500 font-medium">{errors.no_tlp}</p>
                {/if}
              </div>

              <!-- Address -->
              <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider" for="addr">Alamat Kirim</label>
                <div class="relative">
                  <MapPin class="absolute left-3.5 top-3 w-4 text-slate-400" />
                  <textarea
                    id="addr"
                    bind:value={form.address}
                    placeholder="Alamat lengkap penerima"
                    rows="3"
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 text-sm font-medium"
                  ></textarea>
                </div>
                {#if errors.address}
                  <p class="text-xs text-rose-500 font-medium">{errors.address}</p>
                {/if}
              </div>
            </div>
          </div>
        </div>
      {/if}
    </div>
  </div>
</AppLayout>

<style>
  @keyframes slide-in-from-top-2 {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
  }
  @keyframes slide-in-from-right-3 {
    from { opacity: 0; transform: translateX(12px); }
    to { opacity: 1; transform: translateX(0); }
  }
  .animate-in {
    animation-duration: 250ms;
    animation-timing-function: cubic-bezier(0.16, 1, 0.3, 1);
    animation-fill-mode: both;
  }
  .slide-in-from-top-2 { animation-name: slide-in-from-top-2; }
  .slide-in-from-right-3 { animation-name: slide-in-from-right-3; }
  .fade-in { animation-name: fade-in; }
  
  @keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
  }
</style>
