# VCY Accounting — Panduan Desain & UI

> Semua halaman dan komponen baru WAJIB mengikuti panduan ini.

---

## 🎨 Color Palette

| Peran | Warna | Tailwind Class |
|---|---|---|
| **Primary Brand** | Teal | `teal-700` / `teal-800` |
| **Background** | Slate muted | `bg-muted/40`, `bg-slate-50` |
| **Card / Surface** | White | `bg-white` |
| **Border** | Slate light | `border-slate-100`, `border-slate-200` |
| **Text Primary** | Slate dark | `text-slate-900` |
| **Text Secondary** | Slate medium | `text-slate-500`, `text-slate-600` |
| **Success / Paid** | Emerald | `bg-emerald-50 text-emerald-700` |
| **Warning / Sent** | Amber/Sky | `bg-amber-50 text-amber-700` |
| **Neutral / Draft** | Slate | `bg-slate-50 text-slate-600` |
| **Document / Titip** | Violet | `bg-violet-50 text-violet-700` |
| **Jurnal / Payment** | Indigo | `bg-indigo-50 text-indigo-700` |
| **Faktur** | Amber | `bg-amber-50 text-amber-800` |
| **Dok Kirim** | Blue | `bg-blue-50 text-blue-700` |

---

## 🧩 Komponen Standar

### Badge / Chip
```svelte
<!-- Status badge (rounded-full) -->
<span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200">
  Lunas
</span>

<!-- Data badge (rounded-md) -->
<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-200/60">
  <span class="h-1 w-1 rounded-full bg-blue-500"></span>
  3 Dokumen
</span>
```

### Card
```svelte
<Card.Root class="bg-white shadow-sm border-slate-100 hover:shadow-md transition cursor-pointer">
  <Card.Content class="flex items-center gap-4 p-5">
    <div class="p-3 rounded-xl bg-teal-50 text-teal-600">
      <!-- icon -->
    </div>
    <div>
      <div class="text-2xl font-bold text-slate-900">0</div>
      <div class="text-xs font-medium text-slate-500 mt-0.5">Label</div>
    </div>
  </Card.Content>
</Card.Root>
```

### Table Header
```svelte
<Table.Head class="font-semibold text-slate-500 text-[10px] uppercase tracking-wider">
  Nama Kolom
</Table.Head>
```

### Page Header
```svelte
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
  <div>
    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Judul Halaman</h1>
    <p class="text-sm text-muted-foreground mt-1">Deskripsi singkat</p>
  </div>
  <div class="flex flex-wrap items-center gap-2">
    <!-- action buttons -->
  </div>
</div>
```

---

## 📐 Layout Rules

### Full-width Pages (Data Dense)
Halaman dengan banyak kolom tabel (seperti Invoices) harus menggunakan full-width layout:
```svelte
<AppLayout fullWidth={true}>
```

### Standard Pages
Halaman biasa (form, detail) gunakan layout default:
```svelte
<AppLayout>
```

### Overflow & Dropdown
- ❌ **JANGAN** pakai `overflow-hidden` di container yang memiliki dropdown/popover anak
- ✅ Dropdown/popover yang muncul dari dalam tabel/card → gunakan `position: fixed` dengan z-index tinggi (`z-50`)
- ✅ Selalu sertakan backdrop transparan (`fixed inset-0 z-40`) untuk close-on-click-outside

---

## 🏗️ Struktur Tabel Standar

Setiap halaman list/tabel wajib punya:

1. **Stats Cards** (4 kartu ringkasan di atas)
2. **Filter Panel** (collapsible, dengan badge "Aktif" jika ada filter)
3. **Toolbar** (info jumlah data + tombol kolom + per-page selector)
4. **Table** dengan:
   - Expand/collapse row (▶) di kolom pertama
   - Checkbox selection di kolom kedua
   - Aksi (titik 3) di kolom ketiga — **DI DEPAN, bukan di belakang**
   - Pagination di bawah
5. **Column Toggle** (show/hide kolom via overlay fixed)
6. **Empty State** yang informatif

---

## ✨ Micro-interactions & Polish

- Semua tombol/card interaktif **HARUS** punya `cursor-pointer`
- Hover state: `hover:shadow-md transition` untuk card, `hover:bg-slate-50` untuk row
- Tombol utama (CTA): `bg-teal-700 hover:bg-teal-800 text-white`
- Collapse/expand row: animasi chevron rotate (`transition-transform duration-200`)
- Loading state tabel: bar progress di atas tabel dengan warna kontras

---

## 📱 Mobile Rules

- Toolbar tabel: `flex-col` di mobile, `flex-row` di sm ke atas
- Jangan ada konten yang terpotong di mobile karena `overflow-hidden`
- Panel overlay (dropdown kolom, dll) → gunakan `position: fixed` selalu
- Pagination button: `min-w-[2rem] px-2.5` agar angka ribuan tidak terpotong

---

## 🔤 Typography

- Page title: `text-3xl font-extrabold tracking-tight text-slate-900`
- Section header: `text-sm font-semibold text-slate-700`
- Label kolom tabel: `text-[10px] font-semibold uppercase tracking-wider text-slate-500`
- Body text: `text-sm text-slate-600`
- Data kecil/mono: `text-xs font-mono text-slate-500`
- Hint/caption: `text-[10px] text-slate-400`
