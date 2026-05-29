<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { Button } from '$lib/components/ui/button';
  import {
    TrendingUp, TrendingDown, ArrowUpRight, DollarSign, FileText,
    ArrowRight, Activity, Plus, Briefcase, FilePlus, BookOpen,
    Settings as SettingsIcon, AlertCircle, CheckCircle, HelpCircle,
    UserCheck, Inbox
  } from 'lucide-svelte';
  import { Link } from '@inertiajs/svelte';

  // Props sent from DashboardController
  export let stats = {
    revenue: { current: 0, growth: 0 },
    expense: { current: 0, growth: 0 },
    profit: { current: 0, growth: 0 },
    outstanding: { total: 0, count: 0 }
  };
  export let cashFlow = [];
  export let expensesByCategory = [];
  export let recentInvoices = [];

  // Reactive state for chart width
  let chartWidth = 600;
  const chartHeight = 220;
  const padding = { top: 20, right: 20, bottom: 30, left: 60 };

  // Format currency helper
  function formatIDR(amount) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  }

  // Reactive chart calculations
  $: maxVal = Math.max(
    ...cashFlow.map(d => Math.max(d.revenue, d.expense, Math.abs(d.profit))),
    10000000 // default minimum scale (10M IDR)
  ) * 1.1; // 10% headroom

  $: points = cashFlow.map((d, i) => {
    const x = padding.left + (i / Math.max(cashFlow.length - 1, 1)) * (chartWidth - padding.left - padding.right);
    
    // Y coordinates
    const yRev = chartHeight - padding.bottom - (d.revenue / maxVal) * (chartHeight - padding.top - padding.bottom);
    const yExp = chartHeight - padding.bottom - (d.expense / maxVal) * (chartHeight - padding.top - padding.bottom);
    
    // Profit can be negative
    const profitRatio = d.profit / maxVal;
    const yProfit = chartHeight - padding.bottom - (d.profit / maxVal) * (chartHeight - padding.top - padding.bottom);

    return { ...d, x, yRev, yExp, yProfit };
  });

  // SVG Line paths
  $: revPath = points.map(p => `${p.x},${p.yRev}`).join(' L ');
  $: expPath = points.map(p => `${p.x},${p.yExp}`).join(' L ');
  $: profitPath = points.map(p => `${p.x},${p.yProfit}`).join(' L ');

  // Hover state for chart tooltips
  let activeIndex = null;

  // Max expense category total for progress bar percentage
  $: maxExpenseCat = Math.max(...expensesByCategory.map(e => e.amount), 1);
</script>

