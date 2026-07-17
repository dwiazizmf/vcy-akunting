<script>
  import AppLayout from '../../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Card from '$lib/components/ui/card';
  import { Printer, Download, Search, Calendar, Wallet, Building2, Landmark } from 'lucide-svelte';

  export let assets = [];
  export let liabilities = [];
  export let equities = [];
  
  export let total_assets = 0;
  export let total_liabilities = 0;
  export let total_equities = 0;
  export let total_liabilities_equities = 0;
  
  export let filters = { start_date: '', end_date: '' };

  let start_date = filters.start_date || '';
  let end_date = filters.end_date || '';

  function applyFilters() {
    router.get('/reports/balance-sheet', { 
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

<AppLayout title="Balance Sheet">
  <div class="space-y-6 max-w-6xl mx-auto">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Balance Sheet (Neraca)</h1>
        <p class="text-sm text-gray-500 mt-1">Laporan posisi keuangan (Aset, Kewajiban, Ekuitas) pada tanggal tertentu.</p>
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
              <Calendar class="w-4 h-4 text-teal-500"/> End Date (As of)
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
      <div class="p-6 sm:p-10">
        <!-- Header -->
        <div class="text-center mb-10 border-b pb-6">
          <h2 class="text-xl font-bold text-gray-900 uppercase tracking-widest">Balance Sheet</h2>
          <p class="text-gray-500 mt-2">Period: {start_date} to {end_date}</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
          
          <!-- LEFT COLUMN: ASSETS -->
          <div>
            <div class="flex items-center gap-2 mb-4 border-b pb-2">
              <Landmark class="w-5 h-5 text-teal-600" />
              <h3 class="text-lg font-bold text-gray-800 uppercase">Aset (Assets)</h3>
            </div>
            
            <div class="space-y-1 min-h-[300px]">
              {#if assets.length === 0}
                <p class="text-sm text-gray-500 italic px-7">Tidak ada data aset.</p>
              {:else}
                {#each assets as item}
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
            
            <div class="flex justify-between items-center py-4 px-4 mt-6 bg-teal-50/80 rounded-xl border-2 border-teal-200">
              <span class="text-lg font-bold text-teal-900">Total Assets</span>
              <span class="text-lg font-bold font-mono text-teal-900">{formatCurrency(total_assets)}</span>
            </div>
          </div>

          <!-- RIGHT COLUMN: LIABILITIES & EQUITY -->
          <div class="flex flex-col h-full">
            
            <!-- Liabilities -->
            <div class="mb-8">
              <div class="flex items-center gap-2 mb-4 border-b pb-2">
                <Building2 class="w-5 h-5 text-indigo-600" />
                <h3 class="text-lg font-bold text-gray-800 uppercase">Kewajiban (Liabilities)</h3>
              </div>
              
              <div class="space-y-1">
                {#if liabilities.length === 0}
                  <p class="text-sm text-gray-500 italic px-7">Tidak ada data kewajiban.</p>
                {:else}
                  {#each liabilities as item}
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
              
              <div class="flex justify-between items-center py-2 px-2 mt-2 border-t border-dashed border-gray-300">
                <span class="font-bold text-gray-800">Total Liabilities</span>
                <span class="font-bold font-mono text-gray-800">{formatCurrency(total_liabilities)}</span>
              </div>
            </div>

            <!-- Equities -->
            <div class="flex-grow">
              <div class="flex items-center gap-2 mb-4 border-b pb-2">
                <Wallet class="w-5 h-5 text-purple-600" />
                <h3 class="text-lg font-bold text-gray-800 uppercase">Ekuitas (Equity)</h3>
              </div>
              
              <div class="space-y-1">
                {#if equities.length === 0}
                  <p class="text-sm text-gray-500 italic px-7">Tidak ada data ekuitas.</p>
                {:else}
                  {#each equities as item}
                    <div class="flex justify-between items-center py-2 px-2 hover:bg-gray-50 rounded group {item.is_virtual ? 'bg-purple-50/50' : ''}">
                      <div class="flex items-center gap-3">
                        <span class="text-sm font-mono text-gray-400 group-hover:text-gray-600">{item.code}</span>
                        <span class="text-sm {item.is_virtual ? 'text-purple-700 font-medium' : 'text-gray-700'}">
                          {item.name} {item.is_virtual ? '*' : ''}
                        </span>
                      </div>
                      <span class="text-sm font-mono {item.is_virtual ? 'text-purple-700 font-medium' : 'text-gray-700'}">
                        {formatCurrency(item.amount)}
                      </span>
                    </div>
                  {/each}
                {/if}
              </div>

              <div class="flex justify-between items-center py-2 px-2 mt-2 border-t border-dashed border-gray-300">
                <span class="font-bold text-gray-800">Total Equity</span>
                <span class="font-bold font-mono text-gray-800">{formatCurrency(total_equities)}</span>
              </div>
            </div>

            <!-- Total L & E -->
            <div class="flex justify-between items-center py-4 px-4 mt-6 bg-indigo-50/80 rounded-xl border-2 border-indigo-200">
              <span class="text-lg font-bold text-indigo-900">Total Liabilities & Equity</span>
              <span class="text-lg font-bold font-mono text-indigo-900">{formatCurrency(total_liabilities_equities)}</span>
            </div>

          </div>
        </div>

        <!-- Balance Check -->
        <div class="mt-12">
          {#if Math.abs(total_assets - total_liabilities_equities) < 0.01}
            <div class="flex items-center justify-center p-4 bg-green-50 text-green-700 rounded-lg border border-green-200 gap-3">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              <span class="font-medium text-lg">Neraca Seimbang (Balanced)</span>
            </div>
          {:else}
            <div class="flex flex-col items-center justify-center p-4 bg-red-50 text-red-700 rounded-lg border border-red-200 gap-2">
              <div class="flex items-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span class="font-medium text-lg">Neraca Tidak Seimbang (Unbalanced)</span>
              </div>
              <p class="text-sm">Selisih: {formatCurrency(Math.abs(total_assets - total_liabilities_equities))}</p>
            </div>
          {/if}
        </div>

      </div>
    </Card.Root>
  </div>
</AppLayout>
