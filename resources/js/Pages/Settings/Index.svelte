<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import CoaSelect from '../../Components/CoaSelect.svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Card from '$lib/components/ui/card';
  import * as Table from '$lib/components/ui/table';
  import {
    Building2, Users, FileText, Receipt, Settings, Search, Plus,
    Pencil, Trash2, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight,
    Save, X, AlertCircle, CheckCircle, Shield, Key,
    Upload, Image, Tag, Percent, Lock, Unlock
  } from 'lucide-svelte';

  // ============================================================
  // PROPS FROM LARAVEL
  // ============================================================
  export let activeTab         = 'companies';
  export let initialCompanies  = { data: [], pagination: {} };
  export let initialUsers      = { data: [], pagination: {} };
  export let initialRoles      = [];
  export let allPermissions    = [];
  export let allCompaniesForForm = [];
  export let initialTaxes      = { data: [], pagination: {} };
  export let initialDiscounts  = { data: [], pagination: {} };
  export let invoiceSetting    = {};
  export let accounts          = [];
  export let isAdmin           = false;

  // ============================================================
  // STATE
  // ============================================================
  let currentTab = activeTab;

  // Companies
  let companies = initialCompanies.data;
  let companiesPag = initialCompanies.pagination;
  let companySearch = '';
  let companyLoading = false;
  let showCompanyModal = false;
  let editingCompany = null;
  let companyForm = { name: '', code: '', address: '', phone: '', npwp: '', enabled: true };
  let companyLogoFile = null;
  let companyLogoPreview = null;

  // Taxes
  let taxes = initialTaxes.data;
  let taxesPag = initialTaxes.pagination;
  let taxSearch = '';
  let taxLoading = false;
  let showTaxModal = false;
  let editingTax = null;
  let taxForm = { name: '', rate: '', type: 'percentage', account_id: '', description: '', enabled: true };

  // Discounts
  let discounts = initialDiscounts.data;
  let discountsPag = initialDiscounts.pagination;
  let discountSearch = '';
  let discountLoading = false;
  let showDiscountModal = false;
  let editingDiscount = null;
  let discountForm = { name: '', rate: '', type: 'percentage', description: '', enabled: true };

  export let initialInvoiceTypes = { data: [], pagination: {} };
  let invoiceTypes = initialInvoiceTypes.data;
  let invoiceTypesPag = initialInvoiceTypes.pagination;
  let invoiceTypeSearch = '';
  let invoiceTypeLoading = false;
  let showInvoiceTypeModal = false;
  let editingInvoiceType = null;
  let invoiceTypeForm = { name: '' };

  // Users
  let users = initialUsers.data;
  let usersPag = initialUsers.pagination;
  let userSearch = '';
  let userLoading = false;
  let showUserModal = false;
  let editingUser = null;
  let userForm = { name: '', email: '', password: '', roles: [], companies: [] };

  // Roles
  let roles = initialRoles;
  let showRoleModal = false;
  let editingRole = null;
  let roleForm = { name: '', permissions: [] };
  let showPermModal = false;
  let newPermName = '';

  // Invoice Settings
  let invSet = { ...invoiceSetting };
  let invSetSaving = false;
  let invSetMsg = '';

  // Global toast
  let toast = { show: false, msg: '', type: 'success' };
  function showToast(msg, type = 'success') {
    toast = { show: true, msg, type };
    setTimeout(() => toast = { ...toast, show: false }, 3000);
  }
  import { showConfirm } from '../../Stores/confirmStore.js';

  // Periods
  let periodYear = new Date().getFullYear();
  let periods = [];
  let periodLoading = false;

  async function loadPeriods() {
    periodLoading = true;
    try {
      periods = await apiFetch(`/api/settings/posted-periode?year=${periodYear}`);
    } catch(e) { showToast('Gagal memuat data periode', 'error'); }
    periodLoading = false;
  }

  async function togglePeriod(id, currentStatus) {
    if (!(await showConfirm(currentStatus ? 'Buka periode ini?' : 'Kunci periode ini?'))) return;
    try {
      await apiFetch(`/api/settings/posted-periode/${id}/toggle`, { method: 'POST', body: { status: !currentStatus } });
      showToast('Status periode berhasil diubah', 'success');
      await loadPeriods();
    } catch(e) { showToast('Gagal mengubah status', 'error'); }
  }

  $: if (currentTab === 'periods') {
     loadPeriods();
  }

  // ============================================================
  // CSRF TOKEN
  // ============================================================
  function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  async function apiFetch(url, options = {}) {
    const defaults = {
      headers: {
        'X-CSRF-TOKEN': getCsrf(),
        'Accept': 'application/json',
        ...options.headers,
      },
    };
    if (!(options.body instanceof FormData)) {
      defaults.headers['Content-Type'] = 'application/json';
      if (options.body && typeof options.body === 'object') {
        options.body = JSON.stringify(options.body);
      }
    }
    const res = await fetch(url, { ...options, ...defaults, headers: { ...defaults.headers, ...(options.headers || {}) } });
    const data = await res.json();
    if (!res.ok) throw data;
    return data;
  }

  // ============================================================
  // COMPANIES CRUD
  // ============================================================
  async function loadCompanies(page = 1) {
    companyLoading = true;
    try {
      const data = await apiFetch(`/api/settings/companies?search=${encodeURIComponent(companySearch)}&per_page=25&page=${page}`);
      companies = data.companies;
      companiesPag = data.pagination;
    } catch(e) { showToast('Gagal memuat data perusahaan', 'error'); }
    companyLoading = false;
  }

  function openCompanyModal(company = null) {
    editingCompany = company;
    companyLogoPreview = null;
    companyLogoFile = null;
    if (company) {
      companyForm = { name: company.name, code: company.code || '', address: company.address || '', phone: company.phone || '', npwp: company.npwp || '', enabled: company.enabled };
      if (company.logo_path) companyLogoPreview = `/storage/${company.logo_path}`;
    } else {
      companyForm = { name: '', code: '', address: '', phone: '', npwp: '', enabled: true };
    }
    showCompanyModal = true;
  }

  function handleLogoChange(e) {
    companyLogoFile = e.target.files[0];
    if (companyLogoFile) {
      const reader = new FileReader();
      reader.onload = (ev) => companyLogoPreview = ev.target.result;
      reader.readAsDataURL(companyLogoFile);
    }
  }

  async function saveCompany() {
    const fd = new FormData();
    Object.entries(companyForm).forEach(([k, v]) => fd.append(k, v));
    if (companyLogoFile) fd.append('logo', companyLogoFile);

    try {
      if (editingCompany) {
        await apiFetch(`/api/settings/companies/${editingCompany.id}`, { method: 'POST', body: fd, headers: {} });
        showToast('Perusahaan berhasil diperbarui');
      } else {
        await apiFetch(`/api/settings/companies`, { method: 'POST', body: fd, headers: {} });
        showToast('Perusahaan berhasil ditambahkan');
      }
      showCompanyModal = false;
      await loadCompanies();
    } catch(e) { showToast(e?.message || 'Gagal menyimpan perusahaan', 'error'); }
  }

  async function deleteCompany(id) {
    if (!(await showConfirm('Hapus perusahaan ini?'))) return;
    try {
      await apiFetch(`/api/settings/companies/${id}`, { method: 'DELETE' });
      showToast('Perusahaan dihapus');
      await loadCompanies();
    } catch(e) { showToast('Gagal menghapus', 'error'); }
  }

  // ============================================================
  // TAXES CRUD
  // ============================================================
  async function loadTaxes(page = 1) {
    taxLoading = true;
    try {
      const data = await apiFetch(`/api/settings/taxes?search=${encodeURIComponent(taxSearch)}&per_page=25&page=${page}`);
      taxes = data.taxes;
      taxesPag = data.pagination;
    } catch(e) { showToast('Gagal memuat data pajak', 'error'); }
    taxLoading = false;
  }

  function openTaxModal(tax = null) {
    editingTax = tax;
    if (tax) {
      taxForm = { name: tax.name, rate: tax.rate, type: tax.type, account_id: tax.account_id || '', description: tax.description || '', enabled: tax.enabled };
    } else {
      taxForm = { name: '', rate: '', type: 'percentage', account_id: '', description: '', enabled: true };
    }
    showTaxModal = true;
  }

  async function saveTax() {
    try {
      if (editingTax) {
        await apiFetch(`/api/settings/taxes/${editingTax.id}`, { method: 'PUT', body: taxForm });
        showToast('Pajak berhasil diperbarui');
      } else {
        await apiFetch(`/api/settings/taxes`, { method: 'POST', body: taxForm });
        showToast('Pajak berhasil ditambahkan');
      }
      showTaxModal = false;
      await loadTaxes();
    } catch(e) { showToast(e?.message || 'Gagal menyimpan pajak', 'error'); }
  }

  async function deleteTax(id) {
    if (!(await showConfirm('Hapus pajak ini?'))) return;
    try {
      await apiFetch(`/api/settings/taxes/${id}`, { method: 'DELETE' });
      showToast('Pajak dihapus');
      await loadTaxes();
    } catch(e) { showToast('Gagal menghapus', 'error'); }
  }

  // ============================================================
  // DISCOUNTS CRUD
  // ============================================================
  async function loadDiscounts(page = 1) {
    discountLoading = true;
    try {
      const data = await apiFetch(`/api/settings/discounts?search=${encodeURIComponent(discountSearch)}&per_page=25&page=${page}`);
      discounts = data.discounts;
      discountsPag = data.pagination;
    } catch(e) { showToast('Gagal memuat data diskon', 'error'); }
    discountLoading = false;
  }

  function openDiscountModal(discount = null) {
    editingDiscount = discount;
    if (discount) {
      discountForm = { name: discount.name, rate: discount.rate, type: discount.type, description: discount.description || '', enabled: discount.enabled };
    } else {
      discountForm = { name: '', rate: '', type: 'percentage', description: '', enabled: true };
    }
    showDiscountModal = true;
  }

  async function saveDiscount() {
    try {
      if (editingDiscount) {
        await apiFetch(`/api/settings/discounts/${editingDiscount.id}`, { method: 'PUT', body: discountForm });
        showToast('Diskon berhasil diperbarui');
      } else {
        await apiFetch(`/api/settings/discounts`, { method: 'POST', body: discountForm });
        showToast('Diskon berhasil ditambahkan');
      }
      showDiscountModal = false;
      await loadDiscounts();
    } catch(e) { showToast(e?.message || 'Gagal menyimpan diskon', 'error'); }
  }

  async function deleteDiscount(id) {
    if (!(await showConfirm('Hapus diskon ini?'))) return;
    try {
      await apiFetch(`/api/settings/discounts/${id}`, { method: 'DELETE' });
      showToast('Diskon dihapus');
      await loadDiscounts();
    } catch(e) { showToast('Gagal menghapus', 'error'); }
  }

  let discountSearchTimer;
  function onDiscountSearch() {
    clearTimeout(discountSearchTimer);
    discountSearchTimer = setTimeout(() => loadDiscounts(1), 300);
  }

  // ============================================================
  // INVOICE TYPES CRUD
  // ============================================================
  async function loadInvoiceTypes(page = 1) {
    invoiceTypeLoading = true;
    try {
      const data = await apiFetch(`/api/settings/invoice-types?search=${encodeURIComponent(invoiceTypeSearch)}&per_page=25&page=${page}`);
      invoiceTypes = data.invoiceTypes;
      invoiceTypesPag = data.pagination;
    } catch(e) { showToast('Gagal memuat data tipe invoice', 'error'); }
    invoiceTypeLoading = false;
  }

  function openInvoiceTypeModal(invoiceType = null) {
    editingInvoiceType = invoiceType;
    if (invoiceType) {
      invoiceTypeForm = { name: invoiceType.name };
    } else {
      invoiceTypeForm = { name: '' };
    }
    showInvoiceTypeModal = true;
  }

  async function saveInvoiceType() {
    try {
      if (editingInvoiceType) {
        await apiFetch(`/api/settings/invoice-types/${editingInvoiceType.id}`, { method: 'PUT', body: invoiceTypeForm });
        showToast('Tipe invoice berhasil diperbarui');
      } else {
        await apiFetch(`/api/settings/invoice-types`, { method: 'POST', body: invoiceTypeForm });
        showToast('Tipe invoice berhasil ditambahkan');
      }
      showInvoiceTypeModal = false;
      await loadInvoiceTypes();
    } catch(e) { showToast(e?.message || 'Gagal menyimpan tipe invoice', 'error'); }
  }

  async function deleteInvoiceType(id) {
    if (!(await showConfirm('Hapus tipe invoice ini?'))) return;
    try {
      const res = await apiFetch(`/api/settings/invoice-types/${id}`, { method: 'DELETE' });
      if (res.success) {
        showToast('Tipe invoice dihapus');
        await loadInvoiceTypes();
      } else {
        showToast(res.message || 'Gagal menghapus', 'error');
      }
    } catch(e) { showToast(e?.message || 'Gagal menghapus', 'error'); }
  }

  let invoiceTypeSearchTimer;
  function onInvoiceTypeSearch() {
    clearTimeout(invoiceTypeSearchTimer);
    invoiceTypeSearchTimer = setTimeout(() => loadInvoiceTypes(1), 300);
  }

  // ============================================================
  // USERS CRUD
  // ============================================================
  async function loadUsers(page = 1) {
    userLoading = true;
    try {
      const data = await apiFetch(`/api/settings/users?search=${encodeURIComponent(userSearch)}&per_page=25&page=${page}`);
      users = data.users;
      usersPag = data.pagination;
      roles = data.roles || roles;
    } catch(e) { showToast('Gagal memuat data user', 'error'); }
    userLoading = false;
  }

  function openUserModal(user = null) {
    editingUser = user;
    if (user) {
      const roleIds = roles.filter(r => user.roles.includes(r.name)).map(r => r.id);
      const compIds = user.companies.map(c => c.id);
      userForm = { name: user.name, email: user.email, password: '', roles: roleIds, companies: compIds };
    } else {
      userForm = { name: '', email: '', password: '', roles: [], companies: [] };
    }
    showUserModal = true;
  }

  async function saveUser() {
    try {
      if (editingUser) {
        await apiFetch(`/api/settings/users/${editingUser.id}`, { method: 'PUT', body: userForm });
        showToast('User berhasil diperbarui');
      } else {
        await apiFetch(`/api/settings/users`, { method: 'POST', body: userForm });
        showToast('User berhasil ditambahkan');
      }
      showUserModal = false;
      await loadUsers();
    } catch(e) { showToast(e?.message || 'Gagal menyimpan user', 'error'); }
  }

  async function deleteUser(id) {
    if (!(await showConfirm('Hapus user ini?'))) return;
    try {
      await apiFetch(`/api/settings/users/${id}`, { method: 'DELETE' });
      showToast('User dihapus');
      await loadUsers();
    } catch(e) { showToast('Gagal menghapus', 'error'); }
  }

  // Toggle role/company selection
  function toggleArr(arr, val) {
    const idx = arr.indexOf(val);
    if (idx >= 0) arr.splice(idx, 1);
    else arr.push(val);
    return [...arr];
  }

  // ============================================================
  // ROLES CRUD
  // ============================================================
  async function loadRoles() {
    try {
      const data = await apiFetch(`/api/settings/roles`);
      roles = data.roles;
    } catch(e) { showToast('Gagal memuat roles', 'error'); }
  }

  function openRoleModal(role = null) {
    editingRole = role;
    if (role) {
      const permIds = allPermissions.filter(p => role.permissions.includes(p.name)).map(p => p.id);
      roleForm = { name: role.name, permissions: permIds };
    } else {
      roleForm = { name: '', permissions: [] };
    }
    showRoleModal = true;
  }

  async function saveRole() {
    try {
      if (editingRole) {
        await apiFetch(`/api/settings/roles/${editingRole.id}`, { method: 'PUT', body: roleForm });
        showToast('Role berhasil diperbarui');
      } else {
        await apiFetch(`/api/settings/roles`, { method: 'POST', body: roleForm });
        showToast('Role berhasil ditambahkan');
      }
      showRoleModal = false;
      await loadRoles();
    } catch(e) { showToast(e?.message || 'Gagal menyimpan role', 'error'); }
  }

  async function deleteRole(id) {
    if (!(await showConfirm('Hapus role ini?'))) return;
    try {
      await apiFetch(`/api/settings/roles/${id}`, { method: 'DELETE' });
      showToast('Role dihapus');
      await loadRoles();
    } catch(e) { showToast('Gagal menghapus role', 'error'); }
  }

  async function saveNewPermission() {
    if (!newPermName.trim()) return;
    try {
      const data = await apiFetch(`/api/settings/permissions`, { method: 'POST', body: { name: newPermName.trim() } });
      allPermissions = [...allPermissions, data.permission];
      newPermName = '';
      showToast('Permission ditambahkan');
    } catch(e) { showToast('Gagal menambah permission', 'error'); }
  }

  async function deletePermission(id) {
    if (!(await showConfirm('Hapus permission ini?'))) return;
    try {
      await apiFetch(`/api/settings/permissions/${id}`, { method: 'DELETE' });
      allPermissions = allPermissions.filter(p => p.id !== id);
      showToast('Permission dihapus');
    } catch(e) { showToast('Gagal menghapus', 'error'); }
  }

  // ============================================================
  // INVOICE SETTINGS
  // ============================================================
  async function saveInvoiceSetting() {
    invSetSaving = true;
    invSetMsg = '';
    try {
      await apiFetch('/api/settings/invoice-setting', { method: 'POST', body: invSet });
      invSetMsg = 'Pengaturan berhasil disimpan!';
      showToast('Setting faktur disimpan');
    } catch(e) { invSetMsg = 'Gagal menyimpan pengaturan.'; showToast('Gagal', 'error'); }
    invSetSaving = false;
  }

  // ============================================================
  // HELPERS
  // ============================================================
  function pagInfo(pag) {
    return `${pag.from ?? 0}–${pag.to ?? 0} dari ${pag.total ?? 0}`;
  }

  let companySearchTimer;
  function onCompanySearch() {
    clearTimeout(companySearchTimer);
    companySearchTimer = setTimeout(() => loadCompanies(1), 300);
  }

  let taxSearchTimer;
  function onTaxSearch() {
    clearTimeout(taxSearchTimer);
    taxSearchTimer = setTimeout(() => loadTaxes(1), 300);
  }

  let userSearchTimer;
  function onUserSearch() {
    clearTimeout(userSearchTimer);
    userSearchTimer = setTimeout(() => loadUsers(1), 300);
  }
