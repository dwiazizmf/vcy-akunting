<script>
  import * as Card from '$lib/components/ui/card';

  export let stats = { total: 0, draft: 0, sent: 0, paid: 0 };
</script>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <Card.Root class="bg-white shadow-sm border-slate-100 hover:shadow-md transition cursor-pointer">
    <Card.Content class="flex items-center gap-4 p-5">
      <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      </div>
      <div>
        <div class="text-2xl font-bold text-slate-900">{stats.total?.toLocaleString() || 0}</div>
        <div class="text-xs font-medium text-slate-500 mt-0.5">Total Invoices</div>
      </div>
    </Card.Content>
  </Card.Root>

  <Card.Root class="bg-white shadow-sm border-slate-100 hover:shadow-md transition cursor-pointer">
    <Card.Content class="flex items-center gap-4 p-5">
      <div class="p-3 rounded-xl bg-slate-100 text-slate-600">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div>
        <div class="text-2xl font-bold text-slate-900">{stats.draft || 0}</div>
        <div class="text-xs font-medium text-slate-500 mt-0.5">Draft</div>
      </div>
    </Card.Content>
  </Card.Root>

  <Card.Root class="bg-white shadow-sm border-slate-100 hover:shadow-md transition cursor-pointer">
    <Card.Content class="flex items-center gap-4 p-5">
      <div class="p-3 rounded-xl bg-amber-50 text-amber-600">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
      </div>
      <div>
        <div class="text-2xl font-bold text-slate-900">{stats.sent || 0}</div>
        <div class="text-xs font-medium text-slate-500 mt-0.5">Sent</div>
      </div>
    </Card.Content>
  </Card.Root>

  <Card.Root class="bg-white shadow-sm border-slate-100 hover:shadow-md transition cursor-pointer">
    <Card.Content class="flex items-center gap-4 p-5">
      <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div>
        <div class="text-2xl font-bold text-slate-900">{stats.paid || 0}</div>
        <div class="text-xs font-medium text-slate-500 mt-0.5">Paid</div>
      </div>
    </Card.Content>
  </Card.Root>
</div>
