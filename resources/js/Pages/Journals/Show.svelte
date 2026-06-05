<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { Button } from '$lib/components/ui/button';
  import * as Table from '$lib/components/ui/table';
  import { ArrowLeft, Printer, CheckCircle, XCircle } from 'lucide-svelte';
  import { router } from '@inertiajs/svelte';
  import { showToast } from '../../Stores/toast.js';
  import { showConfirm } from '../../Stores/confirmStore.js';

  export let journal;

  $: ledgers = journal.ledgers || [];
  $: totalDebit = ledgers.reduce((sum, line) => sum + parseFloat(line.debit), 0);
  $: totalCredit = ledgers.reduce((sum, line) => sum + parseFloat(line.credit), 0);

  function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  }

  function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('id-ID', { 
      day: '2-digit', month: 'long', year: 'numeric' 
    });
  }

  async function updateStatus(newStatus) {
    if (await showConfirm(`Apakah Anda yakin ingin menandai jurnal ini sebagai ${newStatus.toUpperCase()}?`)) {
      router.patch(`/journals/${journal.id}/status`, { status: newStatus }, {
        preserveScroll: true,
        onSuccess: () => {
          showToast(`Jurnal berhasil di-${newStatus}`, 'success');
        },
        onError: () => {
          showToast(`Gagal mengupdate status jurnal`, 'error');
        }
      });
    }
  }
</script>

<svelte:head>
  <title>Detail Jurnal: {journal.journal_number} - VCY Accounting</title>
</svelte:head>

<AppLayout>
  <div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div class="flex items-center gap-4">
        <Button variant="outline" size="icon" href="/journals">
          <ArrowLeft size={16} />
        </Button>
        <div>
          <h2 class="text-2xl font-bold tracking-tight text-slate-900">
            Jurnal: {journal.journal_number}
          </h2>
          <div class="flex items-center gap-2 mt-1">
            <span class="text-sm text-slate-500">Dibuat pada {formatDate(journal.created_at)}</span>
            {#if journal.status === 'posted'}
              <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-teal-50 text-teal-700 text-xs font-medium ring-1 ring-inset ring-teal-600/20">
                Posted
              </span>
            {:else if journal.status === 'draft'}
              <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-medium ring-1 ring-inset ring-amber-500/20">
                Draft
              </span>
            {:else}
              <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-xs font-medium ring-1 ring-inset ring-red-600/20">
                Void
              </span>
            {/if}
          </div>
        </div>
      </div>
      <div class="flex gap-2">
        <Button variant="outline" class="gap-2 bg-white" on:click={() => window.print()}>
          <Printer size={16} /> Cetak
        </Button>
        {#if journal.status === 'draft'}
          <Button class="bg-teal-600 hover:bg-teal-700 text-white gap-2" on:click={() => updateStatus('posted')}>
            <CheckCircle size={16} /> Post Jurnal
          </Button>
        {/if}
        {#if journal.status !== 'void'}
          <Button variant="outline" class="text-red-600 hover:text-red-700 hover:bg-red-50 gap-2 border-red-200" on:click={() => updateStatus('void')}>
            <XCircle size={16} /> Void Jurnal
          </Button>
        {/if}
      </div>
    </div>

    <!-- Header Details -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div>
          <p class="text-sm font-medium text-slate-500">Tanggal Transaksi</p>
          <p class="mt-1 text-slate-900 font-medium">{formatDate(journal.date)}</p>
        </div>
        <div>
          <p class="text-sm font-medium text-slate-500">No. Referensi</p>
          <p class="mt-1 text-slate-900 font-medium">{journal.reference || '-'}</p>
        </div>
        <div>
          <p class="text-sm font-medium text-slate-500">Pembuat</p>
          <p class="mt-1 text-slate-900 font-medium">{journal.posted_by?.name || 'System / Admin'}</p>
        </div>
        <div>
          <p class="text-sm font-medium text-slate-500">Status</p>
          <p class="mt-1 text-slate-900 font-medium capitalize">{journal.status}</p>
        </div>
        <div class="md:col-span-2 lg:col-span-4 pt-4 border-t border-slate-100">
          <p class="text-sm font-medium text-slate-500">Keterangan / Deskripsi</p>
          <p class="mt-1 text-slate-900">{journal.description}</p>
        </div>
      </div>
    </div>

    <!-- Ledger Lines -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-5 border-b border-slate-100 bg-slate-50">
        <h3 class="font-semibold text-slate-800">Detail Transaksi (Buku Besar)</h3>
      </div>
      <div class="overflow-x-auto">
        <Table.Root>
          <Table.Header>
            <Table.Row>
              <Table.Head class="w-[30%]">Akun</Table.Head>
              <Table.Head class="w-[20%]">Kontak</Table.Head>
              <Table.Head class="w-[20%]">Keterangan Baris</Table.Head>
              <Table.Head class="w-[15%] text-right">Debit</Table.Head>
              <Table.Head class="w-[15%] text-right">Credit</Table.Head>
            </Table.Row>
          </Table.Header>
          <Table.Body>
            {#each ledgers as line}
              <Table.Row>
                <Table.Cell class="py-3">
                  <div class="font-medium text-slate-900">{line.account?.code}</div>
                  <div class="text-sm text-slate-500">{line.account?.name}</div>
                </Table.Cell>
                <Table.Cell class="py-3 text-slate-600">
                  {line.contact?.name || '-'}
                </Table.Cell>
                <Table.Cell class="py-3 text-slate-600">
                  {line.description || '-'}
                </Table.Cell>
                <Table.Cell class="py-3 text-right font-medium text-slate-900">
                  {line.debit > 0 ? formatCurrency(line.debit) : '-'}
                </Table.Cell>
                <Table.Cell class="py-3 text-right font-medium text-slate-900">
                  {line.credit > 0 ? formatCurrency(line.credit) : '-'}
                </Table.Cell>
              </Table.Row>
            {/each}
          </Table.Body>
          <!-- Table Footer for Totals -->
          <Table.Footer class="bg-slate-50 border-t-2 border-slate-200 font-semibold text-slate-900">
            <Table.Row>
              <Table.Cell colspan={3} class="text-right py-4 text-slate-600">Total Transaksi</Table.Cell>
              <Table.Cell class="text-right py-4">{formatCurrency(totalDebit)}</Table.Cell>
              <Table.Cell class="text-right py-4">{formatCurrency(totalCredit)}</Table.Cell>
            </Table.Row>
          </Table.Footer>
        </Table.Root>
      </div>
    </div>
  </div>
</AppLayout>

<style>
  @media print {
    :global(body) {
      background-color: white !important;
    }
    :global(nav), :global(header) {
      display: none !important;
    }
    .shadow-sm {
      box-shadow: none !important;
    }
    .border-slate-200 {
      border-color: #e2e8f0 !important;
    }
    Button {
      display: none !important;
    }
  }
</style>
