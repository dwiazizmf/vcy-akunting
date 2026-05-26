<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Card from '$lib/components/ui/card';
  import { cn } from '$lib/utils.js';
  import {
    User, Mail, Percent, ArrowLeftRight, Phone, Globe,
    FileText, Save, ArrowLeft
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
  <div class="max-w-5xl mx-auto space-y-6">
    <!-- Back Button & Header -->
    <div class="flex items-center gap-3">
      <button
        class="inline-flex items-center justify-center h-9 w-9 rounded-lg border border-slate-200 bg-white shadow-sm text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors"
        on:click={() => router.visit('/customers')}
      >
        <ArrowLeft class="h-4 w-4" />
      </button>
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">New Customer</h1>
      </div>
    </div>

    <!-- Main Card Form -->
    <Card.Root class="bg-white border-slate-200 border-t-4 border-t-emerald-600 shadow-sm">
      <Card.Content class="p-6 space-y-6">
        
        <!-- ROW 1: Name & Email -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider" for="name">
              Name <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <User class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input
                id="name"
                type="text"
                placeholder="Enter Name"
                bind:value={form.name}
                class={cn("pl-9 bg-white border-slate-200 shadow-sm h-9 text-sm focus-visible:ring-emerald-500/20 focus-visible:border-emerald-600", errors.name && "border-red-450")}
              />
            </div>
            {#if errors.name}
              <p class="text-xs text-red-500 mt-1">{errors.name}</p>
            {/if}
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider" for="email">
              Email
            </label>
            <div class="relative">
              <Mail class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input
                id="email"
                type="email"
                placeholder="Enter Email"
                bind:value={form.email}
                class={cn("pl-9 bg-white border-slate-200 shadow-sm h-9 text-sm", errors.email && "border-red-450")}
              />
            </div>
            {#if errors.email}
              <p class="text-xs text-red-500 mt-1">{errors.email}</p>
            {/if}
          </div>
        </div>

        <!-- ROW 2: Tax Number & Currency -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider" for="tax_number">
              Tax Number
            </label>
            <div class="relative">
              <Percent class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input
                id="tax_number"
                type="text"
                placeholder="Enter Tax Number"
                bind:value={form.tax_number}
                class="pl-9 bg-white border-slate-200 shadow-sm h-9 text-sm"
              />
            </div>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider" for="currency">
              Currency <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <ArrowLeftRight class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <select
                id="currency"
                bind:value={form.currency}
                class="w-full h-9 pl-9 pr-3 rounded-md border border-slate-200 bg-white text-sm outline-none focus:border-emerald-600 shadow-sm transition-colors"
              >
                <option value="IDR">Indonesia Rupiah</option>
                <option value="USD">United States Dollar</option>
                <option value="SGD">Singapore Dollar</option>
              </select>
            </div>
          </div>
        </div>

        <!-- ROW 3: Phone & Website -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider" for="phone">
              Phone
            </label>
            <div class="relative">
              <Phone class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input
                id="phone"
                type="text"
                placeholder="Enter Phone"
                bind:value={form.phone}
                class="pl-9 bg-white border-slate-200 shadow-sm h-9 text-sm"
              />
            </div>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider" for="website">
              Website
            </label>
            <div class="relative">
              <Globe class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input
                id="website"
                type="text"
                placeholder="Enter Website"
                bind:value={form.website}
                class="pl-9 bg-white border-slate-200 shadow-sm h-9 text-sm"
              />
            </div>
          </div>
        </div>

        <!-- Address -->
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider" for="address">
            Address
          </label>
          <textarea
            id="address"
            bind:value={form.address}
            placeholder="Enter Address"
            class="w-full min-h-[90px] p-3 rounded-md border border-slate-200 bg-white text-sm outline-none focus:border-emerald-600 shadow-sm resize-y transition-colors"
          ></textarea>
        </div>

        <!-- ROW 5: Enabled & Reference -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1.5">
            <span class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
              Enabled
            </span>
            <div class="flex items-center gap-1">
              <button
                type="button"
                class={cn(
                  "px-4 py-1.5 text-xs font-semibold rounded transition-colors shadow-sm border",
                  form.is_active
                    ? "bg-emerald-600 border-emerald-600 text-white"
                    : "bg-white border-slate-200 text-slate-600 hover:bg-slate-50"
                )}
                on:click={() => form.is_active = true}
              >
                Yes
              </button>
              <button
                type="button"
                class={cn(
                  "px-4 py-1.5 text-xs font-semibold rounded transition-colors shadow-sm border",
                  !form.is_active
                    ? "bg-red-500 border-red-500 text-white"
                    : "bg-white border-slate-200 text-slate-600 hover:bg-slate-50"
                )}
                on:click={() => form.is_active = false}
              >
                No
              </button>
            </div>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider" for="reference">
              Reference
            </label>
            <div class="relative">
              <FileText class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input
                id="reference"
                type="text"
                placeholder="Enter Reference"
                bind:value={form.reference}
                class="pl-9 bg-white border-slate-200 shadow-sm h-9 text-sm"
              />
            </div>
          </div>
        </div>

        <!-- Allow Login? -->
        <div class="pt-2">
          <label class="flex items-center gap-3 cursor-pointer w-fit">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">
              Allow Login?
            </span>
            <input
              type="checkbox"
              bind:checked={form.allow_login}
              class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 h-5 w-5 cursor-pointer shadow-sm"
            />
          </label>
        </div>

        <!-- Submit Section -->
        <div class="pt-4 border-t border-slate-100 flex gap-3">
          <Button
            class="bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm font-semibold flex items-center gap-2 px-6"
            on:click={submit}
            disabled={submitting}
          >
            <Save class="h-4 w-4" />
            {submitting ? 'Saving...' : 'Save'}
          </Button>
          <Button
            variant="outline"
            class="border-slate-200 text-slate-600 hover:bg-slate-50"
            on:click={() => router.visit('/customers')}
            disabled={submitting}
          >
            Cancel
          </Button>
        </div>

      </Card.Content>
    </Card.Root>
  </div>
</AppLayout>
