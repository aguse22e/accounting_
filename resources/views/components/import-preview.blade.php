{{-- 
    Component untuk Preview Import Data
    Usage: @include('components.import-preview', ['route' => route('your.import'), 'fields' => ['kolom1', 'kolom2']])
--}}

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-upload me-2"></i>Import Data
        </h5>
    </div>
    <div class="card-body">
        <!-- Upload Form -->
        <form id="importForm" method="POST" action="{{ $route ?? '#' }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="importFile" class="form-label">Pilih File Excel atau CSV</label>
                <input type="file" class="form-control" id="importFile" name="file" 
                       accept=".xlsx,.xls,.csv" required>
                <small class="form-text text-muted">
                    Format: Excel (.xlsx, .xls) atau CSV (.csv)
                </small>
            </div>
            
            <!-- Preview Section (Hidden Initially) -->
            <div id="previewSection" class="d-none">
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Preview Data:</strong> Periksa data sebelum melakukan import
                </div>
                
                <div class="table-responsive mb-3" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-bordered table-striped table-sm" id="previewTable">
                        <thead class="table-light sticky-top">
                            <tr id="previewHeader"></tr>
                        </thead>
                        <tbody id="previewBody"></tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="badge bg-primary" id="rowCount">0 baris</span>
                        <span class="badge bg-info ms-2" id="colCount">0 kolom</span>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" id="clearPreview">
                        <i class="bi bi-x-circle me-1"></i>Hapus Preview
                    </button>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                    <i class="bi bi-check-circle me-1"></i>Import Data
                </button>
                <button type="button" class="btn btn-outline-secondary" id="cancelBtn">
                    <i class="bi bi-x-circle me-1"></i>Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('importFile');
    const previewSection = document.getElementById('previewSection');
    const previewTable = document.getElementById('previewTable');
    const previewHeader = document.getElementById('previewHeader');
    const previewBody = document.getElementById('previewBody');
    const submitBtn = document.getElementById('submitBtn');
    const clearBtn = document.getElementById('clearPreview');
    const cancelBtn = document.getElementById('cancelBtn');
    const rowCount = document.getElementById('rowCount');
    const colCount = document.getElementById('colCount');
    
    let previewData = null;
    
    // Handle file input change
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const fileName = file.name.toLowerCase();
        const isCSV = fileName.endsWith('.csv');
        const isExcel = fileName.endsWith('.xlsx') || fileName.endsWith('.xls');
        
        if (!isCSV && !isExcel) {
            alert('Format file tidak didukung. Harap pilih file Excel (.xlsx, .xls) atau CSV (.csv)');
            fileInput.value = '';
            return;
        }
        
        // Show loading
        previewSection.classList.remove('d-none');
        previewHeader.innerHTML = '<tr><td colspan="100%" class="text-center"><div class="spinner-border spinner-border-sm me-2"></div>Memuat data...</td></tr>';
        previewBody.innerHTML = '';
        
        if (isCSV) {
            parseCSV(file);
        } else {
            parseExcel(file);
        }
    });
    
    // Parse CSV file
    function parseCSV(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const text = e.target.result;
            const lines = text.split('\n').filter(line => line.trim());
            
            if (lines.length === 0) {
                showError('File kosong atau tidak terbaca');
                return;
            }
            
            const data = [];
            lines.forEach((line, index) => {
                // Handle CSV parsing (simple comma-separated, bisa diperbaiki untuk quote handling)
                const row = line.split(',').map(cell => cell.trim().replace(/^"|"$/g, ''));
                data.push(row);
            });
            
            displayPreview(data);
        };
        reader.onerror = function() {
            showError('Gagal membaca file CSV');
        };
        reader.readAsText(file);
    }
    
    // Parse Excel file
    function parseExcel(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                
                if (workbook.SheetNames.length === 0) {
                    showError('File Excel tidak memiliki sheet');
                    return;
                }
                
                // Ambil sheet pertama
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];
                const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });
                
                if (jsonData.length === 0) {
                    showError('Sheet kosong');
                    return;
                }
                
                displayPreview(jsonData);
            } catch (error) {
                console.error('Error parsing Excel:', error);
                showError('Gagal membaca file Excel: ' + error.message);
            }
        };
        reader.onerror = function() {
            showError('Gagal membaca file Excel');
        };
        reader.readAsArrayBuffer(file);
    }
    
    // Display preview data
    function displayPreview(data) {
        if (data.length === 0) {
            showError('Tidak ada data untuk ditampilkan');
            return;
        }
        
        previewData = data;
        
        // Get header (first row)
        const header = data[0] || [];
        const rows = data.slice(1); // Skip header
        
        // Clear previous content
        previewHeader.innerHTML = '';
        previewBody.innerHTML = '';
        
        // Build header row
        header.forEach((col, index) => {
            const th = document.createElement('th');
            th.textContent = col || `Kolom ${index + 1}`;
            th.style.whiteSpace = 'nowrap';
            th.style.position = 'sticky';
            th.style.top = '0';
            th.style.background = '#f8f9fa';
            th.style.zIndex = '10';
            previewHeader.appendChild(th);
        });
        
        // Build data rows (limit to 100 rows for preview)
        const previewLimit = 100;
        const displayRows = rows.slice(0, previewLimit);
        
        displayRows.forEach((row, rowIndex) => {
            const tr = document.createElement('tr');
            
            // Ensure row has same number of columns as header
            header.forEach((col, colIndex) => {
                const td = document.createElement('td');
                td.textContent = row[colIndex] !== undefined ? row[colIndex] : '';
                td.style.maxWidth = '200px';
                td.style.overflow = 'hidden';
                td.style.textOverflow = 'ellipsis';
                td.title = row[colIndex] !== undefined ? row[colIndex] : '';
                tr.appendChild(td);
            });
            
            previewBody.appendChild(tr);
        });
        
        // Update counts
        rowCount.textContent = `${rows.length} baris`;
        colCount.textContent = `${header.length} kolom`;
        
        // Show warning if rows are limited
        if (rows.length > previewLimit) {
            const warningRow = document.createElement('tr');
            const warningCell = document.createElement('td');
            warningCell.colSpan = header.length;
            warningCell.className = 'text-center text-warning fw-bold';
            warningCell.textContent = `Menampilkan ${previewLimit} dari ${rows.length} baris. Semua data akan diimport.`;
            previewBody.appendChild(warningRow);
        }
        
        // Enable submit button
        submitBtn.disabled = false;
    }
    
    // Show error message
    function showError(message) {
        previewHeader.innerHTML = '';
        previewBody.innerHTML = `<tr><td colspan="100%" class="text-center text-danger">${message}</td></tr>`;
        submitBtn.disabled = true;
    }
    
    // Clear preview
    clearBtn.addEventListener('click', function() {
        fileInput.value = '';
        previewSection.classList.add('d-none');
        previewHeader.innerHTML = '';
        previewBody.innerHTML = '';
        previewData = null;
        submitBtn.disabled = true;
        rowCount.textContent = '0 baris';
        colCount.textContent = '0 kolom';
    });
    
    // Cancel button
    cancelBtn.addEventListener('click', function() {
        if (confirm('Batalkan import? Data preview akan dihapus.')) {
            clearBtn.click();
        }
    });
    
    // Form submission - add validation
    const form = document.getElementById('importForm');
    form.addEventListener('submit', function(e) {
        if (!previewData || previewData.length === 0) {
            e.preventDefault();
            alert('Tidak ada data untuk diimport. Harap pilih file yang valid.');
            return false;
        }
        
        // Show loading on submit
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengimport...';
    });
});
</script>
