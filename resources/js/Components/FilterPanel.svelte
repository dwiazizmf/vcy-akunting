<script>
  import * as Card from '$lib/components/ui/card';
  import { Button } from '$lib/components/ui/button';
  import { cn } from '$lib/utils.js';

  export let hasActiveFilter = false;
  export let onApply; // function()
  export let onReset; // function()

  let filterOpen = false;
</script>

<Card.Root class="mb-6 bg-white border-slate-100 shadow-sm overflow-hidden">
  <button class="w-full flex items-center justify-between p-4 bg-slate-50/50 hover:bg-slate-50 transition text-left cursor-pointer" on:click={() => filterOpen = !filterOpen}>
    <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
      <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
      <span>Filter &amp; Pencarian</span>
      {#if hasActiveFilter}
        <span class="px-2 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-800 rounded-full border border-amber-250">Aktif</span>
      {/if}
    </div>
    <svg class={cn("h-4 w-4 text-slate-400 transition-transform duration-200", filterOpen && "rotate-180")} fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
    </svg>
  </button>

  {#if filterOpen}
    <div class="p-4 border-t border-slate-100 bg-white space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- Tempat untuk input filter dari parent -->
        <slot name="inputs"></slot>
      </div>
      
      <div class="flex items-center justify-between pt-2 border-t border-slate-50">
        <div class="flex items-center gap-2">
          <Button size="sm" class="bg-teal-700 hover:bg-teal-800 text-white cursor-pointer" on:click={onApply}>
            <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            Cari
          </Button>
          {#if hasActiveFilter}
            <Button variant="ghost" size="sm" class="text-slate-500 hover:text-slate-700 cursor-pointer" on:click={onReset}>Reset Filter</Button>
          {/if}
        </div>
        <span class="text-xs text-slate-400 hidden sm:inline-block">💡 Tekan Enter untuk mencari cepat</span>
      </div>
    </div>
  {/if}
</Card.Root>
