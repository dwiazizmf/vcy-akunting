<script>
    import AppLayout from "../../Layouts/AppLayout.svelte";
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
    import { showToast } from "../../Stores/toast.js";
    import SearchableSelect from "../../Components/SearchableSelect.svelte";

    export let activeTaxes = [];
    export let customers = [];
    export let invoice = null;
    export let invoiceTypes = [];

    const form = useForm({
        customer_id: invoice?.customer_id || null,
        customer_name: invoice?.customer_name || "",
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
        items: invoice?.items?.length
            ? invoice.items.map((i) => ({
                  name: i.name,
                  quantity: i.quantity,
                  price: i.price,
              }))
            : [{ name: "", quantity: 1, price: 0 }],
        header_tax_details: invoice?.header_tax_details || [],
    });

    let isTax = $form.header_tax_details.length > 0;

    let selectedTaxIds = [];
    if (isTax) {
        const existingTaxNames = $form.header_tax_details.map((t) => t.name);
        selectedTaxIds = activeTaxes
            .filter((t) => existingTaxNames.includes(t.name))
            .map((t) => t.id);
    }

    $: subtotal = $form.items.reduce(
        (sum, item) => sum + (item.quantity || 0) * (item.price || 0),
        0,
    );

    $: computedTaxDetails = !isTax
        ? []
        : selectedTaxIds.map((id) => {
              const tax = activeTaxes.find((t) => t.id === id);
              return {
                  name: tax.name,
                  rate: tax.rate,
                  amount: subtotal * (tax.rate / 100),
              };
          });

    $: totalTax = computedTaxDetails.reduce((sum, t) => sum + t.amount, 0);
    $: grandTotal = subtotal + totalTax;

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
        } else {
            selectedTaxIds = [...selectedTaxIds, taxId];
        }
    }

    let isProcessing = false;

    function updateCustomerName() {
        const selected = customers.find((c) => c.id === $form.customer_id);
        if (selected) {
            $form.customer_name = selected.name;
        }
    }

    function submit() {
        if (isProcessing) return;
        isProcessing = true;
        $form.header_tax_details = computedTaxDetails;
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
                        <div class="space-y-1.5">
                            <label
                                class="text-xs font-bold text-slate-700 uppercase tracking-wider"
                                >No Faktur Pajak</label
                            >
                            <div class="relative">
                                <FileText
                                    class="absolute left-3 top-2.5 h-4 w-4 text-slate-400"
                                />
                                <Input
                                    type="text"
                                    bind:value={$form.no_faktur_pajak}
                                    placeholder="No Faktur"
                                    class="pl-9 h-9 text-sm bg-white"
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
                    <!-- Tax Controls -->
                    <div
                        class="w-1/2 bg-slate-50 p-4 rounded-md border border-slate-200"
                    >
                        <label
                            class="flex items-center gap-2 cursor-pointer w-fit mb-3"
                        >
                            <input
                                type="checkbox"
                                class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 h-4 w-4"
                                bind:checked={isTax}
                            />
                            <span class="text-sm font-bold text-slate-800"
                                >Apply Header Tax</span
                            >
                        </label>

                        {#if isTax}
                            <div
                                class="space-y-2 mt-2 pt-2 border-t border-slate-200"
                            >
                                <span
                                    class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 block"
                                    >Available Taxes</span
                                >
                                {#each activeTaxes as tax}
                                    <label
                                        class="flex items-center gap-2 cursor-pointer text-sm text-slate-700 hover:bg-slate-100 p-1.5 rounded transition-colors"
                                    >
                                        <input
                                            type="checkbox"
                                            class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 h-3.5 w-3.5"
                                            checked={selectedTaxIds.includes(
                                                tax.id,
                                            )}
                                            on:change={() => toggleTax(tax.id)}
                                        />
                                        {tax.name}
                                        <span class="text-xs text-slate-400"
                                            >({tax.rate}%)</span
                                        >
                                    </label>
                                {/each}
                                {#if activeTaxes.length === 0}
                                    <div class="text-xs text-slate-500 italic">
                                        No active taxes found in system.
                                    </div>
                                {/if}
                            </div>
                        {/if}
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

                        {#if isTax && $form.header_tax_details.length > 0}
                            <div class="space-y-1">
                                {#each $form.header_tax_details as tax}
                                    <div
                                        class="flex justify-between items-center text-xs text-slate-500"
                                    >
                                        <span>{tax.name} ({tax.rate}%)</span>
                                        <span
                                            >Rp {tax.amount.toLocaleString(
                                                "id-ID",
                                            )}</span
                                        >
                                    </div>
                                {/each}
                            </div>
                            <div
                                class="flex justify-between items-center text-sm border-t border-slate-100 pt-2"
                            >
                                <span class="text-slate-500 font-medium"
                                    >Total Tax</span
                                >
                                <span class="font-semibold text-slate-700"
                                    >Rp {totalTax.toLocaleString("id-ID")}</span
                                >
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
