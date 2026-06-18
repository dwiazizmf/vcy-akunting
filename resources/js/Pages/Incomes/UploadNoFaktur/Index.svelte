<script>
  import AppLayout from '../../../Layouts/AppLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import * as Card from '$lib/components/ui/card';
  import { FileText, Save, X } from 'lucide-svelte';

  let selectedFile = null;

  function handleFileChange(event) {
    const files = event.target.files;
    if (files.length > 0) {
      selectedFile = files[0];
    } else {
      selectedFile = null;
    }
  }

  function handleSave() {
    if (!selectedFile) {
      alert('Silakan pilih file terlebih dahulu.');
      return;
    }
    alert(`Mengupload file: ${selectedFile.name}`);
  }

  function handleCancel() {
    selectedFile = null;
    // Redirect or clear
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
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-2 pt-4 border-t border-slate-100">
        <!-- Save Button (Classic Green) -->
        <Button
          on:click={handleSave}
          class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-4 py-2 flex items-center gap-1.5 shadow-sm h-9 rounded-md transition-colors"
        >
          <Save class="h-3.5 w-3.5" />
          Save
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
