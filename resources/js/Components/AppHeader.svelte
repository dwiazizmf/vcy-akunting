<script>
    import { page, router } from "@inertiajs/svelte";
    import * as DropdownMenu from "$lib/components/ui/dropdown-menu";
    import { Button } from "$lib/components/ui/button";
    import { cn } from "$lib/utils.js";

    $: companies = $page.props.companies || [];
    $: activeCompanyId = $page.props.active_company_id;
    $: activeCompany = activeCompanyId === 'all' 
        ? { id: 'all', name: 'Semua Perusahaan (Konsolidasi)' } 
        : companies.find((c) => c.id === activeCompanyId);
    $: pathname = $page.url;

    function setCompany(id) {
        if (!id) return;
        router.post("/set-company", { company_id: id });
    }

    let mobileMenuOpen = false;
    let mobileIncomesOpen = false;
    let mobileDokumenOpen = false;
    let mobileExpensesOpen = false;
    let mobileDoubleEntryOpen = false;

    const subItems = [
        { name: "Customers", href: "/customers" },
        { name: "History Invoice", href: "#history-invoice" },
        { name: "Upload No Faktur", href: "/upload-no-faktur" },
    ];

    const dokumenTopItems = [
        { name: "Create Dokumen", href: "/documents/create" },
        { name: "Tanda Terima", href: "/tanda-terima" },
        { name: "Tanda Terima New", href: "/tanda-terima/new" },
        { name: "Surat Tagihan", href: "/surat-tagihan" },
        { name: "Schedule Tukar Faktur", href: "/schedule-tukar-faktur" },
        { name: "Titip Internal", href: "/titip-internal" },
    ];

    const dokumenBottomItems = [
        { name: "List Kirim Tagihan", href: "/list-kirim-tagihan" },
        { name: "Report Mayora", href: "/report-mayora" },
        { name: "Kwitansi", href: "/kwitansi" },
    ];
    
    const dokumenItems = [...dokumenTopItems, ...dokumenBottomItems];

    function logout() {
        router.post("/logout");
    }
</script>

<header
    class="fixed top-0 left-0 right-0 z-50 bg-teal-900 border-b border-teal-800 shadow-md"
