<script>
  import AppLayout from '../../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Card from '$lib/components/ui/card';
  import { Printer, Download, Search, Calendar, TrendingUp, TrendingDown } from 'lucide-svelte';

  export let revenues = [];
  export let expenses = [];
  export let total_revenue = 0;
  export let total_expense = 0;
  export let net_profit = 0;
  export let filters = { start_date: '', end_date: '' };

  let start_date = filters.start_date || '';
  let end_date = filters.end_date || '';

  function applyFilters() {
    router.get('/reports/profit-and-loss', { 
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

<AppLayout title="Profit & Loss">
  <div class="space-y-6 max-w-5xl mx-auto">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Profit & Loss (Laba Rugi)</h1>
        <p class="text-sm text-gray-500 mt-1">Laporan kinerja pendapatan dan beban perusahaan.</p>
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

    <!-- Report Paper -->
    <Card.Root class="shadow-sm overflow-hidden bg-white">
      <div class="p-8 sm:p-12">
        <!-- Header -->
        <div class="text-center mb-10 border-b pb-6">
          <h2 class="text-xl font-bold text-gray-900 uppercase tracking-widest">Profit & Loss Statement</h2>
          <p class="text-gray-500 mt-2">For the period: {start_date} to {end_date}</p>
        </div>

        <div class="space-y-8">
          <!-- Revenues Section -->
          <div>
            <div class="flex items-center gap-2 mb-4 border-b pb-2">
              <TrendingUp class="w-5 h-5 text-teal-600" />
              <h3 class="text-lg font-bold text-gray-800 uppercase">Pendapatan (Revenues)</h3>
            </div>
            
            <div class="space-y-1">
              {#if revenues.length === 0}
                <p class="text-sm text-gray-500 italic px-7">Tidak ada data pendapatan.</p>
              {:else}
                {#each revenues as item}
                  <div class="flex justify-between items-center py-2 px-2 hover:bg-gray-50 rounded group">
                    <div class="flex items-center gap-3">
                      <span class="text-sm font-mono text-gray-400 group-hover:text-gray-600">{item.code}</span>
                      <span class="text-sm text-gray-700">{item.name}</span>
                    </div>
                    <span class="text-sm font-mono text-gray-700">{formatCurrency(item.amount)}</span>
                  </div>
                {/each}
              {/if}
            </div>
            
            <div class="flex justify-between items-center py-3 px-2 mt-2 bg-teal-50/50 rounded-lg border border-teal-100">
              <span class="font-bold text-teal-900">Total Pendapatan</span>
              <span class="font-bold font-mono text-teal-900">{formatCurrency(total_revenue)}</span>
            </div>
          </div>

          <!-- Expenses Section -->
          <div>
            <div class="flex items-center gap-2 mb-4 border-b pb-2 mt-8">
              <TrendingDown class="w-5 h-5 text-red-500" />
              <h3 class="text-lg font-bold text-gray-800 uppercase">Beban (Expenses)</h3>
            </div>
            
            <div class="space-y-1">
              {#if expenses.length === 0}
                <p class="text-sm text-gray-500 italic px-7">Tidak ada data beban.</p>
              {:else}
                {#each expenses as item}
                  <div class="flex justify-between items-center py-2 px-2 hover:bg-gray-50 rounded group">
                    <div class="flex items-center gap-3">
                      <span class="text-sm font-mono text-gray-400 group-hover:text-gray-600">{item.code}</span>
                      <span class="text-sm text-gray-700">{item.name}</span>
                    </div>
                    <span class="text-sm font-mono text-gray-700">{formatCurrency(item.amount)}</span>
                  </div>
                {/each}
              {/if}
            </div>
            
            <div class="flex justify-between items-center py-3 px-2 mt-2 bg-red-50/50 rounded-lg border border-red-100">
              <span class="font-bold text-red-900">Total Beban</span>
              <span class="font-bold font-mono text-red-900">{formatCurrency(total_expense)}</span>
            </div>
          </div>

          <!-- Net Profit -->
          <div class="mt-12 pt-6 border-t-4 border-double border-gray-300">
            <div class="flex justify-between items-center bg-gray-900 text-white p-4 rounded-xl shadow-inner">
              <span class="text-lg font-bold uppercase tracking-wider">Laba Bersih (Net Profit)</span>
              <span class="text-xl font-bold font-mono {net_profit < 0 ? 'text-red-400' : 'text-teal-400'}">
                {formatCurrency(net_profit)}
              </span>
            </div>
          </div>
        </div>
      </div>
    </Card.Root>
  </div>
</AppLayout>
