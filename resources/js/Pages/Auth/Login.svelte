<script>
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import { cn } from '$lib/utils.js';
  import { Mail, Lock, LogIn, Eye, EyeOff, ShieldAlert } from 'lucide-svelte';
  import { fly } from 'svelte/transition';

  let email = '';
  let password = '';
  let remember = false;
  let showPassword = false;
  let submitting = false;
  let errors = {};

  function submit() {
    submitting = true;
    errors = {};
    
    if (!email) {
      errors.email = 'Email wajib diisi.';
    }
    if (!password) {
      errors.password = 'Password wajib diisi.';
    }
    
    if (Object.keys(errors).length > 0) {
      submitting = false;
      return;
    }

    router.post('/login', { email, password, remember }, {
      onError: (err) => {
        errors = err;
        submitting = false;
      },
      onFinish: () => {
        submitting = false;
      }
    });
  }
</script>

<div class="min-h-screen flex flex-col justify-center items-center bg-slate-50 relative px-4 overflow-hidden">
  <!-- Decorative Background Blobs -->
  <div class="absolute top-1/4 left-1/4 -translate-x-1/2 -translate-y-1/2 w-80 h-80 rounded-full bg-teal-500/10 blur-3xl"></div>
  <div class="absolute bottom-1/4 right-1/4 translate-x-1/2 translate-y-1/2 w-80 h-80 rounded-full bg-blue-500/10 blur-3xl"></div>

  <!-- Main Card -->
  <div
    in:fly={{ y: 20, duration: 500 }}
    class="w-full max-w-md bg-white border border-slate-200 border-t-4 border-t-teal-700 rounded-xl shadow-xl z-10 overflow-hidden"
  >
    <div class="p-8 space-y-6">
      
      <!-- Brand & Header -->
      <div class="text-center space-y-2">
        <div class="inline-flex items-center justify-center mb-2">
          <img src="/images/logo.png" alt="VCY Logo" class="w-14 h-14 rounded-2xl object-contain bg-slate-50 p-1 shadow-md border border-slate-100" />
        </div>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Welcome Back</h2>
        <p class="text-xs text-slate-500 font-medium">Masuk ke sistem akuntansi Vcy Accounting</p>
      </div>

      <!-- Error Alert (Global) -->
      {#if errors.error}
        <div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-lg flex items-start gap-2.5 text-xs animate-shake">
          <ShieldAlert class="h-4 w-4 shrink-0 mt-0.5" />
          <span>{errors.error}</span>
        </div>
      {/if}

      <!-- Form -->
      <form on:submit|preventDefault={submit} class="space-y-4">
        <!-- Email Field -->
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider" for="email">Email</label>
          <div class="relative">
            <Mail class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
            <Input
              id="email"
              type="email"
              placeholder="name@company.com"
              bind:value={email}
              class={cn("pl-9 bg-white border-slate-200 shadow-sm h-9 text-sm focus-visible:ring-teal-500/20 focus-visible:border-teal-750", errors.email && "border-red-450")}
            />
          </div>
          {#if errors.email}
            <p class="text-xs text-red-500 mt-1">{errors.email}</p>
          {/if}
        </div>

        <!-- Password Field -->
        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider" for="password">Password</label>
            <a href="#forgot" class="text-xs font-semibold text-teal-700 hover:text-teal-850 hover:underline">Lupa Password?</a>
          </div>
          <div class="relative">
            <Lock class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
            <Input
              id="password"
              type={showPassword ? "text" : "password"}
              placeholder="Masukkan password"
              bind:value={password}
              class={cn("pl-9 pr-9 bg-white border-slate-200 shadow-sm h-9 text-sm focus-visible:ring-teal-500/20 focus-visible:border-teal-750", errors.password && "border-red-450")}
            />
            <button
              type="button"
              class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 transition-colors"
              on:click={() => showPassword = !showPassword}
            >
              {#if showPassword}
                <EyeOff class="h-4 w-4" />
              {:else}
                <Eye class="h-4 w-4" />
              {/if}
            </button>
          </div>
          {#if errors.password}
            <p class="text-xs text-red-500 mt-1">{errors.password}</p>
          {/if}
        </div>

        <!-- Remember Me Checkbox -->
        <div class="flex items-center">
          <label class="flex items-center gap-2 cursor-pointer select-none">
            <input
              type="checkbox"
              bind:checked={remember}
              class="rounded border-slate-300 text-teal-700 focus:ring-teal-500 h-4 w-4 cursor-pointer"
            />
            <span class="text-xs font-semibold text-slate-600">Ingat saya di perangkat ini</span>
          </label>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
          <Button
            type="submit"
            disabled={submitting}
            class="w-full bg-teal-700 hover:bg-teal-800 text-white font-bold h-9 text-sm shadow-md flex items-center justify-center gap-2 transition"
          >
            {#if submitting}
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Memproses...
            {:else}
              <LogIn class="h-4 w-4" />
              Masuk
            {/if}
          </Button>
        </div>
      </form>
    </div>
  </div>

  <!-- Footer Info -->
  <div class="mt-8 text-center text-slate-400 text-[11px] font-medium z-10 space-y-1">
    <p>© 2026 Vcy Accounting. All rights reserved.</p>
    <p>Powered By ID-Fleet</p>
  </div>
</div>

<style>
  /* Subtle shake animation for errors */
  @keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-2px); }
    75% { transform: translateX(2px); }
  }
  .animate-shake {
    animation: shake 0.2s ease-in-out 0s 2;
  }
</style>
