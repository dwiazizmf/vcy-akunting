<script>
    import { toastMessage } from '../Stores/toast.js';
    import { slide } from 'svelte/transition';
    import { quintOut } from 'svelte/easing';

    $: isError = $toastMessage?.type === 'error';
    $: bgClass = isError ? 'bg-rose-50' : 'bg-emerald-50';
    $: borderClass = isError ? 'border-rose-300' : 'border-emerald-300';
    $: textClass = isError ? 'text-rose-800' : 'text-emerald-800';
    $: iconColorClass = isError ? 'text-rose-500' : 'text-emerald-500';
    $: btnHoverClass = isError ? 'hover:bg-rose-200' : 'hover:bg-emerald-200';
    $: btnFocusClass = isError ? 'focus:ring-rose-400' : 'focus:ring-emerald-400';
</script>

{#if $toastMessage}
    <div 
        transition:slide={{ delay: 0, duration: 300, easing: quintOut, axis: 'y' }} 
        class="fixed bottom-6 right-6 z-50 flex items-center p-4 mb-4 {textClass} border {borderClass} rounded-lg {bgClass} shadow-lg" 
        role="alert">
        {#if isError}
            <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z"/>
            </svg>
        {:else}
            <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
            </svg>
        {/if}
        <div class="ms-3 text-sm font-medium">
            {$toastMessage.message}
        </div>
        <button type="button" class="ms-auto -mx-1.5 -my-1.5 {bgClass} {iconColorClass} rounded-lg focus:ring-2 {btnFocusClass} p-1.5 {btnHoverClass} inline-flex items-center justify-center h-8 w-8" on:click={() => toastMessage.set(null)} aria-label="Close">
            <span class="sr-only">Close</span>
            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
            </svg>
        </button>
    </div>
{/if}
