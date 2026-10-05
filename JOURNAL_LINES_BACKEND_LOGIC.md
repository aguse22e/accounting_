# Journal Lines Backend Logic Documentation

## Overview
Dokumentasi ini menjelaskan logika backend untuk fitur Journal Lines pada sistem akuntansi. Fitur ini mengimplementasikan sistem double-entry bookkeeping di mana setiap transaksi harus memiliki minimal 2 lines (debit dan credit) yang seimbang.

## Struktur Database

### 1. Table: `journal_entries`
Menyimpan header dari journal entry.

**Columns:**
- `id` (PK) - Primary Key
- `journal_number` - Nomor jurnal otomatis (format: JNL-YYYYMMDD-XXXX)
- `journal_date` - Tanggal jurnal
- `description` - Deskripsi jurnal
- `reference` - Nomor referensi dokumen (opsional)
- `status` - Status jurnal (draft, posted, reversed)
- `total_debit` - Total debit dari semua lines
- `total_credit` - Total credit dari semua lines
- `kode_project` - Kode project terkait (opsional)
- `created_by` - ID user yang membuat
- `posted_by` - ID user yang memposting
- `posted_at` - Waktu posting
- `reversed_at` - Waktu reverse (jika direverse)
- `notes` - Catatan tambahan (opsional)
- `created_at` - Waktu pembuatan
- `updated_at` - Waktu update terakhir

### 2. Table: `journal_lines`
Menyimpan detail setiap line dalam journal entry.

**Columns:**
- `id` (PK) - Primary Key
- `journal_entry_id` (FK) - Foreign key ke journal_entries
- `account_code` - Kode akun yang terlibat
- `type` - Tipe transaksi (debit/credit)
- `amount` - Nominal transaksi
- `description` - Deskripsi line
- `balance_after` - Saldo setelah transaksi ini
- `created_at` - Waktu pembuatan
- `updated_at` - Waktu update terakhir

## Model Relationships

### JournalEntry Model
```php
class JournalEntry extends Model
{
    protected $fillable = [
        'journal_number',
        'journal_date',
        'description',
        'reference',
        'status',
        'total_debit',
        'total_credit',
        'kode_project',
        'created_by',
        'posted_by',
        'posted_at',
        'reversed_at',
        'notes',
    ];

    // Relationships
    public function lines() {
        return $this->hasMany(JournalLine::class);
    }

    public function project() {
        return $this->belongsTo(Project::class, 'kode_project', 'kode_project');
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by', 'Id_User');
    }

    public function poster() {
        return $this->belongsTo(User::class, 'posted_by', 'Id_User');
    }

    // Business Logic Methods
    public function isBalanced(): bool {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }

    public function canBePosted(): bool {
        return $this->status === 'draft' && $this->isBalanced() && $this->lines()->count() >= 2;
    }
}
```

### JournalLine Model
```php
class JournalLine extends Model
{
    protected $fillable = [
        'journal_entry_id',
        'account_code',
        'type',
        'amount',
        'description',
        'balance_after',
    ];

    // Relationships
    public function journalEntry() {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account() {
        return $this->belongsTo(Akun::class, 'account_code', 'kode_akun');
    }
}
```

## API Endpoints

### 1. POST `/transaksi/jurnal` - Create Journal Entry
**Controller Method:** `JurnalController@store`

**Request Body:**
```json
{
    "journal_date": "2024-01-15",
    "description": "Purchase of office supplies",
    "reference": "INV-001",
    "kode_project": "PRJ001",
    "notes": "Optional notes",
    "lines": [
        {
            "account_code": "101",
            "type": "debit",
            "amount": 1000.00,
            "description": "Office supplies expense"
        },
        {
            "account_code": "201",
            "type": "credit",
            "amount": 1000.00,
            "description": "Cash payment"
        }
    ]
}
```

**Validation Rules:**
- `journal_date` - required, date
- `description` - required, string
- `reference` - nullable, string
- `kode_project` - nullable, string
- `notes` - nullable, string
- `lines` - required, array, min: 2
- `lines.*.account_code` - required, string, exists in akun table
- `lines.*.type` - required, in: debit, credit
- `lines.*.amount` - required, numeric, min: 0.01
- `lines.*.description` - nullable, string

