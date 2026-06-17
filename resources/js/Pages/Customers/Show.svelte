<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { cn } from '$lib/utils.js';
  import {
    User, Mail, Phone, Globe, Percent, MapPin, 
    ArrowLeft, Edit, Building2, Shield, DollarSign,
    CheckCircle2, FileText
  } from 'lucide-svelte';

  export let customer;

  function goBack() {
    router.visit('/customers');
  }

  function goEdit() {
    router.visit(`/customers/${customer.id}/edit`);
  }

  function formatRp(val) {
    if (!val && val !== 0) return 'Rp0,00';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2 }).format(val);
  }
</script>

<AppLayout>
  <div class="max-w-4xl mx-auto space-y-8 pb-12">
    
    <!-- Header Section -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <button
          class="inline-flex items-center justify-center h-10 w-10 rounded-xl border border-slate-200 bg-white shadow-sm text-slate-500 hover:bg-slate-50 hover:text-slate-900 transition-all duration-200 hover:shadow"
          on:click={goBack}
        >
          <ArrowLeft class="h-4 w-4" />
        </button>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900">{customer.name}</h1>
          <p class="text-sm text-slate-500 mt-1">Customer Detail</p>
        </div>
      </div>
      
      <!-- Top Action -->
      <button
        class="hidden sm:flex items-center gap-2 bg-gradient-to-r from-teal-500 to-emerald-500 hover:from-teal-600 hover:to-emerald-600 text-white px-6 h-10 rounded-xl shadow-md shadow-emerald-500/20 hover:shadow-emerald-500/40 transition-all duration-300 font-medium"
        on:click={goEdit}
      >
        <Edit class="h-4 w-4" />
        Edit Customer
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      
      <!-- Left Column: Primary Stats / Quick Info -->
      <div class="space-y-6">
        
        <div class="bg-white/80 backdrop-blur-xl border-0 ring-1 ring-slate-200/60 rounded-2xl shadow-sm p-6 flex flex-col items-center text-center">
          <div class="h-20 w-20 bg-teal-100 rounded-full flex items-center justify-center mb-4">
            <Building2 class="h-10 w-10 text-teal-600" />
          </div>
          <h2 class="text-xl font-bold text-slate-800">{customer.name}</h2>
          {#if customer.enabled}
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 shadow-sm mt-3">
              <CheckCircle2 class="h-3.5 w-3.5" />
              Active Customer
            </span>
          {:else}
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 shadow-sm mt-3">
              Disabled
            </span>
          {/if}
        </div>

        <div class="bg-white/80 backdrop-blur-xl border-0 ring-1 ring-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
          <div class="px-5 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center gap-2">
            <DollarSign class="h-4 w-4 text-teal-600" />
            <h3 class="font-semibold text-slate-800 text-sm">Financial Summary</h3>
          </div>
          <div class="p-5">
            <div class="text-sm text-slate-500">Total Unpaid Balance</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{formatRp(customer.unpaid || 0)}</div>
          </div>
        </div>

      </div>

      <!-- Right Column: Detailed Info -->
      <div class="md:col-span-2 space-y-6">
        
        <div class="bg-white/80 backdrop-blur-xl border-0 ring-1 ring-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
          <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center gap-2">
            <User class="h-5 w-5 text-teal-600" />
            <h3 class="font-semibold text-slate-800">Profile & Contact</h3>
          </div>
          <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-8">
            
            <div>
              <div class="text-xs text-slate-500 font-medium mb-1 flex items-center gap-1.5"><Percent class="h-3.5 w-3.5" /> NPWP / Tax Number</div>
              <div class="text-sm font-semibold text-slate-800">{customer.npwp || '-'}</div>
            </div>

            <div>
              <div class="text-xs text-slate-500 font-medium mb-1 flex items-center gap-1.5"><FileText class="h-3.5 w-3.5" /> Internal Reference</div>
              <div class="text-sm font-semibold text-slate-800">{customer.reference || '-'}</div>
            </div>

            <div>
              <div class="text-xs text-slate-500 font-medium mb-1 flex items-center gap-1.5"><Mail class="h-3.5 w-3.5" /> Email</div>
              <div class="text-sm font-semibold text-slate-800">{customer.email || 'N/A'}</div>
            </div>

            <div>
              <div class="text-xs text-slate-500 font-medium mb-1 flex items-center gap-1.5"><Phone class="h-3.5 w-3.5" /> Phone</div>
              <div class="text-sm font-semibold text-slate-800">{customer.phone || 'N/A'}</div>
            </div>
            
            <div class="sm:col-span-2">
              <div class="text-xs text-slate-500 font-medium mb-1 flex items-center gap-1.5"><MapPin class="h-3.5 w-3.5" /> Address</div>
              <div class="text-sm font-semibold text-slate-800 leading-relaxed">
                {customer.address || '-'}
              </div>
            </div>

          </div>
        </div>

      </div>

    </div>
    
  </div>
</AppLayout>
