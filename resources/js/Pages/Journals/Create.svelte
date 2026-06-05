<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import CoaSelect from '../../Components/CoaSelect.svelte';
  import { router, useForm } from '@inertiajs/svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Table from '$lib/components/ui/table';
  import {
    Save, ArrowLeft, Plus, Trash2, AlertCircle
  } from 'lucide-svelte';
  import { showToast } from '../../Stores/toast.js';

  export let accounts = [];
  export let contacts = [];
  export let currentDate = '';

  const form = useForm({
    date: currentDate,
    reference: '',
    description: '',
    status: 'draft',
    lines: [
      { id: Date.now() + 1, account_id: '', contact_id: '', description: '', debit: 0, credit: 0 },
      { id: Date.now() + 2, account_id: '', contact_id: '', description: '', debit: 0, credit: 0 }
    ]
  });

  $: totalDebit = $form.lines.reduce((sum, line) => sum + (parseFloat(line.debit) || 0), 0);
  $: totalCredit = $form.lines.reduce((sum, line) => sum + (parseFloat(line.credit) || 0), 0);
  $: difference = Math.abs(totalDebit - totalCredit);
  $: isBalanced = difference < 0.001;

  function addLine() {
    $form.lines = [...$form.lines, { 
      id: Date.now(), 
      account_id: '', 
      contact_id: '', 
      description: '', 
      debit: 0, 
      credit: 0 
    }];
  }

  function removeLine(index) {
    if ($form.lines.length <= 2) {
      showToast('Minimal harus ada 2 baris jurnal', 'error');
      return;
    }
    $form.lines = $form.lines.filter((_, i) => i !== index);
  }

  function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  }

  function handleSave(status) {
    if (!isBalanced) {
      showToast('Total Debit dan Credit harus seimbang (Balance)', 'error');
      return;
    }
    if (totalDebit <= 0) {
      showToast('Total transaksi tidak boleh nol', 'error');
      return;
    }

    $form.status = status;
    $form.post('/journals', {
      preserveScroll: true,
      onError: (errors) => {
        showToast('Terjadi kesalahan, periksa form Anda', 'error');
      }
    });
  }
</script>

<svelte:head>
  <title>Buat Jurnal Umum - VCY Accounting</title>
</svelte:head>

