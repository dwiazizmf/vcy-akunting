<script>
  export let title = 'Tidak ada data';
  export let description = 'Coba sesuaikan pencarian atau filter Anda untuk menemukan apa yang Anda cari.';
</script>

<div class="flex flex-col items-center justify-center py-12 px-4 text-center">
  <div class="h-12 w-12 text-slate-300 mb-4 bg-slate-50 rounded-full flex items-center justify-center ring-4 ring-slate-50/50">
    <slot name="icon">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
    </slot>
  </div>
  <h3 class="text-lg font-semibold text-slate-800 mb-1">{title}</h3>
  <p class="text-sm text-slate-500 max-w-sm mx-auto mb-6">
    {description}
  </p>
  <slot name="action"></slot>
</div>