>
    <!-- Brand / Top Bar -->
    <div class="h-14 px-4 lg:px-8 flex items-center justify-between">
        <div class="flex items-center gap-2 sm:gap-3">
            <img
                src="/images/logo.png"
                alt="VCY Logo"
                class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg object-contain bg-white p-0.5 shadow-md border border-teal-500/30 shrink-0"
            />
            <span
                class="hidden sm:block font-bold text-white text-lg tracking-tight"
                >VCY Accounting</span
            >
        </div>

        <div class="flex items-center gap-3">
            <!-- Company Selection Dropdown -->
            <DropdownMenu.Root>
                <DropdownMenu.Trigger asChild let:builder>
                    <Button
                        builders={[builder]}
                        variant="ghost"
                        class="flex items-center gap-2 hover:bg-teal-800/50 text-teal-100 hover:text-white px-3 py-1.5 h-auto rounded-lg border border-teal-800/40 cursor-pointer"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                            />
                        </svg>
                        {activeCompany ? activeCompany.name : "Select Company"}
                        <svg
                            class="h-3 w-3 opacity-60"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>
                    </Button>
                </DropdownMenu.Trigger>
                <DropdownMenu.Content
                    class="w-48 bg-white border border-slate-200 rounded-lg p-1 shadow-lg"
                >
                    <DropdownMenu.Label
                        class="text-xs font-semibold text-slate-500 px-3 py-2"
                        >Pilih Perusahaan</DropdownMenu.Label
                    >
                    <DropdownMenu.Separator class="bg-slate-100 my-1" />
                    <DropdownMenu.Item
                        class="px-3 py-2 text-sm text-slate-700 hover:bg-teal-50 rounded-md cursor-pointer flex items-center justify-between font-medium text-teal-800 bg-teal-50/50"
                        on:click={() => setCompany('all')}
                    >
                        <span>Semua Perusahaan (Konsolidasi)</span>
                        {#if activeCompanyId === 'all'}
                            <svg class="h-4 w-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        {/if}
                    </DropdownMenu.Item>
                    <DropdownMenu.Separator class="bg-slate-100 my-1" />
                    {#each companies as company}
                        <DropdownMenu.Item
                            class="px-3 py-2 text-sm text-slate-700 hover:bg-teal-50 rounded-md cursor-pointer flex items-center justify-between"
                            on:click={() => setCompany(company.id)}
                        >
                            <span>{company.name}</span>
                            {#if company.id === activeCompanyId}
                                <svg
                                    class="h-4 w-4 text-teal-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            {/if}
                        </DropdownMenu.Item>
                    {/each}
                </DropdownMenu.Content>
            </DropdownMenu.Root>

            <!-- Notification Dropdown -->
            <DropdownMenu.Root>
                <DropdownMenu.Trigger asChild let:builder>
                    <Button
                        builders={[builder]}
                        variant="ghost"
                        size="icon"
                        class="relative text-teal-100 hover:text-white hover:bg-teal-800/50 rounded-lg h-9 w-9 cursor-pointer"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                            />
                        </svg>
                        <span
                            class="absolute -top-1 -right-1 h-4 w-4 bg-rose-500 text-[10px] font-bold text-white rounded-full flex items-center justify-center border-2 border-teal-900"
                        >
                            3
                        </span>
                    </Button>
                </DropdownMenu.Trigger>
                <DropdownMenu.Content
                    class="w-80 bg-white border border-slate-200 rounded-lg p-1 shadow-lg"
                >
                    <DropdownMenu.Label
                        class="text-xs font-semibold text-slate-500 px-3 py-2"
                        >Notifikasi Terbaru</DropdownMenu.Label
                    >
                    <DropdownMenu.Separator class="bg-slate-100 my-1" />
                    <DropdownMenu.Item
                        class="flex flex-col gap-1 items-start px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 rounded-md cursor-pointer"
                    >
                        <span class="font-semibold text-slate-900"
                            >Invoice Terbayar</span
                        >
                        <span class="text-xs text-slate-500"
                            >PT Inbisco telah membayar Invoice #INV-2026-003</span
                        >
                    </DropdownMenu.Item>
                    <DropdownMenu.Item
                        class="flex flex-col gap-1 items-start px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 rounded-md cursor-pointer"
                    >
                        <span class="font-semibold text-slate-900"
                            >Buku Besar Tidak Seimbang</span
                        >
                        <span class="text-xs text-slate-500"
                            >Terdapat selisih kredit pada jurnal penyesuaian</span
                        >
                    </DropdownMenu.Item>
                </DropdownMenu.Content>
            </DropdownMenu.Root>

            <!-- User Menu -->
            <DropdownMenu.Root>
                <DropdownMenu.Trigger asChild let:builder>
                    <Button
                        builders={[builder]}
                        variant="ghost"
                        class="flex items-center gap-2 hover:bg-teal-800/50 text-teal-100 hover:text-white px-3 py-1.5 h-auto rounded-full border border-teal-800/40 cursor-pointer"
                    >
                        <div
                            class="h-6 w-6 rounded-full bg-teal-700 flex items-center justify-center font-bold text-xs text-white"
                        >
                            A
                        </div>
                        <span class="text-xs font-medium hidden sm:inline-block"
                            >Admin</span
                        >
                        <svg
                            class="h-3 w-3 opacity-60"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>
                    </Button>
                </DropdownMenu.Trigger>
                <DropdownMenu.Content
                    class="w-48 bg-white border border-slate-200 rounded-lg p-1 shadow-lg"
                >
                    <DropdownMenu.Label
                        class="text-xs font-semibold text-slate-500 px-3 py-2"
                        >Akun Saya</DropdownMenu.Label
                    >
                    <DropdownMenu.Separator class="bg-slate-100 my-1" />
                    <DropdownMenu.Item
                        class="px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 rounded-md cursor-pointer"
                        >Profil</DropdownMenu.Item
                    >
                    <DropdownMenu.Item
                        class="px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 rounded-md cursor-pointer"
                        >Pengaturan Akun</DropdownMenu.Item
                    >
                    <DropdownMenu.Separator class="bg-slate-100 my-1" />
                    <DropdownMenu.Item
                        class="px-3 py-2 text-sm text-rose-600 hover:bg-rose-50 rounded-md cursor-pointer font-medium"
                        on:click={logout}
                        >Keluar</DropdownMenu.Item
                    >
                </DropdownMenu.Content>
            </DropdownMenu.Root>

            <!-- Mobile Hamburger -->
            <button
                class="text-teal-100 hover:text-white hover:bg-teal-800/50 rounded-lg h-9 w-9 lg:hidden flex items-center justify-center transition-colors"
                on:click={() => (mobileMenuOpen = !mobileMenuOpen)}
            >
                <svg
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d={mobileMenuOpen
                            ? "M6 18L18 6M6 6l12 12"
                            : "M4 6h16M4 12h16M4 18h16"}
                    />
                </svg>
            </button>
        </div>
    </div>

    <!-- Desktop Nav Bar -->
    <nav
        class="hidden lg:flex h-10 px-6 bg-teal-800/90 border-t border-teal-800/40 items-center gap-1"
    >
        <a
            href="/dashboard"
            class={cn(
                "flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium transition",
                pathname.startsWith("/dashboard")
                    ? "bg-teal-700 text-white shadow-inner ring-1 ring-teal-600"
                    : "text-teal-100 hover:text-white hover:bg-teal-700/40",
            )}
        >
            <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                />
            </svg>
            Dashboard
        </a>

        <!-- Incomes Dropdown -->
        <DropdownMenu.Root>
            <DropdownMenu.Trigger asChild let:builder>
                <Button
                    builders={[builder]}
                    variant="ghost"
                    class={cn(
                        "flex items-center gap-1.5 px-3 py-1.5 h-auto text-xs font-medium rounded-md transition cursor-pointer",
                        pathname.startsWith("/invoices") ||
                            pathname.startsWith("/customers") ||
                            pathname.startsWith("/payments") ||
                            subItems.some(item => pathname.startsWith(item.href))
                            ? "bg-teal-700 text-white shadow-inner ring-1 ring-teal-600"
                            : "text-teal-100 hover:text-white hover:bg-teal-700/40",
                    )}
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    Incomes
                    <svg
                        class="h-3 w-3 opacity-60"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
                        />
                    </svg>
                </Button>
            </DropdownMenu.Trigger>
            <DropdownMenu.Content
                class="w-56 max-h-96 overflow-y-auto bg-white border border-slate-200 rounded-lg p-1 shadow-lg"
            >
                <DropdownMenu.Item
                    class={cn(
                        "flex items-center gap-2 px-3 py-2 text-sm rounded-md cursor-pointer font-semibold",
                        pathname.startsWith("/invoices")
                            ? "bg-teal-50 text-teal-700"
                            : "text-slate-700 hover:bg-slate-50",
                    )}
                >
                    <span
                        class={cn(
                            "w-1.5 h-1.5 rounded-full",
                            pathname.startsWith("/invoices")
                                ? "bg-teal-600"
                                : "bg-slate-300",
                        )}
                    ></span>
                    <a href="/invoices" class="w-full">Invoices</a>
                </DropdownMenu.Item>
                <DropdownMenu.Item
                    class={cn(
                        "flex items-center gap-2 px-3 py-2 text-sm rounded-md cursor-pointer font-semibold",
                        pathname.startsWith("/payments")
                            ? "bg-teal-50 text-teal-700"
                            : "text-slate-700 hover:bg-slate-50",
                    )}
                >
                    <span
                        class={cn(
                            "w-1.5 h-1.5 rounded-full",
                            pathname.startsWith("/payments")
                                ? "bg-teal-600"
                                : "bg-slate-300",
                        )}
                    ></span>
                    <a href="/payments" class="w-full">Pembayaran</a>
                </DropdownMenu.Item>
                <DropdownMenu.Separator class="bg-slate-100 my-1" />
                {#each subItems as item}
                    <DropdownMenu.Item
                        class={cn(
                            "flex items-center gap-2 px-3 py-2 text-sm rounded-md cursor-pointer font-semibold",
                            pathname.startsWith(item.href)
                                ? "bg-teal-50 text-teal-700"
                                : "text-slate-700 hover:bg-slate-50",
                        )}
                    >
                        <span
                            class={cn(
                                "w-1.5 h-1.5 rounded-full",
                                pathname.startsWith(item.href)
                                    ? "bg-teal-600"
                                    : "bg-slate-300",
                            )}
                        ></span>
                        <a href={item.href} class="w-full">{item.name}</a>
                    </DropdownMenu.Item>
                {/each}
            </DropdownMenu.Content>
        </DropdownMenu.Root>

        <!-- Dokumen Dropdown -->
        <DropdownMenu.Root>
            <DropdownMenu.Trigger asChild let:builder>
                <Button
                    builders={[builder]}
                    variant="ghost"
                    class={cn(
                        "flex items-center gap-1.5 px-3 py-1.5 h-auto text-xs font-medium rounded-md transition cursor-pointer",
                        dokumenItems.some(item => pathname.startsWith(item.href))
                            ? "bg-teal-700 text-white shadow-inner ring-1 ring-teal-600"
                            : "text-teal-100 hover:text-white hover:bg-teal-700/40",
                    )}
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    Dokumen
                    <svg
                        class="h-3 w-3 opacity-60"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
                        />
                    </svg>
                </Button>
            </DropdownMenu.Trigger>
            <DropdownMenu.Content
                class="w-56 max-h-96 overflow-y-auto bg-white border border-slate-200 rounded-lg p-1 shadow-lg"
            >
                {#each dokumenTopItems as item}
                    <DropdownMenu.Item
                        class={cn(
                            "flex items-center gap-2 px-3 py-2 text-sm rounded-md cursor-pointer font-semibold",
                            pathname.startsWith(item.href)
                                ? "bg-teal-50 text-teal-700"
                                : "text-slate-700 hover:bg-slate-50",
                        )}
                    >
                        <span
                            class={cn(
                                "w-1.5 h-1.5 rounded-full",
                                pathname.startsWith(item.href)
                                    ? "bg-teal-600"
                                    : "bg-slate-300",
                            )}
                        ></span>
                        <a href={item.href} class="w-full">{item.name}</a>
                    </DropdownMenu.Item>
                {/each}
                
                <DropdownMenu.Separator class="bg-slate-100 my-1" />

                {#each dokumenBottomItems as item}
                    <DropdownMenu.Item
                        class={cn(
                            "flex items-center gap-2 px-3 py-2 text-sm rounded-md cursor-pointer font-semibold",
                            pathname.startsWith(item.href)
                                ? "bg-teal-50 text-teal-700"
                                : "text-slate-700 hover:bg-slate-50",
                        )}
                    >
                        <span
                            class={cn(
                                "w-1.5 h-1.5 rounded-full",
                                pathname.startsWith(item.href)
                                    ? "bg-teal-600"
                                    : "bg-slate-300",
                            )}
                        ></span>
                        <a href={item.href} class="w-full">{item.name}</a>
                    </DropdownMenu.Item>
                {/each}
            </DropdownMenu.Content>
        </DropdownMenu.Root>

        <!-- Expenses Dropdown -->
        <DropdownMenu.Root>
            <DropdownMenu.Trigger asChild let:builder>
                <Button
                    builders={[builder]}
                    variant="ghost"
                    class={cn(
                        "flex items-center gap-1.5 px-3 py-1.5 h-auto text-xs font-medium rounded-md transition cursor-pointer",
                        pathname.startsWith("/expenses") ||
                            pathname.startsWith("/vendors") ||
                            pathname.startsWith("/expense-payments")
                            ? "bg-teal-700 text-white shadow-inner ring-1 ring-teal-600"
                            : "text-teal-100 hover:text-white hover:bg-teal-700/40",
                    )}
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                    </svg>
                    Expenses
                    <svg
                        class="h-3 w-3 opacity-60"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
                        />
                    </svg>
                </Button>
            </DropdownMenu.Trigger>
            <DropdownMenu.Content
                class="w-52 bg-white border border-slate-200 rounded-lg p-1 shadow-lg"
            >
                <DropdownMenu.Item
                    class={cn(
                        "flex items-center gap-2 px-3 py-2 text-sm rounded-md cursor-pointer font-semibold",
                        pathname.startsWith("/expenses")
                            ? "bg-teal-50 text-teal-700"
                            : "text-slate-700 hover:bg-slate-50",
                    )}
                >
                    <span
                        class={cn(
                            "w-1.5 h-1.5 rounded-full",
                            pathname.startsWith("/expenses")
                                ? "bg-teal-600"
                                : "bg-slate-300",
                        )}
                    ></span>
                    <a href="/expenses" class="w-full">Expenses & Bills</a>
                </DropdownMenu.Item>
                <DropdownMenu.Item
                    class={cn(
                        "flex items-center gap-2 px-3 py-2 text-sm rounded-md cursor-pointer font-semibold",
                        pathname.startsWith("/expense-payments")
                            ? "bg-teal-50 text-teal-700"
                            : "text-slate-700 hover:bg-slate-50",
                    )}
                >
                    <span
                        class={cn(
                            "w-1.5 h-1.5 rounded-full",
                            pathname.startsWith("/expense-payments")
                                ? "bg-teal-600"
                                : "bg-slate-300",
                        )}
                    ></span>
                    <a href="/expense-payments" class="w-full">Bill Payments</a>
                </DropdownMenu.Item>
                <DropdownMenu.Separator class="bg-slate-100 my-1" />
                <DropdownMenu.Item
                    class={cn(
                        "flex items-center gap-2 px-3 py-2 text-sm rounded-md cursor-pointer font-semibold",
                        pathname.startsWith("/vendors")
                            ? "bg-teal-50 text-teal-700"
                            : "text-slate-700 hover:bg-slate-50",
                    )}
                >
                    <span
                        class={cn(
                            "w-1.5 h-1.5 rounded-full",
                            pathname.startsWith("/vendors")
                                ? "bg-teal-600"
                                : "bg-slate-300",
                        )}
                    ></span>
                    <a href="/vendors" class="w-full">Vendors</a>
                </DropdownMenu.Item>
                <DropdownMenu.Item
                    class={cn(
                        "flex items-center gap-2 px-3 py-2 text-sm rounded-md cursor-pointer font-semibold",
                        pathname.startsWith("/settings/payment-limits")
                            ? "bg-teal-50 text-teal-700"
                            : "text-slate-700 hover:bg-slate-50",
                    )}
                >
                    <span
                        class={cn(
                            "w-1.5 h-1.5 rounded-full",
                            pathname.startsWith("/settings/payment-limits")
                                ? "bg-teal-600"
                                : "bg-slate-300",
                        )}
                    ></span>
                    <a href="/settings/payment-limits" class="w-full"
                        >Payment Limits</a
                    >
                </DropdownMenu.Item>
            </DropdownMenu.Content>
        </DropdownMenu.Root>

        <!-- Double Entry Dropdown -->
        <DropdownMenu.Root>
            <DropdownMenu.Trigger asChild let:builder>
                <Button
                    builders={[builder]}
                    variant="ghost"
                    class={cn(
                        "flex items-center gap-1.5 px-3 py-1.5 h-auto text-xs font-medium rounded-md transition cursor-pointer",
                        pathname.startsWith("/accounts") ||
                            pathname.startsWith("/journals") ||
                            pathname.startsWith("/ledger")
                            ? "bg-teal-700 text-white shadow-inner ring-1 ring-teal-600"
                            : "text-teal-100 hover:text-white hover:bg-teal-700/40",
                    )}
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                        />
                    </svg>
                    Double-Entry
                    <svg
                        class="h-3 w-3 opacity-60"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
                        />
                    </svg>
                </Button>
            </DropdownMenu.Trigger>
            <DropdownMenu.Content
                class="w-52 bg-white border border-slate-200 rounded-lg p-1 shadow-lg"
            >
                <DropdownMenu.Item
                    class={cn(
                        "flex items-center gap-2 px-3 py-2 text-sm rounded-md cursor-pointer font-semibold",
                        pathname.startsWith("/accounts")
                            ? "bg-teal-50 text-teal-700"
                            : "text-slate-700 hover:bg-slate-50",
                    )}
                >
                    <span
                        class={cn(
                            "w-1.5 h-1.5 rounded-full",
                            pathname.startsWith("/accounts")
                                ? "bg-teal-600"
                                : "bg-slate-300",
                        )}
                    ></span>
                    <a href="/accounts" class="w-full">Chart of Accounts</a>
                </DropdownMenu.Item>
                <DropdownMenu.Item
                    class={cn(
                        "flex items-center gap-2 px-3 py-2 text-sm rounded-md cursor-pointer font-semibold",
                        pathname.startsWith("/journals")
                            ? "bg-teal-50 text-teal-700"
                            : "text-slate-700 hover:bg-slate-50",
                    )}
                >
                    <span
                        class={cn(
                            "w-1.5 h-1.5 rounded-full",
                            pathname.startsWith("/journals")
                                ? "bg-teal-600"
                                : "bg-slate-300",
                        )}
                    ></span>
                    <a href="/journals" class="w-full">Journal Entry</a>
                </DropdownMenu.Item>
                <DropdownMenu.Item
                    class={cn(
                        "flex items-center gap-2 px-3 py-2 text-sm rounded-md cursor-pointer font-semibold",
                        pathname.startsWith("/ledger")
                            ? "bg-teal-50 text-teal-700"
                            : "text-slate-700 hover:bg-slate-50",
                    )}
                >
                    <span
                        class={cn(
                            "w-1.5 h-1.5 rounded-full",
                            pathname.startsWith("/ledger")
                                ? "bg-teal-600"
                                : "bg-slate-300",
                        )}
                    ></span>
                    <a href="/ledger" class="w-full">General Ledger</a>
                </DropdownMenu.Item>
                <DropdownMenu.Separator class="bg-slate-100 my-1" />
                <DropdownMenu.Item
                    class={cn(
                        "flex items-center gap-2 px-3 py-2 text-sm rounded-md cursor-pointer font-semibold",
                        pathname.startsWith("/settings/bank-accounts")
                            ? "bg-teal-50 text-teal-700"
                            : "text-slate-700 hover:bg-slate-50",
                    )}
                >
                    <span
                        class={cn(
                            "w-1.5 h-1.5 rounded-full",
                            pathname.startsWith("/settings/bank-accounts")
                                ? "bg-teal-600"
                                : "bg-slate-300",
                        )}
                    ></span>
                    <a href="/settings/bank-accounts" class="w-full"
                        >Master Bank & Kas</a
                    >
                </DropdownMenu.Item>
            </DropdownMenu.Content>
        </DropdownMenu.Root>

        <a
            href="/settings"
            class={cn(
                "flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium transition ml-auto",
                pathname.startsWith("/settings")
                    ? "bg-teal-700 text-white shadow-inner ring-1 ring-teal-600"
                    : "text-teal-100 hover:text-white hover:bg-teal-700/40",
            )}
        >
            <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
            </svg>
            Settings
        </a>
    </nav>

    <!-- Mobile Menu Drawer -->
    {#if mobileMenuOpen}
        <nav
            class="lg:hidden bg-teal-900 border-t border-teal-800 px-4 py-3 flex flex-col gap-1 transition-all duration-200 max-h-[calc(100vh-3.5rem)] overflow-y-auto"
        >
            <!-- Mobile Company Selection -->
            <div class="px-1 py-2 border-b border-teal-800 mb-2">
                <label
                    class="text-[10px] font-bold text-teal-400 uppercase tracking-wider mb-1.5 block"
                    >Company</label
                >
                <select
                    class="w-full bg-teal-850 border-teal-700 text-teal-100 text-sm rounded-md focus:ring-teal-500 py-1.5 px-2"
                    value={activeCompanyId}
                    on:change={(e) => setCompany(e.target.value === 'all' ? 'all' : parseInt(e.target.value))}
                >
                    <option value="all">Semua Perusahaan (Konsolidasi)</option>
                    {#each companies as company}
                        <option value={company.id}>{company.name}</option>
                    {/each}
                </select>
            </div>

            <a
                href="/dashboard"
                class="px-3 py-2 text-sm font-medium text-teal-100 hover:text-white hover:bg-teal-850 rounded-md transition"
                on:click={() => (mobileMenuOpen = false)}
            >
                Dashboard
            </a>

            <!-- Mobile Incomes Expandable -->
            <div>
                <button
                    class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-teal-100 hover:text-white hover:bg-teal-850 rounded-md transition"
                    on:click={() => (mobileIncomesOpen = !mobileIncomesOpen)}
                >
                    <span>Incomes</span>
                    <svg
                        class={cn(
                            "h-4 w-4 transition-transform",
                            mobileIncomesOpen && "rotate-180",
                        )}
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
                        />
                    </svg>
                </button>
                {#if mobileIncomesOpen}
                    <div
                        class="pl-4 mt-1 flex flex-col border-l border-teal-800 ml-3 gap-1"
                    >
                        <a
                            href="/invoices"
                            class="px-3 py-1.5 text-xs text-teal-200 hover:text-white rounded-md"
                            on:click={() => (mobileMenuOpen = false)}
                        >
                            • Invoices
                        </a>
                        <a
                            href="/payments"
                            class="px-3 py-1.5 text-xs text-teal-200 hover:text-white rounded-md"
                            on:click={() => (mobileMenuOpen = false)}
                        >
                            • Pembayaran
                        </a>
                        {#each subItems as item}
                            <a
                                href={item.href}
                                class="px-3 py-1.5 text-xs text-teal-300 hover:text-white rounded-md"
                                on:click={() => (mobileMenuOpen = false)}
                            >
                                • {item.name}
                            </a>
                        {/each}
                    </div>
                {/if}
            </div>

            <!-- Mobile Dokumen Expandable -->
            <div>
                <button
                    class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-teal-100 hover:text-white hover:bg-teal-850 rounded-md transition"
                    on:click={() => (mobileDokumenOpen = !mobileDokumenOpen)}
                >
                    <span>Dokumen</span>
                    <svg
                        class={cn(
                            "h-4 w-4 transition-transform",
                            mobileDokumenOpen && "rotate-180",
                        )}
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
                        />
                    </svg>
                </button>
                {#if mobileDokumenOpen}
                    <div
                        class="pl-4 mt-1 flex flex-col border-l border-teal-800 ml-3 gap-1"
                    >
                        {#each dokumenItems as item}
                            <a
                                href={item.href}
                                class="px-3 py-1.5 text-xs text-teal-300 hover:text-white rounded-md"
                                on:click={() => (mobileMenuOpen = false)}
                            >
                                • {item.name}
                            </a>
                        {/each}
                    </div>
                {/if}
            </div>

            <!-- Mobile Expenses Expandable -->
            <div>
                <button
                    class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-teal-100 hover:text-white hover:bg-teal-850 rounded-md transition"
                    on:click={() => (mobileExpensesOpen = !mobileExpensesOpen)}
                >
                    <span>Expenses</span>
                    <svg
                        class={cn(
                            "h-4 w-4 transition-transform",
                            mobileExpensesOpen && "rotate-180",
                        )}
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
                        />
                    </svg>
                </button>
                {#if mobileExpensesOpen}
                    <div
                        class="pl-4 mt-1 flex flex-col border-l border-teal-800 ml-3 gap-1"
                    >
                        <a
                            href="/expenses"
                            class="px-3 py-1.5 text-xs text-teal-200 hover:text-white rounded-md"
                            on:click={() => (mobileMenuOpen = false)}
                        >
                            • Expenses & Bills
                        </a>
                        <a
                            href="/expense-payments"
                            class="px-3 py-1.5 text-xs text-teal-200 hover:text-white rounded-md"
                            on:click={() => (mobileMenuOpen = false)}
                        >
                            • Bill Payments
                        </a>
                        <a
                            href="/vendors"
                            class="px-3 py-1.5 text-xs text-teal-200 hover:text-white rounded-md"
                            on:click={() => (mobileMenuOpen = false)}
                        >
                            • Vendors
                        </a>
                        <a
                            href="/settings/payment-limits"
                            class="px-3 py-1.5 text-xs text-teal-200 hover:text-white rounded-md"
                            on:click={() => (mobileMenuOpen = false)}
                        >
                            • Payment Limits
                        </a>
                    </div>
                {/if}
            </div>

            <!-- Mobile Double-Entry Expandable -->
            <div>
                <button
                    class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-teal-100 hover:text-white hover:bg-teal-850 rounded-md transition"
                    on:click={() => (mobileDoubleEntryOpen = !mobileDoubleEntryOpen)}
                >
                    <span>Double-Entry</span>
                    <svg
                        class={cn(
                            "h-4 w-4 transition-transform",
                            mobileDoubleEntryOpen && "rotate-180",
                        )}
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
                        />
                    </svg>
                </button>
                {#if mobileDoubleEntryOpen}
                    <div
                        class="pl-4 mt-1 flex flex-col border-l border-teal-800 ml-3 gap-1"
                    >
                        <a
                            href="/accounts"
                            class="px-3 py-1.5 text-xs text-teal-250 hover:text-white rounded-md"
                            on:click={() => (mobileMenuOpen = false)}
                        >
                            • Chart of Accounts
                        </a>
                        <a
                            href="/journals"
                            class="px-3 py-1.5 text-xs text-teal-250 hover:text-white rounded-md"
                            on:click={() => (mobileMenuOpen = false)}
                        >
                            • Journal Entry
                        </a>
                        <a
                            href="/ledger"
                            class="px-3 py-1.5 text-xs text-teal-250 hover:text-white rounded-md"
                            on:click={() => (mobileMenuOpen = false)}
                        >
                            • General Ledger
                        </a>
                        <a
                            href="/settings/bank-accounts"
                            class="px-3 py-1.5 text-xs text-teal-250 hover:text-white rounded-md"
                            on:click={() => (mobileMenuOpen = false)}
                        >
                            • Master Bank & Kas
                        </a>
                    </div>
                {/if}
            </div>

            <a
                href="/settings"
                class="px-3 py-2 text-sm font-medium text-teal-100 hover:text-white hover:bg-teal-850 rounded-md transition"
                on:click={() => (mobileMenuOpen = false)}
            >
                Settings
            </a>
        </nav>
    {/if}
</header>
