<script>
    import { createEventDispatcher } from 'svelte';
    const dispatch = createEventDispatcher();

    export let activeTaxes = [];
    export let applyTax = true;

    // Ketika checkbox diklik, beri tahu parent komponen untuk me-recalculate
    function handleToggle() {
        dispatch('change', { applyTax });
    }
</script>

<div class="flex items-center space-x-3 bg-gray-50 p-4 rounded-lg border border-gray-200">
    <div class="flex items-center h-5">
        <input 
            id="apply-tax-checkbox" 
            type="checkbox" 
            bind:checked={applyTax} 
            on:change={handleToggle}
            class="w-4 h-4 text-teal-600 bg-gray-100 border-gray-300 rounded focus:ring-teal-500 focus:ring-2"
        >
    </div>
    <div class="flex flex-col">
        <label for="apply-tax-checkbox" class="text-sm font-medium text-gray-900">
            Apply Master Taxes
        </label>
        <p class="text-xs font-normal text-gray-500">
            {#if activeTaxes.length > 0}
                Aktif: {activeTaxes.map(t => t.name + ' (' + t.rate + (t.type === 'percentage' ? '%' : '') + ')').join(', ')}
            {:else}
                Tidak ada data master pajak yang aktif.
            {/if}
        </p>
    </div>
</div>
