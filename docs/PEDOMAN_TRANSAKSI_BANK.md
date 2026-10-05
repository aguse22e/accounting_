# Pedoman Transaksi Bank

Dokumen ini menjelaskan alur ekonomi, fungsi, dan gaya tampilan (style) untuk modul Transaksi Bank dalam aplikasi Accounting.

---

## 1. Alur Ekonomi (Economic Flow)

### 1.1 Definisi
Transaksi Bank adalah modul yang digunakan untuk mencatat setiap pergerakan dana yang melewati rekening bank perusahaan. Modul ini berfungsi sebagai bukti tertulis (record) dari semua aktivitas perbankan perusahaan.

### 1.2 Tipe Transaksi

| Tipe | Kode | Dampak pada Saldo | Contoh Penggunaan |
|------|------|-------------------|-------------------|
| **Debit** | D | Menambah saldo bank | Setoran dari pelanggan, penerimaan pembayaran, transfer masuk |
| **Kredit** | K | Mengurangi saldo bank | Pembayaran kepada供应商, penarikan uang, biaya bank |

### 1.3 Perhitungan Saldo Kumulatif (Running Balance)

Transaksi Bank memiliki fitur **running balance** yang dihitung secara otomatis dengan logika:

```
Saldo Akhir = Saldo Awal + Total Debit - Total Kredit
```

- **Debit** → menambah saldo
- **Kredit** → mengurangi saldo

### 1.4 Relasi dengan Modul Lain

```
TransaksiBank
    ├── KodeAkun (akun_kode) - Relasi ke master akun
    ├── Project (kode_project) - Relasi ke project (opsional)
    └── ActivityUser - Logging aktivitas (Create, Update, Delete)
```

### 1.5 Field Data

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| kode_transaksi_bank | string | Ya | Kode unik transaksi bank |
| tanggal | date | Ya | Tanggal transaksi |
| akun_kode | string | Ya | Kode akun yang terkait |
| tipe | enum | Ya | "Debit" atau "Kredit" |
| nominal | decimal | Ya | Jumlah transaksi |
| total | decimal | Ya | Sama dengan nominal |
| kode_project | string | Tidak | Kode project (opsional) |
| deskripsi | string | Tidak | Keterangan transaksi |
| reference | string | Tidak | Nomor referensi eksternal |

---

## 2. Fungsi (Functions)

### 2.1 Daftar Fitur

| Fitur | Method | Route | Keterangan |
|-------|--------|-------|------------|
| **List/Index** | GET | `/transaksi-bank` | Menampilkan semua transaksi dengan filter |
| **Create (Form)** | GET | `/transaksi-bank/create` | Form tambah transaksi baru |
| **Store** | POST | `/transaksi-bank` | Simpan transaksi baru |
| **Show** | GET | `/transaksi-bank/{id}` | Lihat detail transaksi |
| **Edit (Form)** | GET | `/transaksi-bank/{id}/edit` | Form edit transaksi |
| **Update** | PUT/PATCH | `/transaksi-bank/{id}` | Update transaksi |
| **Delete** | DELETE | `/transaksi-bank/{id}` | Hapus transaksi (soft delete) |
| **Print PDF** | GET | `/transaksi-bank/print-pdf` | Cetak PDF berdasarkan tanggal |
| **Export Excel** | GET | `/transaksi-bank/export-excel` | Ekspor ke Excel |

### 2.2 Fitur Filter

Transaksi Bank memiliki filter dengan parameter:

- **Kode Voucher**: Pencarian berdasarkan kode_transaksi_bank
- **Kode Akun**: Filter berdasarkan akun_kode
- **Nominal**: Filter berdasarkan jumlah nominal
- **Pencarian Umum (Search)**: Mencari di semua field:
  - Kode Transaksi
  - Kode Akun
  - Nominal
  - Tipe
  - Nama Akun

### 2.3 Activity Logging

Setiap operasi (Create, Update, Delete) dicatat menggunakan ActivityUser untuk keperluan audit trail.

---

## 3. Style Tampilan (UI/UX)

### 3.1 Warna Palette

| Nama Variabel | Nilai | Penggunaan |
|---------------|-------|------------|
| --primary-purple | #8B5CF6 | Tombol utama, aktif tab, focus |
| --primary-pink | #EC4899 | Gradient aksen |
| --primary-blue | #3B82F6 | Gradient aksen |

### 3.2 Struktur Layout