**Business Logic:**
1. Validasi minimal 2 lines
2. Validasi account exists
3. Validasi type (debit/credit)
4. Validasi amount > 0
5. Validasi balance (total_debit == total_credit ± 0.01)
6. Generate journal_number otomatis
7. Set status = 'draft'
8. Calculate total_debit dan total_credit
9. Create journal entry dan lines dalam transaction

**Response:**
```json
{
    "success": true,
    "message": "Journal entry created successfully",
    "data": {
        "id": 1,
        "journal_number": "JNL-20240115-0001",
        "journal_date": "2024-01-15",
        "description": "Purchase of office supplies",
        "status": "draft",
        "total_debit": 1000.00,
        "total_credit": 1000.00,
        "lines": [...]
    }
}
```

### 2. PUT `/transaksi/jurnal/{id}` - Update Journal Entry
**Controller Method:** `JurnalController@update`

**Request Body:** Same as create

**Validation Rules:** Same as create

**Business Logic:**
1. Validasi journal entry exists dan dalam status draft
2. Validasi input (sama seperti create)
3. Delete existing lines
4. Update journal entry header
5. Create new lines
6. Recalculate totals

### 3. DELETE `/transaksi/jurnal/{id}` - Delete Journal Entry
**Controller Method:** `JurnalController@destroy`

**Business Logic:**
1. Delete lines first (due to foreign key constraint)
2. Delete journal entry
3. Log activity

### 4. POST `/transaksi/jurnal/{id}/post` - Post to General Ledger
**Controller Method:** `JurnalController@post`

**Business Logic:**
1. Check if journal entry can be posted:
   - Status must be 'draft'
   - Must be balanced
   - Must have at least 2 lines
2. Update status to 'posted'
3. Set posted_by dan posted_at
4. Post each line to general_ledger table
5. Calculate balance_after untuk setiap line berdasarkan:
   - Account type (debit/credit normal balance)
   - Transaction type (debit/credit)
   - Current balance from general ledger

### 5. POST `/transaksi/jurnal/{id}/reverse` - Reverse Journal Entry
**Controller Method:** `JurnalController@reverse`

**Business Logic:**
1. Check if journal entry is posted
2. Create new reversing journal entry:
   - journal_number = 'REV-' + original journal_number
   - Swap debit/credit untuk setiap line
   - status = 'posted'
   - total_debit = original total_credit
   - total_credit = original total_debit
3. Post reversing entry to general ledger
4. Mark original as 'reversed'

### 6. POST `/transaksi/api/jurnal/validate-lines` - Validate Lines (Real-time)
**Controller Method:** `JurnalController@validateLines`

**Request Body:**
```json
{
    "lines": [
        {
            "account_code": "101",
            "type": "debit",
            "amount": 1000.00,
            "description": "Description"
        }
    ]
}
```

**Response:**
```json
{
    "valid": true,
    "errors": [],
    "total_debit": 1000.00,
    "total_credit": 1000.00,
    "balance_status": "Balanced",
    "can_submit": true
}
```

**Business Logic:**
1. Validasi minimal 2 lines
2. Validasi account exists
3. Validasi type (debit/credit)
4. Validasi amount > 0
5. Calculate totals
6. Check balance
7. Return validation result dengan detailed errors

### 7. POST `/transaksi/api/jurnal/calculate-totals` - Calculate Totals (Lightweight)
**Controller Method:** `JurnalController@calculateTotals`

**Request Body:** Same as validate-lines

**Response:**
```json
{
    "total_debit": 1000.00,
    "total_credit": 1000.00,
    "balance_status": "Balanced",
    "difference": 0.00
}
```

**Business Logic:**
- Lightweight calculation tanpa full validation
- Used untuk real-time UI updates
- Hanya calculate totals dan check balance

