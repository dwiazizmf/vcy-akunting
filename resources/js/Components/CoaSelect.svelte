<script>
  import { createEventDispatcher, onMount } from 'svelte';
  import { ChevronDown, Search, Check } from 'lucide-svelte';

  export let options = []; // array of {id, code, name, parent_id}
  export let value = null; // bound value
  export let placeholder = "Pilih Akun...";
  export let disabled = false;

  const dispatch = createEventDispatcher();

  let isOpen = false;
  let searchQuery = '';
  let wrapperRef;

  // Search filter matching code or name
  $: filteredOptions = options.filter(opt => {
    const code = opt.code || '';
    const name = opt.name || '';
    const query = searchQuery.toLowerCase();
    return code.toLowerCase().includes(query) || name.toLowerCase().includes(query);
  });

  $: selectedOption = options.find(opt => opt.id === value);

  function toggleDropdown() {
    if (disabled) return;
    isOpen = !isOpen;
    if (isOpen) {
      searchQuery = '';
      setTimeout(() => {
        const searchInput = wrapperRef?.querySelector('input[type="text"]');
        if (searchInput) searchInput.focus();
      }, 50);
    }
  }

  function selectOption(id) {
    value = id;
    isOpen = false;
    dispatch('change', { value: id });
  }

  function handleClickOutside(event) {
    if (isOpen && wrapperRef && !wrapperRef.contains(event.target)) {
      isOpen = false;
    }
  }
</script>

<svelte:window on:click={handleClickOutside} />

<div class="relative w-full {isOpen ? 'z-[100]' : ''}" bind:this={wrapperRef}>
  <!-- Trigger Button -->
  <button
    type="button"
    {disabled}
    class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 disabled:cursor-not-allowed disabled:opacity-50 transition-all shadow-sm {isOpen ? 'ring-2 ring-teal-500 border-teal-500' : ''}"
    on:click={toggleDropdown}
  >
    <span class="truncate {selectedOption ? 'text-slate-900 font-medium' : 'text-slate-500'}">
      {selectedOption ? `${selectedOption.code} - ${selectedOption.name}` : placeholder}
    </span>
    <ChevronDown class="h-4 w-4 opacity-50" />
  </button>

  <!-- Dropdown Menu -->
  {#if isOpen}
    <div
      class="absolute z-50 mt-1 max-h-60 w-full overflow-hidden rounded-md border bg-white text-slate-950 shadow-lg border-slate-200 focus:outline-none animate-in fade-in-50 slide-in-from-top-1 duration-100"
    >
      <!-- Search Input -->
      <div class="flex items-center border-b px-3 bg-slate-50/50">
        <Search class="mr-2 h-4 w-4 shrink-0 opacity-50 text-slate-500" />
        <input
          type="text"
          class="flex h-9 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-slate-400 border-0 focus:ring-0"
          placeholder="Cari akun (kode/nama)..."
          bind:value={searchQuery}
        />
      </div>
      
      <!-- Options List -->
      <div class="overflow-y-auto overflow-x-auto max-h-[190px] p-1 space-y-0.5 scrollbar-thin scrollbar-thumb-slate-200">
        {#if filteredOptions.length === 0}
          <div class="py-6 text-center text-sm text-slate-500">
            Akun tidak ditemukan.
          </div>
        {:else}
          {#each filteredOptions as option}
            {@const isParent = option.is_parent !== undefined ? option.is_parent : !option.parent_id}
            <button
              type="button"
              class="relative flex w-full min-w-max cursor-default select-none items-center rounded-sm py-1.5 pr-4 text-sm outline-none transition-colors hover:bg-slate-100 hover:text-slate-900
                     {isParent ? 'font-bold text-slate-900 bg-slate-50/30 pl-8' : 'pl-14 text-slate-600'}
                     {value === option.id ? 'bg-teal-50 font-medium text-teal-700' : ''}"
              on:click={() => selectOption(option.id)}
            >
              {#if value === option.id}
                <span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
                  <Check class="h-4 w-4 text-teal-600" />
                </span>
              {/if}
              <span class="flex items-center gap-1.5 whitespace-nowrap">
                {#if !isParent}
                  <span class="text-slate-300 font-normal select-none">└─</span>
                {/if}
                <span>{option.code} - {option.name}</span>
              </span>
            </button>
          {/each}
        {/if}
      </div>
    </div>
  {/if}
</div>
