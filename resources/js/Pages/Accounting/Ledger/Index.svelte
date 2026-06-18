<script>
  import AppLayout from '../../../Layouts/AppLayout.svelte';
  import CoaSelect from '../../../Components/CoaSelect.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Card from '$lib/components/ui/card';
  import * as Table from '$lib/components/ui/table';
  import { Printer, Download, Search, Filter } from 'lucide-svelte';

  export let accounts = [];
  export let selectedAccount = null;
  export let openingBalance = 0;
  export let closingBalance = 0;
  export let totalDebit = 0;
  export let totalCredit = 0;
  export let ledgers = [];
  export let filters = { account_id: '', date_from: '', date_to: '' };

  let account_id = filters.account_id || '';
  let date_from = filters.date_from || '';
  let date_to = filters.date_to || '';

  function applyFilters() {
    router.get('/ledger', { 
      account_id, 
      date_from, 
      date_to 
    }, {
      preserveState: true,
      replace: true
    });
  }

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
      day: '2-digit', month: 'short', year: 'numeric' 
    });
  }
</script>

<svelte:head>
  <title>Buku Besar (Ledger) - VCY Accounting</title>
</svelte:head>

<AppLayout>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Buku Besar (General Ledger)</h1>
        <p class="text-sm text-slate-500 mt-1">Laporan mutasi transaksi per akun</p>
      </div>
      <div class="flex gap-2">
        <Button variant="outline" size="sm" class="bg-white gap-2" on:click={() => window.print()}>
          <Printer size={16} /> Cetak
        </Button>
      </div>
    </div>

    <!-- Filter Panel -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="md:col-span-2 space-y-1.5">
        <label class="text-xs font-medium text-slate-600">Pilih Akun</label>
        <CoaSelect bind:value={account_id} options={accounts} placeholder="Pilih Akun..." on:change={applyFilters} />
      </div>
      
      <div class="space-y-1.5">
        <label class="text-xs font-medium text-slate-600">Dari Tanggal</label>
        <Input type="date" bind:value={date_from} on:change={applyFilters} class="h-10" />
      </div>
      
      <div class="space-y-1.5">
        <label class="text-xs font-medium text-slate-600">Sampai Tanggal</label>
        <Input type="date" bind:value={date_to} on:change={applyFilters} class="h-10" />
      </div>
    </div>

    {#if selectedAccount}
      <!-- Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card.Root class="bg-white shadow-sm border-slate-200">
          <Card.Content class="p-5">
            <div class="text-sm font-medium text-slate-500 mb-1">Saldo Awal</div>
            <div class="text-xl font-bold text-slate-800">{formatCurrency(openingBalance)}</div>
          </Card.Content>
        </Card.Root>

        <Card.Root class="bg-white shadow-sm border-slate-200">
          <Card.Content class="p-5">
            <div class="text-sm font-medium text-slate-500 mb-1">Total Debit</div>
            <div class="text-xl font-bold text-teal-600">{formatCurrency(totalDebit)}</div>
          </Card.Content>
        </Card.Root>

        <Card.Root class="bg-white shadow-sm border-slate-200">
          <Card.Content class="p-5">
            <div class="text-sm font-medium text-slate-500 mb-1">Total Credit</div>
            <div class="text-xl font-bold text-rose-600">{formatCurrency(totalCredit)}</div>
          </Card.Content>
        </Card.Root>

        <Card.Root class="bg-white shadow-sm border-slate-200">
          <Card.Content class="p-5">
            <div class="text-sm font-medium text-slate-500 mb-1">Saldo Akhir</div>
            <div class="text-xl font-bold text-blue-600">{formatCurrency(closingBalance)}</div>
          </Card.Content>
        </Card.Root>
      </div>

      <!-- Ledger Table -->
      <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <div class="p-4 bg-slate-50 border-b border-slate-200">
          <h3 class="font-semibold text-slate-800">
            Akun: {selectedAccount.code} - {selectedAccount.name}
            <span class="text-xs font-normal text-slate-500 ml-2">({selectedAccount.type?.category})</span>
          </h3>
        </div>
        
        <div class="overflow-x-auto">
          <Table.Root>
            <Table.Header>
              <Table.Row>
                <Table.Head class="w-[120px]">Tanggal</Table.Head>
                <Table.Head class="w-[150px]">No. Jurnal</Table.Head>
                <Table.Head>Keterangan</Table.Head>
                <Table.Head class="w-[150px] text-right">Debit</Table.Head>
                <Table.Head class="w-[150px] text-right">Credit</Table.Head>
                <Table.Head class="w-[150px] text-right">Saldo (Balance)</Table.Head>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              <!-- Opening Balance Row -->
              <Table.Row class="bg-slate-50/50">
                <Table.Cell class="py-3 font-medium text-slate-600">{formatDate(date_from)}</Table.Cell>
                <Table.Cell class="py-3 text-slate-500">-</Table.Cell>
                <Table.Cell class="py-3 font-medium text-slate-700 italic">Saldo Awal (Opening Balance)</Table.Cell>
                <Table.Cell class="py-3 text-right">-</Table.Cell>
                <Table.Cell class="py-3 text-right">-</Table.Cell>
                <Table.Cell class="py-3 text-right font-bold text-slate-800">{formatCurrency(openingBalance)}</Table.Cell>
              </Table.Row>

              <!-- Transactions -->
              {#each ledgers as ledger}
                <Table.Row class="hover:bg-slate-50 transition-colors">
                  <Table.Cell class="py-3 text-slate-700">{formatDate(ledger.journal.date)}</Table.Cell>
                  <Table.Cell class="py-3">
                    <a href={`/journals/${ledger.journal.id}`} class="text-teal-600 hover:text-teal-800 font-medium hover:underline">
                      {ledger.journal.journal_number}
                    </a>
                  </Table.Cell>
                  <Table.Cell class="py-3">
                    <div class="text-slate-800">{ledger.description}</div>
                    {#if ledger.contact}
                      <div class="text-xs text-slate-500 mt-0.5">Kontak: {ledger.contact.name}</div>
                    {/if}
                  </Table.Cell>
                  <Table.Cell class="py-3 text-right text-slate-700">
                    {ledger.debit > 0 ? formatCurrency(ledger.debit) : '-'}
                  </Table.Cell>
                  <Table.Cell class="py-3 text-right text-slate-700">
                    {ledger.credit > 0 ? formatCurrency(ledger.credit) : '-'}
                  </Table.Cell>
                  <Table.Cell class="py-3 text-right font-semibold text-slate-900">
                    {formatCurrency(ledger.balance)}
                  </Table.Cell>
                </Table.Row>
              {:else}
                <Table.Row>
                  <Table.Cell colspan={6} class="py-8 text-center text-slate-500">
                    Tidak ada transaksi pada periode ini.
                  </Table.Cell>
                </Table.Row>
              {/each}
            </Table.Body>
          </Table.Root>
        </div>
      </div>
    {:else}
      <div class="bg-white border border-slate-200 border-dashed rounded-xl p-12 text-center">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 mb-4 text-slate-400">
          <Search size={24} />
        </div>
        <h3 class="text-lg font-semibold text-slate-800 mb-2">Pilih Akun</h3>
        <p class="text-slate-500 max-w-sm mx-auto">
          Silakan pilih akun pada filter di atas untuk melihat mutasi Buku Besar (General Ledger).
        </p>
      </div>
    {/if}
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
    Button {
      display: none !important;
    }
  }
</style>