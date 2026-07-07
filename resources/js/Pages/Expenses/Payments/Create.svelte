<script>
    import AppLayout from "../../../Layouts/AppLayout.svelte";
    import { router } from "@inertiajs/svelte";
    import { Button } from "$lib/components/ui/button";
    import { Input } from "$lib/components/ui/input";
    import * as Card from "$lib/components/ui/card";
    import * as Table from "$lib/components/ui/table";
    import {
        CreditCard,
        Save,
        ArrowLeft,
        Plus,
        Trash2,
        AlertCircle,
    } from "lucide-svelte";
    import SearchableSelect from "../../../Components/SearchableSelect.svelte";
    import { showToast } from "../../../Stores/toast.js";
    import axios from "axios";

    export let banks = [];
    export let customers = [];
    export let accounts = [];
    export let errors = {};

    $: accountOptions = accounts.map(a => ({ id: a.id, name: `${a.code} - ${a.name}` }));

    // Form state
    let form = {
        paid_at: new Date().toISOString().split("T")[0],
        payment_method: "transfer",
        bank_account_id: banks.find((b) => b.is_default)?.id ?? banks[0]?.id ?? null,
        reference: "",
        notes: "",
        allocations: [
            {
                id: Date.now() + 1,
                customer_id: "",
                invoice_id: "",
                outstanding: 0,
                allocated_amount: "",
            },
        ],
        adjustments: [],
    };

    let isSaving = false;

    // Cache invoices per customer { customerId: [invoices...] }
    let customerInvoices = {};
    let loadingInvoices = {};

    async function loadCustomerInvoices(customerId) {
        if (
            !customerId ||
            customerInvoices[customerId] ||
            loadingInvoices[customerId]
        )
            return;
        loadingInvoices[customerId] = true;
        try {
            const res = await axios.get(
                `/api/payments/outstanding?customer_id=${customerId}`,
            );
            customerInvoices[customerId] = res.data;
            customerInvoices = { ...customerInvoices };
        } catch {
            showToast("Gagal memuat invoice customer.", "error");
        } finally {
            loadingInvoices[customerId] = false;
        }
    }

    function handleCustomerChange(index) {
        const line = form.allocations[index];

        // Ensure all lines belong to the same customer
        const otherCustomer = form.allocations.find((a, i) => i !== index && a.customer_id)?.customer_id;
        if (otherCustomer && line.customer_id && otherCustomer !== line.customer_id) {
            showToast("Hanya bisa memilih tagihan dari Customer yang sama dalam 1 pembayaran!", "error");
            line.customer_id = otherCustomer;
        }

        line.invoice_id = "";
        line.outstanding = 0;
        line.allocated_amount = "";
        form.allocations = [...form.allocations];

        if (line.customer_id) {
            loadCustomerInvoices(line.customer_id);
        }
    }

    function handleInvoiceChange(index) {
        const line = form.allocations[index];
        
        // Prevent duplicate invoice selection
        const duplicate = form.allocations.some((a, i) => i !== index && a.invoice_id == line.invoice_id);
        if (duplicate && line.invoice_id) {
            showToast("Tagihan (Invoice) ini sudah dipilih di baris lain!", "error");
            line.invoice_id = "";
        }

        const invList = customerInvoices[line.customer_id] || [];
        const inv = invList.find((i) => i.id == line.invoice_id);

        if (inv) {
            line.outstanding = inv.outstanding;
            line.allocated_amount = inv.outstanding; // Auto-fill
        } else {
            line.outstanding = 0;
            line.allocated_amount = "";
        }
        form.allocations = [...form.allocations];
    }

    function addLine() {
        form.allocations = [
            ...form.allocations,
            {
                id: Date.now(),
                customer_id: "",
                invoice_id: "",
                outstanding: 0,
                allocated_amount: "",
            },
        ];
    }

    function removeLine(index) {
        if (form.allocations.length <= 1) {
            showToast("Minimal harus ada 1 baris invoice", "error");
            return;
        }
        form.allocations = form.allocations.filter((_, i) => i !== index);
    }

    function addAdjustment() {
        form.adjustments = [
            ...form.adjustments,
            { id: Date.now(), account_id: "", amount: "", type: "deduction", description: "" }
        ];
    }

    function removeAdjustment(index) {
        form.adjustments = form.adjustments.filter((_, i) => i !== index);
    }

    $: totalAllocated = form.allocations.reduce(
        (sum, line) => sum + (parseFloat(line.allocated_amount) || 0),
        0,
    );
    $: totalAdditions = form.adjustments.filter(a => a.type === 'addition').reduce((sum, a) => sum + (parseFloat(a.amount) || 0), 0);
    $: totalDeductions = form.adjustments.filter(a => a.type === 'deduction').reduce((sum, a) => sum + (parseFloat(a.amount) || 0), 0);
    $: totalPayment = totalAllocated + totalAdditions - totalDeductions;

    function submit() {
        // Basic validation
        const validAllocations = form.allocations.filter(
            (a) => a.invoice_id && parseFloat(a.allocated_amount) > 0,
        );

        if (validAllocations.length === 0) {
            showToast(
                "Harap isi minimal 1 baris invoice dengan nominal > 0",
                "error",
            );
            return;
        }

        const payload = {
            ...form,
            allocations: validAllocations,
        };

        isSaving = true;
        router.post("/payments", payload, {
            onSuccess: () => {
                isSaving = false;
            },
            onError: (e) => {
                isSaving = false;
                const msg = Object.values(e)[0];
                showToast(msg || "Gagal menyimpan pembayaran.", "error");
            },
        });
    }

    function fmt(n) {
        return new Intl.NumberFormat("id-ID", {
            maximumFractionDigits: 0,
        }).format(n || 0);
    }
