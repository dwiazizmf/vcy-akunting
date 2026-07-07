<script>
  import AppLayout from '../../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import * as Card from '$lib/components/ui/card';
  import * as Table from '$lib/components/ui/table';
  import { ArrowLeft, Printer, CreditCard, Building2, BookOpen } from 'lucide-svelte';

  export let payment = {};
  export let allocations = [];
  export let journal = null;
  export let companies = [];
  export let active_company_id = null;

  function fmt(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
  }

  $: totalAllocated = allocations.reduce((sum, a) => sum + parseFloat(a.allocated_amount || 0), 0);

  const methodLabel = { cash: 'Tunai', transfer: 'Transfer Bank', giro: 'Giro', cheque: 'Cek' };

  function print() {
    window.print();
  }
</script>

<AppLayout title="Detail Pembayaran">
  <div class="max-w-4xl mx-auto px-4 py-8 space-y-6 print:p-0">

    <!-- Header -->
    <div class="flex items-center justify-between print:hidden">
      <div class="flex items-center gap-3">
        <button class="p-2 rounded-lg hover:bg-slate-100 text-slate-500" on:click={() => router.visit('/payments')}>
          <ArrowLeft class="h-5 w-5" />
        </button>
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Bukti Penerimaan Kas</h1>
          <p class="text-sm text-slate-500">{payment.payment_number}</p>
        </div>
      </div>
      <Button variant="outline" class="flex items-center gap-2" on:click={print}>
        <Printer class="h-4 w-4" /> Cetak
      </Button>
    </div>

    <!-- Kwitansi -->
    <Card.Root class="shadow-sm border border-slate-200/60">
      <Card.Content class="p-8">

        <!-- Print Header -->
        <div class="hidden print:flex justify-between items-start mb-6 pb-4 border-b">
          <div>
            <h2 class="text-xl font-bold text-slate-900">BUKTI PENERIMAAN KAS</h2>
            <p class="text-sm text-slate-500">No: {payment.payment_number}</p>
          </div>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-2 gap-6 mb-6">
          <div class="space-y-3">
            <div>
              <p class="text-xs text-slate-400 uppercase tracking-wider">No. Kwitansi</p>
              <p class="font-mono font-bold text-teal-700 text-lg">{payment.payment_number}</p>
            </div>
            <div>
              <p class="text-xs text-slate-400 uppercase tracking-wider">Customer</p>
              <p class="font-semibold text-slate-800">{payment.customer_name}</p>
            </div>
            <div>
              <p class="text-xs text-slate-400 uppercase tracking-wider">Tanggal Bayar</p>
              <p class="font-medium text-slate-700">{payment.paid_at}</p>
            </div>
          </div>
          <div class="space-y-3">
            <div>
              <p class="text-xs text-slate-400 uppercase tracking-wider">Bank / Kas</p>
              <p class="font-medium text-slate-700">{payment.bank_name}</p>
            </div>
            <div>
              <p class="text-xs text-slate-400 uppercase tracking-wider">Metode Pembayaran</p>
              <p class="font-medium text-slate-700">{methodLabel[payment.payment_method] || payment.payment_method}</p>
            </div>
            {#if payment.reference}
              <div>
                <p class="text-xs text-slate-400 uppercase tracking-wider">No. Referensi</p>
                <p class="font-mono text-slate-700">{payment.reference}</p>
              </div>
            {/if}
          </div>
        </div>

        <!-- Allocation Table -->
        <div class="rounded-lg border border-slate-200 overflow-hidden mb-6">
          <table class="w-full text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="text-left px-4 py-2 text-xs font-bold uppercase text-slate-500">Invoice</th>
                <th class="text-right px-4 py-2 text-xs font-bold uppercase text-slate-500">Jumlah Dibayar</th>
              </tr>
            </thead>
            <tbody>
              {#each allocations as alloc}
                <tr class="border-t border-slate-100">
                  <td class="px-4 py-2.5">
                    <p class="font-medium text-slate-800">{alloc.invoice_text}</p>
                    {#if alloc.customer_name}<p class="text-xs text-teal-600 mt-0.5 font-medium">{alloc.customer_name}</p>{/if}
                    {#if alloc.invoiced_at}<p class="text-xs text-slate-400">Tgl: {alloc.invoiced_at}</p>{/if}
                  </td>
                  <td class="px-4 py-2.5 text-right font-semibold text-slate-800">{fmt(alloc.allocated_amount)}</td>
                </tr>
              {/each}
            </tbody>
            <tfoot class="bg-teal-50 border-t-2 border-teal-200">
              <tr>
                <td class="px-4 py-2 text-sm font-semibold text-slate-700">Total Alokasi Tagihan</td>
                <td class="px-4 py-2 text-right text-sm font-semibold text-slate-700">{fmt(totalAllocated)}</td>
              </tr>

              {#if payment.tax_amount > 0}
                <tr class="border-t border-teal-200/50">
                  <td class="px-4 py-2 text-sm text-teal-700 font-medium">PPN {payment.tax_name ? `(${payment.tax_name})` : ''} (Legacy)</td>
                  <td class="px-4 py-2 text-right text-sm font-medium text-teal-700">+{fmt(payment.tax_amount)}</td>
                </tr>
              {/if}

              {#if payment.adjustments && payment.adjustments.length > 0}
                {#each payment.adjustments as adj}
                  <tr class="border-t border-teal-200/50">
                    <td class="px-4 py-2 text-sm font-medium {adj.type === 'addition' ? 'text-rose-600' : 'text-teal-700'}">
                      {adj.account_name} 
                      {#if adj.description}<span class="text-[11px] text-slate-500 font-normal ml-1">({adj.description})</span>{/if}
                    </td>
                    <td class="px-4 py-2 text-right text-sm font-medium {adj.type === 'addition' ? 'text-rose-600' : 'text-teal-700'}">
                      {adj.type === 'addition' ? '+' : '-'} {fmt(adj.amount)}
                    </td>
                  </tr>
                {/each}
              {/if}

              <tr class="border-t-2 border-teal-200">
                <td class="px-4 py-3 font-bold text-teal-800">Total Diterima (Bank/Kas)</td>
                <td class="px-4 py-3 text-right font-bold text-teal-800 text-base">{fmt(payment.total_amount)}</td>
              </tr>
              {#if payment.overpayment > 0}
                <tr class="border-t border-teal-200">
                  <td class="px-4 py-2 text-sm text-amber-700">Lebih Bayar (Titipan)</td>
                  <td class="px-4 py-2 text-right text-sm font-medium text-amber-700">{fmt(payment.overpayment)}</td>
                </tr>
              {/if}
            </tfoot>
          </table>
        </div>

        {#if payment.notes}
          <div class="mb-6 p-3 bg-slate-50 rounded-lg">
            <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Catatan</p>
            <p class="text-sm text-slate-600">{payment.notes}</p>
          </div>
        {/if}

        <!-- Signature area for print -->
        <div class="hidden print:grid grid-cols-2 gap-8 mt-10">
          <div class="text-center">
            <div class="h-16"></div>
            <p class="text-sm border-t border-slate-300 pt-2">Penerima</p>
          </div>
          <div class="text-center">
            <div class="h-16"></div>
            <p class="text-sm border-t border-slate-300 pt-2">Customer</p>
          </div>
        </div>
      </Card.Content>
    </Card.Root>

    <!-- Journal Entries (informasi akuntansi) -->
    {#if journal}
      <Card.Root class="shadow-sm border border-slate-200/60 print:hidden">
        <Card.Header>
          <Card.Title class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
            <BookOpen class="h-4 w-4 text-teal-600" /> Jurnal Akuntansi — {journal.journal_number}
          </Card.Title>
        </Card.Header>
        <Card.Content class="pt-0">
          <Table.Root>
            <Table.Header class="bg-slate-50">
              <Table.Row>
                <Table.Head class="text-xs font-bold uppercase text-slate-500">Akun</Table.Head>
                <Table.Head class="text-xs font-bold uppercase text-slate-500">Keterangan</Table.Head>
                <Table.Head class="text-xs font-bold uppercase text-slate-500 text-right">Debit</Table.Head>
                <Table.Head class="text-xs font-bold uppercase text-slate-500 text-right">Kredit</Table.Head>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              {#each journal.ledgers as ledger}
                <Table.Row class="hover:bg-slate-50/50">
                  <Table.Cell class="font-mono text-xs font-medium text-slate-700">{ledger.account}</Table.Cell>
                  <Table.Cell class="text-slate-500 text-xs">{ledger.description}</Table.Cell>
                  <Table.Cell class="text-right font-semibold text-slate-800">
                    {ledger.debit > 0 ? fmt(ledger.debit) : '-'}
                  </Table.Cell>
                  <Table.Cell class="text-right font-semibold text-slate-800">
                    {ledger.credit > 0 ? fmt(ledger.credit) : '-'}
                  </Table.Cell>
                </Table.Row>
              {/each}
            </Table.Body>
          </Table.Root>
        </Card.Content>
      </Card.Root>
    {/if}

  </div>
</AppLayout>
