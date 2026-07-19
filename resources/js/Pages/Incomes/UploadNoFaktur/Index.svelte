<script>
  import AppLayout from '../../../Layouts/AppLayout.svelte';
  import { useForm, router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import * as Card from '$lib/components/ui/card';
  import { FileText, Save, X, Loader2 } from 'lucide-svelte';

  const form = useForm({
    file: null,
  });

  function handleFileChange(event) {
    const files = event.target.files;
    if (files.length > 0) {
      $form.file = files[0];
    } else {
      $form.file = null;
    }
  }

  function handleSave() {
    if (!$form.file) {
      alert('Silakan pilih file terlebih dahulu.');
      return;
    }
    
    $form.post('/upload-no-faktur', {
      preserveScroll: true,
      onSuccess: () => {
        $form.file = null;
        const fileInput = document.getElementById('file-upload');
        if(fileInput) fileInput.value = '';
      }
    });
  }

  function handleCancel() {
    $form.file = null;
    const fileInput = document.getElementById('file-upload');
    if(fileInput) fileInput.value = '';
    router.visit('/invoices');
  }
</script>

<AppLayout>
  <!-- Page Header -->
  <div class="flex flex-col gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 flex items-center gap-2.5">
        <span class="inline-flex items-center justify-center h-9 w-9 rounded-xl bg-teal-700 text-white shadow-sm">
          <FileText class="h-5 w-5" />
        </span>
        Upload No Faktur
      </h1>
      <p class="text-sm text-muted-foreground mt-1 ml-11.5">
        Unggah berkas untuk memperbarui nomor faktur invoice
      </p>
    </div>
  </div>

  <!-- Form Card -->
  <Card.Root class="bg-white border-slate-150 shadow-sm overflow-hidden rounded-xl">
    <div class="p-6 space-y-6">
      
      <!-- Upload File Section -->
      <div class="space-y-2">
        <label for="file-upload" class="text-sm font-bold text-slate-700 block">
          Upload File
        </label>
        
        <div class="flex items-center gap-4">
          <div class="relative flex items-center border border-slate-350 rounded-md bg-slate-50/50 hover:bg-slate-50 transition-colors p-2 w-full max-w-md">
            <input
              id="file-upload"
              type="file"
              on:change={handleFileChange}
              class="block w-full text-xs text-slate-500
                     file:mr-4 file:py-1.5 file:px-3
                     file:rounded-md file:border-0
                     file:text-xs file:font-semibold
                     file:bg-slate-200 file:text-slate-700
                     hover:file:bg-slate-300 file:cursor-pointer cursor-pointer"
            />
          </div>
        </div>

        <div class="mt-4 p-4 bg-slate-50 border border-slate-200 rounded-lg max-w-md">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-xs font-semibold text-slate-800">Contoh Format Excel</h4>
            <a 
              href="/contoh_upload_faktur.xlsx" 
              download 
              class="text-xs font-semibold text-teal-600 hover:text-teal-700 flex items-center gap-1 bg-teal-50 hover:bg-teal-100 px-2 py-1 rounded transition-colors"
            >
              <FileText class="h-3.5 w-3.5" />
              Download File Contoh
            </a>
          </div>
          <div class="border border-slate-200 rounded-md overflow-hidden">
            <table class="w-full text-left text-[11px] text-slate-600 bg-white">
              <thead class="border-b border-slate-200 font-semibold text-slate-700 bg-slate-50">
                <tr>
                  <th class="py-1.5 px-3">Kolom A (Invoice Text)</th>
                  <th class="py-1.5 px-3 border-l border-slate-200">Kolom B (No Faktur)</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr>
                  <td class="py-1.5 px-3 font-medium">00001/VI/2026</td>
                  <td class="py-1.5 px-3 border-l border-slate-100">010.000-26.12345678</td>
                </tr>
                <tr>
                  <td class="py-1.5 px-3 font-medium">00002/VI/2026</td>
                  <td class="py-1.5 px-3 border-l border-slate-100">010.000-26.12345679</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p class="text-[10px] text-slate-500 mt-2">* Pastikan baris ke-1 berisi Header/Judul kolom. Data akan dibaca mulai dari baris ke-2.</p>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-2 pt-4 border-t border-slate-100">
        <!-- Save Button (Classic Green) -->
        <Button
          on:click={handleSave}
          disabled={$form.processing}
          class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-4 py-2 flex items-center gap-1.5 shadow-sm h-9 rounded-md transition-colors disabled:opacity-50"
        >
          {#if $form.processing}
            <Loader2 class="h-3.5 w-3.5 animate-spin" />
            Menyimpan...
          {:else}
            <Save class="h-3.5 w-3.5" />
            Save
          {/if}
        </Button>

        <!-- Cancel Button -->
        <Button
          variant="outline"
          on:click={handleCancel}
          class="bg-white border-slate-200 text-slate-700 hover:bg-slate-50 font-semibold text-xs px-4 py-2 flex items-center gap-1.5 shadow-sm h-9 rounded-md transition-colors"
        >
          <X class="h-3.5 w-3.5 text-slate-500" />
          Cancel
        </Button>
      </div>

    </div>
  </Card.Root>
</AppLayout>
