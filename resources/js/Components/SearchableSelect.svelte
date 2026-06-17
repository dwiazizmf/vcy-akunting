<script>
  import { createEventDispatcher, onMount } from 'svelte';
  import { ChevronDown, Search, Check } from 'lucide-svelte';

  export let options = []; // array of {id, name}
  export let value = null; // bound value
  export let placeholder = "Select an option...";
  export let disabled = false;

  const dispatch = createEventDispatcher();

  let isOpen = false;
  let searchQuery = '';
  let wrapperRef;

  $: filteredOptions = options.filter(opt => 
    opt.name.toLowerCase().includes(searchQuery.toLowerCase())
  );

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
    class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 disabled:cursor-not-allowed disabled:opacity-50 {isOpen ? 'ring-2 ring-teal-500 border-teal-500' : ''}"
    on:click={toggleDropdown}
  >
    <span class="truncate {selectedOption ? 'text-slate-900' : 'text-slate-500'}">
      {selectedOption ? selectedOption.name : placeholder}
    </span>
    <ChevronDown class="h-4 w-4 opacity-50" />
  </button>

  <!-- Dropdown Menu -->
  {#if isOpen}
    <div
      class="absolute z-50 mt-1 max-h-60 w-full overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md bg-white border-slate-200"
    >
      <div class="flex items-center border-b px-3">
        <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
        <input
          type="text"
          class="flex h-10 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 border-0 focus:ring-0"
          placeholder="Search..."
          bind:value={searchQuery}
        />
      </div>
      
      <div class="overflow-y-auto max-h-[180px] p-1">
        {#if filteredOptions.length === 0}
          <div class="py-6 text-center text-sm text-slate-500">
            No results found.
          </div>
        {:else}
          {#each filteredOptions as option}
            <button
              type="button"
              class="relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pr-2 text-sm outline-none hover:bg-slate-100 hover:text-slate-900 {value === option.id ? 'bg-slate-50 font-medium text-teal-700' : ''} {option.is_parent ? 'font-bold pl-3' : 'pl-8'}"
              on:click={() => selectOption(option.id)}
            >
              {#if value === option.id}
                <span class="absolute {option.is_parent ? 'left-0' : 'left-3'} flex h-3.5 w-3.5 items-center justify-center">
                  <Check class="h-4 w-4 text-teal-600" />
                </span>
              {/if}
              <span class="truncate">{option.name}</span>
            </button>
          {/each}
        {/if}
      </div>
    </div>
  {/if}
</div>