### 8. DELETE `/transaksi/jurnal/{id}/lines/{lineId}` - Delete Journal Line
**Controller Method:** `JurnalController@destroyLine`

**Business Logic:**
1. Check if line belongs to journal entry
2. Check if journal entry has at least 2 lines after deletion
3. Delete the line
4. Recalculate totals untuk journal entry
5. Update total_debit dan total_credit

### 9. PUT `/transaksi/jurnal/{id}/lines/{lineId}` - Update Journal Line
**Controller Method:** `JurnalController@updateLine`

**Request Body:**
```json
{
    "account_code": "102",
    "type": "debit",
    "amount": 1500.00,
    "description": "Updated description"
}
```

**Business Logic:**
1. Check if line belongs to journal entry
2. Update line data
3. Recalculate totals
4. Check if still balanced
5. If not balanced, rollback update
6. Update journal entry totals

## Service Layer: JournalPostingService

### validateJournalEntry(array $data): array
Validasi journal entry untuk double entry compliance.

**Returns:**
```php
[
    'valid' => bool,
    'errors' => array,
    'total_debit' => float,
    'total_credit' => float,
]
```

**Validation Steps:**
1. Check minimal 2 lines
2. Validate account exists untuk setiap line
3. Validate amount > 0 untuk setiap line
4. Calculate total debit dan credit
5. Check balance (toleransi 0.01 untuk floating point)

### createJournalEntry(array $data): JournalEntry
Create journal entry dengan lines dalam transaction.

**Steps:**
1. Validate menggunakan validateJournalEntry()
2. Create journal entry dengan status 'draft'
3. Calculate dan set total_debit, total_credit
4. Create journal lines
5. Return journal entry dengan loaded lines

### postToLedger(JournalEntry $journalEntry): void
Post journal entry ke general ledger.

**Steps:**
1. Check if canBePosted()
2. Update status ke 'posted'
3. Set posted_by dan posted_at
4. Untuk setiap line:
   - Calculate balance_after
   - Create general_ledger entry
   - Update journal line balance_after

### calculateBalanceAfter(JournalLine $line): float
Calculate balance setelah journal line.

**Logic:**
1. Get account dari line
2. Get current balance dari general ledger atau account saldo_awal
3. Calculate new balance:
   - Jika account saldo_normal = 'debit':
     - Debit transaction: balance + amount
     - Credit transaction: balance - amount
   - Jika account saldo_normal = 'credit':
     - Credit transaction: balance + amount
     - Debit transaction: balance - amount

### reverseJournalEntry(JournalEntry $journalEntry): JournalEntry
Reverse posted journal entry.

**Steps:**
1. Check if status = 'posted'
2. Create reversing journal entry dalam transaction
3. Swap debit/credit untuk setiap line
4. Post reversing entry ke general ledger
5. Mark original as 'reversed'
6. Return reversing entry

## Business Rules

### 1. Double Entry Principle
- Setiap journal entry harus memiliki minimal 2 lines
- Total debit harus sama dengan total credit (toleransi ±0.01)
- Tidak boleh hanya debit atau hanya credit

### 2. Account Validation
- Account code harus exists di table akun
- Account harus valid dan aktif

### 3. Amount Validation
- Amount harus berupa numeric
- Amount harus > 0
- Amount dengan 2 decimal places

### 4. Status Workflow
- **Draft** → **Posted** → **Reversed**
- Draft: Bisa di-edit dan di-delete
- Posted: Tidak bisa di-edit atau di-delete, hanya bisa di-reverse
- Reversed: Final state, tidak bisa diubah

### 5. Balance Calculation
- Total Debit = Sum semua lines dengan type = 'debit'
- Total Credit = Sum semua lines dengan type = 'credit'
- Balance Status = 'Balanced' jika |Total Debit - Total Credit| < 0.01
- Balance Status = 'Not Balanced' jika |Total Debit - Total Credit| >= 0.01

### 6. General Ledger Posting
- Hanya journal entry dengan status 'posted' yang dipost ke general ledger
- Setiap line mencatat debit_amount atau credit_amount (satu adalah 0)
- Balance_after dihitung berdasarkan account type dan transaction type
- Balance digunakan untuk trial balance dan financial statements

