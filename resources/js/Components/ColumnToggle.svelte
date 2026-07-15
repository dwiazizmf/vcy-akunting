<script>
  import { cn } from '$lib/utils.js';

  export let columns = [];
  
  let isOpen = false;

  $: visibleCount = columns.filter(c => c.visible).length;

  function toggleColumn(key) {
    columns = columns.map(c => c.key === key ? { ...c, visible: !c.visible } : c);
  }
</script>

<div class="relative">
  <button
    id="col-toggle-btn"
    on:click={() => isOpen = !isOpen}
    class={cn(
      "flex items-center gap-1.5 h-8 px-3 rounded-md border text-xs font-semibold transition-colors cursor-pointer",
      isOpen
        ? "bg-teal-700 text-white border-teal-700"
        : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50"
    )}
  >
    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    Kolom
    <span class="px-1.5 py-0 rounded-full text-[10px] font-bold bg-teal-100 text-teal-700 ml-0.5">{visibleCount}</span>
  </button>

  <!-- ===== COLUMN PICKER OVERLAY ===== -->
  {#if isOpen}
    <!-- Backdrop transparan untuk close on click-outside -->
    <!-- svelte-ignore a11y-click-events-have-key-events -->
    <!-- svelte-ignore a11y-no-static-element-interactions -->
    <div
      class="fixed inset-0 z-40"
      on:click={() => isOpen = false}
    ></div>

    <!-- Panel kolom — absolute agar menempel di bawah tombol, right-0 agar tidak keluar layar ke kanan -->
    <div class="absolute z-50 right-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-2xl p-3 w-56 max-h-[80vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-2 px-1">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pilih Kolom Tampil</span>
        <button
          on:click={() => isOpen = false}
          class="text-slate-400 hover:text-slate-600 cursor-pointer rounded p-0.5 hover:bg-slate-100"
        >
          <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      {#each columns as col}
        <label class="flex items-center gap-2.5 px-1 py-1.5 rounded-lg hover:bg-slate-50 cursor-pointer group">
          <input
            type="checkbox"
            checked={col.visible}
            on:change={() => toggleColumn(col.key)}
            class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 cursor-pointer h-3.5 w-3.5 shrink-0"
          />
          <span class="text-xs text-slate-700 group-hover:text-slate-900 font-medium">{col.label}</span>
        </label>
      {/each}

      <div class="border-t border-slate-100 mt-2 pt-2 flex gap-1.5">
        <button
          on:click={() => { columns = columns.map(c => ({ ...c, visible: true })); }}
          class="flex-1 text-[11px] py-1.5 rounded-md bg-teal-50 text-teal-700 hover:bg-teal-100 font-semibold cursor-pointer transition-colors"
        >Semua</button>
        <button
          on:click={() => { columns = columns.map((c, i) => ({ ...c, visible: i < 6 })); }}
          class="flex-1 text-[11px] py-1.5 rounded-md bg-slate-50 text-slate-600 hover:bg-slate-100 font-semibold cursor-pointer transition-colors"
        >Reset</button>
      </div>
    </div>
  {/if}
</div>