</script>

<AppLayout title="Catat Pembayaran">
    <div class="max-w-6xl mx-auto px-4 py-8 space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <Button variant="outline" size="icon" href="/payments">
                    <ArrowLeft size={16} />
                </Button>
                <div>
                    <h2
                        class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2"
                    >
                        <CreditCard class="h-6 w-6 text-teal-600" /> Catat Pembayaran
                    </h2>
                    <p class="text-sm text-slate-500">
                        Mendukung banyak tagihan lintas-customer
                    </p>
                </div>
            </div>
        </div>

        <!-- Payment Info -->
        <div
            class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4"
        >
            <h3 class="font-semibold text-slate-800">Informasi Umum</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="space-y-2">
                    <label
                        class="text-xs font-bold text-slate-500 uppercase tracking-wider"
                        >Tanggal *</label
                    >
                    <Input type="date" bind:value={form.paid_at} class="h-9" />
                </div>

                <div class="space-y-2">
                    <label
                        class="text-xs font-bold text-slate-500 uppercase tracking-wider"
                        >Bank / Kas *</label
                    >
                    <select
                        bind:value={form.bank_account_id}
                        class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    >
                        <option value={null}>-- Pilih Bank --</option>
                        {#each banks as b}
                            <option value={b.id}
                                >{b.name}
                                {b.is_default ? "(Default)" : ""}</option
                            >
                        {/each}
                    </select>
                </div>

                <div class="space-y-2">
                    <label
                        class="text-xs font-bold text-slate-500 uppercase tracking-wider"
                        >Metode Bayar</label
                    >
                    <select
                        bind:value={form.payment_method}
                        class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    >
                        <option value="transfer">Transfer Bank</option>
                        <option value="cash">Tunai</option>
                        <option value="giro">Giro</option>
                        <option value="cheque">Cek</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label
                        class="text-xs font-bold text-slate-500 uppercase tracking-wider"
                        >No Referensi</label
                    >
                    <Input
                        bind:value={form.reference}
                        placeholder="No. transfer / cek"
                        class="h-9"
                    />
                </div>

                <div class="md:col-span-4 space-y-2">
                    <label
                        class="text-xs font-bold text-slate-500 uppercase tracking-wider block"
                        >Catatan Tambahan</label
                    >
                    <textarea
                        bind:value={form.notes}
                        rows="2"
                        placeholder="Catatan opsional..."
                        class="w-full px-3 py-2 rounded-md border border-slate-200 bg-white text-sm outline-none focus:border-teal-500 resize-none"
                    ></textarea>
                </div>
            </div>
        </div>

        <!-- Allocations Table -->
        <div
            class="bg-white border border-slate-200 rounded-xl overflow-visible shadow-sm"
        >
            <div
                class="p-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center rounded-t-xl"
            >
                <h3 class="font-semibold text-slate-800">
                    Alokasi ke Tagihan (Invoices)
                </h3>
                <Button
                    variant="outline"
                    size="sm"
                    class="h-8 gap-1"
                    on:click={addLine}
                >
                    <Plus size={14} /> Tambah Baris
                </Button>
            </div>

            <div class="overflow-visible pb-16 md:pb-32 px-4 md:px-0">
                <table class="w-full text-sm block md:table">
                    <thead class="border-b hidden md:table-header-group">
                        <tr class="md:table-row">
                            <th class="h-12 px-4 text-left align-middle text-xs font-bold text-slate-500 uppercase w-[25%]">Customer</th>
                            <th class="h-12 px-4 text-left align-middle text-xs font-bold text-slate-500 uppercase w-[30%]">Invoice</th>
                            <th class="h-12 px-4 text-right align-middle text-xs font-bold text-slate-500 uppercase w-[20%]">Sisa Tagihan (Rp)</th>
                            <th class="h-12 px-4 text-right align-middle text-xs font-bold text-slate-500 uppercase w-[20%]">Jumlah Bayar (Rp)</th>
                            <th class="h-12 px-4 text-center align-middle w-[50px]"></th>
                        </tr>
                    </thead>
                    <tbody class="block md:table-row-group [&_tr:last-child]:border-b-0 md:[&_tr:last-child]:border-0">
                        {#each form.allocations as line, i (line.id)}
                            <tr class="block md:table-row border border-slate-200 md:border-0 md:border-b rounded-xl md:rounded-none mb-4 md:mb-0 p-4 md:p-0 bg-white md:bg-transparent shadow-sm md:shadow-none transition-colors md:hover:bg-slate-50/50 relative">
                                
                                <!-- Customer Select -->
                                <td class="block md:table-cell md:p-2 align-top mb-3 md:mb-0">
                                    <label class="md:hidden text-xs font-bold text-slate-500 uppercase mb-1 block">Customer</label>
                                    <div class="w-full">
                                        <SearchableSelect
                                            options={customers}
                                            bind:value={line.customer_id}
                                            placeholder="-- Cari/Pilih Customer --"
                                            on:change={() => handleCustomerChange(i)}
                                        />
                                    </div>
                                </td>

                                <!-- Invoice Select -->
                                <td class="block md:table-cell md:p-2 align-top mb-3 md:mb-0">
                                    <label class="md:hidden text-xs font-bold text-slate-500 uppercase mb-1 block">Invoice</label>
                                    <select
                                        class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-1.5 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:bg-slate-50 disabled:text-slate-400"
                                        bind:value={line.invoice_id}
                                        on:change={() => handleInvoiceChange(i)}
                                        disabled={!line.customer_id || loadingInvoices[line.customer_id]}
                                    >
                                        <option value="">-- Pilih Invoice --</option>
                                        {#if line.customer_id && customerInvoices[line.customer_id]}
                                            {#each customerInvoices[line.customer_id] as inv}
                                                <option value={inv.id}>{inv.invoice_number} - {inv.invoice_text} (Rp {fmt(inv.outstanding)})</option>
                                            {/each}
                                        {/if}
                                    </select>
                                    {#if loadingInvoices[line.customer_id]}
                                        <p class="text-[10px] text-teal-600 mt-1">Memuat data invoice...</p>
                                    {:else if line.customer_id && (!customerInvoices[line.customer_id] || customerInvoices[line.customer_id].length === 0)}
                                        <p class="text-[10px] text-rose-500 mt-1">Tidak ada tagihan tertunggak.</p>
                                    {/if}
                                </td>

                                <!-- Outstanding Info -->
                                <td class="block md:table-cell md:p-2 align-top text-left md:text-right md:pt-4 mb-3 md:mb-0">
                                    <label class="md:hidden text-xs font-bold text-slate-500 uppercase mb-1 block">Sisa Tagihan (Rp)</label>
                                    <span class="font-semibold text-slate-700">{fmt(line.outstanding)}</span>
                                </td>

                                <!-- Amount Input -->
                                <td class="block md:table-cell md:p-2 align-top mb-3 md:mb-0">
                                    <label class="md:hidden text-xs font-bold text-slate-500 uppercase mb-1 block">Jumlah Bayar (Rp)</label>
                                    <Input
                                        type="number"
                                        class="h-9 text-left md:text-right font-bold text-teal-700"
                                        min="0"
                                        step="0.01"
                                        bind:value={line.allocated_amount}
                                        placeholder="0"
                                        disabled={!line.invoice_id}
                                    />
                                    {#if parseFloat(line.allocated_amount) > line.outstanding}
                                        <p class="text-[10px] text-amber-600 mt-1 text-left md:text-right">Lebih bayar untuk inv ini.</p>
                                    {/if}
                                </td>

                                <!-- Action -->
                                <td class="block md:table-cell md:p-2 text-right md:text-center align-top md:pt-2.5 absolute md:relative top-2 right-2 md:top-auto md:right-auto">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-slate-400 hover:text-red-600 hover:bg-red-50"
                                        on:click={() => removeLine(i)}
                                    >
                                        <Trash2 size={16} />
                                    </Button>
                                </td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>

            <!-- Footer Totals -->
            <div
                class="bg-slate-50 p-6 border-t border-slate-200"
            >
                <div
                    class="w-full flex flex-col items-end space-y-3"
                >
                    <!-- Total Allocation (Sum) -->
                    <div class="flex items-center justify-between w-full">
                        <span class="text-slate-600 font-medium"
                            >Total Alokasi (Terdistribusi):</span
                        >
                        <span class="text-slate-800 font-bold text-lg"
                            >Rp {fmt(totalAllocated)}</span
                        >
                    </div>

                    <!-- Adjustments Section -->
                    <div class="w-full pt-4 border-t border-slate-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-700 font-bold">Penyesuaian (Biaya/Pajak)</span>
                            <Button variant="outline" size="sm" class="h-7 text-xs px-2" on:click={addAdjustment}>
                                <Plus size={12} class="mr-1" /> Tambah
                            </Button>
                        </div>
                        
                        {#if form.adjustments.length === 0}
                            <p class="text-[11px] text-slate-400 italic text-right">Tidak ada penyesuaian tambahan.</p>
                        {/if}

                        {#each form.adjustments as adj, j (adj.id)}
                            <div class="flex flex-col gap-2 p-3 bg-white border border-slate-200 rounded-lg relative">
                                <Button variant="ghost" size="icon" class="absolute top-1 right-1 h-6 w-6 text-slate-400 hover:text-red-500" on:click={() => removeAdjustment(j)}>
                                    <Trash2 size={12} />
                                </Button>
                                <div class="grid grid-cols-2 gap-2 mt-2">
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase">Tipe</label>
                                        <select bind:value={adj.type} class="w-full h-8 text-xs border border-slate-200 rounded px-2">
                                            <option value="deduction">Memotong (Debit)</option>
                                            <option value="addition">Menambah (Kredit)</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase">Akun COA</label>
                                        <SearchableSelect
                                            options={accountOptions}
                                            bind:value={adj.account_id}
                                            placeholder="-- Cari/Pilih Akun --"
                                        />
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase">Keterangan</label>
                                        <Input type="text" bind:value={adj.description} placeholder="Contoh: PPh 23..." class="h-8 text-xs" />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase">Nominal</label>
                                        <Input type="number" min="0" step="0.01" bind:value={adj.amount} class="h-8 text-xs text-right font-bold" placeholder="0" />
                                    </div>
                                </div>
                            </div>
                        {/each}
                    </div>

                    <!-- Summary -->
                    {#if totalAdditions > 0}
                        <div class="flex items-center justify-between w-full pt-3 text-rose-600">
                            <span class="text-sm font-medium">Total Penambahan:</span>
                            <span class="text-sm font-bold">+ Rp {fmt(totalAdditions)}</span>
                        </div>
                    {/if}
                    {#if totalDeductions > 0}
                        <div class="flex items-center justify-between w-full pt-1 text-teal-600">
                            <span class="text-sm font-medium">Total Pemotongan:</span>
                            <span class="text-sm font-bold">- Rp {fmt(totalDeductions)}</span>
                        </div>
                    {/if}

                    <!-- Total Payment -->
                    <div class="flex items-center justify-between w-full pt-3 border-t border-slate-200">
                        <span class="text-slate-800 font-bold">Total Pembayaran:</span>
                        <span class="text-teal-700 font-bold text-xl">Rp {fmt(totalPayment)}</span>
                    </div>

                    <!-- Save Payment Button -->
                    <div class="pt-6 w-full flex justify-end">
                        <Button
                            class="w-full bg-teal-600 hover:bg-teal-700 text-white gap-2 h-11 text-sm font-bold rounded-xl cursor-pointer"
                            on:click={submit}
                            disabled={isSaving}
                        >
                            <Save size={16} />
                            {isSaving ? "Menyimpan..." : "Simpan Pembayaran"}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</AppLayout>