## Error Handling

### Common Errors
1. **Journal tidak seimbang**
   - Error: "Jurnal tidak seimbang. Total Debit: X, Total Credit: Y. Selisih: Z"
   - Solution: Pastikan total debit sama dengan total credit

2. **Minimal 2 lines**
   - Error: "Journal entry harus memiliki minimal 2 lines (debit dan credit)"
   - Solution: Tambahkan line hingga minimal 2

3. **Account tidak ditemukan**
   - Error: "Kode akun XXX tidak ditemukan"
   - Solution: Gunakan account code yang valid

4. **Amount tidak valid**
   - Error: "Line N: Amount harus berupa angka positif"
   - Solution: Masukkan numeric value > 0

5. **Tidak bisa diposting**
   - Error: "Journal entry cannot be posted. Must be balanced and have at least 2 lines."
   - Solution: Pastikan balanced dan minimal 2 lines

## Frontend Integration

### Real-time Validation
Frontend menggunakan API endpoint untuk validasi real-time:

1. **calculateTotals()** - Dipanggil setiap kali amount atau type berubah
   - POST ke `/transaksi/api/jurnal/calculate-totals`
   - Update UI: total debit, total credit, balance status
   - Enable/disable submit button berdasarkan balance status

2. **validateLines()** - Dipanggil saat form submit
   - POST ke `/transaksi/api/jurnal/validate-lines`
   - Full validation sebelum submit ke server
   - Prevent submission jika invalid

### Form Submit Flow
1. User mengisi form
2. calculateTotals() dipanggil real-time
3. Submit button disabled jika not balanced
4. User klik submit
5. validateLines() dipanggil
6. Jika valid, form disubmit
7. Jika invalid, error ditampilkan

## Performance Considerations

1. **Caching**
   - Akun data di-cache selama 1 jam
   - Project data di-cache selama 1 jam
   - Cache di-clear setelah create/update/delete journal entry

2. **Database Transactions**
   - Semua operasi journal entry menggunakan DB::transaction()
   - Memastikan data consistency
   - Rollback jika error

3. **Eager Loading**
   - Journal entries dengan lines: `with(['lines.account', 'project'])`
   - Mengurangi N+1 query problem

## Security

1. **CSRF Protection**
   - Semua POST/PUT/DELETE request memerlukan CSRF token
   - Token disertakan di meta tag dan request headers

2. **Authorization**
   - Semua routes dilindungi dengan auth middleware
   - Manager access restriction untuk fitur tertentu

3. **Input Validation**
   - Server-side validation untuk semua input
   - Sanitization untuk prevent injection attacks

## Testing

### Unit Tests
- Test validation logic
- Test balance calculation
- Test status transitions
- Test service methods

### Integration Tests
- Test full journal entry creation flow
- Test posting to general ledger
- Test reversal flow
- Test API endpoints

### Manual Testing Checklist
- [ ] Create journal entry dengan 2 lines (balanced)
- [ ] Create journal entry dengan lebih dari 2 lines
- [ ] Attempt create dengan 1 line (should fail)
- [ ] Attempt create dengan unbalanced amounts (should fail)
- [ ] Update journal entry (draft status)
- [ ] Attempt update posted entry (should fail)
- [ ] Post journal entry to ledger
- [ ] Reverse posted journal entry
- [ ] Delete journal line (draft status)
- [ ] Attempt delete line if only 2 lines remain (should fail)
- [ ] Real-time validation UI updates correctly

## Maintenance

### Database Migrations
Pastikan migration berikut dijalankan:
- Create journal_entries table
- Create journal_lines table
- Add foreign key constraints
- Add indexes untuk performance

### Monitoring
- Monitor journal entry creation frequency
- Monitor balance validation errors
- Monitor posting failures
- Monitor performance untuk API endpoints

### Backup
- Regular backup untuk journal_entries dan journal_lines
- Backup general_ledger table
- Audit trail untuk semua changes
