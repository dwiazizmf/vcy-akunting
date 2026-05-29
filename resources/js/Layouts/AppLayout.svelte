<script>
  import AppHeader from '../Components/AppHeader.svelte';
  import AppFooter from '../Components/AppFooter.svelte';
  export let fullWidth = false;
</script>

<div class="min-h-screen bg-muted/40 flex flex-col">
  <AppHeader />

  <!-- 52px brand bar + 40px nav bar = 92px (approx 5.75rem) -->
  <main class="flex-1 px-4 pb-4 pt-20 lg:px-8 lg:pb-8 lg:pt-32 min-h-screen">
    <div class={fullWidth ? "w-full space-y-6" : "max-w-7xl mx-auto space-y-6"}>
      <slot />
    </div>
  </main>

  <AppFooter />
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
