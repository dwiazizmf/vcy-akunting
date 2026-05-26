<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Card from '$lib/components/ui/card';
  import * as Table from '$lib/components/ui/table';
  import { cn } from '$lib/utils.js';
  
  import {
    User, Calendar, ShoppingCart, FileText, MapPin, 
    Ship, Anchor, Tag, Plus, Trash2, Save
  } from 'lucide-svelte';

  let items = [
    { name: '', sku: '', satuan: '', qty: 0, price: 0, cubication: 0, tpv: 0, total: 0, tax: false }
  ];

  function addItem() {
    items = [...items, { name: '', sku: '', satuan: '', qty: 0, price: 0, cubication: 0, tpv: 0, total: 0, tax: false }];
  }

  function removeItem(index) {
    items = items.filter((_, i) => i !== index);
  }
</script>

<AppLayout>
  <div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">New Invoice</h1>
    </div>

    <Card.Root class="bg-white border-slate-200 shadow-sm">
      <Card.Content class="p-6 space-y-6">
        
        <!-- ROW 1 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Customer <span class="text-red-500">*</span></label>
            <div class="flex items-center gap-2">
              <div class="relative flex-1">
                <User class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                <select class="w-full h-9 pl-9 pr-3 rounded-md border border-slate-200 bg-white text-sm outline-none focus:border-teal-500 shadow-sm transition-colors">
                  <option>- Select Customer -</option>
                  <option>PT. Logistik Maju Jaya</option>
                </select>
              </div>
              <Button variant="outline" size="icon" class="h-9 w-9 shrink-0 shadow-sm bg-slate-50 hover:bg-slate-100">
                <Plus class="h-4 w-4" />
              </Button>
            </div>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Invoice Date <span class="text-red-500">*</span></label>
            <div class="relative">
              <Calendar class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input type="date" class="pl-9 bg-white border-slate-200 shadow-sm h-9" />
            </div>
          </div>
        </div>

        <!-- ROW 2 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Due Date <span class="text-red-500">*</span></label>
            <div class="relative">
              <Calendar class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input type="date" class="pl-9 bg-white border-slate-200 shadow-sm h-9" />
            </div>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Order Number</label>
            <div class="relative">
              <ShoppingCart class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input type="text" placeholder="Enter Order Number" class="pl-9 bg-white border-slate-200 shadow-sm h-9" />
            </div>
          </div>
        </div>

        <!-- ROW 3 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Invoice number</label>
            <div class="relative">
              <FileText class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input type="text" placeholder="Enter invoice number" class="pl-9 bg-white border-slate-200 shadow-sm h-9" />
            </div>
          </div>
        </div>

        <!-- Alamat Invoice -->
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Alamat Invoice</label>
          <textarea class="w-full min-h-[80px] p-3 rounded-md border border-slate-200 bg-white text-sm outline-none focus:border-teal-500 shadow-sm resize-y" placeholder="Enter Alamat Invoice"></textarea>
        </div>

        <!-- ROW 4 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Pelabuhan Asal</label>
            <div class="relative">
              <MapPin class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input type="text" placeholder="Enter Pelabuhan Asal" class="pl-9 bg-white border-slate-200 shadow-sm h-9" />
            </div>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Pelabuhan Tujuan</label>
            <div class="relative">
              <MapPin class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input type="text" placeholder="Enter Pelabuhan Tujuan" class="pl-9 bg-white border-slate-200 shadow-sm h-9" />
            </div>
          </div>
        </div>

        <!-- ROW 5 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Nama Kapal</label>
            <div class="relative">
              <Ship class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input type="text" placeholder="Enter Nama Kapal" class="pl-9 bg-white border-slate-200 shadow-sm h-9" />
            </div>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Voy</label>
            <div class="relative">
              <Anchor class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input type="text" placeholder="Enter Voy" class="pl-9 bg-white border-slate-200 shadow-sm h-9" />
            </div>
          </div>
        </div>

        <!-- ROW 6 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Departure Date <span class="text-red-500">*</span></label>
            <div class="relative">
              <Calendar class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input type="date" class="pl-9 bg-white border-slate-200 shadow-sm h-9" />
            </div>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Tipe Invoice</label>
            <div class="relative">
              <Tag class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <select class="w-full h-9 pl-9 pr-3 rounded-md border border-slate-200 bg-white text-sm outline-none focus:border-teal-500 shadow-sm transition-colors">
                <option>--Pilih Tipe--</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Keterangan Invoice -->
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Keterangan Invoice</label>
          <textarea class="w-full min-h-[80px] p-3 rounded-md border border-slate-200 bg-white text-sm outline-none focus:border-teal-500 shadow-sm resize-y" placeholder="Enter Keterangan Invoice"></textarea>
        </div>

        <!-- Checkboxes -->
        <div class="flex flex-col gap-4 py-2">
          <label class="flex items-center gap-3 cursor-pointer w-fit">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider w-32">Is FCL</span>
            <input type="checkbox" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 h-4 w-4" />
          </label>
          <label class="flex items-center gap-3 cursor-pointer w-fit">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider w-32">Faktur Otomatis ?</span>
            <input type="checkbox" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 h-4 w-4" />
          </label>
        </div>

        <!-- ROW 7 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">No Faktur Pajak</label>
            <div class="relative">
              <FileText class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input type="text" placeholder="Enter No Faktur Pajak" class="pl-9 bg-white border-slate-200 shadow-sm h-9" />
            </div>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">NPWP</label>
            <div class="relative">
              <FileText class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input type="text" placeholder="Enter NPWP" class="pl-9 bg-white border-slate-200 shadow-sm h-9" />
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Notes</label>
          <textarea class="w-full min-h-[80px] p-3 rounded-md border border-slate-200 bg-white text-sm outline-none focus:border-teal-500 shadow-sm resize-y" placeholder="Enter Notes"></textarea>
        </div>

        <!-- Is Tax -->
        <div class="py-2">
          <label class="flex items-center gap-3 cursor-pointer w-fit">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider w-32">Is Tax</span>
            <input type="checkbox" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 h-4 w-4" />
          </label>
        </div>

        <!-- User Name -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">User Name</label>
            <div class="relative">
              <User class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              <Input type="text" placeholder="Enter User Name" class="pl-9 bg-white border-slate-200 shadow-sm h-9" />
            </div>
          </div>
        </div>

        <!-- Items Table -->
        <div class="space-y-4 pt-6">
          <h3 class="text-sm font-bold text-slate-900 tracking-tight">Items</h3>
          
          <div class="w-full overflow-x-auto border border-slate-200 rounded-md">
            <Table.Root>
              <Table.Header class="bg-slate-50/50">
                <Table.Row class="hover:bg-transparent">
                  <Table.Head class="font-bold text-slate-700 text-xs text-center">Name</Table.Head>
                  <Table.Head class="font-bold text-slate-700 text-xs text-center">SKU</Table.Head>
                  <Table.Head class="font-bold text-slate-700 text-xs text-center">Satuan</Table.Head>
                  <Table.Head class="font-bold text-slate-700 text-xs text-center">Qty</Table.Head>
                  <Table.Head class="font-bold text-slate-700 text-xs text-center">Price</Table.Head>
                  <Table.Head class="font-bold text-slate-700 text-xs text-center">Cubication</Table.Head>
                  <Table.Head class="font-bold text-slate-700 text-xs text-center">TPV</Table.Head>
                  <Table.Head class="font-bold text-slate-700 text-xs text-center">Total</Table.Head>
                  <Table.Head class="font-bold text-slate-700 text-xs text-center">Tax</Table.Head>
                  <Table.Head class="font-bold text-slate-700 text-xs text-center">Action</Table.Head>
                </Table.Row>
              </Table.Header>
              <Table.Body>
                {#each items as item, i (i)}
                  <Table.Row class="hover:bg-slate-50 transition-colors">
                    <Table.Cell class="p-2"><Input type="text" class="h-8 text-xs bg-white min-w-[120px]" bind:value={item.name} /></Table.Cell>
                    <Table.Cell class="p-2"><Input type="text" class="h-8 text-xs bg-white min-w-[100px]" bind:value={item.sku} /></Table.Cell>
                    <Table.Cell class="p-2"><Input type="text" class="h-8 text-xs bg-white min-w-[80px]" bind:value={item.satuan} /></Table.Cell>
                    <Table.Cell class="p-2"><Input type="number" class="h-8 text-xs bg-white text-right min-w-[70px]" bind:value={item.qty} /></Table.Cell>
                    <Table.Cell class="p-2"><Input type="number" class="h-8 text-xs bg-white text-right min-w-[90px]" bind:value={item.price} /></Table.Cell>
                    <Table.Cell class="p-2"><Input type="number" class="h-8 text-xs bg-white text-right min-w-[80px]" bind:value={item.cubication} /></Table.Cell>
                    <Table.Cell class="p-2"><Input type="number" class="h-8 text-xs bg-white text-right min-w-[80px]" bind:value={item.tpv} /></Table.Cell>
                    <Table.Cell class="p-2"><Input type="number" class="h-8 text-xs bg-white text-right min-w-[100px]" bind:value={item.total} /></Table.Cell>
                    <Table.Cell class="p-2 text-center">
                      <input type="checkbox" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" bind:checked={item.tax} />
                    </Table.Cell>
                    <Table.Cell class="p-2 text-center">
                      <Button variant="ghost" size="icon" class="h-8 w-8 text-red-500 hover:text-red-700 hover:bg-red-50" on:click={() => removeItem(i)}>
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </Table.Cell>
                  </Table.Row>
                {/each}
              </Table.Body>
            </Table.Root>
            <div class="p-3 bg-slate-100/50 border-t border-slate-200">
              <Button variant="ghost" class="w-full text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 shadow-sm" on:click={addItem}>
                Add Row
              </Button>
            </div>
          </div>
        </div>

        <!-- Submit Section -->
        <div class="pt-6">
          <Button class="bg-teal-700 hover:bg-teal-800 text-white shadow-sm font-semibold flex items-center gap-2 px-6">
            <Save class="h-4 w-4" />
            Save
          </Button>
        </div>

      </Card.Content>
    </Card.Root>
  </div>
</AppLayout>
