<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Card from '$lib/components/ui/card';
  import { CreditCard, Search, ArrowLeft, ChevronDown, ChevronUp, AlertCircle, Plus, Minus } from 'lucide-svelte';
  import { showToast } from '../../Stores/toast.js';
  import axios from 'axios';
  import { onMount } from 'svelte';

  export let banks = [];
  export let customers = [];
  export let selectedCustomerId = null;
  export let outstandingInvoices = [];
  export let errors = {};
  export let companies = [];
  export let active_company_id = null;

  // Form state
  let form = {
    customer_id: selectedCustomerId,
    paid_at: new Date().toISOString().split('T')[0],
    payment_method: 'transfer',
    bank_account_id: banks.find(b => b.is_default)?.id ?? (banks[0]?.id ?? null),
    reference: '',
    notes: '',
    allocations: [], // [{invoice_id, allocated_amount}]
  };

  let invoices = outstandingInvoices;
  let loading = false;
  let isSaving = false;

  // When customer changes, load outstanding invoices
  async function loadOutstanding() {
    if (!form.customer_id) { invoices = []; return; }
    loading = true;
    try {
      const res = await axios.get(`/api/payments/outstanding/${form.customer_id}`);
      invoices = res.data;
      // Reset allocations
      form.allocations = [];
    } catch {
      showToast('Gagal memuat invoice.', 'error');
    } finally {
      loading = false;
    }
  }

  function isAllocated(invoiceId) {
    return form.allocations.some(a => a.invoice_id === invoiceId);
  }

  function getAllocAmount(invoiceId) {
    return form.allocations.find(a => a.invoice_id === invoiceId)?.allocated_amount ?? 0;
  }

  function toggleInvoice(inv) {
    if (isAllocated(inv.id)) {
      form.allocations = form.allocations.filter(a => a.invoice_id !== inv.id);
    } else {
      form.allocations = [...form.allocations, { invoice_id: inv.id, allocated_amount: inv.outstanding }];
    }
  }

  function updateAlloc(invoiceId, val) {
    const amount = Math.max(0, parseFloat(val) || 0);
    form.allocations = form.allocations.map(a =>
      a.invoice_id === invoiceId ? { ...a, allocated_amount: amount } : a
    );
  }

  function payFull(inv) {
    if (!isAllocated(inv.id)) {
      form.allocations = [...form.allocations, { invoice_id: inv.id, allocated_amount: inv.outstanding }];
    } else {
      updateAlloc(inv.id, inv.outstanding);
    }
  }

  $: totalAllocated = form.allocations.reduce((s, a) => s + (parseFloat(a.allocated_amount) || 0), 0);
  $: totalAmount = totalAllocated;
  $: overpayment = Math.max(0, totalAmount - totalAllocated);

  function submit() {
    if (!form.customer_id) { showToast('Pilih customer terlebih dahulu', 'error'); return; }
    if (form.allocations.length === 0) { showToast('Pilih minimal satu invoice untuk dialokasikan', 'error'); return; }
    if (!form.bank_account_id) { showToast('Pilih bank/kas', 'error'); return; }

    isSaving = true;
    router.post('/payments', { ...form, total_amount: totalAmount }, {
      onSuccess: () => { isSaving = false; },
      onError: (e) => {
        isSaving = false;
        const msg = Object.values(e)[0];
        showToast(msg || 'Gagal menyimpan pembayaran.', 'error');
      },
    });
  }

  function fmt(n) {
    return 'Rp ' + Number(n).toLocaleString('id-ID');
  }

  // If customer was pre-selected
  onMount(() => {
    if (selectedCustomerId && outstandingInvoices.length === 0) {
      loadOutstanding();
    }
  });
</script>

