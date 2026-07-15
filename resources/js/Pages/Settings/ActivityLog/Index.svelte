<script>
  import { router, page } from '@inertiajs/svelte';
  import AppLayout from '../../../Layouts/AppLayout.svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Card from '$lib/components/ui/card';
  import { Search, History, Calendar, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-svelte';

  export let logs = [];
  export let pagination = {};
  export let filters = { search: '', date: '' };

  let search = filters.search;
  let date = filters.date;
  let isLoading = false;

  let searchTimer;
  function onSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => filterData(), 500);
  }

  function filterData() {
    isLoading = true;
    router.get('/settings/activity-log', { search, date }, {
      preserveState: true,
      replace: true,
      onFinish: () => (isLoading = false)
    });
  }

  function changePage(p) {
    if (p < 1 || p > pagination.lastPage || p === pagination.currentPage) return;
    isLoading = true;
    router.get('/settings/activity-log', { search, date, page: p }, {
      preserveState: true,
      onFinish: () => (isLoading = false)
    });
  }

  function formatProperties(props) {
    if (!props || Object.keys(props).length === 0) return '-';
    // Usually spatie/laravel-activitylog saves changes under 'attributes' and 'old' keys
    if (props.attributes || props.old) {
        let text = '';
        if (props.old) text += `Old Data:\n${JSON.stringify(props.old, null, 2)}\n\n`;
        if (props.attributes) text += `New Data:\n${JSON.stringify(props.attributes, null, 2)}`;
        return text;
    }
    return JSON.stringify(props, null, 2);
  }

  function translateEvent(event) {
      if (event === 'created') return { label: 'Created', class: 'bg-emerald-100 text-emerald-700 border-emerald-200' };
      if (event === 'updated') return { label: 'Updated', class: 'bg-amber-100 text-amber-700 border-amber-200' };
      if (event === 'deleted') return { label: 'Deleted', class: 'bg-rose-100 text-rose-700 border-rose-200' };
      return { label: event || 'System', class: 'bg-slate-100 text-slate-700 border-slate-200' };
  }
</script>

