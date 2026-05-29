<script>
  import { Button } from '$lib/components/ui/button';
  import { cn } from '$lib/utils.js';

  export let pagination;
  export let onGoToPage; // function(page: number)

  $: pageNumbers = (() => {
    if (!pagination) return [];
    const total = pagination.lastPage;
    const cur   = pagination.currentPage;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const pages = new Set([1, total, cur]);
    if (cur > 1) pages.add(cur - 1);
    if (cur < total) pages.add(cur + 1);
    if (cur > 2) pages.add(cur - 2);
    if (cur < total - 1) pages.add(cur + 2);
    return [...pages].sort((a, b) => a - b);
  })();
</script>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 border-t border-slate-100 gap-3 bg-slate-50/20">
  <span class="text-xs text-slate-500">
    Halaman <strong>{pagination.currentPage}</strong> dari <strong>{pagination.lastPage.toLocaleString()}</strong>
  </span>
  <div class="flex items-center gap-1 flex-wrap">
    <Button variant="outline" size="icon" class="h-8 w-8 bg-white border-slate-200 text-slate-600 shadow-sm cursor-pointer" disabled={pagination.currentPage === 1} on:click={() => onGoToPage(1)}>«</Button>
    <Button variant="outline" size="icon" class="h-8 w-8 bg-white border-slate-200 text-slate-600 shadow-sm cursor-pointer" disabled={pagination.currentPage === 1} on:click={() => onGoToPage(pagination.currentPage - 1)}>‹</Button>

    {#each pageNumbers as p, i}
      {#if i > 0 && pageNumbers[i] - pageNumbers[i-1] > 1}
        <span class="px-2 text-slate-400 text-xs">…</span>
      {/if}
      <Button
        variant={p === pagination.currentPage ? 'default' : 'outline'}
        class={cn(
          "h-8 min-w-[2rem] px-2.5 shadow-sm cursor-pointer text-xs font-semibold",
          p === pagination.currentPage
            ? "bg-teal-700 hover:bg-teal-800 text-white"
            : "bg-white border-slate-200 text-slate-700 hover:bg-slate-50"
        )}
        on:click={() => onGoToPage(p)}
      >{p}</Button>
    {/each}

    <Button variant="outline" size="icon" class="h-8 w-8 bg-white border-slate-200 text-slate-600 shadow-sm cursor-pointer" disabled={pagination.currentPage === pagination.lastPage} on:click={() => onGoToPage(pagination.currentPage + 1)}>›</Button>
    <Button variant="outline" size="icon" class="h-8 w-8 bg-white border-slate-200 text-slate-600 shadow-sm cursor-pointer" disabled={pagination.currentPage === pagination.lastPage} on:click={() => onGoToPage(pagination.lastPage)}>»</Button>
  </div>
</div>
