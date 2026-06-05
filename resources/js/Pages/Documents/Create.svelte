<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { cn } from '$lib/utils.js';
  import {
    FileText, Users, Receipt, ArrowLeft, Save, Trash2, Plus, 
    Layers, XCircle, Settings2, PackagePlus, ListChecks, Hash, Building2
  } from 'lucide-svelte';

  // ================================================
  // DUMMY DATA FOR SELECTS
  // ================================================
  let documentTypes = [
    { value: 'surat_tanda_terima', label: 'Surat Tanda Terima' },
    { value: 'list_kirim', label: 'List Kirim' },
    { value: 'surat_tagihan', label: 'Surat Tagihan' },
    { value: 'tukar_faktur', label: 'Tukar Faktur' }
  ];

  let accounts = [
    { id: 1, name: 'PT. Yudha Jakarta' },
    { id: 2, name: 'PT. Yudha Surabaya' }
  ];

  let invoices = [
    { id: 1012, number: 'INV-202605001', orderNumber: 'PO-2026-098', customerName: 'PT. BARUNA LOGISTIK' },
    { id: 1013, number: 'INV-202605002', orderNumber: 'PO-2026-099', customerName: 'SOOPLAI CORP' },
    { id: 1014, number: 'INV-202605003', orderNumber: 'PO-2026-100', customerName: 'AB SHOP' },
    { id: 1015, number: 'INV-202605004', orderNumber: 'PO-2026-101', customerName: 'ACHAN FARM' }
  ];

  // ================================================
  // FORM STATE
  // ================================================
  let form = {
    document_type: '',
    account_id: '',
    items: []
  };

  // Temporary selection state before adding to items table
  let selectedInvoiceId = '';
  let tempInvoiceId = '';
  let tempCustomerName = '';
  let tempInvoiceNumber = '';
  let tempOrderNumber = '';

  // Reactive matching of selected invoice details
  $: {
    const inv = invoices.find(i => i.id === parseInt(selectedInvoiceId));
    if (inv) {
      tempInvoiceId = inv.id.toString();
      tempCustomerName = inv.customerName;
      tempInvoiceNumber = inv.number;
      tempOrderNumber = inv.orderNumber;
    } else {
      tempInvoiceId = '';
      tempCustomerName = '';
      tempInvoiceNumber = '';
      tempOrderNumber = '';
    }
  }

  function addItem() {
    if (!selectedInvoiceId) return;
    if (form.items.some(item => item.id === tempInvoiceId)) return;

    form.items = [...form.items, {
      id: tempInvoiceId,
      invoiceNumber: tempInvoiceNumber,
      orderNumber: tempOrderNumber,
      customerName: tempCustomerName,
      bpb: '' // User can type this in the table row
    }];

    selectedInvoiceId = ''; // Reset
  }

  function removeItem(id) {
    form.items = form.items.filter(item => item.id !== id);
  }

  let submitting = false;
  let errors = {};

  function submit() {
    submitting = true;
    router.post('/documents', form, {
      onError: (e) => { errors = e; submitting = false; },
      onSuccess: () => { submitting = false; }
    });
  }
</script>

