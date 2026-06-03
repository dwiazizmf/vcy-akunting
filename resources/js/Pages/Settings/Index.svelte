<script>
  import AppLayout from '../../Layouts/AppLayout.svelte';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import * as Card from '$lib/components/ui/card';
  import * as Table from '$lib/components/ui/table';
  import {
    Building2, Users, FileText, Receipt, Settings, Search, Plus,
    Pencil, Trash2, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight,
    Save, X, AlertCircle, CheckCircle, Shield, Key,
    Upload, Image
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
  export let invoiceSetting    = {};

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
  let companyForm = { name: '', code: '', address: '', phone: '', npwp: '', is_active: true };
  let companyLogoFile = null;
  let companyLogoPreview = null;

  // Taxes
  let taxes = initialTaxes.data;
  let taxesPag = initialTaxes.pagination;
  let taxSearch = '';
  let taxLoading = false;
  let showTaxModal = false;
  let editingTax = null;
  let taxForm = { name: '', rate: '', type: 'percentage', description: '', is_active: true };

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
      companyForm = { name: company.name, code: company.code || '', address: company.address || '', phone: company.phone || '', npwp: company.npwp || '', is_active: company.is_active };
      if (company.logo_path) companyLogoPreview = `/storage/${company.logo_path}`;
    } else {
      companyForm = { name: '', code: '', address: '', phone: '', npwp: '', is_active: true };
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
      taxForm = { name: tax.name, rate: tax.rate, type: tax.type, description: tax.description || '', is_active: tax.is_active };
    } else {
      taxForm = { name: '', rate: '', type: 'percentage', description: '', is_active: true };
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
        {#each [
          { id: 'companies', label: 'Perusahaan', icon: Building2 },
          { id: 'users', label: 'User & Role', icon: Users },
          { id: 'invoice-setting', label: 'Setting Faktur', icon: FileText },
          { id: 'taxes', label: 'Pajak', icon: Receipt }
        ] as tab}
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
                          {co.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}">
                          {co.is_active ? 'Aktif' : 'Nonaktif'}
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
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <!-- Users Table -->
        <div class="xl:col-span-2">
          <Card.Root class="border border-slate-200 shadow-sm">
            <Card.Header class="py-3 px-4 border-b border-slate-100 flex flex-row items-center justify-between gap-3">
              <div class="flex items-center gap-2">
                <Users class="h-4 w-4 text-teal-600" />
                <span class="text-sm font-semibold text-slate-800">Manajemen User</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="relative">
                  <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                  <Input bind:value={userSearch} on:input={onUserSearch} placeholder="Cari user..." class="pl-8 h-8 text-xs w-40 border-slate-200" />
                </div>
                <Button class="h-8 text-xs gap-1.5 bg-teal-700 hover:bg-teal-800" on:click={() => openUserModal()}>
                  <Plus class="h-3.5 w-3.5" /> Tambah
                </Button>
              </div>
            </Card.Header>
            <Card.Content class="p-0">
              <Table.Root>
                <Table.Header>
                  <Table.Row class="bg-slate-50/80 border-b border-slate-100">
                    <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Nama</Table.Head>
                    <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Email</Table.Head>
                    <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Role</Table.Head>
                    <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500">Perusahaan</Table.Head>
                    <Table.Head class="py-2 px-3 text-xs font-semibold text-slate-500 text-right">Aksi</Table.Head>
                  </Table.Row>
                </Table.Header>
                <Table.Body>
                  {#if userLoading}
                    <Table.Row><Table.Cell colspan="5" class="text-center py-8 text-xs text-slate-400">Memuat...</Table.Cell></Table.Row>
                  {:else if users.length === 0}
                    <Table.Row><Table.Cell colspan="5" class="text-center py-8 text-xs text-slate-400">Belum ada user</Table.Cell></Table.Row>
                  {:else}
                    {#each users as u}
                      <Table.Row class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                        <Table.Cell class="py-1.5 px-3 text-xs font-medium text-slate-800">{u.name}</Table.Cell>
                        <Table.Cell class="py-1.5 px-3 text-xs text-slate-500">{u.email}</Table.Cell>
                        <Table.Cell class="py-1.5 px-3">
                          <div class="flex flex-wrap gap-1">
                            {#each u.roles as r}
                              <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-teal-100 text-teal-700 font-semibold">{r}</span>
                            {/each}
                            {#if u.roles.length === 0}<span class="text-[10px] text-slate-400">-</span>{/if}
                          </div>
                        </Table.Cell>
                        <Table.Cell class="py-1.5 px-3">
                          <div class="flex flex-wrap gap-1">
                            {#each u.companies as c}
                              <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-slate-100 text-slate-600">{c.name}</span>
                            {/each}
                            {#if u.companies.length === 0}<span class="text-[10px] text-slate-400">Semua</span>{/if}
                          </div>
                        </Table.Cell>
                        <Table.Cell class="py-1.5 px-3">
                          <div class="flex items-center gap-1 justify-end">
                            <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-teal-50 text-slate-400 hover:text-teal-600" on:click={() => openUserModal(u)}><Pencil class="h-3.5 w-3.5" /></button>
                            <button class="h-7 w-7 flex items-center justify-center rounded hover:bg-rose-50 text-slate-400 hover:text-rose-500" on:click={() => deleteUser(u.id)}><Trash2 class="h-3.5 w-3.5" /></button>
                          </div>
                        </Table.Cell>
                      </Table.Row>
                    {/each}
                  {/if}
                </Table.Body>
              </Table.Root>
            </Card.Content>
          </Card.Root>
        </div>

        <!-- Roles Panel -->
        <div class="space-y-4">
          <Card.Root class="border border-slate-200 shadow-sm">
            <Card.Header class="py-3 px-4 border-b border-slate-100 flex flex-row items-center justify-between">
              <div class="flex items-center gap-2">
                <Shield class="h-4 w-4 text-indigo-600" />
                <span class="text-sm font-semibold text-slate-800">Roles</span>
              </div>
              <Button class="h-7 text-[11px] gap-1 bg-indigo-600 hover:bg-indigo-700 px-2.5" on:click={() => openRoleModal()}>
                <Plus class="h-3 w-3" /> Tambah
              </Button>
            </Card.Header>
            <Card.Content class="p-0">
              {#if roles.length === 0}
                <p class="text-center py-6 text-xs text-slate-400">Belum ada role</p>
              {:else}
                {#each roles as role}
                  <div class="flex items-start justify-between gap-2 px-3 py-2 border-b border-slate-50 last:border-0 hover:bg-slate-50/50">
                    <div>
                      <p class="text-xs font-semibold text-slate-800">{role.name}</p>
                      <p class="text-[10px] text-slate-400 mt-0.5">{role.permissions.length} permission · {role.users_count} user</p>
                    </div>
                    <div class="flex gap-1 shrink-0">
                      <button class="h-6 w-6 flex items-center justify-center rounded hover:bg-indigo-50 text-slate-400 hover:text-indigo-600" on:click={() => openRoleModal(role)}><Pencil class="h-3 w-3" /></button>
                      <button class="h-6 w-6 flex items-center justify-center rounded hover:bg-rose-50 text-slate-400 hover:text-rose-500" on:click={() => deleteRole(role.id)}><Trash2 class="h-3 w-3" /></button>
                    </div>
                  </div>
                {/each}
              {/if}
            </Card.Content>
          </Card.Root>

          <!-- Permissions Panel -->
          <Card.Root class="border border-slate-200 shadow-sm">
            <Card.Header class="py-3 px-4 border-b border-slate-100 flex flex-row items-center justify-between">
              <div class="flex items-center gap-2">
                <Key class="h-4 w-4 text-amber-600" />
                <span class="text-sm font-semibold text-slate-800">Permissions</span>
              </div>
              <span class="text-[10px] text-slate-400">{allPermissions.length} total</span>
            </Card.Header>
            <Card.Content class="p-3 space-y-2">
              <div class="flex gap-2">
                <Input bind:value={newPermName} placeholder="nama.permission" class="h-7 text-xs border-slate-200 flex-1" />
                <Button class="h-7 text-[11px] px-2.5 bg-amber-500 hover:bg-amber-600" on:click={saveNewPermission}><Plus class="h-3 w-3" /></Button>
              </div>
              <div class="max-h-48 overflow-y-auto space-y-0.5">
                {#each allPermissions as perm}
                  <div class="flex items-center justify-between px-2 py-1 rounded hover:bg-slate-50 group">
                    <span class="text-[11px] font-mono text-slate-600">{perm.name}</span>
                    <button class="h-5 w-5 flex items-center justify-center rounded opacity-0 group-hover:opacity-100 hover:bg-rose-50 text-rose-400" on:click={() => deletePermission(perm.id)}><X class="h-2.5 w-2.5" /></button>
                  </div>
                {/each}
              </div>
            </Card.Content>
          </Card.Root>
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
              <p class="text-sm font-mono font-bold text-teal-700">
                {[invSet.first_faktur, invSet.second_faktur, invSet.third_faktur, invSet.fourth_faktur].filter(Boolean).join('/')}
                {#if invSet.no_awal !== undefined}/{String(invSet.no_awal).padStart(4, '0')}{/if}
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
                        {tax.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}">
                        {tax.is_active ? 'Aktif' : 'Nonaktif'}
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
        <input type="checkbox" id="co-active" bind:checked={companyForm.is_active} class="rounded border-slate-300" />
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
        <label class="text-xs font-medium">Keterangan</label>
        <Input bind:value={taxForm.description} placeholder="Keterangan opsional" class="h-8 text-xs border-slate-200" />
      </div>
      <div class="flex items-center gap-2">
        <input type="checkbox" id="tax-active" bind:checked={taxForm.is_active} class="rounded border-slate-300" />
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

