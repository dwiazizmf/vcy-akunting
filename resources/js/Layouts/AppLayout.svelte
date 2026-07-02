<script>
  import AppHeader from '../Components/AppHeader.svelte';
  import AppFooter from '../Components/AppFooter.svelte';
  import Toast from '../Components/Toast.svelte';
  import ConfirmModal from '../Components/ConfirmModal.svelte';
  import { page } from '@inertiajs/svelte';
  import { showToast } from '../Stores/toast.js';

  export let fullWidth = false;

  $: {
    if ($page.props.errors?.error) {
      showToast($page.props.errors.error, 'error', 5000);
    }
  }

  $: isConsolidated = $page.props.active_company_id === 'all';
</script>

<div class="min-h-screen bg-muted/40 flex flex-col">
  <AppHeader />

  {#if isConsolidated}
    <div class="fixed top-14 left-0 right-0 z-40 bg-amber-500 text-amber-950 px-4 py-1.5 text-xs font-semibold flex items-center justify-center gap-2 shadow-sm border-b border-amber-600">
      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      Mode Konsolidasi Aktif (Semua Perusahaan). Data bersifat View-Only (Read Only). Anda tidak dapat membuat, mengubah, atau menghapus data.
    </div>
  {/if}

  <!-- 52px brand bar + 40px nav bar = 92px (approx 5.75rem) -->
  <main class="flex-1 px-4 pb-4 pt-20 lg:px-8 lg:pb-8 lg:pt-32 min-h-screen {isConsolidated ? 'mt-8' : ''}">
    <div class={fullWidth ? "w-full space-y-6" : "max-w-7xl mx-auto space-y-6"}>
      <slot />
    </div>
  </main>

  <AppFooter />
  <Toast />
  <ConfirmModal />
</div>

<style>
  :global(body) {
    margin: 0;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  }
  :global(.overflow-x-auto::-webkit-scrollbar) { height: 8px; }
  :global(.overflow-x-auto::-webkit-scrollbar-track) { background: transparent; }
  :global(.overflow-x-auto::-webkit-scrollbar-thumb) { background: var(--color-border); border-radius: 4px; }
  :global(.overflow-x-auto::-webkit-scrollbar-thumb:hover) { background: hsl(var(--muted-foreground) / 0.5); }
</style>
