<script>
  import * as Card from '$lib/components/ui/card';
  import * as Table from '$lib/components/ui/table';
  import Pagination from './Pagination.svelte';

  export let pagination;
  export let perPage;
  export let hasActiveFilter = false;
  export let statsTotal = 0;
  
  export let onChangePerPage; // function(e)
  export let onGoToPage;      // function(page)
</script>

<Card.Root class="bg-white border-slate-100 shadow-sm rounded-xl">
  <!-- Toolbar -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 border-b border-slate-100 gap-3 bg-slate-50/20">
    <span class="text-xs text-slate-500">
      Menampilkan <strong class="text-slate-700">{pagination.from}–{pagination.to}</strong> dari
      <strong class="text-slate-700">{pagination.total.toLocaleString()}</strong> hasil
      {#if hasActiveFilter}(difilter dari {statsTotal.toLocaleString()}){/if}
    </span>
    <div class="flex items-center gap-2">
      <!-- Slot for custom toolbar actions (e.g., ColumnToggle) -->
      <slot name="toolbar-actions"></slot>

      <span class="text-xs text-slate-500">Per halaman:</span>
      <select value={perPage} on:change={onChangePerPage} class="h-8 rounded-md border border-slate-200 bg-white px-2 py-1 text-xs outline-none cursor-pointer">
        <option value={10}>10</option>
        <option value={25}>25</option>
        <option value={50}>50</option>
        <option value={100}>100</option>
      </select>
    </div>
  </div>

  <!-- Table Container -->
  <div class="w-full overflow-x-auto rounded-b-xl">
    <Table.Root>
      <slot></slot>
    </Table.Root>
  </div>

  <Pagination {pagination} {onGoToPage} />
</Card.Root>