<AppLayout>
  <div class="max-w-5xl mx-auto space-y-8 pb-12">
    
    <!-- Header Section -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <button
          class="inline-flex items-center justify-center h-10 w-10 rounded-xl border border-slate-200 bg-white shadow-sm text-slate-500 hover:bg-slate-50 hover:text-slate-900 transition-all duration-200 hover:shadow"
          on:click={() => router.visit('/documents')}
        >
          <ArrowLeft class="h-4 w-4" />
        </button>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900">Create New Document</h1>
          <p class="text-sm text-slate-500 mt-1">Generate lists or receipt documents based on invoices.</p>
        </div>
      </div>
      
      <!-- Top Action -->
      <button
        class="hidden sm:flex items-center gap-2 bg-gradient-to-r from-teal-500 to-emerald-500 hover:from-teal-600 hover:to-emerald-600 text-white px-6 h-10 rounded-xl shadow-md shadow-emerald-500/20 hover:shadow-emerald-500/40 transition-all duration-300 font-medium"
        on:click={submit}
        disabled={submitting || form.items.length === 0}
      >
        <Save class="h-4 w-4" />
        {submitting ? 'Saving...' : 'Save Document'}
      </button>
    </div>

    <div class="space-y-6">
      <!-- CARD 1: Document Configuration -->
      <div class="bg-white/80 backdrop-blur-xl border-0 ring-1 ring-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center gap-2">
          <Settings2 class="h-5 w-5 text-teal-600" />
          <h3 class="font-semibold text-slate-800">Configuration</h3>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700" for="doc_type">
              Jenis Dokumen <span class="text-rose-500">*</span>
            </label>
            <div class="relative group">
              <FileText class="absolute left-3.5 top-3 h-4 w-4 text-slate-400 group-focus-within:text-teal-500 transition-colors z-10" />
              <select
                id="doc_type"
                bind:value={form.document_type}
                class={cn("w-full h-11 pl-10 pr-10 rounded-xl bg-slate-50/50 border outline-none transition-all duration-200 focus:bg-white focus:ring-4 appearance-none relative", errors.document_type ? "border-rose-300 focus:border-rose-500 focus:ring-rose-500/20" : "border-slate-200 focus:border-teal-500 focus:ring-teal-500/20")}
              >
                <option value="" disabled hidden>Select document type...</option>
                {#each documentTypes as type}
                  <option value={type.value}>{type.label}</option>
                {/each}
              </select>
              <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
              </div>
            </div>
            {#if errors.document_type}
              <p class="text-xs text-rose-500 font-medium animate-in slide-in-from-top-1">{errors.document_type}</p>
            {/if}
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700" for="account_id">
              Account
            </label>
            <div class="relative group">
              <Users class="absolute left-3.5 top-3 h-4 w-4 text-slate-400 group-focus-within:text-teal-500 transition-colors z-10" />
              <select
                id="account_id"
                bind:value={form.account_id}
                class={cn("w-full h-11 pl-10 pr-10 rounded-xl bg-slate-50/50 border outline-none transition-all duration-200 focus:bg-white focus:ring-4 appearance-none relative", errors.account_id ? "border-rose-300 focus:border-rose-500 focus:ring-rose-500/20" : "border-slate-200 focus:border-teal-500 focus:ring-teal-500/20")}
              >
                <option value="" disabled hidden>Select account...</option>
                {#each accounts as acc}
                  <option value={acc.id}>{acc.name}</option>
                {/each}
              </select>
              <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
              </div>
            </div>
            {#if errors.account_id}
              <p class="text-xs text-rose-500 font-medium animate-in slide-in-from-top-1">{errors.account_id}</p>
            {/if}
          </div>
        </div>
      </div>

      <!-- CARD 2: Invoice Selection -->
      <div class="bg-white/80 backdrop-blur-xl border-0 ring-1 ring-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <PackagePlus class="h-5 w-5 text-teal-600" />
            <h3 class="font-semibold text-slate-800">Add Invoice</h3>
          </div>
        </div>
        
        <div class="p-6">
          <div class="flex flex-col md:flex-row gap-6">
            <!-- Select Input -->
            <div class="flex-1 space-y-2">
              <label class="text-sm font-semibold text-slate-700" for="invoice_select">Find Invoice</label>
              <div class="relative group">
                <Receipt class="absolute left-3.5 top-3 h-4 w-4 text-slate-400 group-focus-within:text-teal-500 transition-colors z-10" />
                <select
                  id="invoice_select"
                  bind:value={selectedInvoiceId}
                  class="w-full h-11 pl-10 pr-10 rounded-xl bg-slate-50/50 border border-slate-200 outline-none transition-all duration-200 focus:bg-white focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 appearance-none relative font-medium text-slate-700"
                >
                  <option value="" disabled hidden>-- Select an invoice to add --</option>
                  {#each invoices as inv}
                    <option value={inv.id}>{inv.number} &mdash; {inv.customerName}</option>
                  {/each}
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-400">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
              </div>
            </div>

            <!-- Preview Card (if selected) -->
            <div class="flex-1">
              {#if selectedInvoiceId}
                <div class="h-full rounded-xl border border-teal-100 bg-teal-50/30 p-4 flex flex-col justify-center animate-in fade-in zoom-in-95 duration-200">
                  <div class="grid grid-cols-2 gap-y-3 gap-x-4">
                    <div>
                      <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Customer</span>
                      <span class="text-sm font-bold text-slate-800 flex items-center gap-1.5 mt-0.5"><Building2 class="h-3.5 w-3.5 text-teal-600" /> {tempCustomerName}</span>
                    </div>
                    <div>
                      <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Inv No.</span>
                      <span class="text-sm font-bold text-slate-800 flex items-center gap-1.5 mt-0.5"><Hash class="h-3.5 w-3.5 text-teal-600" /> {tempInvoiceNumber}</span>
                    </div>
                  </div>
                </div>
              {:else}
                <div class="h-full rounded-xl border border-dashed border-slate-200 bg-slate-50/50 p-4 flex items-center justify-center text-slate-400 text-sm">
                  No invoice selected
                </div>
              {/if}
            </div>

            <!-- Add Button -->
            <div class="flex items-end">
              <button
                type="button"
                class="h-11 px-6 rounded-xl font-semibold flex items-center justify-center gap-2 transition-all duration-300 w-full md:w-auto shadow-sm disabled:opacity-50 disabled:cursor-not-allowed bg-emerald-600 text-white hover:bg-emerald-700 hover:shadow-emerald-500/20 hover:shadow-md"
                on:click={addItem}
                disabled={!selectedInvoiceId}
              >
                <Plus class="h-4 w-4" />
                Add to List
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- CARD 3: Items Table -->
      <div class="bg-white/80 backdrop-blur-xl border-0 ring-1 ring-slate-200/60 rounded-2xl shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <ListChecks class="h-5 w-5 text-teal-600" />
            <h3 class="font-semibold text-slate-800">Document Items</h3>
          </div>
          <span class="bg-teal-100 text-teal-800 text-xs font-bold px-2.5 py-0.5 rounded-full">{form.items.length} Items</span>
        </div>
        
        <div class="w-full overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500 border-b border-slate-100">
              <tr>
                <th class="px-6 py-3 font-semibold text-center w-16">ID</th>
                <th class="px-6 py-3 font-semibold">Inv Number</th>
                <th class="px-6 py-3 font-semibold">Order Number</th>
                <th class="px-6 py-3 font-semibold">Customer</th>
                <th class="px-6 py-3 font-semibold w-48">BPB</th>
                <th class="px-6 py-3 font-semibold text-center w-20">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              {#each form.items as item, i (item.id)}
                <tr class="hover:bg-slate-50/80 transition-colors group">
                  <td class="px-6 py-3 text-center text-xs font-mono font-medium text-slate-400">{item.id}</td>
                  <td class="px-6 py-3 font-semibold text-slate-800">{item.invoiceNumber}</td>
                  <td class="px-6 py-3">{item.orderNumber}</td>
                  <td class="px-6 py-3 font-medium text-slate-700">{item.customerName}</td>
                  <td class="px-6 py-2">
                    <input
                      type="text"
                      placeholder="Enter BPB..."
                      bind:value={item.bpb}
                      class="w-full h-9 px-3 rounded-lg bg-white border border-slate-200 outline-none transition-all duration-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 text-sm shadow-sm"
                    />
                  </td>
                  <td class="px-6 py-2 text-center">
                    <button
                      type="button"
                      class="h-8 w-8 rounded-lg inline-flex items-center justify-center text-slate-400 hover:text-rose-500 hover:bg-rose-50 transition-colors"
                      on:click={() => removeItem(item.id)}
                      title="Remove Item"
                    >
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </td>
                </tr>
              {/each}
              
              {#if form.items.length === 0}
                <tr>
                  <td colspan="6">
                    <div class="flex flex-col items-center justify-center py-12 text-slate-400">
                      <div class="h-12 w-12 rounded-full bg-slate-50 flex items-center justify-center mb-3">
                        <Layers class="h-6 w-6 text-slate-300" />
                      </div>
                      <p class="text-sm font-medium text-slate-500">No invoices added yet</p>
                      <p class="text-xs mt-1 max-w-sm text-center">Select an invoice from the dropdown above and click "Add to List" to build your document.</p>
                    </div>
                  </td>
                </tr>
              {/if}
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <!-- Mobile Bottom Action -->
    <div class="sm:hidden mt-8 grid grid-cols-2 gap-3">
      <button
        class="w-full flex items-center justify-center gap-2 border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 px-4 h-12 rounded-xl shadow-sm transition-all font-medium"
        on:click={() => router.visit('/documents')}
        disabled={submitting}
      >
        <XCircle class="h-4 w-4" />
        Cancel
      </button>
      <button
        class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-teal-500 to-emerald-500 hover:from-teal-600 hover:to-emerald-600 text-white px-4 h-12 rounded-xl shadow-md shadow-emerald-500/20 transition-all font-medium disabled:opacity-50"
        on:click={submit}
        disabled={submitting || form.items.length === 0}
      >
        <Save class="h-4 w-4" />
        {submitting ? 'Saving...' : 'Save'}
      </button>
    </div>

  </div>
</AppLayout>

<style>
  @keyframes slide-in-from-top-1 {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .animate-in {
    animation-duration: 200ms;
    animation-timing-function: cubic-bezier(0.16, 1, 0.3, 1);
    animation-fill-mode: both;
  }
  .slide-in-from-top-1 { animation-name: slide-in-from-top-1; }
  .fade-in { animation-name: fade-in; }
  .zoom-in-95 { animation-name: zoom-in-95; }
  
  @keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
  }
  @keyframes zoom-in-95 {
    from { transform: scale(0.95); }
    to { transform: scale(1); }
  }
</style>
