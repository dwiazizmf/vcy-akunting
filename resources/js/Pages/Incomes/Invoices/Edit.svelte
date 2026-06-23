<script>
    import AppLayout from "../../../Layouts/AppLayout.svelte";
    import { Button } from "$lib/components/ui/button";
    import { Input } from "$lib/components/ui/input";
    import * as Card from "$lib/components/ui/card";
    import * as Table from "$lib/components/ui/table";
    import { router, useForm } from "@inertiajs/svelte";

    import {
        User,
        Calendar,
        ShoppingCart,
        FileText,
        MapPin,
        Ship,
        Anchor,
        Plus,
        Trash2,
        Save,
        ArrowLeft,
        Receipt,
        Loader2,
    } from "lucide-svelte";
    import { showToast } from "../../../Stores/toast.js";
    import SearchableSelect from "../../../Components/SearchableSelect.svelte";
    import axios from "axios";

    export let activeTaxes = [];
    export let activeDiscounts = [];
    export let customers = [];
    export let invoice = null;
    export let invoiceTypes = [];
    export let invoices = [];

    const form = useForm({
        revised_invoice_id: invoice?.revised_invoice_id || null,
        customer_id: invoice?.customer_id || null,
        customer_name: invoice?.customer_name || "",
        customer_address: invoice?.customer_address || "",
        customer_npwp: invoice?.customer_npwp || "",
        invoice_type_id: invoice?.invoice_type_id || null,
        invoiced_at: invoice?.invoiced_at
            ? invoice.invoiced_at.split(" ")[0]
            : new Date().toISOString().split("T")[0],
        due_at: invoice?.due_at
            ? invoice.due_at.split(" ")[0]
            : new Date(Date.now() + 7 * 24 * 60 * 60 * 1000)
                  .toISOString()
                  .split("T")[0],
        order_number: invoice?.order_number || "",
        nama_kapal: invoice?.nama_kapal || "",
        departure_date: invoice?.departure_date
            ? invoice.departure_date.split(" ")[0]
            : "",
        notes: invoice?.notes || "",
        no_faktur_pajak: invoice?.no_faktur_pajak || "",
        isFCL: invoice?.isFCL === 1 || invoice?.isFCL === true,
        no_container: invoice?.no_container || "",
        isFaktur: invoice?.isFaktur === 1 || invoice?.isFaktur === true,
        voy: invoice?.voy || "",
        pelabuhan_asal: invoice?.pelabuhan_asal || "",
        pelabuhan_tujuan: invoice?.pelabuhan_tujuan || "",
        items: invoice?.items?.length
            ? invoice.items.map((i) => ({
                  name: i.name,
                  quantity: i.quantity,
                  price: i.price,
              }))
            : [{ name: "", quantity: 1, price: 0 }],
        header_tax_details: invoice?.header_tax_details || [],
        header_discount_details: invoice?.header_discount_details || [],
    });

    let isTax = $form.header_tax_details.length > 0;
    let selectedTaxIds = [];
    let customTaxAmounts = {};

    if (isTax) {
        $form.header_tax_details.forEach(ht => {
            const masterTax = activeTaxes.find(t => t.name === ht.name);
            if (masterTax) {
                selectedTaxIds.push(masterTax.id);
                if (masterTax.type === 'fixed') {
                    customTaxAmounts[masterTax.id] = ht.amount;
                }
            }
        });
    }

    let isDiscount = $form.header_discount_details.length > 0;
    let selectedDiscountIds = [];
    let customDiscountAmounts = {};

    if (isDiscount) {
        $form.header_discount_details.forEach(hd => {
            const masterDiscount = activeDiscounts.find(d => d.name === hd.name);
            if (masterDiscount) {
                selectedDiscountIds.push(masterDiscount.id);
                if (masterDiscount.type === 'fixed') {
                    customDiscountAmounts[masterDiscount.id] = hd.amount;
                }
            }
        });
    }

    $: subtotal = $form.items.reduce(
        (sum, item) => sum + (item.quantity || 0) * (item.price || 0),
        0,
    );

    $: computedTaxDetails = !isTax
        ? []
        : selectedTaxIds.map((id) => {
              const tax = activeTaxes.find((t) => t.id === id);
              let amount = 0;
              if (tax.type === 'percentage') {
                  amount = subtotal * (tax.rate / 100);
              } else {
                  amount = customTaxAmounts[id] !== undefined ? customTaxAmounts[id] : tax.rate;
              }
              return {
                  id: tax.id,
                  name: tax.name,
                  rate: tax.rate,
                  type: tax.type,
                  amount: Number(amount) || 0,
              };
          });

    $: computedDiscountDetails = !isDiscount
        ? []
        : selectedDiscountIds.map((id) => {
              const discount = activeDiscounts.find((d) => d.id === id);
              let amount = 0;
              if (discount.type === 'percentage') {
                  amount = subtotal * (discount.rate / 100);
              } else {
                  amount = customDiscountAmounts[id] !== undefined ? customDiscountAmounts[id] : discount.rate;
              }
              return {
                  id: discount.id,
                  name: discount.name,
                  rate: discount.rate,
                  type: discount.type,
                  amount: Number(amount) || 0,
              };
          });

    $: totalTax = computedTaxDetails.reduce((sum, t) => sum + t.amount, 0);
    $: totalDiscount = computedDiscountDetails.reduce((sum, t) => sum + t.amount, 0);
    $: grandTotal = subtotal - totalDiscount + totalTax;

    function addItem() {
        $form.items = [...$form.items, { name: "", quantity: 1, price: 0 }];
    }

    function removeItem(index) {
        if ($form.items.length > 1) {
            $form.items = $form.items.filter((_, i) => i !== index);
        }
    }

    function toggleTax(taxId) {
        if (selectedTaxIds.includes(taxId)) {
            selectedTaxIds = selectedTaxIds.filter((id) => id !== taxId);
            delete customTaxAmounts[taxId];
            customTaxAmounts = customTaxAmounts;
        } else {
            selectedTaxIds = [...selectedTaxIds, taxId];
            const tax = activeTaxes.find(t => t.id === taxId);
            if (tax && tax.type === 'fixed') {
                customTaxAmounts[taxId] = tax.rate;
                customTaxAmounts = customTaxAmounts;
            }
        }
    }

    function toggleDiscount(discountId) {
        if (selectedDiscountIds.includes(discountId)) {
            selectedDiscountIds = selectedDiscountIds.filter((id) => id !== discountId);
            delete customDiscountAmounts[discountId];
            customDiscountAmounts = customDiscountAmounts;
        } else {
            selectedDiscountIds = [...selectedDiscountIds, discountId];
            const discount = activeDiscounts.find(d => d.id === discountId);
            if (discount && discount.type === 'fixed') {
                customDiscountAmounts[discountId] = discount.rate;
                customDiscountAmounts = customDiscountAmounts;
            }
        }
    }

    let isProcessing = false;

    function updateCustomerName() {
        const selected = customers.find((c) => c.id === $form.customer_id);
        if (selected) {
            $form.customer_name = selected.name;
            $form.customer_address = selected.address || "";
            $form.customer_npwp = selected.npwp || "";
        }
    }

    async function handleRevisionInvoiceChange(event) {
        const id = event.detail.value;
        if (!id) return;

        try {
            const response = await axios.get(`/api/invoices/${id}`);
            const data = response.data;
            if (data) {
                $form.customer_id = data.customer_id;
                $form.customer_name = data.customer_name;
                $form.customer_address = data.customer_address || "";
                $form.customer_npwp = data.customer_npwp || "";
                $form.invoice_type_id = data.invoice_type_id;
                $form.account_id = data.account_id;
                $form.order_number = data.order_number || "";
                $form.nama_kapal = data.nama_kapal || "";
                $form.voy = data.voy || "";
                $form.pelabuhan_asal = data.pelabuhan_asal || "";
                $form.pelabuhan_tujuan = data.pelabuhan_tujuan || "";
                $form.departure_date = data.departure_date ? data.departure_date.split(" ")[0] : "";
                $form.notes = data.notes || "";
                $form.no_faktur_pajak = data.no_faktur_pajak || "";
                $form.isFCL = data.isFCL === 1 || data.isFCL === true;
                $form.no_container = data.no_container || "";
                $form.isFaktur = data.isFaktur === 1 || data.isFaktur === true;
                
                // Map items
                $form.items = data.items.map(item => ({
                    name: item.name,
                    quantity: item.quantity,
                    price: item.price
                }));

                // Map taxes
                selectedTaxIds = [];
                customTaxAmounts = {};
                if (data.header_tax_details) {
                    isTax = data.header_tax_details.length > 0;
                    data.header_tax_details.forEach(ht => {
                        const masterTax = activeTaxes.find(t => t.name === ht.name);
                        if (masterTax) {
                            selectedTaxIds.push(masterTax.id);
                            if (masterTax.type === 'fixed') {
                                customTaxAmounts[masterTax.id] = ht.amount;
                            }
                        }
                    });
                    selectedTaxIds = selectedTaxIds;
                    customTaxAmounts = customTaxAmounts;
                }

                // Map discounts
                selectedDiscountIds = [];
                customDiscountAmounts = {};
                if (data.header_discount_details) {
                    isDiscount = data.header_discount_details.length > 0;
                    data.header_discount_details.forEach(hd => {
                        const masterDiscount = activeDiscounts.find(d => d.name === hd.name);
                        if (masterDiscount) {
                            selectedDiscountIds.push(masterDiscount.id);
                            if (masterDiscount.type === 'fixed') {
                                customDiscountAmounts[masterDiscount.id] = hd.amount;
                            }
                        }
                    });
                    selectedDiscountIds = selectedDiscountIds;
                    customDiscountAmounts = customDiscountAmounts;
                }
                
                showToast("Data invoice lama berhasil dimuat!", "success");
            }
        } catch (error) {
            console.error(error);
            showToast("Gagal mengambil data invoice lama.", "error");
        }
    }

    function submit() {
        if (isProcessing) return;
        isProcessing = true;
        $form.header_tax_details = computedTaxDetails;
        $form.header_discount_details = computedDiscountDetails;
        $form.put(`/invoices/${invoice.id}`, {
            onSuccess: () => {
                showToast("Invoice updated successfully!", "success");
            },
            onFinish: () => {
                isProcessing = false;
            },
        });
    }
