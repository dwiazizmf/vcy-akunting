<script>
  import AppLayout from '../../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Card from '$lib/components/ui/card';
  import * as Table from '$lib/components/ui/table';
  import { Printer, Download, Search, Calendar } from 'lucide-svelte';

  export let data = [];
  export let totals = { debit: 0, credit: 0 };
  export let filters = { start_date: '', end_date: '' };

  let start_date = filters.start_date || '';
  let end_date = filters.end_date || '';

  function applyFilters() {
    router.get('/reports/trial-balance', { 
      start_date, 
      end_date 
    }, {
      preserveState: true,
      replace: true
    });
  }

  function formatCurrency(amount) {
    if (amount === 0 || amount === null || amount === undefined) return '-';
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  }
</script>

<AppLayout title="Trial Balance">
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Trial Balance (Neraca Saldo)</h1>
        <p class="text-sm text-gray-500 mt-1">Laporan saldo seluruh akun buku besar.</p>
      </div>
      <div class="flex gap-2">
        <Button variant="outline" class="gap-2">
          <Printer class="w-4 h-4" /> Print
        </Button>
        <Button variant="outline" class="gap-2 text-teal-600 hover:text-teal-700">
          <Download class="w-4 h-4" /> Export Excel
        </Button>
      </div>
    </div>

    <!-- Filter Card -->
    <Card.Root class="border-t-4 border-t-teal-500 shadow-sm">
      <Card.Content class="p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row items-end gap-4">
          <div class="flex-1 w-full space-y-1.5">
            <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
              <Calendar class="w-4 h-4 text-teal-500"/> Start Date
            </label>
            <Input type="date" bind:value={start_date} class="bg-gray-50" />
          </div>
          <div class="flex-1 w-full space-y-1.5">
            <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
              <Calendar class="w-4 h-4 text-teal-500"/> End Date
            </label>
            <Input type="date" bind:value={end_date} class="bg-gray-50" />
          </div>
          <Button on:click={applyFilters} class="w-full sm:w-auto bg-teal-600 hover:bg-teal-700 text-white gap-2">
            <Search class="w-4 h-4" /> View Report
          </Button>
        </div>
      </Card.Content>
    </Card.Root>

    <!-- Report Table -->
    <Card.Root class="shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <Table.Root>
          <Table.Header class="bg-gray-50/80">
            <Table.Row>
              <Table.Head class="w-[100px] font-semibold text-gray-700">Code</Table.Head>
              <Table.Head class="font-semibold text-gray-700">Account Name</Table.Head>
              <Table.Head class="font-semibold text-gray-700 text-center w-[120px]">Type</Table.Head>
              <Table.Head class="text-right font-semibold text-gray-700">Begin. Balance</Table.Head>
              <Table.Head class="text-right font-semibold text-gray-700">Debit</Table.Head>
              <Table.Head class="text-right font-semibold text-gray-700">Credit</Table.Head>
              <Table.Head class="text-right font-semibold text-gray-700">End. Balance</Table.Head>
            </Table.Row>
          </Table.Header>
          <Table.Body>
            {#if data.length === 0}
              <Table.Row>
                <Table.Cell colspan="7" class="h-32 text-center text-gray-500">
                  <div class="flex flex-col items-center justify-center">
                    <Search class="w-8 h-8 mb-2 text-gray-300" />
                    <p>No transactions found for the selected period.</p>
                  </div>
                </Table.Cell>
              </Table.Row>
            {:else}
              {#each data as row}
                <Table.Row class="hover:bg-gray-50/50 transition-colors">
                  <Table.Cell class="font-medium text-gray-900">{row.code}</Table.Cell>
                  <Table.Cell>{row.name}</Table.Cell>
                  <Table.Cell class="text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                      {row.category}
                    </span>
                  </Table.Cell>
                  <Table.Cell class="text-right font-mono text-sm text-gray-600">{formatCurrency(row.beginning_balance)}</Table.Cell>
                  <Table.Cell class="text-right font-mono text-sm text-gray-600">{formatCurrency(row.debit)}</Table.Cell>
                  <Table.Cell class="text-right font-mono text-sm text-gray-600">{formatCurrency(row.credit)}</Table.Cell>
                  <Table.Cell class="text-right font-mono text-sm font-medium {row.ending_balance < 0 ? 'text-red-600' : 'text-gray-900'}">
                    {formatCurrency(row.ending_balance)}
                  </Table.Cell>
                </Table.Row>
              {/each}
              <!-- Totals Row -->
              <Table.Row class="bg-gray-50 font-bold border-t-2 border-gray-200">
                <Table.Cell colspan="4" class="text-right text-gray-900 uppercase tracking-wider text-sm">
                  Grand Total
                </Table.Cell>
                <Table.Cell class="text-right font-mono text-teal-700">{formatCurrency(totals.debit)}</Table.Cell>
                <Table.Cell class="text-right font-mono text-teal-700">{formatCurrency(totals.credit)}</Table.Cell>
                <Table.Cell class="text-right"></Table.Cell>
              </Table.Row>
            {/if}
          </Table.Body>
        </Table.Root>
      </div>
    </Card.Root>
  </div>
</AppLayout>
