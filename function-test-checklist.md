# Checklist Testing Transaksi Functions

## 1. Halaman Transaksi Utama (http://127.0.0.1:8000/transaksi)

### Filter Functions:
- [ ] Cari (Search) - `filterBankTable()`
- [ ] Hari ini (Today) - `setBankToday()`  
- [ ] Reset - `resetBankFilter()`

### Export Functions:
- [ ] Print PDF - `printPdf()`
- [ ] Export Excel - `exportExcel()`

### CRUD Functions:
- [ ] Create - Link to create page
- [ ] Read/View - Action buttons
- [ ] Update/Edit - `route('transaksi.bank.edit', $transaksi->kode_transaksi_bank)`
- [ ] Delete - `deleteBankTransaction(id)`

## 2. Halaman Jurnal (http://127.0.0.1:8000/transaksi/jurnal)

### Filter Functions:
- [ ] Cari (Search) - `filterJurnalTable()`
- [ ] Hari ini (Today) - `setJurnalToday()`
- [ ] Reset - `resetJurnalFilter()`

### Export Functions:
- [ ] Print PDF - `printPdf()`
- [ ] Export Excel - `exportExcel()`

### CRUD Functions:
- [ ] Create - Link to create page
- [ ] Read/View - `route('transaksi.jurnal.show', $entry)`
- [ ] Update/Edit - `route('transaksi.jurnal.edit', $entry)`
- [ ] Delete - `deleteJournalEntry(id)`

## Issues Found:
(To be filled during testing)

## Recommendations:
(To be filled after testing)