<AppLayout title="Catat Pembayaran">
  <div class="max-w-4xl mx-auto px-4 py-8 space-y-6">

    <!-- Header -->
    <div class="flex items-center gap-3">
      <button class="p-2 rounded-lg hover:bg-slate-100 text-slate-500" on:click={() => router.visit('/payments')}>
        <ArrowLeft class="h-5 w-5" />
      </button>
      <div>
        <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
          <CreditCard class="h-6 w-6 text-teal-600" /> Catat Pembayaran
        </h1>
        <p class="text-sm text-slate-500">Pembayaran bisa untuk satu atau lebih invoice sekaligus</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- LEFT: Invoice Selection -->
      <div class="lg:col-span-2 space-y-4">

        <!-- Customer -->
        <Card.Root class="shadow-sm border border-slate-200/60">
          <Card.Header class="pb-3">
            <Card.Title class="text-sm font-bold text-slate-700 uppercase tracking-wider">1. Pilih Customer</Card.Title>
          </Card.Header>
          <Card.Content class="pt-0">
            <select bind:value={form.customer_id} on:change={loadOutstanding}
              class="w-full h-10 px-3 rounded-md border border-slate-200 bg-white text-sm outline-none focus:border-teal-500 shadow-sm">
              <option value={null}>-- Pilih Customer --</option>
              {#each customers as c}
                <option value={c.id}>{c.name}</option>
              {/each}
            </select>
          </Card.Content>
        </Card.Root>

        <!-- Outstanding Invoices -->
        <Card.Root class="shadow-sm border border-slate-200/60">
          <Card.Header class="pb-3">
            <Card.Title class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center justify-between">
              2. Pilih Invoice yang Dibayar
              {#if invoices.length > 0}
                <button class="text-xs font-normal text-teal-600 hover:underline" on:click={() => invoices.forEach(i => payFull(i))}>
                  Bayar Semua
                </button>
              {/if}
            </Card.Title>
          </Card.Header>
          <Card.Content class="pt-0">
            {#if loading}
              <div class="py-8 text-center text-slate-400 text-sm">Memuat invoice...</div>
            {:else if !form.customer_id}
              <div class="py-8 text-center text-slate-400 text-sm">Pilih customer terlebih dahulu</div>
            {:else if invoices.length === 0}
              <div class="py-8 text-center">
                <AlertCircle class="h-8 w-8 text-slate-300 mx-auto mb-2" />
                <p class="text-slate-400 text-sm">Tidak ada invoice outstanding untuk customer ini</p>
              </div>
            {:else}
              <div class="space-y-2">
                {#each invoices as inv}
                  {@const allocated = isAllocated(inv.id)}
                  <div class="rounded-lg border transition {allocated ? 'border-teal-300 bg-teal-50/50' : 'border-slate-200 bg-white'} p-3">
                    <div class="flex items-start gap-3">
                      <input type="checkbox" checked={allocated} on:change={() => toggleInvoice(inv)}
                        class="mt-0.5 rounded border-slate-300 text-teal-600 cursor-pointer" />
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                          <p class="text-sm font-semibold text-slate-800 truncate">{inv.invoice_text}</p>
                          <span class="text-xs px-2 py-0.5 rounded-full {inv.payment_status === 'partial' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-600'} shrink-0">
                            {inv.payment_status === 'partial' ? 'Sebagian' : 'Belum Bayar'}
                          </span>
                        </div>
                        <div class="flex items-center gap-4 mt-1 text-xs text-slate-500">
                          <span>Total: {fmt(inv.amount)}</span>
                          {#if inv.total_paid > 0}<span>Dibayar: {fmt(inv.total_paid)}</span>{/if}
                          <span class="font-semibold text-slate-700">Sisa: {fmt(inv.outstanding)}</span>
                        </div>

                        {#if allocated}
                          <div class="mt-2 flex items-center gap-2">
                            <label class="text-xs text-slate-500 shrink-0">Jumlah bayar:</label>
                            <div class="relative flex-1 max-w-xs">
                              <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400">Rp</span>
                              <input type="number" value={getAllocAmount(inv.id)}
                                on:input={(e) => updateAlloc(inv.id, e.target.value)}
                                min="0" max={inv.outstanding} step="1000"
                                class="w-full pl-8 pr-3 h-8 text-sm rounded-md border border-teal-300 focus:outline-none focus:border-teal-500 bg-white" />
                            </div>
                            <button class="text-xs text-teal-600 hover:underline shrink-0" on:click={() => payFull(inv)}>
                              Penuh
                            </button>
                          </div>
                        {/if}
                      </div>
                    </div>
                  </div>
                {/each}
              </div>
            {/if}
          </Card.Content>
        </Card.Root>
      </div>

      <!-- RIGHT: Payment Info -->
      <div class="space-y-4">

        <!-- Summary -->
        <Card.Root class="shadow-sm border border-teal-200 bg-teal-50/30">
          <Card.Header class="pb-3">
            <Card.Title class="text-sm font-bold text-slate-700 uppercase tracking-wider">Ringkasan</Card.Title>
          </Card.Header>
          <Card.Content class="pt-0 space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-slate-500">Invoice dipilih</span>
              <span class="font-semibold">{form.allocations.length}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-slate-500">Total alokasi</span>
              <span class="font-semibold text-slate-800">{fmt(totalAllocated)}</span>
            </div>
            <hr class="border-teal-200" />
            <div class="flex justify-between">
              <span class="text-sm font-bold text-slate-800">Total Dibayar</span>
              <span class="font-bold text-teal-700 text-base">{fmt(totalAmount)}</span>
            </div>
          </Card.Content>
        </Card.Root>

        <!-- Payment Details -->
        <Card.Root class="shadow-sm border border-slate-200/60">
          <Card.Header class="pb-3">
            <Card.Title class="text-sm font-bold text-slate-700 uppercase tracking-wider">Detail Pembayaran</Card.Title>
          </Card.Header>
          <Card.Content class="pt-0 space-y-3">

            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-600 uppercase">Tanggal Bayar</label>
              <Input type="date" bind:value={form.paid_at} class="h-9 text-sm" />
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-600 uppercase">Bank / Kas <span class="text-red-500">*</span></label>
              <select bind:value={form.bank_account_id} class="w-full h-9 px-3 rounded-md border border-slate-200 bg-white text-sm outline-none focus:border-teal-500">
                <option value={null}>-- Pilih Bank --</option>
                {#each banks as b}
                  <option value={b.id}>{b.name} {b.is_default ? '(Default)' : ''}</option>
                {/each}
              </select>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-600 uppercase">Metode Bayar</label>
              <select bind:value={form.payment_method} class="w-full h-9 px-3 rounded-md border border-slate-200 bg-white text-sm outline-none focus:border-teal-500">
                <option value="transfer">Transfer Bank</option>
                <option value="cash">Tunai</option>
                <option value="giro">Giro</option>
                <option value="cheque">Cek</option>
              </select>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-600 uppercase">No. Referensi</label>
              <Input bind:value={form.reference} placeholder="No. transfer / cek" class="h-9 text-sm" />
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-600 uppercase">Catatan</label>
              <textarea bind:value={form.notes} rows="2" placeholder="Catatan pembayaran..."
                class="w-full px-3 py-2 rounded-md border border-slate-200 bg-white text-sm outline-none focus:border-teal-500 resize-none"></textarea>
            </div>

          </Card.Content>
        </Card.Root>

        <Button class="w-full bg-teal-600 hover:bg-teal-700 text-white h-11 font-semibold text-base"
          on:click={submit} disabled={isSaving || form.allocations.length === 0}>
          {isSaving ? 'Menyimpan...' : 'Simpan Pembayaran'}
        </Button>
      </div>
    </div>
  </div>
</AppLayout>
