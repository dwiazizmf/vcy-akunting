<script>
  export let mobileSidebarOpen = false;
  export let collapsed = false;
  export let onClose = () => {};
  export let onToggleCollapse = () => {};

  const subItems = ['Customers', 'Download E-Faktur', 'Create Dokumen', 'Tanda Terima', 'Tanda Terima New', 'List Kirim Tagihan', 'Schedule Tukar Faktur', 'Surat Tagihan', 'History Invoice', 'Report Mayora', 'Kwitansi', 'Titip Internal', 'Upload No Faktur'];
</script>

<!-- Sidebar -->
<aside
  class="fixed left-0 top-12 bottom-0 bg-slate-800 text-gray-300 overflow-y-auto overflow-x-hidden z-40
         transform transition-all duration-300 ease-in-out
         {mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'}
         lg:translate-x-0
         {collapsed ? 'w-16' : 'w-64'}"
>
  <!-- Collapse Toggle Button -->
  <div class="flex items-center border-b border-slate-700 {collapsed ? 'justify-center py-3' : 'justify-between p-4'}">
    {#if !collapsed}
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-teal-600 rounded-full flex items-center justify-center shrink-0">
          <span class="text-white font-bold text-xs">PT</span>
        </div>
        <div>
          <p class="text-white font-medium text-sm">PT Yudha</p>
          <button class="text-xs text-teal-400 hover:text-teal-300">+ Switch</button>
        </div>
      </div>
    {/if}
    <button
      on:click={onToggleCollapse}
      class="p-1.5 rounded hover:bg-slate-700 text-gray-400 hover:text-white transition-colors hidden lg:flex items-center justify-center"
      title={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
    >
      {#if collapsed}
        <!-- Expand icon (chevron right) -->
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      {:else}
        <!-- Collapse icon (chevron left) -->
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
      {/if}
    </button>
  </div>

  <!-- Search (hidden when collapsed) -->
  {#if !collapsed}
    <div class="p-4">
      <div class="relative">
        <input
          type="text"
          placeholder="Search..."
          class="w-full bg-slate-700 border-none rounded px-3 py-2 text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-teal-500 focus:outline-none"
        />
        <svg class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
      </div>
    </div>
  {:else}
    <!-- Search icon only when collapsed -->
    <div class="flex justify-center py-3 border-b border-slate-700">
      <button class="p-1.5 rounded hover:bg-slate-700 text-gray-400 hover:text-white transition-colors" title="Search">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
      </button>
    </div>
  {/if}

  <!-- Navigation -->
  <nav class="px-2 py-2 space-y-0.5">

    <!-- Dashboard -->
    <a
      href="/dashboard"
      title="Dashboard"
      class="flex items-center gap-3 px-3 py-2.5 rounded hover:bg-slate-700 transition-colors {collapsed ? 'justify-center' : ''}"
    >
      <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
      </svg>
      {#if !collapsed}<span class="text-sm whitespace-nowrap">Dashboard</span>{/if}
    </a>

    <!-- Incomes -->
    <div>
      <button
        title="Incomes"
        class="w-full flex items-center gap-3 px-3 py-2.5 rounded hover:bg-slate-700 transition-colors text-teal-400 {collapsed ? 'justify-center' : 'justify-between'}"
      >
        <div class="flex items-center gap-3">
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {#if !collapsed}<span class="text-sm font-medium whitespace-nowrap">Incomes</span>{/if}
        </div>
        {#if !collapsed}
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        {/if}
      </button>

      <!-- Submenu (only when expanded) -->
      {#if !collapsed}
        <div class="ml-4 mt-1 space-y-0.5">
          <a href="/invoices" class="flex items-center gap-2 px-3 py-2 text-sm text-teal-400 bg-slate-700/50 rounded">
            <span class="w-1.5 h-1.5 bg-teal-400 rounded-full shrink-0"></span>
            Invoices
          </a>
          {#each subItems as item}
            {@const href = item === 'Customers' ? '/customers' : (item === 'Create Dokumen' ? '/documents/create' : (item === 'Tanda Terima' ? '/tanda-terima' : (item === 'Tanda Terima New' ? '/tanda-terima/new' : (item === 'List Kirim Tagihan' ? '/list-kirim-tagihan' : (item === 'Schedule Tukar Faktur' ? '/schedule-tukar-faktur' : `#${item.toLowerCase().replace(/ /g, '-')}`))))) }
            <a href={href} class="flex items-center gap-2 px-3 py-2 text-sm text-gray-400 hover:text-white rounded hover:bg-slate-700/30 transition-colors">
              <span class="w-1.5 h-1.5 bg-gray-500 rounded-full shrink-0"></span>
              {item}
            </a>
          {/each}
        </div>
      {/if}
    </div>

    <!-- Expenses -->
    <button
      title="Expenses"
      class="w-full flex items-center gap-3 px-3 py-2.5 rounded hover:bg-slate-700 transition-colors {collapsed ? 'justify-center' : 'justify-between'}"
    >
      <div class="flex items-center gap-3">
        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        {#if !collapsed}<span class="text-sm whitespace-nowrap">Expenses</span>{/if}
      </div>
      {#if !collapsed}
        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      {/if}
    </button>

    <!-- Double-Entry -->
    <button
      title="Double-Entry"
      class="w-full flex items-center gap-3 px-3 py-2.5 rounded hover:bg-slate-700 transition-colors {collapsed ? 'justify-center' : 'justify-between'}"
    >
      <div class="flex items-center gap-3">
        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
        </svg>
        {#if !collapsed}<span class="text-sm whitespace-nowrap">Double-Entry</span>{/if}
      </div>
      {#if !collapsed}
        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      {/if}
    </button>

    <!-- Settings -->
    <a
      href="/settings"
      title="Settings"
      class="flex items-center gap-3 px-3 py-2.5 rounded hover:bg-slate-700 transition-colors {collapsed ? 'justify-center' : ''}"
    >
      <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>
      {#if !collapsed}<span class="text-sm whitespace-nowrap">Settings</span>{/if}
    </a>
  </nav>
</aside>

<!-- Mobile Overlay -->
{#if mobileSidebarOpen}
  <button
    class="fixed inset-0 bg-black/50 z-30 lg:hidden"
    on:click={onClose}
    aria-label="Close sidebar"
  ></button>
{/if}

<style>
  aside::-webkit-scrollbar { width: 4px; }
  aside::-webkit-scrollbar-track { background: transparent; }
  aside::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }
  aside::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.25); }
</style>