<AppLayout>
  <div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <Button variant="outline" size="icon" href="/journals">
          <ArrowLeft size={16} />
        </Button>
        <div>
          <h2 class="text-2xl font-bold tracking-tight text-slate-900">Buat Jurnal Umum</h2>
          <p class="text-sm text-slate-500">Catat transaksi manual secara double-entry</p>
        </div>
      </div>
      <div class="flex gap-2">
        <Button variant="outline" class="gap-2" on:click={() => handleSave('draft')} disabled={$form.processing}>
          <Save size={16} /> Simpan Draft
        </Button>
        <Button class="bg-teal-600 hover:bg-teal-700 text-white gap-2" on:click={() => handleSave('posted')} disabled={$form.processing || !isBalanced}>
          <Save size={16} /> Simpan & Post
        </Button>
      </div>
    </div>

    <!-- Header Info -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
      <h3 class="font-semibold text-slate-800">Informasi Jurnal</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="space-y-2">
          <label for="date" class="text-sm font-medium leading-none">Tanggal Jurnal *</label>
          <Input id="date" type="date" bind:value={$form.date} required />
          {#if $form.errors.date}<p class="text-xs text-red-500">{$form.errors.date}</p>{/if}
        </div>
        <div class="space-y-2">
          <label for="reference" class="text-sm font-medium leading-none">No. Referensi (Opsional)</label>
          <Input id="reference" type="text" bind:value={$form.reference} placeholder="Misal: INV-2023-01" />
          {#if $form.errors.reference}<p class="text-xs text-red-500">{$form.errors.reference}</p>{/if}
        </div>
        <div class="space-y-2 md:col-span-3">
          <label for="description" class="text-sm font-medium leading-none">Deskripsi Transaksi *</label>
          <Input id="description" type="text" bind:value={$form.description} placeholder="Keterangan singkat tentang jurnal ini..." required />
          {#if $form.errors.description}<p class="text-xs text-red-500">{$form.errors.description}</p>{/if}
        </div>
      </div>
    </div>

    <!-- Lines Entry -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
      <div class="p-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
        <h3 class="font-semibold text-slate-800">Baris Jurnal (Lines)</h3>
        <Button variant="outline" size="sm" class="h-8 gap-1" on:click={addLine}>
          <Plus size={14} /> Tambah Baris
        </Button>
      </div>
      
      <div class="overflow-x-auto">
        <Table.Root>
          <Table.Header>
            <Table.Row>
              <Table.Head class="w-[30%]">Akun</Table.Head>
              <Table.Head class="w-[20%]">Kontak (Opsional)</Table.Head>
              <Table.Head class="w-[20%]">Keterangan Baris</Table.Head>
              <Table.Head class="w-[15%] text-right">Debit</Table.Head>
              <Table.Head class="w-[15%] text-right">Credit</Table.Head>
              <Table.Head class="w-[50px]"></Table.Head>
            </Table.Row>
          </Table.Header>
          <Table.Body>
            {#each $form.lines as line, i (line.id)}
              <Table.Row>
                <Table.Cell class="p-2">
                  <CoaSelect bind:value={line.account_id} options={accounts} placeholder="Pilih Akun..." />
                  {#if $form.errors[`lines.${i}.account_id`]}<p class="text-xs text-red-500 mt-1">{$form.errors[`lines.${i}.account_id`]}</p>{/if}
                </Table.Cell>
                <Table.Cell class="p-2">
                  <select 
                    class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    bind:value={line.contact_id}
                  >
                    <option value="">-- Tidak Ada --</option>
                    {#each contacts as contact}
                      <option value={contact.id}>{contact.name}</option>
                    {/each}
                  </select>
                </Table.Cell>
                <Table.Cell class="p-2">
                  <Input type="text" class="h-9" placeholder="Keterangan..." bind:value={line.description} />
                </Table.Cell>
                <Table.Cell class="p-2">
                  <Input type="number" class="h-9 text-right" min="0" step="0.01" bind:value={line.debit} on:input={() => { if(line.debit > 0) line.credit = 0; }} />
                </Table.Cell>
                <Table.Cell class="p-2">
                  <Input type="number" class="h-9 text-right" min="0" step="0.01" bind:value={line.credit} on:input={() => { if(line.credit > 0) line.debit = 0; }} />
                </Table.Cell>
                <Table.Cell class="p-2 text-center">
                  <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-400 hover:text-red-600 hover:bg-red-50" on:click={() => removeLine(i)}>
                    <Trash2 size={14} />
                  </Button>
                </Table.Cell>
              </Table.Row>
            {/each}
          </Table.Body>
        </Table.Root>
      </div>

      <!-- Footer Totals -->
      <div class="bg-slate-50 p-6 border-t border-slate-200">
        <div class="flex flex-col items-end space-y-2">
          <div class="flex items-center gap-8 w-full max-w-sm justify-between">
            <span class="text-slate-600 font-medium">Total Debit:</span>
            <span class="text-slate-900 font-bold text-lg">{formatCurrency(totalDebit)}</span>
          </div>
          <div class="flex items-center gap-8 w-full max-w-sm justify-between">
            <span class="text-slate-600 font-medium">Total Credit:</span>
            <span class="text-slate-900 font-bold text-lg">{formatCurrency(totalCredit)}</span>
          </div>
          <div class="flex items-center gap-8 w-full max-w-sm justify-between pt-2 border-t border-slate-200">
            <span class="text-slate-600 font-medium">Selisih:</span>
            <span class={`font-bold text-lg ${isBalanced ? 'text-teal-600' : 'text-red-600'}`}>
              {formatCurrency(difference)}
            </span>
          </div>
          
          {#if !isBalanced}
            <div class="flex items-center gap-2 text-red-600 bg-red-50 px-3 py-2 rounded-lg mt-4 w-full max-w-sm">
              <AlertCircle size={16} />
              <span class="text-sm font-medium">Jurnal tidak seimbang (Unbalanced). Tambahkan {formatCurrency(difference)} ke {totalDebit > totalCredit ? 'Credit' : 'Debit'}.</span>
            </div>
          {/if}
          {#if $form.errors.lines}
            <p class="text-sm font-medium text-red-500 mt-2">{$form.errors.lines}</p>
          {/if}
        </div>
      </div>
    </div>
  </div>
</AppLayout>