<AppLayout title="Activity Log">
  <div class="p-4 lg:p-6 space-y-4">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <div class="h-9 w-9 rounded-lg bg-teal-900 flex items-center justify-center">
        <History class="h-5 w-5 text-teal-300" />
      </div>
      <div>
        <h1 class="text-lg font-bold text-slate-900">Activity Log (Audit Trail)</h1>
        <p class="text-xs text-slate-500">Pantau seluruh aktivitas yang terjadi dalam sistem beserta perubahan datanya.</p>
      </div>
    </div>

    <!-- Main Content -->
    <Card.Root class="border border-slate-200 shadow-sm overflow-hidden bg-white/80 backdrop-blur-xl">
      <div class="px-5 py-4 bg-slate-50/50 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <h3 class="font-semibold text-slate-800 text-sm">Riwayat Aktivitas</h3>
        
        <div class="flex items-center gap-3">
          <div class="relative group">
            <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
            <input
              type="date"
              bind:value={date}
              on:change={filterData}
              class="h-9 pl-9 pr-4 text-sm w-40 rounded-xl bg-white border border-slate-200 outline-none transition-all focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20"
            />
          </div>
          <div class="relative group">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 group-focus-within:text-teal-500 transition-colors" />
            <input
              type="text"
              bind:value={search}
              on:input={onSearch}
              placeholder="Cari nama user atau deskripsi..."
              class="h-9 pl-9 pr-4 text-sm w-48 sm:w-64 rounded-xl bg-white border border-slate-200 outline-none transition-all duration-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 shadow-sm"
            />
          </div>
        </div>
      </div>

      <div class="w-full overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
          <thead class="bg-slate-50 text-xs uppercase text-slate-500 border-b border-slate-100">
            <tr>
              <th class="px-5 py-3 font-semibold w-40">Waktu</th>
              <th class="px-5 py-3 font-semibold w-40">User</th>
              <th class="px-5 py-3 font-semibold w-24">Aksi</th>
              <th class="px-5 py-3 font-semibold w-32">Subjek Data</th>
              <th class="px-5 py-3 font-semibold">Deskripsi & Perubahan JSON</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            {#if isLoading}
              <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">Memuat...</td></tr>
            {:else if logs.length === 0}
              <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">Belum ada riwayat aktivitas yang sesuai dengan pencarian Anda.</td></tr>
            {:else}
              {#each logs as log}
                <tr class="hover:bg-slate-50/80 transition-colors group">
                  <td class="px-5 py-3 whitespace-nowrap text-[12px] text-slate-500 font-medium">
                    {log.created_at}
                  </td>
                  <td class="px-5 py-3 text-slate-700 font-semibold whitespace-nowrap text-sm">
                    {log.causer}
                  </td>
                  <td class="px-5 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {translateEvent(log.event).class}">
                      {translateEvent(log.event).label}
                    </span>
                  </td>
                  <td class="px-5 py-3">
                    <div class="flex flex-col">
                      <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{log.subject_type || '-'}</span>
                      <span class="font-medium text-slate-700 text-xs mt-0.5">ID: #{log.subject_id || '-'}</span>
                    </div>
                  </td>
                  <td class="px-5 py-3">
                    <p class="font-medium text-slate-800 text-xs mb-1">{log.description}</p>
                    {#if log.properties && Object.keys(log.properties).length > 0}
                      <details class="text-[11px] text-slate-500 mt-1 cursor-pointer group/details">
                        <summary class="font-medium text-teal-600 group-hover/details:text-teal-700 select-none opacity-80 hover:opacity-100 transition-opacity">
                          Tampilkan Data JSON
                        </summary>
                        <pre class="mt-2 p-3 bg-slate-900 text-emerald-300 rounded-lg overflow-x-auto text-[10px] whitespace-pre-wrap font-mono leading-relaxed shadow-inner border border-slate-700">{formatProperties(log.properties)}</pre>
                      </details>
                    {/if}
                  </td>
                </tr>
              {/each}
            {/if}
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      {#if pagination.lastPage > 1}
        <div class="flex items-center justify-between px-5 py-3 border-t border-slate-100 bg-slate-50/50">
          <span class="text-xs text-slate-500 font-medium">
            Menampilkan {pagination.from}–{pagination.to} dari {pagination.total} data
          </span>
          <div class="flex items-center gap-1.5">
            <button
              class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-white border border-transparent hover:border-slate-200 hover:shadow-sm text-slate-500 disabled:opacity-30 disabled:hover:bg-transparent transition-all"
              disabled={pagination.currentPage <= 1}
              on:click={() => changePage(1)}
            ><ChevronsLeft class="h-4 w-4" /></button>
            <button
              class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-white border border-transparent hover:border-slate-200 hover:shadow-sm text-slate-500 disabled:opacity-30 disabled:hover:bg-transparent transition-all"
              disabled={pagination.currentPage <= 1}
              on:click={() => changePage(pagination.currentPage - 1)}
            ><ChevronLeft class="h-4 w-4" /></button>
            <span class="text-xs px-2 font-bold text-slate-700">{pagination.currentPage} / {pagination.lastPage}</span>
            <button
              class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-white border border-transparent hover:border-slate-200 hover:shadow-sm text-slate-500 disabled:opacity-30 disabled:hover:bg-transparent transition-all"
              disabled={pagination.currentPage >= pagination.lastPage}
              on:click={() => changePage(pagination.currentPage + 1)}
            ><ChevronRight class="h-4 w-4" /></button>
            <button
              class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-white border border-transparent hover:border-slate-200 hover:shadow-sm text-slate-500 disabled:opacity-30 disabled:hover:bg-transparent transition-all"
              disabled={pagination.currentPage >= pagination.lastPage}
              on:click={() => changePage(pagination.lastPage)}
            ><ChevronsRight class="h-4 w-4" /></button>
          </div>
        </div>
      {/if}
    </Card.Root>
  </div>
</AppLayout>
