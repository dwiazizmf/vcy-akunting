<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Card from '$lib/components/ui/card';
  import * as Table from '$lib/components/ui/table';
  import { cn } from '$lib/utils.js';
  import {
    FileText, Users, Receipt, CreditCard, User, Hash, Plus, Trash2, Save, XCircle, ArrowLeft
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

    // Check if already in the list
    if (form.items.some(item => item.id === tempInvoiceId)) {
      return;
    }

    form.items = [...form.items, {
      id: tempInvoiceId,
      invoiceNumber: tempInvoiceNumber,
      orderNumber: tempOrderNumber,
      customerName: tempCustomerName,
      bpb: '' // User can type this in the table row
    }];

    // Reset selection inputs
    selectedInvoiceId = '';
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
  <div class="max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-3">
      <button
        class="inline-flex items-center justify-center h-9 w-9 rounded-lg border border-slate-200 bg-white shadow-sm text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors"
        on:click={() => router.visit('/documents')}
      >
        <ArrowLeft class="h-4 w-4" />
      </button>
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Create New Dokumen</h1>
      </div>
    </div>

    <!-- Main Card -->
    <Card.Root class="bg-white border-slate-200 border-t-4 border-t-emerald-600 shadow-sm">
      <Card.Content class="p-6 space-y-6">
        
        <!-- ROW 1: Jenis Dokumen & Account -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Jenis Dokumen</label>
            <div class="relative">
              <FileText class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <select
                bind:value={form.document_type}
                class="w-full h-9 pl-9 pr-3 rounded-md border border-slate-200 bg-white text-sm outline-none focus:border-emerald-600 shadow-sm transition-colors"
              >
                <option value="">-Pilih Jenis-</option>
                {#each documentTypes as type}
                  <option value={type.value}>{type.label}</option>
                {/each}
              </select>
            </div>
            {#if errors.document_type}
              <p class="text-xs text-red-500 mt-1">{errors.document_type}</p>
            {/if}
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Account</label>
            <div class="relative">
              <Users class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <select
                bind:value={form.account_id}
                class="w-full h-9 pl-9 pr-3 rounded-md border border-slate-200 bg-white text-sm outline-none focus:border-emerald-600 shadow-sm transition-colors"
              >
                <option value="">-Select Account-</option>
                {#each accounts as acc}
                  <option value={acc.id}>{acc.name}</option>
                {/each}
              </select>
            </div>
            {#if errors.account_id}
              <p class="text-xs text-red-500 mt-1">{errors.account_id}</p>
            {/if}
          </div>
        </div>

        <!-- ROW 2: Invoice & ID Inv -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Invoice</label>
            <div class="relative">
              <Receipt class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <select
                bind:value={selectedInvoiceId}
                class="w-full h-9 pl-9 pr-3 rounded-md border border-slate-200 bg-white text-sm outline-none focus:border-emerald-600 shadow-sm transition-colors"
              >
                <option value="">-Select Invoice-</option>
                {#each invoices as inv}
                  <option value={inv.id}>{inv.number} - {inv.customerName}</option>
                {/each}
              </select>
            </div>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">ID Inv</label>
            <div class="relative">
              <CreditCard class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input
                type="text"
                readonly
                value={tempInvoiceId}
                class="pl-9 bg-slate-50 border-slate-200 shadow-sm h-9 text-sm text-slate-500 select-none"
              />
            </div>
          </div>
        </div>

        <!-- ROW 3: Customer Name & Inv Number -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Customer Name</label>
            <div class="relative">
              <User class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input
                type="text"
                readonly
                value={tempCustomerName}
                class="pl-9 bg-slate-50 border-slate-200 shadow-sm h-9 text-sm text-slate-500 select-none"
              />
            </div>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Inv Number</label>
            <div class="relative">
              <Hash class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input
                type="text"
                readonly
                value={tempInvoiceNumber}
                class="pl-9 bg-slate-50 border-slate-200 shadow-sm h-9 text-sm text-slate-500 select-none"
              />
            </div>
          </div>
        </div>

        <!-- Add Button -->
        <div>
          <Button
            type="button"
            class="bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm font-semibold flex items-center gap-1.5 px-4 h-9"
            on:click={addItem}
            disabled={!selectedInvoiceId}
          >
            <Plus class="h-4 w-4" />
            Add
          </Button>
        </div>

        <!-- Items Table Section -->
        <div class="space-y-3 pt-4">
          <h3 class="text-sm font-bold text-slate-900 tracking-tight">Items</h3>
          
          <div class="w-full overflow-x-auto border border-slate-200 rounded-md">
            <Table.Root>
              <Table.Header class="bg-slate-50/50">
                <Table.Row class="hover:bg-transparent">
                  <Table.Head class="font-bold text-slate-700 text-xs py-2.5 text-center w-[15%]">ID Inv</Table.Head>
                  <Table.Head class="font-bold text-slate-700 text-xs py-2.5 text-center w-[20%]">Inv Number</Table.Head>
                  <Table.Head class="font-bold text-slate-700 text-xs py-2.5 text-center w-[20%]">Order Number</Table.Head>
                  <Table.Head class="font-bold text-slate-700 text-xs py-2.5 text-center w-[25%]">Customer Name</Table.Head>
                  <Table.Head class="font-bold text-slate-700 text-xs py-2.5 text-center w-[15%]">BPB</Table.Head>
                  <Table.Head class="font-bold text-slate-700 text-xs py-2.5 text-center w-[5%]">Action</Table.Head>
                </Table.Row>
              </Table.Header>
              <Table.Body>
                {#each form.items as item, i (item.id)}
                  <Table.Row class="hover:bg-slate-50 transition-colors">
                    <Table.Cell class="p-2 text-center text-xs font-mono text-slate-500">{item.id}</Table.Cell>
                    <Table.Cell class="p-2 text-center text-xs font-semibold text-slate-800">{item.invoiceNumber}</Table.Cell>
                    <Table.Cell class="p-2 text-center text-xs text-slate-600">{item.orderNumber}</Table.Cell>
                    <Table.Cell class="p-2 text-center text-xs text-slate-800 font-medium">{item.customerName}</Table.Cell>
                    <Table.Cell class="p-2">
                      <Input
                        type="text"
                        placeholder="Enter BPB"
                        bind:value={item.bpb}
                        class="h-8 text-xs bg-white text-center shadow-inner focus-visible:ring-emerald-500/20"
                      />
                    </Table.Cell>
                    <Table.Cell class="p-2 text-center">
                      <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8 text-red-500 hover:text-red-700 hover:bg-red-50"
                        on:click={() => removeItem(item.id)}
                      >
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </Table.Cell>
                  </Table.Row>
                {/each}

                {#if form.items.length === 0}
                  <Table.Row class="hover:bg-transparent">
                    <Table.Cell colspan="6" class="text-center py-10 text-slate-400 text-xs">
                      Belum ada invoice yang ditambahkan. Silakan pilih invoice di atas dan klik tombol Add.
                    </Table.Cell>
                  </Table.Row>
                {/if}
              </Table.Body>
            </Table.Root>
          </div>
        </div>

        <!-- Submit Section -->
        <div class="pt-4 border-t border-slate-100 flex gap-2">
          <Button
            class="bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm font-semibold flex items-center gap-1.5 px-6"
            on:click={submit}
            disabled={submitting || form.items.length === 0}
          >
            <Save class="h-4 w-4" />
            {submitting ? 'Saving...' : 'Save'}
          </Button>
          <Button
            variant="outline"
            class="border-slate-200 text-slate-600 hover:bg-slate-50 flex items-center gap-1.5"
            on:click={() => router.visit('/documents')}
            disabled={submitting}
          >
            <XCircle class="h-4 w-4" />
            Cancel
          </Button>
        </div>

      </Card.Content>
    </Card.Root>
  </div>
</AppLayout>