```
┌─────────────────────────────────────────────────────┐
│  HEADER                                             │
│  [Judul: Transaksi Bank List]    [Print PDF][Export]│
├─────────────────────────────────────────────────────┤
│  TAB                                                │
│  [Semua Transaksi Bank]                             │
├─────────────────────────────────────────────────────┤
│  FILTER SECTION                                     │
│  [Cari] [Tipe ▼] [Dari Tanggal] [Sampai Tanggal]   │
│                                   [Cari][Hari Ini][Reset]
├─────────────────────────────────────────────────────┤
│  [ + Tambah Transaksi Bank ]                        │
├─────────────────────────────────────────────────────┤
│  TABLE                                              │
│  ┌─────┬────────┬──────┬──────┬────────┬────────┬──┤
│  │Kode │Tanggal │Akun  │Tipe  │Debit   │Kredit  │S │aldo│Aksi │
│  ├─────┼────────┼──────┼──────┼────────┼────────┼───┼────┤     │
│  │ ... │ ...    │ ...  │ ...  │ ...    │ ...    │...│     │
│  └─────┴────────┴──────┴──────┴────────┴────────┴───┴─────┘
└─────────────────────────────────────────────────────┘
```

### 3.3 Komponen UI

#### Badge Styles

| Element | Background | Text Color | Usage |
|---------|------------|------------|-------|
| Kode Transaksi | #dbeafe (biru muda) | #0c4a6e (biru gelap) | Kode voucher |
| Akun | #e0e7ff (ungu muda) | #3730a3 (ungu gelap) | Kode & nama akun |
| Tipe Debit | #bbf7d0 (hijau muda) | #065f46 (hijau gelap) | Transaksi Debit |
| Tipe Kredit | #fecaca (merah muda) | #991b1b (merah gelap) | Transaksi Kredit |

#### Amount Styles

| Tipe | Warna | Weight |
|------|-------|--------|
| Debit | #059669 (hijau) | 600 |
| Kredit | #dc2626 (merah) | 600 |
| Saldo Positif | #059669 (hijau) | 700 |
| Saldo Negatif | #dc2626 (merah) | 700 |

#### Action Buttons

| Action | Background | Icon |
|--------|------------|------|
| View | #3b82f6 (biru) | bi-eye |
| Edit | #fbbf24 (kuning) | bi-pencil |
| Delete | #ef4444 (merah) | bi-trash |

### 3.4 Responsive Behavior

Pada layar mobile (< 768px):
- Header menyesuaikan ke layout vertikal
- Filter section menjadi kolom tunggal
- Ukuran font tabel dikecilkan (0.8rem)
- Tombol aksi lebih kecil (28x28px)

---

## 4. Format Export

### 4.1 PDF Export
- Judul: "Transaksi Bank"
- Kolom: Kode, Tanggal, Akun, Tipe, Debit, Kredit, Saldo
- Periode: Ditentukan oleh user (from/to)

### 4.2 Excel Export
- Header style: Background #8B5CF6, teks putih, bold
- Format angka: #,##0.00 (dua decimal)
- Lebar kolom optimal untuk setiap field
- Include running balance

---

## 5. Routes Configuration

Routes untuk Transaksi Bank harus didefinisikan di `routes/web.php`:

```php
Route::resource('transaksi.bank', TransaksiBankController::class);
Route::get('transaksi-bank/print-pdf', [TransaksiBankController::class, 'printPdf'])->name('transaksi.bank.print-pdf');
Route::get('transaksi-bank/export-excel', [TransaksiBankController::class, 'exportExcel'])->name('transaksi.bank.export-excel');
```

---

## 6. Catatan Penting

1. **Validasi**: Kode transaksi bank harus unik
2. **Soft Delete**: Penghapusan menggunakan soft delete (deleted_at)
3. **Deleted By**: Setiap penghapusan mencatat user yang menghapus
4. **Tanggal Ordering**: Data diurutkan berdasarkan created_at pertama, lalu tanggal
5. **Decimal**: Semua nilai nominal menggunakan 2 decimal places

---

## 7. Struktur File

```
app/
├── Http/Controllers/
│   └── TransaksiBankController.php
├── Models/
│   └── TransaksiBank.php
├── Exports/
│   └── TransaksiBankExport.php

resources/views/
└── transaksi/bank/
    ├── index.blade.php
    ├── create.blade.php
    ├── edit.blade.php
    ├── show.blade.php
    └── pdf.blade.php
```

---

*Dokumen ini dibuat secara otomatis berdasarkan analisis kode yang ada di proyek.*