</script>

<AppLayout title="Settings">
  <!-- Toast -->
  {#if toast.show}
    <div class="fixed bottom-4 right-4 z-[200] flex items-center gap-2 px-4 py-3 rounded-lg shadow-lg text-sm font-medium transition-all
      {toast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'}">
      {#if toast.type === 'success'}<CheckCircle class="h-4 w-4 shrink-0" />{:else}<AlertCircle class="h-4 w-4 shrink-0" />{/if}
      <span>{toast.msg}</span>
    </div>
  {/if}

  <div class="p-4 lg:p-6 space-y-4">
    <!-- Header -->
    <div class="flex items-center gap-3">
      <div class="h-9 w-9 rounded-lg bg-teal-900 flex items-center justify-center">
        <Settings class="h-5 w-5 text-teal-300" />
      </div>
      <div>
        <h1 class="text-lg font-bold text-slate-900">Settings</h1>
        <p class="text-xs text-slate-500">Kelola perusahaan, pengguna, role, pajak, dan konfigurasi sistem</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-slate-200">
      <div class="flex gap-0 -mb-px overflow-x-auto">
        {#each (() => {
          let menuItems = [
            { id: 'companies', label: 'Perusahaan', icon: Building2 },
            { id: 'users', label: 'User & Role', icon: Users },
            { id: 'invoice-setting', label: 'Setting Faktur', icon: FileText },
            { id: 'taxes', label: 'Pajak', icon: Receipt },
            { id: 'discounts', label: 'Diskon', icon: Percent },
            { id: 'invoice-types', label: 'Tipe Invoice', icon: Tag }
          ];
          if (isAdmin) menuItems.push({ id: 'periods', label: 'Kunci Periode', icon: Lock });
          return menuItems;
        })() as tab}
          <button
            id="tab-{tab.id}"
            class="flex items-center gap-2 px-4 py-2.5 text-xs font-semibold border-b-2 transition-colors whitespace-nowrap
              {currentTab === tab.id
                ? 'border-teal-600 text-teal-700 bg-teal-50/60'
                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'}"
            on:click={() => currentTab = tab.id}
          >
            <svelte:component this={tab.icon} class="h-3.5 w-3.5" />
            {tab.label}
          </button>
        {/each}
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- TAB: COMPANIES -->
    <!-- ============================================================ -->
    {#if currentTab === 'companies'}
      <Card.Root class="border border-slate-200 shadow-sm">
        <Card.Header class="py-3 px-4 border-b border-slate-100 flex flex-row items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <Building2 class="h-4 w-4 text-teal-600" />
            <span class="text-sm font-semibold text-slate-800">Daftar Perusahaan</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="relative">
              <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
              <Input
                bind:value={companySearch}
                on:input={onCompanySearch}
                placeholder="Cari perusahaan..."
                class="pl-8 h-8 text-xs w-48 border-slate-200"
              />
            </div>
            <Button class="h-8 text-xs gap-1.5 bg-teal-700 hover:bg-teal-800" on:click={() => openCompanyModal()}>
              <Plus class="h-3.5 w-3.5" /> Tambah
            </Button>
          </div>
        </Card.Header>
        <Card.Content class="p-0">
          <div class="overflow-x-auto">
            <Table.Root>
              <Table.Header>
                <Table.Row class="bg-slate-50/80 border-b border-slate-100">
                  <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500 w-10">#</Table.Head>
                  <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Logo</Table.Head>
                  <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Nama Perusahaan</Table.Head>
                  <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Kode</Table.Head>
                  <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Telepon</Table.Head>
                  <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">NPWP</Table.Head>
                  <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Status</Table.Head>
                  <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500 text-right">Aksi</Table.Head>
                </Table.Row>
              </Table.Header>
              <Table.Body>
                {#if companyLoading}
                  <Table.Row><Table.Cell colspan="8" class="text-center py-8 text-xs text-slate-400">Memuat...</Table.Cell></Table.Row>
                {:else if companies.length === 0}
                  <Table.Row><Table.Cell colspan="8" class="text-center py-8 text-xs text-slate-400">Belum ada data perusahaan</Table.Cell></Table.Row>
                {:else}
                  {#each companies as co, i}
                    <Table.Row class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                      <Table.Cell class="py-1.5 px-3 text-xs text-slate-400">{(companiesPag.from ?? 0) + i}</Table.Cell>
                      <Table.Cell class="py-1.5 px-3">
                        {#if co.logo_path}
                          <img src="/storage/{co.logo_path}" alt={co.name} class="h-8 w-8 rounded object-contain border border-slate-200 bg-white p-0.5" />
                        {:else}
                          <div class="h-8 w-8 rounded bg-slate-100 flex items-center justify-center">
                            <Image class="h-4 w-4 text-slate-300" />
                          </div>
                        {/if}
                      </Table.Cell>
                      <Table.Cell class="py-1.5 px-3 text-xs font-medium text-slate-800">{co.name}</Table.Cell>
                      <Table.Cell class="py-1.5 px-3 text-xs text-slate-600">{co.code || '-'}</Table.Cell>
                      <Table.Cell class="py-1.5 px-3 text-xs text-slate-600">{co.phone || '-'}</Table.Cell>
                      <Table.Cell class="py-1.5 px-3 text-xs text-slate-600 font-mono">{co.npwp || '-'}</Table.Cell>
                      <Table.Cell class="py-1.5 px-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                          {co.enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}">
                          {co.enabled ? 'Aktif' : 'Nonaktif'}
                        </span>
                      </Table.Cell>
                      <Table.Cell class="py-1.5 px-3">
                        <div class="flex items-center gap-1 justify-end">
                          <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-teal-50 text-slate-400 hover:text-teal-600 transition-colors" on:click={() => openCompanyModal(co)}>
                            <Pencil class="h-3.5 w-3.5" />
                          </button>
                          <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-rose-50 text-slate-400 hover:text-rose-500 transition-colors" on:click={() => deleteCompany(co.id)}>
                            <Trash2 class="h-3.5 w-3.5" />
                          </button>
                        </div>
                      </Table.Cell>
                    </Table.Row>
                  {/each}
                {/if}
              </Table.Body>
            </Table.Root>
          </div>
          <!-- Pagination -->
          {#if companiesPag.lastPage > 1}
            <div class="flex items-center justify-between px-4 py-2 border-t border-slate-100 bg-slate-50/50">
              <span class="text-[11px] text-slate-400">Showing {pagInfo(companiesPag)}</span>
              <div class="flex items-center gap-1">
                <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-slate-200 text-slate-500 disabled:opacity-30" disabled={companiesPag.currentPage <= 1} on:click={() => loadCompanies(1)}><ChevronsLeft class="h-3.5 w-3.5" /></button>
                <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-slate-200 text-slate-500 disabled:opacity-30" disabled={companiesPag.currentPage <= 1} on:click={() => loadCompanies(companiesPag.currentPage - 1)}><ChevronLeft class="h-3.5 w-3.5" /></button>
                <span class="text-xs px-2 text-slate-600">{companiesPag.currentPage}/{companiesPag.lastPage}</span>
                <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-slate-200 text-slate-500 disabled:opacity-30" disabled={companiesPag.currentPage >= companiesPag.lastPage} on:click={() => loadCompanies(companiesPag.currentPage + 1)}><ChevronRight class="h-3.5 w-3.5" /></button>
                <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-slate-200 text-slate-500 disabled:opacity-30" disabled={companiesPag.currentPage >= companiesPag.lastPage} on:click={() => loadCompanies(companiesPag.lastPage)}><ChevronsRight class="h-3.5 w-3.5" /></button>
              </div>
            </div>
          {/if}
        </Card.Content>
      </Card.Root>
    {/if}

    <!-- ============================================================ -->
    <!-- TAB: USERS & ROLES -->
    <!-- ============================================================ -->
    {#if currentTab === 'users'}
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Users Table -->
        <div class="xl:col-span-2">
          <div class="bg-white/80 backdrop-blur-xl border-0 ring-1 ring-slate-200/60 rounded-2xl shadow-sm overflow-hidden flex flex-col h-full">
            <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div class="flex items-center gap-2">
                <Users class="h-5 w-5 text-teal-600" />
                <h3 class="font-semibold text-slate-800">Manajemen User</h3>
              </div>
              <div class="flex items-center gap-3">
                <div class="relative group">
                  <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 group-focus-within:text-teal-500 transition-colors" />
                  <input
                    type="text"
                    bind:value={userSearch}
                    on:input={onUserSearch}
                    placeholder="Cari user..."
                    class="h-9 pl-9 pr-4 text-sm w-48 sm:w-56 rounded-xl bg-white border border-slate-200 outline-none transition-all duration-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/20 shadow-sm"
                  />
                </div>
                <button
                  class="flex items-center gap-1.5 h-9 px-4 rounded-xl font-medium text-sm text-white bg-gradient-to-r from-teal-500 to-emerald-500 hover:from-teal-600 hover:to-emerald-600 shadow-md shadow-emerald-500/20 hover:shadow-emerald-500/40 transition-all duration-300"
                  on:click={() => openUserModal()}
                >
                  <Plus class="h-4 w-4" />
                  Tambah
                </button>
              </div>
            </div>
            
            <div class="w-full overflow-x-auto flex-1">
              <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 border-b border-slate-100">
                  <tr>
                    <th class="px-6 py-3 font-semibold">Nama</th>
                    <th class="px-6 py-3 font-semibold">Email</th>
                    <th class="px-6 py-3 font-semibold">Role</th>
                    <th class="px-6 py-3 font-semibold">Perusahaan</th>
                    <th class="px-6 py-3 font-semibold text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  {#if userLoading}
                    <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400">Memuat...</td></tr>
                  {:else if users.length === 0}
                    <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400">Belum ada user yang terdaftar.</td></tr>
                  {:else}
                    {#each users as u}
                      <tr class="hover:bg-slate-50/80 transition-colors group">
                        <td class="px-6 py-3 font-medium text-slate-800">{u.name}</td>
                        <td class="px-6 py-3 text-slate-500">{u.email}</td>
                        <td class="px-6 py-3">
                          <div class="flex flex-wrap gap-1.5">
                            {#each u.roles as r}
                              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-teal-100 text-teal-800 border border-teal-200/50">{r}</span>
                            {/each}
                            {#if u.roles.length === 0}<span class="text-[11px] text-slate-400 italic">No roles</span>{/if}
                          </div>
                        </td>
                        <td class="px-6 py-3">
                          <div class="flex flex-wrap gap-1.5">
                            {#each u.companies as c}
                              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-200/60">{c.name}</span>
                            {/each}
                            {#if u.companies.length === 0}<span class="text-[11px] font-semibold text-slate-400">Semua Akses</span>{/if}
                          </div>
                        </td>
                        <td class="px-6 py-3">
                          <div class="flex items-center gap-1.5 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                            <button class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-teal-50 text-slate-400 hover:text-teal-600 transition-colors" on:click={() => openUserModal(u)} title="Edit User"><Pencil class="h-4 w-4" /></button>
                            <button class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-rose-50 text-slate-400 hover:text-rose-500 transition-colors" on:click={() => deleteUser(u.id)} title="Hapus User"><Trash2 class="h-4 w-4" /></button>
                          </div>
                        </td>
                      </tr>
                    {/each}
                  {/if}
                </tbody>
              </table>
            </div>
            
            <!-- User Pagination -->
            {#if usersPag.lastPage > 1}
              <div class="flex items-center justify-between px-6 py-3 border-t border-slate-100 bg-slate-50/50">
                <span class="text-xs text-slate-500 font-medium">Menampilkan {pagInfo(usersPag)}</span>
                <div class="flex items-center gap-1.5">
                  <button class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-white border border-transparent hover:border-slate-200 hover:shadow-sm text-slate-500 disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:border-transparent disabled:hover:shadow-none transition-all" disabled={usersPag.currentPage <= 1} on:click={() => loadUsers(1)}><ChevronsLeft class="h-4 w-4" /></button>
                  <button class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-white border border-transparent hover:border-slate-200 hover:shadow-sm text-slate-500 disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:border-transparent disabled:hover:shadow-none transition-all" disabled={usersPag.currentPage <= 1} on:click={() => loadUsers(usersPag.currentPage - 1)}><ChevronLeft class="h-4 w-4" /></button>
                  <span class="text-xs px-2 font-medium text-slate-600">{usersPag.currentPage} dari {usersPag.lastPage}</span>
                  <button class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-white border border-transparent hover:border-slate-200 hover:shadow-sm text-slate-500 disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:border-transparent disabled:hover:shadow-none transition-all" disabled={usersPag.currentPage >= usersPag.lastPage} on:click={() => loadUsers(usersPag.currentPage + 1)}><ChevronRight class="h-4 w-4" /></button>
                  <button class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-white border border-transparent hover:border-slate-200 hover:shadow-sm text-slate-500 disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:border-transparent disabled:hover:shadow-none transition-all" disabled={usersPag.currentPage >= usersPag.lastPage} on:click={() => loadUsers(usersPag.lastPage)}><ChevronsRight class="h-4 w-4" /></button>
                </div>
              </div>
            {/if}
          </div>
        </div>

        <!-- Roles & Perms Panels -->
        <div class="space-y-6">
          <!-- Roles Panel -->
          <div class="bg-white/80 backdrop-blur-xl border-0 ring-1 ring-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Shield class="h-5 w-5 text-indigo-600" />
                <h3 class="font-semibold text-slate-800">Manajemen Role</h3>
              </div>
              <button
                class="flex items-center gap-1.5 h-8 px-3 rounded-lg font-medium text-xs text-white bg-gradient-to-r from-indigo-500 to-violet-500 hover:from-indigo-600 hover:to-violet-600 shadow-sm shadow-indigo-500/20 transition-all duration-300"
                on:click={() => openRoleModal()}
              >
                <Plus class="h-3.5 w-3.5" />
                Tambah
              </button>
            </div>
            <div class="p-0">
              {#if roles.length === 0}
                <div class="py-8 text-center text-sm text-slate-400">Belum ada role terdaftar.</div>
              {:else}
                <div class="divide-y divide-slate-50">
                  {#each roles as role}
                    <div class="flex items-start justify-between gap-3 px-5 py-3 hover:bg-slate-50/50 transition-colors group">
                      <div>
                        <p class="text-sm font-semibold text-slate-800 group-hover:text-indigo-600 transition-colors">{role.name}</p>
                        <p class="text-xs text-slate-500 mt-1 flex items-center gap-2">
                          <span class="inline-flex items-center gap-1"><Key class="h-3 w-3" /> {role.permissions.length} perms</span>
                          <span class="text-slate-300">|</span>
                          <span class="inline-flex items-center gap-1"><Users class="h-3 w-3" /> {role.users_count} users</span>
                        </p>
                      </div>
                      <div class="flex gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-indigo-50 text-slate-400 hover:text-indigo-600 transition-colors" on:click={() => openRoleModal(role)} title="Edit Role"><Pencil class="h-4 w-4" /></button>
                        <button class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-rose-50 text-slate-400 hover:text-rose-500 transition-colors" on:click={() => deleteRole(role.id)} title="Hapus Role"><Trash2 class="h-4 w-4" /></button>
                      </div>
                    </div>
                  {/each}
                </div>
              {/if}
            </div>
          </div>

          <!-- Permissions Panel -->
          <div class="bg-white/80 backdrop-blur-xl border-0 ring-1 ring-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Key class="h-5 w-5 text-amber-500" />
                <h3 class="font-semibold text-slate-800">Permissions</h3>
              </div>
              <span class="text-xs font-semibold bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full">{allPermissions.length} total</span>
            </div>
            <div class="p-5 space-y-4">
              <div class="flex gap-2">
                <div class="relative flex-1 group">
                  <input
                    type="text"
                    bind:value={newPermName}
                    placeholder="Contoh: view_reports"
                    class="w-full h-9 px-3 text-sm rounded-xl bg-white border border-slate-200 outline-none transition-all duration-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/20 shadow-sm font-mono placeholder:font-sans"
                  />
                </div>
                <button
                  class="flex items-center justify-center h-9 w-9 shrink-0 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white shadow-sm shadow-amber-500/20 transition-all duration-300"
                  on:click={saveNewPermission}
                  title="Tambah Permission"
                >
                  <Plus class="h-4 w-4" />
                </button>
              </div>
              <div class="max-h-64 overflow-y-auto space-y-1.5 pr-2 custom-scrollbar">
                {#each allPermissions as perm}
                  <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-slate-50 border border-slate-100 hover:border-amber-200 hover:bg-amber-50/30 group transition-all">
                    <span class="text-xs font-mono text-slate-700">{perm.name}</span>
                    <button class="h-6 w-6 flex items-center justify-center rounded-md opacity-0 group-hover:opacity-100 hover:bg-rose-100 text-rose-500 transition-all" on:click={() => deletePermission(perm.id)} title="Hapus Permission"><X class="h-3.5 w-3.5" /></button>
                  </div>
                {/each}
                {#if allPermissions.length === 0}
                  <div class="text-center py-4 text-xs text-slate-400">Belum ada permission</div>
                {/if}
              </div>
            </div>
          </div>
        </div>
      </div>
    {/if}

    <!-- ============================================================ -->
    <!-- TAB: INVOICE SETTINGS -->
    <!-- ============================================================ -->
    {#if currentTab === 'invoice-setting'}
      <div class="max-w-2xl">
        <Card.Root class="border border-slate-200 shadow-sm">
          <Card.Header class="py-3 px-4 border-b border-slate-100">
            <div class="flex items-center gap-2">
              <FileText class="h-4 w-4 text-teal-600" />
              <span class="text-sm font-semibold text-slate-800">Setting Nomor Faktur</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Format no faktur disusun dari bagian-bagian yang bisa digabung, misal: INV / 2026 / 0001</p>
          </Card.Header>
          <Card.Content class="p-5 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <label class="text-xs font-medium text-slate-700">Bagian Pertama (First Faktur)</label>
                <Input bind:value={invSet.first_faktur} placeholder="Contoh: INV" class="h-9 text-sm border-slate-200" />
              </div>
              <div class="space-y-1.5">
                <label class="text-xs font-medium text-slate-700">Bagian Kedua (Second Faktur)</label>
                <Input bind:value={invSet.second_faktur} placeholder="Contoh: 2026 atau kosong" class="h-9 text-sm border-slate-200" />
              </div>
              <div class="space-y-1.5">
                <label class="text-xs font-medium text-slate-700">Bagian Ketiga (Third Faktur)</label>
                <Input bind:value={invSet.third_faktur} placeholder="Contoh: KA atau kosong" class="h-9 text-sm border-slate-200" />
              </div>
              <div class="space-y-1.5">
                <label class="text-xs font-medium text-slate-700">Bagian Keempat (Fourth Faktur)</label>
                <Input bind:value={invSet.fourth_faktur} placeholder="Contoh: 001 atau kosong" class="h-9 text-sm border-slate-200" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-2 border-t border-slate-100">
              <div class="space-y-1.5">
                <label class="text-xs font-medium text-slate-700">Nomor Urut Awal</label>
                <Input bind:value={invSet.no_awal} type="number" min="0" placeholder="1" class="h-9 text-sm border-slate-200" />
              </div>
              <div class="space-y-1.5">
                <label class="text-xs font-medium text-slate-700">Nomor Urut Akhir (Batas)</label>
                <Input bind:value={invSet.no_akhir} type="number" min="1" placeholder="9999" class="h-9 text-sm border-slate-200" />
              </div>
            </div>

            <!-- Preview -->
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
              <p class="text-xs font-medium text-slate-500 mb-1">Preview Format Faktur:</p>
              <p class="text-base font-mono font-bold text-teal-700 tracking-wide">
                {[invSet.first_faktur, invSet.second_faktur, invSet.third_faktur, invSet.fourth_faktur]
                  .filter(v => v !== null && v !== undefined && String(v).trim() !== '')
                  .concat([String(parseInt(invSet.no_awal) || 1).padStart(
                    !isNaN(parseInt(invSet.no_akhir)) ? String(parseInt(invSet.no_akhir)).length : 4,
                    '0'
                  )])
                  .join('/') || '—'}
              </p>
              <p class="text-[10px] text-slate-400 mt-1.5">
                Format: 
                <span class="font-mono">
                  {[invSet.first_faktur, invSet.second_faktur, invSet.third_faktur, invSet.fourth_faktur]
                    .filter(v => v !== null && v !== undefined && String(v).trim() !== '')
                    .join('/')}
                  {[invSet.first_faktur, invSet.second_faktur, invSet.third_faktur, invSet.fourth_faktur]
                    .filter(v => v !== null && v !== undefined && String(v).trim() !== '').length > 0 ? '/' : ''}
                  <span class="text-teal-500">{String(parseInt(invSet.no_awal) || 1).padStart(
                    !isNaN(parseInt(invSet.no_akhir)) ? String(parseInt(invSet.no_akhir)).length : 4,
                    '0'
                  )}</span>
                  <span class="text-slate-300 ml-1">← nomor urut</span>
                </span>
              </p>
            </div>

            {#if invSetMsg}
              <div class="flex items-center gap-2 text-xs {invSetMsg.includes('berhasil') ? 'text-emerald-600' : 'text-rose-600'}">
                {#if invSetMsg.includes('berhasil')}<CheckCircle class="h-3.5 w-3.5" />{:else}<AlertCircle class="h-3.5 w-3.5" />{/if}
                {invSetMsg}
              </div>
            {/if}

            <Button
              class="h-9 text-sm gap-2 bg-teal-700 hover:bg-teal-800 w-full"
              on:click={saveInvoiceSetting}
              disabled={invSetSaving}
            >
              <Save class="h-4 w-4" />
              {invSetSaving ? 'Menyimpan...' : 'Simpan Setting'}
            </Button>
          </Card.Content>
        </Card.Root>
      </div>
    {/if}

    <!-- ============================================================ -->
    <!-- TAB: TAXES -->
    <!-- ============================================================ -->
    {#if currentTab === 'taxes'}
      <Card.Root class="border border-slate-200 shadow-sm">
        <Card.Header class="py-3 px-4 border-b border-slate-100 flex flex-row items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <Receipt class="h-4 w-4 text-teal-600" />
            <span class="text-sm font-semibold text-slate-800">Daftar Pajak</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="relative">
              <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
              <Input bind:value={taxSearch} on:input={onTaxSearch} placeholder="Cari pajak..." class="pl-8 h-8 text-xs w-40 border-slate-200" />
            </div>
            <Button class="h-8 text-xs gap-1.5 bg-teal-700 hover:bg-teal-800" on:click={() => openTaxModal()}>
              <Plus class="h-3.5 w-3.5" /> Tambah
            </Button>
          </div>
        </Card.Header>
        <Card.Content class="p-0">
          <Table.Root>
            <Table.Header>
              <Table.Row class="bg-slate-50/80 border-b border-slate-100">
                <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500 w-10">#</Table.Head>
                <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Nama Pajak</Table.Head>
                <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Rate</Table.Head>
                <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Tipe</Table.Head>
                <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Keterangan</Table.Head>
                <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Status</Table.Head>
                <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500 text-right">Aksi</Table.Head>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              {#if taxLoading}
                <Table.Row><Table.Cell colspan="7" class="text-center py-8 text-xs text-slate-400">Memuat...</Table.Cell></Table.Row>
              {:else if taxes.length === 0}
                <Table.Row><Table.Cell colspan="7" class="text-center py-8 text-xs text-slate-400">Belum ada data pajak</Table.Cell></Table.Row>
              {:else}
                {#each taxes as tax, i}
                  <Table.Row class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                    <Table.Cell class="py-1.5 px-3 text-xs text-slate-400">{(taxesPag.from ?? 0) + i}</Table.Cell>
                    <Table.Cell class="py-1.5 px-3 text-xs font-medium text-slate-800">{tax.name}</Table.Cell>
                    <Table.Cell class="py-1.5 px-3 text-xs font-mono text-slate-700">
                      {tax.rate}{tax.type === 'percentage' ? '%' : ''}
                    </Table.Cell>
                    <Table.Cell class="py-1.5 px-3">
                      <span class="px-1.5 py-0.5 rounded text-[10px] font-medium {tax.type === 'percentage' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700'}">
                        {tax.type === 'percentage' ? 'Persentase' : 'Tetap'}
                      </span>
                    </Table.Cell>
                    <Table.Cell class="py-1.5 px-3 text-xs text-slate-500">{tax.description || '-'}</Table.Cell>
                    <Table.Cell class="py-1.5 px-3">
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                        {tax.enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}">
                        {tax.enabled ? 'Aktif' : 'Nonaktif'}
                      </span>
                    </Table.Cell>
                    <Table.Cell class="py-1.5 px-3">
                      <div class="flex items-center gap-1 justify-end">
                        <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-teal-50 text-slate-400 hover:text-teal-600" on:click={() => openTaxModal(tax)}><Pencil class="h-3.5 w-3.5" /></button>
                        <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-rose-50 text-slate-400 hover:text-rose-500" on:click={() => deleteTax(tax.id)}><Trash2 class="h-3.5 w-3.5" /></button>
                      </div>
                    </Table.Cell>
                  </Table.Row>
                {/each}
              {/if}
            </Table.Body>
          </Table.Root>
          {#if taxesPag.lastPage > 1}
            <div class="flex items-center justify-between px-4 py-2 border-t border-slate-100 bg-slate-50/50">
              <span class="text-[11px] text-slate-400">Showing {pagInfo(taxesPag)}</span>
              <div class="flex items-center gap-1">
                <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-slate-200 text-slate-500 disabled:opacity-30" disabled={taxesPag.currentPage <= 1} on:click={() => loadTaxes(1)}><ChevronsLeft class="h-3.5 w-3.5" /></button>
                <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-slate-200 text-slate-500 disabled:opacity-30" disabled={taxesPag.currentPage <= 1} on:click={() => loadTaxes(taxesPag.currentPage - 1)}><ChevronLeft class="h-3.5 w-3.5" /></button>
                <span class="text-xs px-2 text-slate-600">{taxesPag.currentPage}/{taxesPag.lastPage}</span>
                <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-slate-200 text-slate-500 disabled:opacity-30" disabled={taxesPag.currentPage >= taxesPag.lastPage} on:click={() => loadTaxes(taxesPag.currentPage + 1)}><ChevronRight class="h-3.5 w-3.5" /></button>
                <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-slate-200 text-slate-500 disabled:opacity-30" disabled={taxesPag.currentPage >= taxesPag.lastPage} on:click={() => loadTaxes(taxesPag.lastPage)}><ChevronsRight class="h-3.5 w-3.5" /></button>
              </div>
            </div>
          {/if}
        </Card.Content>
      </Card.Root>
    {/if}

    <!-- ============================================================ -->
    <!-- TAB: DISCOUNTS -->
    <!-- ============================================================ -->
    {#if currentTab === 'discounts'}
      <Card.Root class="border border-slate-200 shadow-sm">
        <Card.Header class="py-3 px-4 border-b border-slate-100 flex flex-row items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <Percent class="h-4 w-4 text-teal-600" />
            <span class="text-sm font-semibold text-slate-800">Daftar Diskon</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="relative">
              <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
              <Input bind:value={discountSearch} on:input={onDiscountSearch} placeholder="Cari diskon..." class="pl-8 h-8 text-xs w-40 border-slate-200" />
            </div>
            <Button class="h-8 text-xs gap-1.5 bg-teal-700 hover:bg-teal-800" on:click={() => openDiscountModal()}>
              <Plus class="h-3.5 w-3.5" /> Tambah
            </Button>
          </div>
        </Card.Header>
        <Card.Content class="p-0">
          <Table.Root>
            <Table.Header>
              <Table.Row class="bg-slate-50/80 border-b border-slate-100">
                <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500 w-10">#</Table.Head>
                <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Nama Diskon</Table.Head>
                <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Rate</Table.Head>
                <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Tipe</Table.Head>
                <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Keterangan</Table.Head>
                <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Status</Table.Head>
                <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500 text-right">Aksi</Table.Head>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              {#if discountLoading}
                <Table.Row><Table.Cell colspan="7" class="text-center py-8 text-xs text-slate-400">Memuat...</Table.Cell></Table.Row>
              {:else if discounts.length === 0}
                <Table.Row><Table.Cell colspan="7" class="text-center py-8 text-xs text-slate-400">Belum ada data diskon</Table.Cell></Table.Row>
              {:else}
                {#each discounts as discount, i}
                  <Table.Row class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                    <Table.Cell class="py-1.5 px-3 text-xs text-slate-400">{(discountsPag.from ?? 0) + i}</Table.Cell>
                    <Table.Cell class="py-1.5 px-3 text-xs font-medium text-slate-800">{discount.name}</Table.Cell>
                    <Table.Cell class="py-1.5 px-3 text-xs font-mono text-slate-700">
                      {discount.rate}{discount.type === 'percentage' ? '%' : ''}
                    </Table.Cell>
                    <Table.Cell class="py-1.5 px-3">
                      <span class="px-1.5 py-0.5 rounded text-[10px] font-medium {discount.type === 'percentage' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700'}">
                        {discount.type === 'percentage' ? 'Persentase' : 'Tetap'}
                      </span>
                    </Table.Cell>
                    <Table.Cell class="py-1.5 px-3 text-xs text-slate-500">{discount.description || '-'}</Table.Cell>
                    <Table.Cell class="py-1.5 px-3">
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                        {discount.enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}">
                        {discount.enabled ? 'Aktif' : 'Nonaktif'}
                      </span>
                    </Table.Cell>
                    <Table.Cell class="py-1.5 px-3">
                      <div class="flex items-center gap-1 justify-end">
                        <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-teal-50 text-slate-400 hover:text-teal-600" on:click={() => openDiscountModal(discount)}><Pencil class="h-3.5 w-3.5" /></button>
                        <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-rose-50 text-slate-400 hover:text-rose-500" on:click={() => deleteDiscount(discount.id)}><Trash2 class="h-3.5 w-3.5" /></button>
                      </div>
                    </Table.Cell>
                  </Table.Row>
                {/each}
              {/if}
            </Table.Body>
          </Table.Root>
          {#if discountsPag.lastPage > 1}
            <div class="flex items-center justify-between px-4 py-2 border-t border-slate-100 bg-slate-50/50">
              <span class="text-[11px] text-slate-400">Showing {pagInfo(discountsPag)}</span>
              <div class="flex items-center gap-1">
                <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-slate-200 text-slate-500 disabled:opacity-30" disabled={discountsPag.currentPage <= 1} on:click={() => loadDiscounts(1)}><ChevronsLeft class="h-3.5 w-3.5" /></button>
                <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-slate-200 text-slate-500 disabled:opacity-30" disabled={discountsPag.currentPage <= 1} on:click={() => loadDiscounts(discountsPag.currentPage - 1)}><ChevronLeft class="h-3.5 w-3.5" /></button>
                <span class="text-xs px-2 text-slate-600">{discountsPag.currentPage}/{discountsPag.lastPage}</span>
                <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-slate-200 text-slate-500 disabled:opacity-30" disabled={discountsPag.currentPage >= discountsPag.lastPage} on:click={() => loadDiscounts(discountsPag.currentPage + 1)}><ChevronRight class="h-3.5 w-3.5" /></button>
                <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-slate-200 text-slate-500 disabled:opacity-30" disabled={discountsPag.currentPage >= discountsPag.lastPage} on:click={() => loadDiscounts(discountsPag.lastPage)}><ChevronsRight class="h-3.5 w-3.5" /></button>
              </div>
            </div>
          {/if}
        </Card.Content>
      </Card.Root>
    {/if}

    <!-- ============================================================ -->
    <!-- TAB: INVOICE TYPES -->
    <!-- ============================================================ -->
    {#if currentTab === 'invoice-types'}
      <Card.Root class="border border-slate-200 shadow-sm">
        <Card.Header class="py-3 px-4 border-b border-slate-100 flex flex-row items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <Tag class="h-4 w-4 text-teal-600" />
            <span class="text-sm font-semibold text-slate-800">Daftar Tipe Invoice</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="relative">
              <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
              <Input
                bind:value={invoiceTypeSearch}
                on:input={onInvoiceTypeSearch}
                placeholder="Cari tipe invoice..."
                class="pl-8 h-8 text-xs w-48 border-slate-200"
              />
            </div>
            <Button class="h-8 text-xs gap-1.5 bg-teal-700 hover:bg-teal-800" on:click={() => openInvoiceTypeModal()}>
              <Plus class="h-3.5 w-3.5" /> Tambah
            </Button>
          </div>
        </Card.Header>
        <Card.Content class="p-0">
          <div class="overflow-x-auto">
            <Table.Root>
              <Table.Header>
                <Table.Row class="bg-slate-50/80 border-b border-slate-100">
                  <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500 w-10">#</Table.Head>
                  <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Nama Tipe Invoice</Table.Head>
                  <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500 text-right">Aksi</Table.Head>
                </Table.Row>
              </Table.Header>
              <Table.Body>
                {#if invoiceTypeLoading}
                  <Table.Row><Table.Cell colspan="3" class="text-center py-8 text-xs text-slate-400">Memuat...</Table.Cell></Table.Row>
                {:else if invoiceTypes.length === 0}
                  <Table.Row><Table.Cell colspan="3" class="text-center py-8 text-xs text-slate-400">Belum ada data tipe invoice</Table.Cell></Table.Row>
                {:else}
                  {#each invoiceTypes as type, i}
                    <Table.Row class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                      <Table.Cell class="py-1.5 px-3 text-xs text-slate-400">{(invoiceTypesPag.from ?? 0) + i}</Table.Cell>
                      <Table.Cell class="py-1.5 px-3 text-xs font-medium text-slate-800">{type.name}</Table.Cell>
                      <Table.Cell class="py-1.5 px-3">
                        <div class="flex items-center gap-1 justify-end">
                          <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-teal-50 text-slate-400 hover:text-teal-600 transition-colors" on:click={() => openInvoiceTypeModal(type)} title="Edit Tipe">
                            <Pencil class="h-3.5 w-3.5" />
                          </button>
                          <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-rose-50 text-slate-400 hover:text-rose-500 transition-colors" on:click={() => deleteInvoiceType(type.id)} title="Hapus Tipe">
                            <Trash2 class="h-3.5 w-3.5" />
                          </button>
                        </div>
                      </Table.Cell>
                    </Table.Row>
                  {/each}
                {/if}
              </Table.Body>
            </Table.Root>
          </div>
          <!-- Pagination -->
          {#if invoiceTypesPag.lastPage > 1}
            <div class="flex items-center justify-between px-4 py-2 border-t border-slate-100 bg-slate-50/50">
              <span class="text-[11px] text-slate-400">Showing {pagInfo(invoiceTypesPag)}</span>
              <div class="flex items-center gap-1">
                <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-slate-200 text-slate-500 disabled:opacity-30" disabled={invoiceTypesPag.currentPage <= 1} on:click={() => loadInvoiceTypes(1)}><ChevronsLeft class="h-3.5 w-3.5" /></button>
                <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-slate-200 text-slate-500 disabled:opacity-30" disabled={invoiceTypesPag.currentPage <= 1} on:click={() => loadInvoiceTypes(invoiceTypesPag.currentPage - 1)}><ChevronLeft class="h-3.5 w-3.5" /></button>
                <span class="text-xs px-2 text-slate-600">{invoiceTypesPag.currentPage}/{invoiceTypesPag.lastPage}</span>
                <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-slate-200 text-slate-500 disabled:opacity-30" disabled={invoiceTypesPag.currentPage >= invoiceTypesPag.lastPage} on:click={() => loadInvoiceTypes(invoiceTypePag.currentPage + 1)}><ChevronRight class="h-3.5 w-3.5" /></button>
                <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-slate-200 text-slate-500 disabled:opacity-30" disabled={invoiceTypesPag.currentPage >= invoiceTypesPag.lastPage} on:click={() => loadInvoiceTypes(invoiceTypesPag.lastPage)}><ChevronsRight class="h-3.5 w-3.5" /></button>
              </div>
            </div>
          {/if}
        </Card.Content>
      </Card.Root>
    {/if}

    <!-- Kunci Periode Tab -->
    {#if currentTab === 'periods'}
      <Card.Root class="shadow-sm border-slate-200">
        <Card.Header class="bg-slate-50/50 border-b border-slate-100 pb-3">
          <div class="flex items-center justify-between">
            <div class="space-y-1">
              <Card.Title class="text-base text-slate-800">Kunci Periode Akuntansi</Card.Title>
              <Card.Description class="text-xs">Cegah perubahan atau penambahan transaksi pada periode yang sudah ditutup.</Card.Description>
            </div>
            <div class="flex items-center gap-2">
              <Button variant="outline" size="sm" class="h-8 w-8 p-0 border-slate-200 cursor-pointer" on:click={() => { periodYear--; loadPeriods(); }}><ChevronLeft class="h-4 w-4" /></Button>
              <span class="text-sm font-bold w-12 text-center text-slate-700">{periodYear}</span>
              <Button variant="outline" size="sm" class="h-8 w-8 p-0 border-slate-200 cursor-pointer" on:click={() => { periodYear++; loadPeriods(); }}><ChevronRight class="h-4 w-4" /></Button>
            </div>
          </div>
        </Card.Header>
        <Card.Content class="p-0">
          {#if periodLoading}
            <div class="p-8 text-center text-sm text-slate-500">Memuat periode...</div>
          {:else}
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 p-4">
              {#each periods as p}
                <div class="flex flex-col items-center justify-center p-4 border rounded-xl transition-all {p.status ? 'border-rose-200 bg-rose-50/30' : 'border-teal-200 bg-teal-50/30'}">
                  <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Bulan</div>
                  <div class="text-xl font-black mb-3 {p.status ? 'text-rose-900' : 'text-teal-900'}">{p.bulan}</div>
                  
                  <button
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold cursor-pointer transition-colors {p.status ? 'bg-rose-100 text-rose-700 hover:bg-rose-200' : 'bg-teal-100 text-teal-700 hover:bg-teal-200'}"
                    on:click={() => togglePeriod(p.id, p.status)}
                  >
                    {#if p.status}
                      <Lock class="h-3 w-3" /> Tutup
                    {:else}
                      <Unlock class="h-3 w-3" /> Buka
                    {/if}
                  </button>
                </div>
              {/each}
            </div>
          {/if}
        </Card.Content>
      </Card.Root>
    {/if}
  </div>
</AppLayout>

<!-- ============================================================ -->
<!-- MODALS -->
<!-- ============================================================ -->

<!-- Modal Backdrop Helper -->
<style>
  .modal-backdrop {
    position: fixed; inset: 0; background: rgba(0,0,0,0.5);
    z-index: 100; display: flex; align-items: center; justify-content: center; padding: 1rem;
  }
  .modal-box {
    background: white; border-radius: 0.75rem; box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    width: 100%; max-height: 90vh; overflow-y: auto; padding: 1.25rem;
  }
  .modal-lg { max-width: 32rem; }
  .modal-md { max-width: 26rem; }
  .modal-hdr { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9; }
  .modal-ftr { display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #f1f5f9; }
</style>

<!-- Company Modal -->
{#if showCompanyModal}
<div class="modal-backdrop" on:click|self={() => showCompanyModal = false}>
  <div class="modal-box modal-lg">
    <div class="modal-hdr">
      <h3 class="text-sm font-semibold text-slate-800">{editingCompany ? 'Edit Perusahaan' : 'Tambah Perusahaan'}</h3>
      <button class="h-6 w-6 flex items-center justify-center rounded hover:bg-slate-100 text-slate-400" on:click={() => showCompanyModal = false}><X class="h-4 w-4" /></button>
    </div>
    <div class="space-y-3">
      <div class="grid grid-cols-2 gap-3">
        <div class="space-y-1.5">
          <label class="text-xs font-medium">Nama Perusahaan <span class="text-rose-500">*</span></label>
          <Input bind:value={companyForm.name} placeholder="PT Contoh Sejahtera" class="h-8 text-xs border-slate-200" />
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-medium">Kode</label>
          <Input bind:value={companyForm.code} placeholder="VCY" class="h-8 text-xs border-slate-200" />
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div class="space-y-1.5">
          <label class="text-xs font-medium">Telepon</label>
          <Input bind:value={companyForm.phone} placeholder="021-xxxxx" class="h-8 text-xs border-slate-200" />
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-medium">NPWP</label>
          <Input bind:value={companyForm.npwp} placeholder="00.000.000.0-000.000" class="h-8 text-xs border-slate-200 font-mono" />
        </div>
      </div>
      <div class="space-y-1.5">
        <label class="text-xs font-medium">Alamat</label>
        <textarea bind:value={companyForm.address} placeholder="Jl. Contoh No. 1..." class="w-full h-16 text-xs border border-slate-200 rounded-md px-3 py-2 resize-none focus:outline-none focus:ring-1 focus:ring-teal-500" />
      </div>
      <div class="space-y-1.5">
        <label class="text-xs font-medium">Logo Perusahaan (untuk print invoice)</label>
        <div class="flex items-center gap-3">
          {#if companyLogoPreview}
            <img src={companyLogoPreview} alt="Preview" class="h-14 w-14 object-contain rounded border border-slate-200 bg-slate-50 p-1" />
          {:else}
            <div class="h-14 w-14 rounded border border-dashed border-slate-300 bg-slate-50 flex items-center justify-center">
              <Image class="h-6 w-6 text-slate-300" />
            </div>
          {/if}
          <label class="cursor-pointer flex items-center gap-1.5 text-xs text-teal-600 hover:text-teal-800 font-medium border border-teal-200 hover:border-teal-400 bg-teal-50 hover:bg-teal-100 rounded px-3 py-1.5 transition-colors">
            <Upload class="h-3.5 w-3.5" /> Pilih Gambar
            <input type="file" accept="image/*" class="hidden" on:change={handleLogoChange} />
          </label>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <input type="checkbox" id="co-active" bind:checked={companyForm.enabled} class="rounded border-slate-300" />
        <label for="co-active" class="text-xs">Aktif</label>
      </div>
    </div>
    <div class="modal-ftr">
      <Button variant="outline" class="h-8 text-xs border-slate-200" on:click={() => showCompanyModal = false}>Batal</Button>
      <Button class="h-8 text-xs bg-teal-700 hover:bg-teal-800 gap-1.5" on:click={saveCompany}>
        <Save class="h-3.5 w-3.5" /> Simpan
      </Button>
    </div>
  </div>
</div>
{/if}

<!-- Tax Modal -->
{#if showTaxModal}
<div class="modal-backdrop" on:click|self={() => showTaxModal = false}>
  <div class="modal-box modal-md">
    <div class="modal-hdr">
      <h3 class="text-sm font-semibold text-slate-800">{editingTax ? 'Edit Pajak' : 'Tambah Pajak'}</h3>
      <button class="h-6 w-6 flex items-center justify-center rounded hover:bg-slate-100 text-slate-400" on:click={() => showTaxModal = false}><X class="h-4 w-4" /></button>
    </div>
    <div class="space-y-3">
      <div class="space-y-1.5">
        <label class="text-xs font-medium">Nama Pajak <span class="text-rose-500">*</span></label>
        <Input bind:value={taxForm.name} placeholder="PPN 11%" class="h-8 text-xs border-slate-200" />
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div class="space-y-1.5">
          <label class="text-xs font-medium">Rate <span class="text-rose-500">*</span></label>
          <Input bind:value={taxForm.rate} type="number" step="0.01" placeholder="11.00" class="h-8 text-xs border-slate-200" />
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-medium">Tipe</label>
          <select bind:value={taxForm.type} class="w-full h-8 text-xs border border-slate-200 rounded-md px-2 focus:outline-none focus:ring-1 focus:ring-teal-500 bg-white">
            <option value="percentage">Persentase (%)</option>
            <option value="fixed">Nominal Tetap</option>
          </select>
        </div>
      </div>
      <div class="space-y-1.5">
        <label class="text-xs font-medium">Akun Pajak (COA)</label>
        <CoaSelect bind:value={taxForm.account_id} options={accounts} placeholder="Pilih Akun Pajak..." />
      </div>
      <div class="space-y-1.5">
        <label class="text-xs font-medium">Keterangan</label>
        <Input bind:value={taxForm.description} placeholder="Keterangan opsional" class="h-8 text-xs border-slate-200" />
      </div>
      <div class="flex items-center gap-2">
        <input type="checkbox" id="tax-active" bind:checked={taxForm.enabled} class="rounded border-slate-300" />
        <label for="tax-active" class="text-xs">Aktif</label>
      </div>
    </div>
    <div class="modal-ftr">
      <Button variant="outline" class="h-8 text-xs border-slate-200" on:click={() => showTaxModal = false}>Batal</Button>
      <Button class="h-8 text-xs bg-teal-700 hover:bg-teal-800 gap-1.5" on:click={saveTax}>
        <Save class="h-3.5 w-3.5" /> Simpan
      </Button>
    </div>
  </div>
</div>
{/if}

<!-- Discount Modal -->
{#if showDiscountModal}
<div class="modal-backdrop" on:click|self={() => showDiscountModal = false}>
  <div class="modal-box modal-md">
    <div class="modal-hdr">
      <h3 class="text-sm font-semibold text-slate-800">{editingDiscount ? 'Edit Diskon' : 'Tambah Diskon'}</h3>
      <button class="h-6 w-6 flex items-center justify-center rounded hover:bg-slate-100 text-slate-400" on:click={() => showDiscountModal = false}><X class="h-4 w-4" /></button>
    </div>
    <div class="space-y-3">
      <div class="space-y-1.5">
        <label class="text-xs font-medium">Nama Diskon <span class="text-rose-500">*</span></label>
        <Input bind:value={discountForm.name} placeholder="Diskon Lebaran" class="h-8 text-xs border-slate-200" />
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div class="space-y-1.5">
          <label class="text-xs font-medium">Rate <span class="text-rose-500">*</span></label>
          <Input bind:value={discountForm.rate} type="number" step="0.01" placeholder="10.00" class="h-8 text-xs border-slate-200" />
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-medium">Tipe</label>
          <select bind:value={discountForm.type} class="w-full h-8 text-xs border border-slate-200 rounded-md px-2 focus:outline-none focus:ring-1 focus:ring-teal-500 bg-white">
            <option value="percentage">Persentase (%)</option>
            <option value="fixed">Nominal Tetap</option>
          </select>
        </div>
      </div>
      <div class="space-y-1.5">
        <label class="text-xs font-medium">Keterangan</label>
        <Input bind:value={discountForm.description} placeholder="Keterangan opsional" class="h-8 text-xs border-slate-200" />
      </div>
      <div class="flex items-center gap-2">
        <input type="checkbox" id="discount-active" bind:checked={discountForm.enabled} class="rounded border-slate-300" />
        <label for="discount-active" class="text-xs">Aktif</label>
      </div>
    </div>
    <div class="modal-ftr">
      <Button variant="outline" class="h-8 text-xs border-slate-200" on:click={() => showDiscountModal = false}>Batal</Button>
      <Button class="h-8 text-xs bg-teal-700 hover:bg-teal-800 gap-1.5" on:click={saveDiscount}>
        <Save class="h-3.5 w-3.5" /> Simpan
      </Button>
    </div>
  </div>
</div>
{/if}

<!-- User Modal -->
{#if showUserModal}
<div class="modal-backdrop" on:click|self={() => showUserModal = false}>
  <div class="modal-box modal-lg">
    <div class="modal-hdr">
      <h3 class="text-sm font-semibold text-slate-800">{editingUser ? 'Edit User' : 'Tambah User'}</h3>
      <button class="h-6 w-6 flex items-center justify-center rounded hover:bg-slate-100 text-slate-400" on:click={() => showUserModal = false}><X class="h-4 w-4" /></button>
    </div>
    <div class="space-y-3">
      <div class="grid grid-cols-2 gap-3">
        <div class="space-y-1.5">
          <label class="text-xs font-medium">Nama <span class="text-rose-500">*</span></label>
          <Input bind:value={userForm.name} placeholder="Nama Lengkap" class="h-8 text-xs border-slate-200" />
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-medium">Email <span class="text-rose-500">*</span></label>
          <Input bind:value={userForm.email} type="email" placeholder="email@domain.com" class="h-8 text-xs border-slate-200" />
        </div>
      </div>
      <div class="space-y-1.5">
        <label class="text-xs font-medium">{editingUser ? 'Password Baru (kosongkan jika tidak diubah)' : 'Password *'}</label>
        <Input bind:value={userForm.password} type="password" placeholder="Min. 8 karakter" class="h-8 text-xs border-slate-200" />
      </div>
      <div class="space-y-1.5">
        <label class="text-xs font-medium">Role</label>
        <div class="flex flex-wrap gap-2 p-2 border border-slate-200 rounded-md min-h-[36px] bg-slate-50">
          {#each roles as role}
            <button
              class="px-2 py-0.5 rounded-full text-[11px] font-medium border transition-colors
                {userForm.roles.includes(role.id)
                  ? 'bg-indigo-600 text-white border-indigo-600'
                  : 'bg-white text-slate-600 border-slate-300 hover:border-indigo-400'}"
              on:click={() => userForm.roles = toggleArr(userForm.roles, role.id)}
            >{role.name}</button>
          {/each}
          {#if roles.length === 0}<span class="text-[11px] text-slate-400">Belum ada role</span>{/if}
        </div>
      </div>
      <div class="space-y-1.5">
        <label class="text-xs font-medium">Akses Perusahaan</label>
        <div class="flex flex-wrap gap-2 p-2 border border-slate-200 rounded-md min-h-[36px] bg-slate-50">
          {#each allCompaniesForForm as co}
            <button
              class="px-2 py-0.5 rounded-full text-[11px] font-medium border transition-colors
                {userForm.companies.includes(co.id)
                  ? 'bg-teal-600 text-white border-teal-600'
                  : 'bg-white text-slate-600 border-slate-300 hover:border-teal-400'}"
              on:click={() => userForm.companies = toggleArr(userForm.companies, co.id)}
            >{co.name}</button>
          {/each}
          {#if allCompaniesForForm.length === 0}<span class="text-[11px] text-slate-400">Belum ada perusahaan</span>{/if}
        </div>
      </div>
    </div>
    <div class="modal-ftr">
      <Button variant="outline" class="h-8 text-xs border-slate-200" on:click={() => showUserModal = false}>Batal</Button>
      <Button class="h-8 text-xs bg-teal-700 hover:bg-teal-800 gap-1.5" on:click={saveUser}>
        <Save class="h-3.5 w-3.5" /> Simpan
      </Button>
    </div>
  </div>
</div>
{/if}

<!-- Role Modal -->
{#if showRoleModal}
<div class="modal-backdrop" on:click|self={() => showRoleModal = false}>
  <div class="modal-box modal-md">
    <div class="modal-hdr">
      <h3 class="text-sm font-semibold text-slate-800">{editingRole ? 'Edit Role' : 'Tambah Role'}</h3>
      <button class="h-6 w-6 flex items-center justify-center rounded hover:bg-slate-100 text-slate-400" on:click={() => showRoleModal = false}><X class="h-4 w-4" /></button>
    </div>
    <div class="space-y-3">
      <div class="space-y-1.5">
        <label class="text-xs font-medium">Nama Role <span class="text-rose-500">*</span></label>
        <Input bind:value={roleForm.name} placeholder="admin / staff / finance" class="h-8 text-xs border-slate-200" />
      </div>
      <div class="space-y-1.5">
        <label class="text-xs font-medium">Permissions ({roleForm.permissions.length} dipilih)</label>
        <div class="max-h-48 overflow-y-auto border border-slate-200 rounded-md p-2 space-y-0.5 bg-slate-50">
          {#each allPermissions as perm}
            <label class="flex items-center gap-2 px-1 py-0.5 rounded hover:bg-white cursor-pointer">
              <input type="checkbox"
                checked={roleForm.permissions.includes(perm.id)}
                on:change={() => roleForm.permissions = toggleArr(roleForm.permissions, perm.id)}
                class="rounded border-slate-300"
              />
              <span class="text-[11px] font-mono text-slate-600">{perm.name}</span>
            </label>
          {/each}
          {#if allPermissions.length === 0}
            <p class="text-[11px] text-slate-400 text-center py-2">Belum ada permissions</p>
          {/if}
        </div>
      </div>
    </div>
    <div class="modal-ftr">
      <Button variant="outline" class="h-8 text-xs border-slate-200" on:click={() => showRoleModal = false}>Batal</Button>
      <Button class="h-8 text-xs bg-indigo-600 hover:bg-indigo-700 gap-1.5" on:click={saveRole}>
        <Save class="h-3.5 w-3.5" /> Simpan
      </Button>
    </div>
  </div>
</div>
{/if}

<!-- Invoice Type Modal -->
{#if showInvoiceTypeModal}
<div class="modal-backdrop" on:click|self={() => showInvoiceTypeModal = false}>
  <div class="modal-box modal-md">
    <div class="modal-hdr">
      <h3 class="text-sm font-semibold text-slate-800">{editingInvoiceType ? 'Edit Tipe Invoice' : 'Tambah Tipe Invoice'}</h3>
      <button class="h-6 w-6 flex items-center justify-center rounded hover:bg-slate-100 text-slate-400" on:click={() => showInvoiceTypeModal = false}><X class="h-4 w-4" /></button>
    </div>
    <div class="space-y-3">
      <div class="space-y-1.5">
        <label class="text-xs font-medium">Nama Tipe Invoice <span class="text-rose-500">*</span></label>
        <Input bind:value={invoiceTypeForm.name} placeholder="Asuransi / Buruh / Trucking / dll." class="h-8 text-xs border-slate-200" />
      </div>
    </div>
    <div class="modal-ftr">
      <Button variant="outline" class="h-8 text-xs border-slate-200" on:click={() => showInvoiceTypeModal = false}>Batal</Button>
      <Button class="h-8 text-xs bg-teal-700 hover:bg-teal-800 gap-1.5" on:click={saveInvoiceType}>
        <Save class="h-3.5 w-3.5" /> Simpan
      </Button>
    </div>
  </div>
</div>
{/if}