<AppLayout>
  <!-- Welcome Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-5">
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-slate-800">Dashboard</h1>
      <p class="text-sm text-slate-500 mt-1">Ringkasan aktivitas keuangan dan akuntansi perusahaan Anda.</p>
    </div>
    <div class="flex items-center gap-2">
      <Link href="/invoices/create">
        <Button class="bg-teal-700 hover:bg-teal-800 text-white gap-1.5 h-9 text-xs">
          <Plus class="h-4 w-4" /> Invoice Baru
        </Button>
      </Link>
      <Link href="/ledger">
        <Button variant="outline" class="border-slate-200 text-slate-600 hover:bg-slate-50 gap-1.5 h-9 text-xs">
          <BookOpen class="h-4 w-4" /> Buku Besar
        </Button>
      </Link>
    </div>
  </div>

  <!-- Key Metrics Grid -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Revenue Card -->
    <div class="bg-white rounded-xl border border-slate-150 p-5 shadow-sm hover:shadow-md transition duration-200 relative overflow-hidden group">
      <div class="absolute right-0 top-0 h-24 w-24 bg-teal-50/40 rounded-bl-full -z-10 group-hover:scale-110 transition-transform duration-305"></div>
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Pendapatan</span>
        <div class="h-8 w-8 rounded-lg bg-teal-50 flex items-center justify-center text-teal-600">
          <ArrowUpRight class="h-4 w-4" />
        </div>
      </div>
      <div class="mt-4">
        <h3 class="text-xl font-bold text-slate-800 tracking-tight">{formatIDR(stats.revenue.current)}</h3>
        <div class="flex items-center gap-1.5 mt-1.5">
          <span class="inline-flex items-center gap-0.5 text-xs font-medium px-1.5 py-0.5 rounded-full {stats.revenue.growth >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'}">
            {#if stats.revenue.growth >= 0}
              <TrendingUp class="h-3 w-3" /> +{stats.revenue.growth}%
            {:else}
              <TrendingDown class="h-3 w-3" /> {stats.revenue.growth}%
            {/if}
          </span>
          <span class="text-[11px] text-slate-400">vs bulan lalu</span>
        </div>
      </div>
    </div>

    <!-- Expenses Card -->
    <div class="bg-white rounded-xl border border-slate-150 p-5 shadow-sm hover:shadow-md transition duration-200 relative overflow-hidden group">
      <div class="absolute right-0 top-0 h-24 w-24 bg-rose-50/30 rounded-bl-full -z-10 group-hover:scale-110 transition-transform duration-305"></div>
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Pengeluaran</span>
        <div class="h-8 w-8 rounded-lg bg-rose-50 flex items-center justify-center text-rose-600">
          <TrendingDown class="h-4 w-4" />
        </div>
      </div>
      <div class="mt-4">
        <h3 class="text-xl font-bold text-slate-800 tracking-tight">{formatIDR(stats.expense.current)}</h3>
        <div class="flex items-center gap-1.5 mt-1.5">
          <span class="inline-flex items-center gap-0.5 text-xs font-medium px-1.5 py-0.5 rounded-full {stats.expense.growth <= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'}">
            {#if stats.expense.growth <= 0}
              <TrendingUp class="h-3 w-3" /> {stats.expense.growth}%
            {:else}
              <TrendingDown class="h-3 w-3" /> +{stats.expense.growth}%
            {/if}
          </span>
          <span class="text-[11px] text-slate-400">vs bulan lalu</span>
        </div>
      </div>
    </div>

    <!-- Net Profit Card -->
    <div class="bg-white rounded-xl border border-slate-150 p-5 shadow-sm hover:shadow-md transition duration-200 relative overflow-hidden group">
      <div class="absolute right-0 top-0 h-24 w-24 bg-indigo-50/30 rounded-bl-full -z-10 group-hover:scale-110 transition-transform duration-305"></div>
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Keuntungan Bersih</span>
        <div class="h-8 w-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
          <DollarSign class="h-4 w-4" />
        </div>
      </div>
      <div class="mt-4">
        <h3 class="text-xl font-bold text-slate-800 tracking-tight">{formatIDR(stats.profit.current)}</h3>
        <div class="flex items-center gap-1.5 mt-1.5">
          <span class="inline-flex items-center gap-0.5 text-xs font-medium px-1.5 py-0.5 rounded-full {stats.profit.growth >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'}">
            {#if stats.profit.growth >= 0}
              <TrendingUp class="h-3 w-3" /> +{stats.profit.growth}%
            {:else}
              <TrendingDown class="h-3 w-3" /> {stats.profit.growth}%
            {/if}
          </span>
          <span class="text-[11px] text-slate-400">vs bulan lalu</span>
        </div>
      </div>
    </div>

    <!-- Outstanding Invoices Card -->
    <div class="bg-white rounded-xl border border-slate-150 p-5 shadow-sm hover:shadow-md transition duration-200 relative overflow-hidden group">
      <div class="absolute right-0 top-0 h-24 w-24 bg-amber-50/40 rounded-bl-full -z-10 group-hover:scale-110 transition-transform duration-305"></div>
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Piutang Outstanding</span>
        <div class="h-8 w-8 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
          <FileText class="h-4 w-4" />
        </div>
      </div>
      <div class="mt-4">
        <h3 class="text-xl font-bold text-slate-800 tracking-tight">{formatIDR(stats.outstanding.total)}</h3>
        <div class="mt-2 text-xs text-slate-500 flex items-center gap-1">
          <span class="font-semibold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-md">{stats.outstanding.count}</span>
          <span>invoice belum lunas</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Charts & Category Section -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Cash Flow Visualizer (Native Svelte SVG) -->
    <div class="bg-white rounded-xl border border-slate-150 p-5 shadow-sm lg:col-span-2">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-sm font-bold text-slate-800">Visualisasi Arus Kas</h3>
          <p class="text-[11px] text-slate-400">Pemasukan vs Pengeluaran dalam 6 bulan terakhir</p>
        </div>
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-full bg-teal-500"></span>
            <span class="text-[10px] text-slate-500 font-medium">Revenues</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
            <span class="text-[10px] text-slate-500 font-medium">Expenses</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
            <span class="text-[10px] text-slate-500 font-medium">Net Profit</span>
          </div>
        </div>
      </div>

      <!-- Responsive SVG Chart container -->
      <div bind:clientWidth={chartWidth} class="relative w-full h-[220px] bg-slate-50/50 rounded-lg border border-slate-100 p-2 overflow-visible">
        <svg width="100%" height="100%" viewBox="0 0 {chartWidth} {chartHeight}">
          <!-- Grid lines -->
          {#each [0, 0.25, 0.5, 0.75, 1] as ratio}
            {@const y = padding.top + ratio * (chartHeight - padding.top - padding.bottom)}
            <line x1={padding.left} y1={y} x2={chartWidth - padding.right} y2={y} stroke="#e2e8f0" stroke-dasharray="3,3" />
            <!-- Y-axis labels -->
            <text x={padding.left - 8} y={y + 4} font-size="9" fill="#94a3b8" text-anchor="end">
              {formatIDR(maxVal * (1 - ratio))}
            </text>
          {/each}

          <!-- Bars (Revenue & Expense) -->
          {#each points as p, i}
            {@const barWidth = Math.max((chartWidth - padding.left - padding.right) / (cashFlow.length * 2.8), 6)}
            {@const revHeight = chartHeight - padding.bottom - p.yRev}
            {@const expHeight = chartHeight - padding.bottom - p.yExp}
            
            <!-- Revenue Bar (Teal) -->
            <rect
              x={p.x - barWidth - 1}
              y={p.yRev}
              width={barWidth}
              height={Math.max(revHeight, 2)}
              rx="2"
              fill={activeIndex === i ? '#0d9488' : '#14b8a6'}
              opacity="0.85"
            />
            
            <!-- Expense Bar (Rose) -->
            <rect
              x={p.x + 1}
              y={p.yExp}
              width={barWidth}
              height={Math.max(expHeight, 2)}
              rx="2"
              fill={activeIndex === i ? '#e11d48' : '#f43f5e'}
              opacity="0.85"
            />

            <!-- Invisible triggers for tooltips -->
            <rect
              x={p.x - 20}
              y={padding.top}
              width="40"
              height={chartHeight - padding.top - padding.bottom}
              fill="transparent"
              class="cursor-pointer"
              on:mouseenter={() => activeIndex = i}
              on:mouseleave={() => activeIndex = null}
            />

            <!-- X-axis Label -->
            <text x={p.x} y={chartHeight - 8} font-size="9" fill="#64748b" text-anchor="middle">
              {p.label}
            </text>
          {/each}

          <!-- Profit Line (Indigo) -->
          {#if points.length > 1}
            <path
              d={`M ${revPath}`}
              fill="none"
              stroke="#0d9488"
              stroke-width="2.5"
              stroke-linecap="round"
              stroke-linejoin="round"
              opacity="0.4"
            />
            <path
              d={`M ${expPath}`}
              fill="none"
              stroke="#e11d48"
              stroke-width="2.5"
              stroke-linecap="round"
              stroke-linejoin="round"
              opacity="0.4"
            />
            <path
              d={`M ${profitPath}`}
              fill="none"
              stroke="#6366f1"
              stroke-width="3"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
            
            <!-- Profit Line Dots -->
            {#each points as p}
              <circle
                cx={p.x}
                cy={p.yProfit}
                r="4.5"
                fill="#ffffff"
                stroke="#6366f1"
                stroke-width="2.5"
              />
            {/each}
          {/if}
        </svg>

        <!-- Tooltip overlay -->
        {#if activeIndex !== null && points[activeIndex]}
          {@const p = points[activeIndex]}
          <div class="absolute bg-slate-800 text-white rounded-lg p-2.5 text-[11px] shadow-xl border border-slate-700 pointer-events-none space-y-1 z-30"
               style="left: {Math.min(p.x - 60, chartWidth - 140)}px; top: 10px;">
            <p class="font-bold text-slate-300 border-b border-slate-700 pb-1 mb-1">{p.label}</p>
            <div class="flex justify-between gap-6">
              <span class="text-teal-400 font-medium">Revenues:</span>
              <span>{formatIDR(p.revenue)}</span>
            </div>
            <div class="flex justify-between gap-6">
              <span class="text-rose-400 font-medium">Expenses:</span>
              <span>{formatIDR(p.expense)}</span>
            </div>
            <div class="flex justify-between gap-6 border-t border-slate-700 pt-1 mt-1 font-bold">
              <span class="text-indigo-300">Profit:</span>
              <span class={p.profit >= 0 ? 'text-emerald-400' : 'text-rose-400'}>{formatIDR(p.profit)}</span>
            </div>
          </div>
        {/if}
      </div>
    </div>

    <!-- Expenses breakdown -->
    <div class="bg-white rounded-xl border border-slate-150 p-5 shadow-sm flex flex-col justify-between">
      <div>
        <h3 class="text-sm font-bold text-slate-800">Distribusi Pengeluaran</h3>
        <p class="text-[11px] text-slate-400 mb-4">Pengeluaran teratas berdasarkan kategori bulan ini</p>
        
        <div class="space-y-3.5">
          {#each expensesByCategory as exp}
            {@const pct = (exp.amount / maxExpenseCat) * 100}
            <div class="space-y-1">
              <div class="flex justify-between text-xs">
                <span class="font-semibold text-slate-700">{exp.category}</span>
                <span class="text-slate-500 font-mono font-medium">{formatIDR(exp.amount)}</span>
              </div>
              <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-teal-600 rounded-full transition-all duration-500" style="width: {pct}%"></div>
              </div>
            </div>
          {/each}

          {#if expensesByCategory.length === 0}
            <div class="flex flex-col items-center justify-center py-8 text-slate-400">
              <Inbox class="h-8 w-8 stroke-1" />
              <p class="text-xs mt-2">Belum ada pengeluaran bulan ini</p>
            </div>
          {/if}
        </div>
      </div>

      <div class="border-t border-slate-100 pt-4 mt-4">
        <Link href="/ledger" class="inline-flex items-center text-xs font-semibold text-teal-700 hover:text-teal-800 transition gap-1">
          Buka Laporan Buku Besar <ArrowRight class="h-3 w-3" />
        </Link>
      </div>
    </div>
  </div>

  <!-- Bottom Panel: Recent Invoices & Quick Links -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Recent Invoices table -->
    <div class="bg-white rounded-xl border border-slate-150 p-5 shadow-sm lg:col-span-2">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-sm font-bold text-slate-800">Invoice Terbaru</h3>
          <p class="text-[11px] text-slate-400">Daftar penerbitan invoice terakhir ke customer</p>
        </div>
        <Link href="/invoices">
          <Button variant="ghost" class="text-xs font-semibold text-teal-700 hover:text-teal-800 p-0 hover:bg-transparent">
            Lihat Semua <ArrowRight class="h-3 w-3 ml-1" />
          </Button>
        </Link>
      </div>

      <div class="overflow-x-auto rounded-lg border border-slate-150">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-150 text-slate-500 font-semibold uppercase tracking-wider text-[10px]">
              <th class="py-2.5 px-3">No. Invoice</th>
              <th class="py-2.5 px-3">Customer</th>
              <th class="py-2.5 px-3">Tanggal</th>
              <th class="py-2.5 px-3 text-right">Jumlah</th>
              <th class="py-2.5 px-3 text-center">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            {#each recentInvoices as inv}
              <tr class="hover:bg-slate-50/50 transition">
                <td class="py-2.5 px-3 font-semibold text-slate-700">{inv.invoice_number}</td>
                <td class="py-2.5 px-3 text-slate-600">{inv.customer_name}</td>
                <td class="py-2.5 px-3 text-slate-500">{inv.invoiced_at}</td>
                <td class="py-2.5 px-3 text-right font-mono font-medium text-slate-700">{formatIDR(inv.amount)}</td>
                <td class="py-2.5 px-3 text-center">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium uppercase tracking-wider
                    {inv.status === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : ''}
                    {inv.status === 'draft' ? 'bg-slate-50 text-slate-600 border border-slate-150' : ''}
                    {inv.status === 'partial' ? 'bg-amber-50 text-amber-700 border border-amber-100' : ''}">
                    {inv.status}
                  </span>
                </td>
              </tr>
            {/each}

            {#if recentInvoices.length === 0}
              <tr>
                <td colspan="5" class="py-8 text-center text-slate-400">
                  <Inbox class="h-8 w-8 stroke-1 mx-auto" />
                  <p class="text-xs mt-2">Belum ada invoice yang diterbitkan</p>
                </td>
              </tr>
            {/if}
          </tbody>
        </table>
      </div>
    </div>

    <!-- Quick Actions Panel -->
    <div class="bg-white rounded-xl border border-slate-150 p-5 shadow-sm flex flex-col justify-between">
      <div>
        <h3 class="text-sm font-bold text-slate-800 mb-4">Aksi Cepat</h3>
        
        <div class="grid grid-cols-1 gap-2.5">
          <Link href="/invoices/create" class="flex items-center gap-3 p-3 rounded-lg border border-slate-150 hover:border-teal-500 hover:bg-teal-50/20 transition group">
            <div class="h-9 w-9 rounded-md bg-teal-50 text-teal-600 flex items-center justify-center group-hover:scale-105 transition-transform">
              <FilePlus class="h-4.5 w-4.5" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-700 group-hover:text-teal-700 transition">Buat Invoice Baru</p>
              <p class="text-[10px] text-slate-400 mt-0.5">Terbitkan invoice ke pelanggan.</p>
            </div>
          </Link>

          <Link href="/ledger" class="flex items-center gap-3 p-3 rounded-lg border border-slate-150 hover:border-teal-500 hover:bg-teal-50/20 transition group">
            <div class="h-9 w-9 rounded-md bg-sky-50 text-sky-600 flex items-center justify-center group-hover:scale-105 transition-transform">
              <BookOpen class="h-4.5 w-4.5" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-700 group-hover:text-sky-700 transition">Buka Buku Besar</p>
              <p class="text-[10px] text-slate-400 mt-0.5">Analisis semua jurnal & transaksi.</p>
            </div>
          </Link>

          <Link href="/settings" class="flex items-center gap-3 p-3 rounded-lg border border-slate-150 hover:border-teal-500 hover:bg-teal-50/20 transition group">
            <div class="h-9 w-9 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center group-hover:scale-105 transition-transform">
              <SettingsIcon class="h-4.5 w-4.5" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-700 group-hover:text-purple-700 transition">Pengaturan Sistem</p>
              <p class="text-[10px] text-slate-400 mt-0.5">Kelola perusahaan, user, dan pajak.</p>
            </div>
          </Link>
        </div>
      </div>

      <div class="bg-teal-900 text-white rounded-lg p-3.5 mt-4 flex items-center gap-3 relative overflow-hidden group">
        <div class="absolute right-0 top-0 h-16 w-16 bg-white/5 rounded-bl-full pointer-events-none group-hover:scale-110 transition-transform"></div>
        <div class="h-8 w-8 rounded-full bg-white/10 flex items-center justify-center text-teal-200">
          <Activity class="h-4 w-4" />
        </div>
        <div>
          <h4 class="text-[11px] font-bold tracking-wide uppercase text-teal-300">Sistem Akuntansi Aktif</h4>
          <p class="text-[10px] text-teal-100 mt-0.5">Semua data disinkronkan secara real-time.</p>
        </div>
      </div>
    </div>
  </div>
</AppLayout>