</script>

<AppLayout>
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex items-center gap-4">
            <Button
                variant="outline"
                size="icon"
                class="h-8 w-8"
                on:click={() => window.history.back()}
            >
                <ArrowLeft class="h-4 w-4" />
            </Button>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
                Edit Invoice
            </h1>
        </div>

        <Card.Root class="bg-white border-slate-200 shadow-sm">
            <Card.Content class="p-6">
                <!-- HEADER FORM -->
                <div
                    class="grid grid-cols-1 md:grid-cols-3 gap-6 border-b border-slate-100 pb-6 mb-6"
                >
                    <div class="space-y-4 col-span-2 grid grid-cols-2 gap-4">
                        <!-- Revisi Dari Invoice -->
                        <div class="space-y-1.5 col-span-2">
                            <label
                                class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                                >Revisi Dari Invoice (Invoice Number)</label
                            >
                            <SearchableSelect
                                options={invoices}
                                bind:value={$form.revised_invoice_id}
                                on:change={handleRevisionInvoiceChange}
                                placeholder="Pilih invoice untuk direvisi..."
                            />
                        </div>

                        <!-- Customer -->
                        <div class="space-y-1.5 col-span-2">
                            <label
                                class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                                for="customer">Customer</label
                            >
                            <SearchableSelect
                                options={customers}
                                bind:value={$form.customer_id}
                                on:change={updateCustomerName}
                                placeholder="Select a customer..."
                            />
                        </div>

                        <!-- Alamat Invoice -->
                        <div class="space-y-1.5 col-span-2">
                            <label
                                class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                                for="customer_address">Alamat Invoice</label
                            >
                            <textarea
                                id="customer_address"
                                bind:value={$form.customer_address}
                                placeholder="Masukkan Alamat Invoice..."
                                class="flex min-h-[80px] w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm transition-colors placeholder:text-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:cursor-not-allowed disabled:opacity-50"
                            ></textarea>
                        </div>

                        <!-- NPWP -->
                        <div class="space-y-1.5 col-span-2">
                            <label
                                class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                                for="customer_npwp">NPWP</label
                            >
                            <Input
                                id="customer_npwp"
                                type="text"
                                bind:value={$form.customer_npwp}
                                placeholder="Masukkan NPWP..."
                                class="flex h-9 w-full rounded-md border border-slate-200 bg-white px-3 py-1 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                            />
                        </div>

                        <!-- Dates -->
                        <div class="space-y-1.5">
                            <label
                                class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                                >Invoice Date <span class="text-red-500">*</span
                                ></label
                            >
                            <div class="relative">
                                <Calendar
                                    class="absolute left-3 top-2.5 h-4 w-4 text-slate-400"
                                />
                                <Input
                                    type="date"
                                    bind:value={$form.invoiced_at}
                                    class="pl-9 h-9 text-sm bg-white"
                                />
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label
                                class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                                for="due_date">Due Date</label
                            >
                            <Input
                                id="due_date"
                                type="date"
                                bind:value={$form.due_at}
                                min={$form.invoiced_at}
                                required
                            />
                        </div>

                        <!-- Optional Info -->
                        <!-- Tipe Invoice -->
                        <div class="space-y-1.5">
                            <label
                                class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                                >Tipe Invoice</label
                            >
                            <div class="relative">
                                <Ship
                                    class="absolute left-3 top-2.5 h-4 w-4 text-slate-400"
                                />
                                <select
                                    bind:value={$form.invoice_type_id}
                                    class="flex h-9 w-full rounded-md border border-slate-200 bg-white px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 pl-9 pr-8 appearance-none focus:border-teal-500 cursor-pointer text-slate-700"
                                >
                                    <option value={null}>--Pilih Tipe--</option>
                                    {#each invoiceTypes as type}
                                        <option value={type.id}>{type.name}</option>
                                    {/each}
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Order Number -->
                        <div class="space-y-1.5">
                            <label
                                class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                                >Order Number</label
                            >
                            <div class="relative">
                                <ShoppingCart
                                    class="absolute left-3 top-2.5 h-4 w-4 text-slate-400"
                                />
                                <Input
                                    type="text"
                                    bind:value={$form.order_number}
                                    placeholder="Order No"
                                    class="pl-9 h-9 text-sm bg-white"
                                />
                            </div>
                        </div>

                        <!-- Nama Kapal -->
                        <div class="space-y-1.5">
                            <label
                                class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                                >Nama Kapal</label
                            >
                            <div class="relative">
                                <Ship
                                    class="absolute left-3 top-2.5 h-4 w-4 text-slate-400"
                                />
                                <Input
                                    type="text"
                                    bind:value={$form.nama_kapal}
                                    placeholder="Kapal"
                                    class="pl-9 h-9 text-sm bg-white"
                                />
                            </div>
                        </div>

                        <!-- Voy -->
                        <div class="space-y-1.5">
                            <label
                                class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                                >Voy</label
                            >
                            <div class="relative">
                                <Anchor
                                    class="absolute left-3 top-2.5 h-4 w-4 text-slate-400"
                                />
                                <Input
                                    type="text"
                                    bind:value={$form.voy}
                                    placeholder="Voyage"
                                    class="pl-9 h-9 text-sm bg-white"
                                />
                            </div>
                        </div>

                        <!-- Pelabuhan Asal -->
                        <div class="space-y-1.5">
                            <label
                                class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                                >Pelabuhan Asal</label
                            >
                            <div class="relative">
                                <MapPin
                                    class="absolute left-3 top-2.5 h-4 w-4 text-slate-400"
                                />
                                <Input
                                    type="text"
                                    bind:value={$form.pelabuhan_asal}
                                    placeholder="Pelabuhan Asal"
                                    class="pl-9 h-9 text-sm bg-white"
                                />
                            </div>
                        </div>

                        <!-- Pelabuhan Tujuan -->
                        <div class="space-y-1.5">
                            <label
                                class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                                >Pelabuhan Tujuan</label
                            >
                            <div class="relative">
                                <MapPin
                                    class="absolute left-3 top-2.5 h-4 w-4 text-slate-400"
                                />
                                <Input
                                    type="text"
                                    bind:value={$form.pelabuhan_tujuan}
                                    placeholder="Pelabuhan Tujuan"
                                    class="pl-9 h-9 text-sm bg-white"
                                />
                            </div>
                        </div>

                        <!-- Dep. Date (Kapal) -->
                        <div class="space-y-1.5">
                            <label
                                class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                                >Dep. Date (Kapal)</label
                            >
                            <div class="relative">
                                <Anchor
                                    class="absolute left-3 top-2.5 h-4 w-4 text-slate-400"
                                />
                                <Input
                                    type="date"
                                    bind:value={$form.departure_date}
                                    class="pl-9 h-9 text-sm bg-white"
                                />
                            </div>
                        </div>

                        <div class="space-y-1.5 col-span-1">
                            <!-- empty space for alignment -->
                        </div>

                        <!-- FCL status & Container Number -->
                        <div class="space-y-1.5 col-span-1">
                            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Container Load</label>
                            <label class="flex items-center gap-2 h-9 text-sm text-slate-700 cursor-pointer">
                                <input
                                    type="checkbox"
                                    bind:checked={$form.isFCL}
                                    class="rounded text-teal-600 focus:ring-teal-500 border-slate-300 h-4 w-4"
                                />
                                FCL (Full Container Load)
                            </label>
                        </div>
                        <div class="space-y-1.5 col-span-1">
                            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider { !$form.isFCL ? 'opacity-40' : '' }">No. Container</label>
                            <div class="relative">
                                <Input
                                    type="text"
                                    bind:value={$form.no_container}
                                    disabled={!$form.isFCL}
                                    placeholder="Nomor Container"
                                    class="h-9 text-sm bg-white disabled:bg-slate-50 disabled:opacity-50"
                                />
                            </div>
                        </div>

                        <!-- No Faktur Pajak -->
                        <div class="space-y-1.5 col-span-2">
                            <div class="flex items-center justify-between">
                                <label
                                    class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                                    >No Faktur Pajak</label
                                >
                                <label class="flex items-center gap-1.5 text-xs text-slate-600 font-medium cursor-pointer">
                                    <input
                                        type="checkbox"
                                        bind:checked={$form.isFaktur}
                                        class="rounded text-teal-600 focus:ring-teal-500 border-slate-300 h-3.5 w-3.5"
                                    />
                                    is faktur otomatis?
                                </label>
                            </div>
                            <div class="relative">
                                <FileText
                                    class="absolute left-3 top-2.5 h-4 w-4 text-slate-400"
                                />
                                <Input
                                    type="text"
                                    bind:value={$form.no_faktur_pajak}
                                    disabled={$form.isFaktur}
                                    placeholder={$form.isFaktur ? "Nomor Faktur Pajak (Otomatis)" : "No Faktur"}
                                    class="pl-9 h-9 text-sm bg-white disabled:bg-slate-50 disabled:text-slate-500"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1.5 col-span-1">
                        <label
                            class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                            for="notes">Notes / Keterangan</label
                        >
                        <textarea
                            id="notes"
                            bind:value={$form.notes}
                            class="w-full min-h-[150px] p-3 rounded-md border border-slate-200 bg-white text-sm outline-none focus:border-teal-500 shadow-sm resize-none"
                            placeholder="Additional notes for the invoice..."
                            rows="7"
                        ></textarea>
                    </div>
                </div>

                <!-- ITEMS TABLE -->
                <div class="space-y-4 mb-8">
                    <div class="flex items-center justify-between">
                        <h3
                            class="text-sm font-bold text-slate-900 flex items-center gap-2"
                        >
                            <Receipt class="h-4 w-4 text-teal-600" /> Invoice Items
                        </h3>
                    </div>

                    <div
                        class="w-full overflow-x-auto border border-slate-200 rounded-md"
                    >
                        <Table.Root>
                            <Table.Header class="bg-slate-50/50">
                                <Table.Row class="hover:bg-transparent">
                                    <Table.Head
                                        class="font-bold text-slate-700 text-xs py-2 w-10 text-center"
                                        >No</Table.Head
                                    >
                                    <Table.Head
                                        class="font-bold text-slate-700 text-xs py-2"
                                        >Item Description</Table.Head
                                    >
                                    <Table.Head
                                        class="font-bold text-slate-700 text-xs py-2 text-right w-32"
                                        >Qty</Table.Head
                                    >
                                    <Table.Head
                                        class="font-bold text-slate-700 text-xs py-2 text-right w-48"
                                        >Unit Price (Rp)</Table.Head
                                    >
                                    <Table.Head
                                        class="font-bold text-slate-700 text-xs py-2 text-right w-48"
                                        >Total (Rp)</Table.Head
                                    >
                                    <Table.Head
                                        class="font-bold text-slate-700 text-xs py-2 text-center w-16"
                                        >Act</Table.Head
                                    >
                                </Table.Row>
                            </Table.Header>
                            <Table.Body>
                                {#each $form.items as item, i (i)}
                                    <Table.Row
                                        class="hover:bg-slate-50 transition-colors"
                                    >
                                        <Table.Cell
                                            class="p-1.5 text-center text-xs text-slate-500"
                                            >{i + 1}</Table.Cell
                                        >
                                        <Table.Cell class="p-1.5">
                                            <Input
                                                type="text"
                                                class="h-8 text-xs bg-white"
                                                placeholder="Item name..."
                                                bind:value={item.name}
                                            />
                                        </Table.Cell>
                                        <Table.Cell class="p-1.5">
                                            <Input
                                                type="number"
                                                min="1"
                                                step="0.01"
                                                class="h-8 text-xs bg-white text-right"
                                                bind:value={item.quantity}
                                            />
                                        </Table.Cell>
                                        <Table.Cell class="p-1.5">
                                            <Input
                                                type="number"
                                                min="0"
                                                step="1000"
                                                class="h-8 text-xs bg-white text-right"
                                                bind:value={item.price}
                                            />
                                        </Table.Cell>
                                        <Table.Cell
                                            class="p-1.5 text-right text-sm font-medium text-slate-700"
                                        >
                                            {(
                                                item.quantity * item.price
                                            ).toLocaleString("id-ID")}
                                        </Table.Cell>
                                        <Table.Cell class="p-1.5 text-center">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="h-7 w-7 text-red-500 hover:text-red-700 hover:bg-red-50"
                                                on:click={() => removeItem(i)}
                                                disabled={$form.items.length ===
                                                    1}
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </Button>
                                        </Table.Cell>
                                    </Table.Row>
                                {/each}
                            </Table.Body>
                        </Table.Root>
                        <div class="p-2 bg-slate-50 border-t border-slate-200">
                            <Button
                                variant="outline"
                                size="sm"
                                class="text-xs h-8 text-teal-700 border-teal-200 hover:bg-teal-50"
                                on:click={addItem}
                            >
                                <Plus class="h-3.5 w-3.5 mr-1" /> Add Item
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- SUMMARY PANEL -->
                <div
                    class="flex justify-between items-start pt-6 border-t border-slate-100"
                >
                    <!-- Tax and Discount Controls -->
                    <div class="w-1/2 space-y-4">
                        <!-- Discounts Section -->
                        <div class="bg-slate-50 p-4 rounded-md border border-slate-200">
                            <label class="flex items-center gap-2 cursor-pointer w-fit mb-3">
                                <input
                                    type="checkbox"
                                    class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 h-4 w-4"
                                    bind:checked={isDiscount}
                                />
                                <span class="text-sm font-bold text-slate-800">Apply Header Discount</span>
                            </label>

                            {#if isDiscount}
                                <div class="space-y-2 mt-2 pt-2 border-t border-slate-200">
                                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 block">Available Discounts</span>
                                    {#each activeDiscounts as discount}
                                        <div class="flex items-center gap-2">
                                            <label class="flex items-center gap-2 cursor-pointer text-sm text-slate-700 hover:bg-slate-100 p-1.5 rounded transition-colors flex-1">
                                                <input
                                                    type="checkbox"
                                                    class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 h-3.5 w-3.5"
                                                    checked={selectedDiscountIds.includes(discount.id)}
                                                    on:change={() => toggleDiscount(discount.id)}
                                                />
                                                {discount.name}
                                                <span class="text-xs text-slate-400">({discount.type === 'percentage' ? discount.rate + '%' : 'Fixed Nominal'})</span>
                                            </label>
                                            {#if selectedDiscountIds.includes(discount.id) && discount.type === 'fixed'}
                                                <div class="w-32">
                                                    <Input
                                                        type="number"
                                                        class="h-7 text-xs text-right bg-white"
                                                        placeholder="Nominal"
                                                        bind:value={customDiscountAmounts[discount.id]}
                                                    />
                                                </div>
                                            {/if}
                                        </div>
                                    {/each}
                                    {#if activeDiscounts.length === 0}
                                        <div class="text-xs text-slate-500 italic">No active discounts found in system.</div>
                                    {/if}
                                </div>
                            {/if}
                        </div>

                        <!-- Taxes Section -->
                        <div class="bg-slate-50 p-4 rounded-md border border-slate-200">
                            <label class="flex items-center gap-2 cursor-pointer w-fit mb-3">
                                <input
                                    type="checkbox"
                                    class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 h-4 w-4"
                                    bind:checked={isTax}
                                />
                                <span class="text-sm font-bold text-slate-800">Apply Header Tax</span>
                            </label>

                            {#if isTax}
                                <div class="space-y-2 mt-2 pt-2 border-t border-slate-200">
                                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 block">Available Taxes</span>
                                    {#each activeTaxes as tax}
                                        <div class="flex items-center gap-2">
                                            <label class="flex items-center gap-2 cursor-pointer text-sm text-slate-700 hover:bg-slate-100 p-1.5 rounded transition-colors flex-1">
                                                <input
                                                    type="checkbox"
                                                    class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 h-3.5 w-3.5"
                                                    checked={selectedTaxIds.includes(tax.id)}
                                                    on:change={() => toggleTax(tax.id)}
                                                />
                                                {tax.name}
                                                <span class="text-xs text-slate-400">({tax.type === 'percentage' ? tax.rate + '%' : 'Fixed Nominal'})</span>
                                            </label>
                                            {#if selectedTaxIds.includes(tax.id) && tax.type === 'fixed'}
                                                <div class="w-32">
                                                    <Input
                                                        type="number"
                                                        class="h-7 text-xs text-right bg-white"
                                                        placeholder="Nominal"
                                                        bind:value={customTaxAmounts[tax.id]}
                                                    />
                                                </div>
                                            {/if}
                                        </div>
                                    {/each}
                                    {#if activeTaxes.length === 0}
                                        <div class="text-xs text-slate-500 italic">No active taxes found in system.</div>
                                    {/if}
                                </div>
                            {/if}
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="w-1/3 space-y-3">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500 font-medium"
                                >Subtotal</span
                            >
                            <span class="font-semibold text-slate-700"
                                >Rp {subtotal.toLocaleString("id-ID")}</span
                            >
                        </div>

                        {#if isDiscount && $form.header_discount_details.length > 0}
                            <div class="space-y-1 text-red-600">
                                {#each $form.header_discount_details as discount}
                                    <div class="flex justify-between items-center text-xs">
                                        <span>{discount.name} ({discount.type === 'percentage' ? discount.rate + '%' : 'Fixed'})</span>
                                        <span>- Rp {discount.amount.toLocaleString("id-ID")}</span>
                                    </div>
                                {/each}
                            </div>
                            <div class="flex justify-between items-center text-sm border-t border-slate-100 pt-2 text-red-600">
                                <span class="font-medium">Total Discount</span>
                                <span class="font-semibold">- Rp {totalDiscount.toLocaleString("id-ID")}</span>
                            </div>
                        {/if}

                        {#if isTax && $form.header_tax_details.length > 0}
                            <div class="space-y-1">
                                {#each $form.header_tax_details as tax}
                                    <div class="flex justify-between items-center text-xs text-slate-500">
                                        <span>{tax.name} ({tax.type === 'percentage' ? tax.rate + '%' : 'Fixed'})</span>
                                        <span>Rp {tax.amount.toLocaleString("id-ID")}</span>
                                    </div>
                                {/each}
                            </div>
                            <div class="flex justify-between items-center text-sm border-t border-slate-100 pt-2">
                                <span class="text-slate-500 font-medium">Total Tax</span>
                                <span class="font-semibold text-slate-700">Rp {totalTax.toLocaleString("id-ID")}</span>
                            </div>
                        {/if}

                        <div
                            class="flex justify-between items-center text-lg border-t-2 border-slate-800 pt-3 mt-3"
                        >
                            <span class="font-extrabold text-slate-900"
                                >Grand Total</span
                            >
                            <span class="font-extrabold text-teal-700"
                                >Rp {grandTotal.toLocaleString("id-ID")}</span
                            >
                        </div>

                        <div class="pt-6">
                            <Button
                                class="w-full bg-teal-700 hover:bg-teal-800 text-white shadow-sm font-semibold flex items-center justify-center gap-2"
                                on:click={submit}
                                disabled={isProcessing}
                            >
                                {#if isProcessing}
                                    <Loader2 class="h-4 w-4 animate-spin" />
                                    Processing...
                                {:else}
                                    <Save class="h-4 w-4" />
                                    Update Invoice
                                {/if}
                            </Button>
                        </div>
                    </div>
                </div>
            </Card.Content>
        </Card.Root>
    </div>
</AppLayout>
