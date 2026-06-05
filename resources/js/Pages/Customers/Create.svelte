<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { cn } from '$lib/utils.js';
  import {
    User, Mail, Percent, ArrowLeftRight, Phone, Globe,
    FileText, Save, ArrowLeft, Building2, MapPin, CheckCircle2, Shield
  } from 'lucide-svelte';

  // ================================================
  // FORM STATE
  // ================================================
  let form = {
    name:         '',
    email:        '',
    tax_number:   '',
    currency:     'IDR', // Default: Indonesia Rupiah
    phone:        '',
    website:      '',
    address:      '',
    is_active:    true,  // Enabled Yes/No
    reference:    '',
    allow_login:  false
  };

  let errors = {};
  let submitting = false;

  function submit() {
    submitting = true;
    router.post('/customers', form, {
      onError: (e) => { errors = e; submitting = false; },
      onSuccess: () => { submitting = false; },
    });
  }
</script>

<AppLayout>
  <div class="max-w-4xl mx-auto space-y-8 pb-12">
    
    <!-- Header Section -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <button
          class="inline-flex items-center justify-center h-10 w-10 rounded-xl border border-slate-200 bg-white shadow-sm text-slate-500 hover:bg-slate-50 hover:text-slate-900 transition-all duration-200 hover:shadow"
          on:click={() => router.visit('/customers')}
        >
          <ArrowLeft class="h-4 w-4" />
        </button>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900">New Customer</h1>
          <p class="text-sm text-slate-500 mt-1">Add a new client or customer to your database.</p>
        </div>
      </div>
      
      <!-- Top Action -->
      <button
        class="hidden sm:flex items-center gap-2 bg-gradient-to-r from-teal-500 to-emerald-500 hover:from-teal-600 hover:to-emerald-600 text-white px-6 h-10 rounded-xl shadow-md shadow-emerald-500/20 hover:shadow-emerald-500/40 transition-all duration-300 font-medium"
        on:click={submit}
        disabled={submitting}
      >
        <Save class="h-4 w-4" />
        {submitting ? 'Saving...' : 'Save Customer'}
      </button>
    </div>

    <!-- Form Content -->
    <div class="space-y-6">
      
      <!-- CARD 1: Basic Information -->
      <div class="bg-white/80 backdrop-blur-xl border-0 ring-1 ring-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center gap-2">
          <Building2 class="h-5 w-5 text-teal-600" />
          <h3 class="font-semibold text-slate-800">Basic Information</h3>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
          
          <div class="space-y-2 md:col-span-2">
            <label class="text-sm font-semibold text-slate-700" for="name">
              Customer Name <span class="text-rose-500">*</span>
            </label>
            <div class="relative group">
              <User class="absolute left-3.5 top-3 h-4 w-4 text-slate-400 group-focus-within:text-teal-500 transition-colors" />
              <input
                id="name"
                type="text"
                placeholder="e.g. PT Maju Bersama"
                bind:value={form.name}
                class={cn("w-full h-11 pl-10 pr-4 rounded-xl bg-slate-50/50 border outline-none transition-all duration-200 focus:bg-white focus:ring-4", errors.name ? "border-rose-300 focus:border-rose-500 focus:ring-rose-500/20" : "border-slate-200 focus:border-teal-500 focus:ring-teal-500/20")}
              />
            </div>
            {#if errors.name}
              <p class="text-xs text-rose-500 font-medium animate-in slide-in-from-top-1">{errors.name}</p>
            {/if}
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700" for="tax_number">
              Tax Number (NPWP)
            </label>
            <div class="relative group">
              <Percent class="absolute left-3.5 top-3 h-4 w-4 text-slate-400 group-focus-within:text-teal-500 transition-colors" />
              <input
                id="tax_number"
                type="text"
                placeholder="00.000.000.0-000.000"
                bind:value={form.tax_number}
                class="w-full h-11 pl-10 pr-4 rounded-xl bg-slate-50/50 border border-slate-200 outline-none transition-all duration-200 focus:bg-white focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20"
              />
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700" for="currency">
              Default Currency <span class="text-rose-500">*</span>
            </label>
            <div class="relative group">
              <ArrowLeftRight class="absolute left-3.5 top-3 h-4 w-4 text-slate-400 group-focus-within:text-teal-500 transition-colors z-10" />
              <select
                id="currency"
                bind:value={form.currency}
                class="w-full h-11 pl-10 pr-10 rounded-xl bg-slate-50/50 border border-slate-200 outline-none transition-all duration-200 focus:bg-white focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 appearance-none relative"
              >
                <option value="IDR">IDR - Indonesia Rupiah</option>
                <option value="USD">USD - United States Dollar</option>
                <option value="SGD">SGD - Singapore Dollar</option>
              </select>
              <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- CARD 2: Contact Details -->
      <div class="bg-white/80 backdrop-blur-xl border-0 ring-1 ring-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center gap-2">
          <MapPin class="h-5 w-5 text-teal-600" />
          <h3 class="font-semibold text-slate-800">Contact Details</h3>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
          
          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700" for="email">Email Address</label>
            <div class="relative group">
              <Mail class="absolute left-3.5 top-3 h-4 w-4 text-slate-400 group-focus-within:text-teal-500 transition-colors" />
              <input
                id="email"
                type="email"
                placeholder="hello@company.com"
                bind:value={form.email}
                class={cn("w-full h-11 pl-10 pr-4 rounded-xl bg-slate-50/50 border outline-none transition-all duration-200 focus:bg-white focus:ring-4", errors.email ? "border-rose-300 focus:border-rose-500 focus:ring-rose-500/20" : "border-slate-200 focus:border-teal-500 focus:ring-teal-500/20")}
              />
            </div>
            {#if errors.email}
              <p class="text-xs text-rose-500 font-medium animate-in slide-in-from-top-1">{errors.email}</p>
            {/if}
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700" for="phone">Phone Number</label>
            <div class="relative group">
              <Phone class="absolute left-3.5 top-3 h-4 w-4 text-slate-400 group-focus-within:text-teal-500 transition-colors" />
              <input
                id="phone"
                type="text"
                placeholder="+62 812 3456 7890"
                bind:value={form.phone}
                class="w-full h-11 pl-10 pr-4 rounded-xl bg-slate-50/50 border border-slate-200 outline-none transition-all duration-200 focus:bg-white focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20"
              />
            </div>
          </div>

          <div class="space-y-2 md:col-span-2">
            <label class="text-sm font-semibold text-slate-700" for="website">Website</label>
            <div class="relative group">
              <Globe class="absolute left-3.5 top-3 h-4 w-4 text-slate-400 group-focus-within:text-teal-500 transition-colors" />
              <input
                id="website"
                type="text"
                placeholder="https://www.company.com"
                bind:value={form.website}
                class="w-full h-11 pl-10 pr-4 rounded-xl bg-slate-50/50 border border-slate-200 outline-none transition-all duration-200 focus:bg-white focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20"
              />
            </div>
          </div>

          <div class="space-y-2 md:col-span-2">
            <label class="text-sm font-semibold text-slate-700" for="address">Full Address</label>
            <textarea
              id="address"
              bind:value={form.address}
              placeholder="Enter complete building, street, and city..."
              class="w-full min-h-[100px] p-4 rounded-xl bg-slate-50/50 border border-slate-200 outline-none transition-all duration-200 focus:bg-white focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 resize-y"
            ></textarea>
          </div>

        </div>
      </div>

      <!-- CARD 3: Preferences & Security -->
      <div class="bg-white/80 backdrop-blur-xl border-0 ring-1 ring-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center gap-2">
          <Shield class="h-5 w-5 text-teal-600" />
          <h3 class="font-semibold text-slate-800">Preferences & Settings</h3>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
          
          <div class="space-y-2">
            <label class="text-sm font-semibold text-slate-700" for="reference">Internal Reference</label>
            <div class="relative group">
              <FileText class="absolute left-3.5 top-3 h-4 w-4 text-slate-400 group-focus-within:text-teal-500 transition-colors" />
              <input
                id="reference"
                type="text"
                placeholder="Any internal note or code..."
                bind:value={form.reference}
                class="w-full h-11 pl-10 pr-4 rounded-xl bg-slate-50/50 border border-slate-200 outline-none transition-all duration-200 focus:bg-white focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20"
              />
            </div>
          </div>

          <!-- Toggles -->
          <div class="space-y-6 md:pl-6 md:border-l border-slate-100">
            <!-- Active Toggle -->
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-semibold text-slate-800">Active Status</h4>
                <p class="text-xs text-slate-500 mt-0.5">Allow this customer to be used in transactions.</p>
              </div>
              <button
                type="button"
                class={cn(
                  "relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2",
                  form.is_active ? "bg-teal-500" : "bg-slate-300"
                )}
                on:click={() => form.is_active = !form.is_active}
              >
                <span
                  class={cn(
                    "pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out",
                    form.is_active ? "translate-x-5" : "translate-x-0"
                  )}
                />
              </button>
            </div>

            <!-- Login Toggle -->
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-semibold text-slate-800">Client Portal</h4>
                <p class="text-xs text-slate-500 mt-0.5">Allow customer to login and view their invoices.</p>
              </div>
              <button
                type="button"
                class={cn(
                  "relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2",
                  form.allow_login ? "bg-teal-500" : "bg-slate-300"
                )}
                on:click={() => form.allow_login = !form.allow_login}
              >
                <span
                  class={cn(
                    "pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out",
                    form.allow_login ? "translate-x-5" : "translate-x-0"
                  )}
                />
              </button>
            </div>
          </div>

        </div>
      </div>

    </div>
    
    <!-- Mobile Bottom Action -->
    <div class="sm:hidden mt-8">
      <button
        class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-teal-500 to-emerald-500 hover:from-teal-600 hover:to-emerald-600 text-white px-6 h-12 rounded-xl shadow-md shadow-emerald-500/20 hover:shadow-emerald-500/40 transition-all duration-300 font-medium"
        on:click={submit}
        disabled={submitting}
      >
        <Save class="h-4 w-4" />
        {submitting ? 'Saving...' : 'Save Customer'}
      </button>
    </div>

  </div>
</AppLayout>

<style>
  /* Optional: Smooth fade-in animations for error messages */
  @keyframes slide-in-from-top-1 {
    from {
      opacity: 0;
      transform: translateY(-4px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  .animate-in {
    animation-duration: 200ms;
    animation-timing-function: cubic-bezier(0.16, 1, 0.3, 1);
    animation-fill-mode: both;
  }
  .slide-in-from-top-1 {
    animation-name: slide-in-from-top-1;
  }
</style>
