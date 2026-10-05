// ==================== GLOBAL HELPER FUNCTIONS (Defined First) ====================

// Date Helper Functions - Simplified for native date inputs
function convertToISO(dateStr) {
    if (!dateStr || !dateStr.trim()) return '';

    // If already in ISO format (yyyy-mm-dd), return as is
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
        return dateStr;
    }

    // If in dd/mm/yyyy format, convert to yyyy-mm-dd
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateStr)) {
        const parts = dateStr.split('/');
        if (parts.length === 3) {
            const day = parts[0].padStart(2, '0');
            const month = parts[1].padStart(2, '0');
            const year = parts[2];
            return `${year}-${month}-${day}`;
        }
    }

    // Try to parse as date and convert
    const date = new Date(dateStr);
    if (!isNaN(date.getTime())) {
        return date.toISOString().split('T')[0];
    }

    return dateStr;
}

function formatDate(date) {
    return date || '';
}

function validateDate(dateStr) {
    if (!dateStr || !dateStr.trim()) return true; // Empty is valid

    // Check if it's in dd/mm/yyyy format
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateStr)) {
        const parts = dateStr.split('/');
        if (parts.length === 3) {
            const day = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10) - 1; // Month is 0-based
            const year = parseInt(parts[2], 10);
            const date = new Date(year, month, day);
            return date.getFullYear() === year && date.getMonth() === month && date.getDate() === day;
        }
        return false;
    }

    // Check if it's in yyyy-mm-dd format
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
        const date = new Date(dateStr);
        return !isNaN(date.getTime()) && date.toISOString().startsWith(dateStr);
    }

    // Try parsing as date
    const date = new Date(dateStr);
    return !isNaN(date.getTime());
}

// Number Formatting
function formatNumber(num) {
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(num || 0);
}

// Notification Function
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.custom-notification');
    existingNotifications.forEach(notification => notification.remove());

    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'custom-notification';
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        padding: 12px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 300px;
    `;

    // Set background color based on type
    switch(type) {
        case 'success':
            notification.style.backgroundColor = '#198754';
            break;
        case 'error':
            notification.style.backgroundColor = '#dc3545';
            break;
        case 'warning':
            notification.style.backgroundColor = '#ffc107';
            notification.style.color = '#000';
            break;
        default:
            notification.style.backgroundColor = '#0dcaf0';
    }

    notification.textContent = message;
    document.body.appendChild(notification);

    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);

    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Loading Functions
function showLoading() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('dataTable');
    if (spinner) spinner.classList.remove('d-none');
    if (table) table.classList.add('d-none');
}

function hideLoading() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('dataTable');
    if (spinner) spinner.classList.add('d-none');
    if (table) table.classList.remove('d-none');
}

// Summary Cards Update Function
let summaryData = null;

function updateSummaryCards(values) {
    // Get summaryData if not already loaded
    if (!summaryData) {
        const el = document.getElementById('summaryDataJson');
        try { 
            summaryData = el ? JSON.parse(el.textContent || 'null') : null; 
        } catch (e) { 
            summaryData = null; 
        }
    }
    
    const s = values || summaryData;
    if (s) {
        const totalBudgetEl = document.getElementById('totalBudget');
        const totalActualEl = document.getElementById('totalActual');
        const totalVarianceEl = document.getElementById('totalVariance');
        const totalVariancePercentEl = document.getElementById('totalVariancePercent');
        
        if (totalBudgetEl) totalBudgetEl.textContent = 'Rp ' + formatNumber(s.grand_total_budget ?? s.total_budget ?? 0);
        if (totalActualEl) totalActualEl.textContent = 'Rp ' + formatNumber(s.grand_total_actual ?? s.total_actual ?? 0);
        if (totalVarianceEl) totalVarianceEl.textContent = 'Rp ' + formatNumber(s.grand_total_variance ?? s.variance ?? 0);
        if (totalVariancePercentEl) totalVariancePercentEl.textContent = formatNumber(s.grand_total_variance_percent ?? s.variance_percent ?? 0) + '%';
    }
}

// ==================== MAIN SCRIPT (DOMContentLoaded) ====================
document.addEventListener('DOMContentLoaded', function() {
    console.log('Budget Actual page loaded - DOMContentLoaded triggered');
    
    // Initialize cards with initial data
    updateSummaryCards();
    
    // Store initial data from server if available (from blade template)
    try {
        const initialDataEl = document.getElementById('initialDataJson');
        if (initialDataEl && initialDataEl.textContent) {
            const parsedData = JSON.parse(initialDataEl.textContent);
            window.currentBudgetActualData = parsedData;
            console.log('Initial data from server stored:', window.currentBudgetActualData);
            
            // Immediately display the data if it exists
            if (parsedData && (parsedData.data || Array.isArray(parsedData))) {
                console.log('Displaying initial data immediately...');
                updateDataTableWithFullData(parsedData);
            }
        }
    } catch (e) {
        console.error('Error parsing initial data:', e);
    }
    
    // Load initial data automatically on page load (if not already displayed)
    setTimeout(function() {
        // Check if data was already displayed from initialDataJson
        const tableBody = document.getElementById('dataTableBody');
        const hasDataDisplayed = tableBody && tableBody.children.length > 0 && 
                                !tableBody.querySelector('.empty-state');
        
        if (hasDataDisplayed) {
            console.log('Data already displayed from initial load, skipping...');
            return;
        }
        
        const fromDate = document.getElementById('fromDate').value;
        const toDate = document.getElementById('toDate').value;
        const searchValue = document.getElementById('searchFilter')?.value || '';
        
        // If data already loaded from server, display it
        if (window.currentBudgetActualData) {
            console.log('Initial data found from server, displaying...');
            const dataToDisplay = window.currentBudgetActualData.data || window.currentBudgetActualData;
            
            if (dataToDisplay && Array.isArray(dataToDisplay) && dataToDisplay.length > 0) {
                // Data exists, display it (will apply search filter if exists)
                updateDataTableWithFullData(window.currentBudgetActualData);
                console.log('Initial data displayed successfully');
            } else {
                // Data structure exists but no data array, try to load from server
                console.log('Data structure exists but no data array, loading from server...');
                applyFilter();
            }
        } else {
            // No data loaded, fetch from server
            console.log('No initial data found, loading from server...');
            applyFilter();
        }
    }, 200); // Small delay to ensure DOM is ready
    
    function updateDataTableWithFullData(data) {
        console.log('updateDataTableWithFullData called with:', data);
        
        // Ensure data is stored globally for search filtering
        if (data && !window.currentBudgetActualData) {
            window.currentBudgetActualData = data;
            console.log('Initial data stored in window.currentBudgetActualData');
        } else if (data) {
            window.currentBudgetActualData = data;
            console.log('Data updated in window.currentBudgetActualData');
        }
        
        // Store server summary data for use in updateMainDataTable
        if (data && data.summary) {
            window.serverSummaryData = data.summary;
            updateSummaryCards(data.summary);
            console.log('Summary cards updated with server data:', data.summary);
        } else {
            // Clear server summary if not available
            window.serverSummaryData = null;
        }
        
        // Update the main data table with new data (this will apply search filter if exists)
        if (data && data.data) {
            updateMainDataTable(data.data);
            
            // Check if data is empty after server response
            const dataArray = data.data;
            if (!dataArray || (Array.isArray(dataArray) && dataArray.length === 0)) {
                const fromDate = document.getElementById('fromDate')?.value || '';
                const toDate = document.getElementById('toDate')?.value || '';
                const searchFilter = document.getElementById('searchFilter')?.value || '';
                const hasActiveFilter = searchFilter || fromDate || toDate;
                
                if (hasActiveFilter) {
                    let filterMessage = 'Data yang Anda cari tidak tersedia.';
                    const filterParts = [];
                    if (searchFilter) filterParts.push(`kode/nama "${searchFilter}"`);
                    if (fromDate && toDate) filterParts.push(`periode ${fromDate} s/d ${toDate}`);
                    else if (fromDate) filterParts.push(`dari tanggal ${fromDate}`);
                    else if (toDate) filterParts.push(`sampai tanggal ${toDate}`);
                    
                    if (filterParts.length > 0) {
                        filterMessage = `Data yang Anda cari tidak tersedia untuk ${filterParts.join(' dan ')}. Silakan coba dengan filter lain atau reset filter.`;
                    }
                    
                    showNotification(filterMessage, 'warning');
                }
            }
        } else if (data && Array.isArray(data)) {
            // If data is directly an array
            updateMainDataTable(data);
            
            // Check if data is empty
            if (!data || data.length === 0) {
                const fromDate = document.getElementById('fromDate')?.value || '';
                const toDate = document.getElementById('toDate')?.value || '';
                const searchFilter = document.getElementById('searchFilter')?.value || '';
                const hasActiveFilter = searchFilter || fromDate || toDate;
                
                if (hasActiveFilter) {
                    let filterMessage = 'Data yang Anda cari tidak tersedia.';
                    const filterParts = [];
                    if (searchFilter) filterParts.push(`kode/nama "${searchFilter}"`);
                    if (fromDate && toDate) filterParts.push(`periode ${fromDate} s/d ${toDate}`);
                    else if (fromDate) filterParts.push(`dari tanggal ${fromDate}`);
                    else if (toDate) filterParts.push(`sampai tanggal ${toDate}`);
                    
                    if (filterParts.length > 0) {
                        filterMessage = `Data yang Anda cari tidak tersedia untuk ${filterParts.join(' dan ')}. Silakan coba dengan filter lain atau reset filter.`;
                    }
                    
                    showNotification(filterMessage, 'warning');
                }
            }
        } else {
            console.error('No data array found!', data);
            showEmptyState();
            
            // Check if filter is active
            const fromDate = document.getElementById('fromDate')?.value || '';
            const toDate = document.getElementById('toDate')?.value || '';
            const searchFilter = document.getElementById('searchFilter')?.value || '';
            const hasActiveFilter = searchFilter || fromDate || toDate;
            
            if (hasActiveFilter) {
                let filterMessage = 'Data yang Anda cari tidak tersedia.';
                const filterParts = [];
                if (searchFilter) filterParts.push(`kode/nama "${searchFilter}"`);
                if (fromDate && toDate) filterParts.push(`periode ${fromDate} s/d ${toDate}`);
                else if (fromDate) filterParts.push(`dari tanggal ${fromDate}`);
                else if (toDate) filterParts.push(`sampai tanggal ${toDate}`);
                
                if (filterParts.length > 0) {
                    filterMessage = `Data yang Anda cari tidak tersedia untuk ${filterParts.join(' dan ')}. Silakan coba dengan filter lain atau reset filter.`;
                }
                
                showNotification(filterMessage, 'warning');
            }
        }
        
        // Add visual feedback
        const tableBody = document.getElementById('dataTableBody');
        if (tableBody) {
            tableBody.classList.add('data-updated');
            setTimeout(() => {
                tableBody.classList.add('fade-out');
                setTimeout(() => {
                    tableBody.classList.remove('data-updated', 'fade-out');
                }, 500);
            }, 1000);
        }
    }
    
    function updateMainDataTable(data) {
        const tableBody = document.getElementById('dataTableBody');
        if (!tableBody) {
            console.error('dataTableBody not found!');
            return;
        }

        console.log('updateMainDataTable called with data:', data);
        console.log('updateMainDataTable - data type:', Array.isArray(data) ? 'Array' : typeof data);
        console.log('updateMainDataTable - data length:', data ? (Array.isArray(data) ? data.length : 'Not an array') : 0);

        // Get filter values for client-side filtering (only if data not already filtered by server)
        const searchInput = document.getElementById('searchFilter');
        let searchFilter = (searchInput?.value || '').trim().toLowerCase();

        console.log('updateMainDataTable - searchFilter:', searchFilter);

        // Clear existing content
        tableBody.innerHTML = '';

        // Ensure data is an array
        const dataArray = Array.isArray(data) ? data : (data && data.data ? data.data : []);

        console.log('updateMainDataTable - dataArray length:', dataArray.length);

        // Check if data was already filtered by server (if search was sent to server, don't filter again)
        const wasServerFiltered = window.lastSearchSentToServer &&
                                  window.lastSearchSentToServer === searchFilter &&
                                  searchFilter.trim().length > 0;

        console.log('updateMainDataTable - wasServerFiltered:', wasServerFiltered, 'lastSearchSentToServer:', window.lastSearchSentToServer, 'currentSearch:', searchFilter);

        if (dataArray && dataArray.length > 0) {
            let filteredData = dataArray;

            // Apply client-side filtering if data was NOT filtered by server
            if (!wasServerFiltered && searchFilter) {
                console.log('Applying client-side filter:', searchFilter);
                filteredData = dataArray.filter(item => {
                    const accountName = (item.nama_akun || '').toLowerCase();
                    const accountCode = (item.kode_akun || '').toLowerCase();
                    const projectName = (item.nama_project || '').toLowerCase();
                    const projectCode = (item.kode_project || '').toLowerCase();

                    // Match: nama akun, kode akun, nama project, kode project
                    return accountName.includes(searchFilter) ||
                           accountCode.includes(searchFilter) ||
                           projectName.includes(searchFilter) ||
                           projectCode.includes(searchFilter);
                });
                console.log('Filtered data:', filteredData.length, 'of', dataArray.length);
            }

            if (filteredData.length > 0) {
                // Render each budget entry as a row
                filteredData.forEach(item => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.kode_akun}</td>
                        <td>${item.nama_akun}</td>
                        <td>${item.kode_project}</td>
                        <td>${item.nama_project}</td>
                        <td class="text-end">${formatNumber(item.budget)}</td>
                        <td class="text-end">${formatNumber(item.actual)}</td>
                        <td class="text-end ${item.variance >= 0 ? 'text-success' : 'text-danger'}">${formatNumber(item.variance)}</td>
                        <td class="text-end ${item.variance_percent >= 0 ? 'text-success' : 'text-danger'}">${formatNumber(item.variance_percent)}%</td>
                    `;
                    tableBody.appendChild(row);
                });

                // Add grand total row
                const grandTotalRow = document.createElement('tr');
                grandTotalRow.className = 'table-primary fw-bold';
                grandTotalRow.innerHTML = `
                    <td colspan="4" class="text-end"><strong>GRAND TOTAL:</strong></td>
                    <td class="text-end"><strong>${formatNumber(filteredData.reduce((sum, item) => sum + item.budget, 0))}</strong></td>
                    <td class="text-end"><strong>${formatNumber(filteredData.reduce((sum, item) => sum + item.actual, 0))}</strong></td>
                    <td class="text-end ${filteredData.reduce((sum, item) => sum + item.variance, 0) >= 0 ? 'text-success' : 'text-danger'}"><strong>${formatNumber(filteredData.reduce((sum, item) => sum + item.variance, 0))}</strong></td>
                    <td class="text-end ${filteredData.reduce((sum, item) => sum + item.variance_percent, 0) >= 0 ? 'text-success' : 'text-danger'}"><strong>${formatNumber(filteredData.reduce((sum, item) => sum + item.variance_percent, 0))}%</strong></td>
                `;
                tableBody.appendChild(grandTotalRow);

                // Update summary cards with filtered totals (only if no server summary available)
                if (!window.serverSummaryData) {
                    const grandTotalBudget = filteredData.reduce((sum, item) => sum + item.budget, 0);
                    const grandTotalActual = filteredData.reduce((sum, item) => sum + item.actual, 0);
                    const grandTotalVariance = grandTotalBudget - grandTotalActual;
                    const grandTotalVariancePercent = grandTotalActual != 0 ? (grandTotalVariance / grandTotalActual) * 100 : 0;

                    updateSummaryCards({
                        grand_total_budget: grandTotalBudget,
                        grand_total_actual: grandTotalActual,
                        grand_total_variance: grandTotalVariance,
                        grand_total_variance_percent: grandTotalVariancePercent
                    });
                }
            } else {
                // No data found after filtering
                const fromDate = document.getElementById('fromDate')?.value || '';
                const toDate = document.getElementById('toDate')?.value || '';
                const hasActiveFilter = searchFilter || fromDate || toDate;

                showEmptyState();

                if (hasActiveFilter) {
                    let filterMessage = 'Data yang Anda cari tidak tersedia.';
                    const filterParts = [];
                    if (searchFilter) filterParts.push(`kode/nama "${searchFilter}"`);
                    if (fromDate && toDate) filterParts.push(`periode ${fromDate} s/d ${toDate}`);
                    else if (fromDate) filterParts.push(`dari tanggal ${fromDate}`);
                    else if (toDate) filterParts.push(`sampai tanggal ${toDate}`);

                    if (filterParts.length > 0) {
                        filterMessage = `Data yang Anda cari tidak tersedia untuk ${filterParts.join(' dan ')}. Silakan coba dengan filter lain atau reset filter.`;
                    }

                    showNotification(filterMessage, 'warning');
                }
            }
        } else {
            // No data at all
            const fromDate = document.getElementById('fromDate')?.value || '';
            const toDate = document.getElementById('toDate')?.value || '';
            const hasActiveFilter = searchFilter || fromDate || toDate;

            showEmptyState();

            if (hasActiveFilter) {
                let filterMessage = 'Data yang Anda cari tidak tersedia.';
                const filterParts = [];
                if (searchFilter) filterParts.push(`kode/nama "${searchFilter}"`);
                if (fromDate && toDate) filterParts.push(`periode ${fromDate} s/d ${toDate}`);
                else if (fromDate) filterParts.push(`dari tanggal ${fromDate}`);
                else if (toDate) filterParts.push(`sampai tanggal ${toDate}`);

                if (filterParts.length > 0) {
                    filterMessage = `Data yang Anda cari tidak tersedia untuk ${filterParts.join(' dan ')}. Silakan coba dengan filter lain atau reset filter.`;
                }

                showNotification(filterMessage, 'warning');
            }
        }
    }
    
    function showEmptyState() {
        const tableBody = document.getElementById('dataTableBody');
        if (!tableBody) return;
        
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = `
            <td colspan="9" class="text-center py-5">
                <div class="empty-state">
                    <i class="bi bi-graph-up fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Belum Ada Data Budget vs Actual</h5>
                    <p class="text-muted">Data akan muncul secara real-time saat ada budget atau transaksi baru</p>
                    <div class="mt-3">
                        <span class="badge bg-info">Status: Menunggu Data</span>
                    </div>
                </div>
            </td>
        `;
        tableBody.appendChild(emptyRow);
        
        // Reset summary cards to zero when no data
        updateSummaryCards({
            grand_total_budget: 0,
            grand_total_actual: 0,
            grand_total_variance: 0,
            grand_total_variance_percent: 0
        });
    }
    

    // Buttons - ensure they are found
    const searchBtn = document.getElementById('searchBtn');
    const todayBtn = document.getElementById('todayBtn');
    const resetBtn = document.getElementById('resetBtn');
    const printPdfBtn = document.getElementById('printPdfBtn');
    
    console.log('Buttons found:', {
        searchBtn: !!searchBtn,
        todayBtn: !!todayBtn,
        resetBtn: !!resetBtn,
        printPdfBtn: !!printPdfBtn
    });
    
    // ==================== SEARCH BUTTON FUNCTION ====================
    if (searchBtn) {
        searchBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            try {
                const fromDateEl = document.getElementById('fromDate');
                const toDateEl = document.getElementById('toDate');
                
                if (!fromDateEl || !toDateEl) {
                    showNotification('Elemen input tanggal tidak ditemukan', 'error');
                    return;
                }
                
                // Validate dates before applying filter
                const fromDate = fromDateEl.value.trim();
                const toDate = toDateEl.value.trim();
                
                if (fromDate && !validateDate(fromDate)) {
                    showNotification('Format tanggal "Dari Tanggal" tidak valid', 'error');
                    return;
                }
                if (toDate && !validateDate(toDate)) {
                    showNotification('Format tanggal "Sampai Tanggal" tidak valid', 'error');
                    return;
                }
                
                // Validate date range
                if (fromDate && toDate) {
                    const from = new Date(convertToISO(fromDate));
                    const to = new Date(convertToISO(toDate));
                    if (from > to) {
                        showNotification('Tanggal dari tidak boleh lebih besar dari tanggal sampai', 'error');
                        return;
                    }
                }
                
                // Apply filter
                showLoading();
                applyFilter();
            } catch (error) {
                console.error('Error in Search button:', error);
                hideLoading();
                showNotification('Terjadi kesalahan: ' + error.message, 'error');
            }
        });
    }
    
    // ==================== PDF EXPORT FUNCTION ====================
    if (printPdfBtn) {
        printPdfBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            try {
                const fromDateEl = document.getElementById('fromDate');
                const toDateEl = document.getElementById('toDate');
                const searchFilterEl = document.getElementById('searchFilter');
                
                if (!fromDateEl || !toDateEl) {
                    showNotification('Elemen input tanggal tidak ditemukan', 'error');
                    return;
                }
                
                // Get and convert dates
                let fromDate = fromDateEl.value.trim() || '';
                let toDate = toDateEl.value.trim() || '';
                const searchFilter = searchFilterEl?.value.trim() || '';
                
                // Validate dates
                if (fromDate && !validateDate(fromDate)) {
                    showNotification('Format tanggal "Dari Tanggal" tidak valid', 'error');
                    return;
                }
                if (toDate && !validateDate(toDate)) {
                    showNotification('Format tanggal "Sampai Tanggal" tidak valid', 'error');
                    return;
                }
                
                // Convert to ISO format
                if (fromDate) fromDate = convertToISO(fromDate);
                if (toDate) toDate = convertToISO(toDate);
                
                // Validate date range
                if (fromDate && toDate) {
                    const from = new Date(fromDate);
                    const to = new Date(toDate);
                    if (from > to) {
                        showNotification('Tanggal dari tidak boleh lebih besar dari tanggal sampai', 'error');
                        return;
                    }
                }
                
                // Build URL
                const params = new URLSearchParams();
                if (fromDate) params.append('from_date', fromDate);
                if (toDate) params.append('to_date', toDate);
                if (searchFilter) params.append('search', searchFilter);
                
                const baseUrl = window.budgetActualRoutes?.exportPdf || '/budget-actual/export-pdf';
                const url = baseUrl + (params.toString() ? '?' + params.toString() : '');
                
                // Show notification and open PDF
                showNotification('Membuka PDF...', 'info');
                const pdfWindow = window.open(url, '_blank');
                
                if (!pdfWindow) {
                    showNotification('Popup diblokir. Silakan izinkan popup untuk browser ini.', 'error');
                }
            } catch (error) {
                console.error('Error in Print PDF:', error);
                showNotification('Terjadi kesalahan: ' + error.message, 'error');
            }
        });
    }
    
    
    // ==================== DATE PICKER INITIALIZATION ====================
    function initializeDatePickers() {
        const dateInputs = document.querySelectorAll('.date-input');
        
        dateInputs.forEach(input => {
            const container = input.parentElement;
            
            // Click handler for calendar icon and input
            container.addEventListener('click', function(e) {
                if (e.target === input || e.target.classList.contains('bi-calendar') || e.target === container) {
                    e.preventDefault();
                    showNativeDatePicker(input);
                }
            });
            
            // Manual typing with auto-format
            input.addEventListener('input', function(e) {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 0) {
                    if (value.length <= 2) {
                        this.value = value;
                    } else if (value.length <= 4) {
                        this.value = value.substring(0, 2) + '/' + value.substring(2);
                    } else if (value.length <= 6) {
                        this.value = value.substring(0, 2) + '/' + value.substring(2, 4) + '/' + value.substring(4);
                    } else {
                        this.value = value.substring(0, 2) + '/' + value.substring(2, 4) + '/' + value.substring(4, 8);
                    }
                }
            });
            
            // Validate on blur
            input.addEventListener('blur', function() {
                if (this.value && !validateDate(this.value)) {
                    showNotification('Format tanggal tidak valid. Gunakan format dd/mm/yyyy', 'warning');
                }
            });
        });
    }
    
    function showNativeDatePicker(input) {
        const tempInput = document.createElement('input');
        tempInput.type = 'date';
        tempInput.style.position = 'fixed';
        tempInput.style.opacity = '0';
        tempInput.style.width = '1px';
        tempInput.style.height = '1px';
        tempInput.style.left = '-9999px';
        
        // Convert current value to ISO
        const currentValue = input.value;
        if (currentValue && validateDate(currentValue)) {
            tempInput.value = convertToISO(currentValue);
        }
        
        const changeHandler = function() {
            if (tempInput.value) {
                input.value = formatDate(tempInput.value);
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
            cleanup();
        };
        
        const cleanup = function() {
            tempInput.removeEventListener('change', changeHandler);
            if (tempInput.parentNode) {
                document.body.removeChild(tempInput);
            }
        };
        
        tempInput.addEventListener('change', changeHandler);
        document.body.appendChild(tempInput);
        
        setTimeout(() => {
            try {
                tempInput.showPicker();
            } catch (e) {
                tempInput.focus();
                tempInput.click();
            }
        }, 50);
    }
    
    // Hari ini button - set dates to today and filter
    if (todayBtn) {
        todayBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('=== Hari ini button clicked ===');
            const today = new Date();
            const todayFormatted = formatDate(today);
            console.log('Setting dates to today:', todayFormatted);
            
            const fromDateEl = document.getElementById('fromDate');
            const toDateEl = document.getElementById('toDate');
            
            if (fromDateEl && toDateEl) {
                fromDateEl.value = todayFormatted;
                toDateEl.value = todayFormatted;
                console.log('Dates set, calling applyFilter...');
                
                // Clear search filter when using "Hari ini"
                const searchFilterEl = document.getElementById('searchFilter');
                if (searchFilterEl) {
                    searchFilterEl.value = '';
                    window.lastSearchSentToServer = '';
                }
                
                // Show loading and fetch data
                showLoading();
                applyFilter();
            } else {
                console.error('Date input elements not found!');
                showNotification('Elemen input tanggal tidak ditemukan', 'error');
            }
        });
    }
    
    // Reset button - clear all filters and reload data
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('=== Reset button clicked ===');
            
            const searchFilterEl = document.getElementById('searchFilter');
            const fromDateEl = document.getElementById('fromDate');
            const toDateEl = document.getElementById('toDate');
            
            if (searchFilterEl) searchFilterEl.value = '';
            if (fromDateEl) fromDateEl.value = '';
            if (toDateEl) toDateEl.value = '';
            
            // Clear server filter flag
            window.lastSearchSentToServer = '';
            
            console.log('Filters cleared, fetching all data...');
            showNotification('Semua filter telah direset, memuat semua data...', 'info');
            
            // Show loading and fetch all data
            showLoading();
            applyFilter();
        });
    }



    function applyFilter() {
        console.log('=== applyFilter function started ===');
        console.log('Current URL:', window.location.href);
        
        try {
            // Get filter values
            const searchFilterEl = document.getElementById('searchFilter');
            const fromDateEl = document.getElementById('fromDate');
            const toDateEl = document.getElementById('toDate');
            
            if (!searchFilterEl || !fromDateEl || !toDateEl) {
                console.error('Filter elements not found!', {
                    searchFilter: !!searchFilterEl,
                    fromDate: !!fromDateEl,
                    toDate: !!toDateEl
                });
                hideLoading();
                showNotification('Elemen filter tidak ditemukan', 'error');
                return;
            }
            
            const searchFilter = searchFilterEl.value || '';
            let fromDate = fromDateEl.value || '';
            let toDate = toDateEl.value || '';

            // Always convert dates to ISO format for server
            if (fromDate) {
                fromDate = convertToISO(fromDate);
            }
            if (toDate) {
                toDate = convertToISO(toDate);
            }

            console.log('Filter values:', { searchFilter, fromDate, toDate });

            // Use the dates as provided by the user (now in ISO format)
            let finalFromDate = fromDate;
            let finalToDate = toDate;

            // Only validate if both dates are provided
            if (finalFromDate && finalToDate) {
                const from = new Date(finalFromDate);
                const to = new Date(finalToDate);
                if (from > to) {
                    hideLoading();
                    alert('Tanggal dari tidak boleh lebih besar dari tanggal sampai');
                    return;
                }
            }

            // Always fetch from server to ensure fresh data
            const hasDateFilter = finalFromDate || finalToDate;
            const hasSearchFilter = searchFilter.trim().length > 0;
            
            // Build URL with parameters
            const params = new URLSearchParams();
            
            if (finalFromDate) params.append('from_date', finalFromDate);
            if (finalToDate) params.append('to_date', finalToDate);
            
            // Add search parameter to server-side filtering
            if (hasSearchFilter) {
                params.append('search', searchFilter.trim());
                // Store that we sent search to server (so we don't filter again client-side)
                window.lastSearchSentToServer = searchFilter.trim().toLowerCase();
            } else {
                // Clear the flag if no search is sent
                window.lastSearchSentToServer = '';
            }

            // If no date filters, server will use wide date range automatically
            // This ensures we always get data even without date filters
            const apiUrl = '/budget-actual/data?' + params.toString();
            console.log('Fetching from URL:', apiUrl);
            console.log('Full API URL:', window.location.origin + apiUrl);
            console.log('Filter values - fromDate:', finalFromDate, 'toDate:', finalToDate, 'searchFilter:', searchFilter);

            // Menggunakan jQuery AJAX untuk filter data tanpa reload halaman
            // 1. User experience lebih baik - tidak ada flash/reload
            // 2. Update tampilan cepat tanpa refresh browser
            // 3. Support loading indicator dan error handling yang jelas
            if (typeof $ !== 'undefined' && $.ajax) {
            console.log('Using jQuery AJAX');
            $.ajax({
                url: apiUrl,
                type: 'GET',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(result) {
                    console.log('AJAX Success:', result);
                    console.log('Result type:', typeof result);
                    console.log('Result keys:', result ? Object.keys(result) : 'null');

                    if (result && result.success && result.data) {
                        console.log('Updating table and summary cards...');
                        console.log('Data received:', result.data);
                        console.log('Data type:', typeof result.data);
                        console.log('Data length:', Array.isArray(result.data) ? result.data.length : 'not array');

                        // Store data globally for client-side search
                        window.currentBudgetActualData = result.data;
                        console.log('Data stored in window.currentBudgetActualData:', result.data);

                        // Always update with full data (will apply search filter inside updateMainDataTable)
                        // This ensures summary cards are updated correctly
                        updateDataTableWithFullData(result.data);

                        // Show success notification
                        showNotification('Data berhasil diperbarui', 'success');
                    } else {
                        console.error('Invalid response:', result);
                        console.log('Response details:', {
                            success: result?.success,
                            hasData: result?.data ? true : false,
                            dataType: typeof result?.data,
                            dataLength: Array.isArray(result?.data) ? result.data.length : 'not array'
                        });
                        // Show empty state when no data
                        showEmptyState();
                        hideLoading();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', { xhr, status, error, responseText: xhr.responseText });
                    hideLoading();
                    let errorMessage = 'Gagal memuat data dari server';
                    if (xhr.responseText) {
                        try {
                            const errorData = JSON.parse(xhr.responseText);
                            errorMessage = errorData.message || errorMessage;
                        } catch (e) {
                            errorMessage = error + ' (Status: ' + xhr.status + ')';
                        }
                    }
                    showNotification(errorMessage, 'error');
                },
                complete: function() {
                    hideLoading();
                    console.log('AJAX request completed');
                }
            });
        } else {
            // Fallback to XMLHttpRequest
            console.log('Using XMLHttpRequest fallback');
            const xhr = new XMLHttpRequest();
            xhr.open('GET', apiUrl, true);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const result = JSON.parse(xhr.responseText);
                            console.log('XHR Success:', result);
                            
                            if (result.success && result.data) {
                                // Store data globally for client-side search
                                window.currentBudgetActualData = result.data;
                                console.log('Data stored in window.currentBudgetActualData (XHR):', result.data);
                                
                                // Always update with full data (will apply search filter inside updateMainDataTable)
                                // This ensures summary cards are updated correctly
                                updateDataTableWithFullData(result.data);
                                
                                // Show success notification
                                showNotification('Data berhasil dimuat', 'success');
                            } else {
                                console.error('Invalid response:', result);
                                // Pesan error dihapus sesuai permintaan
                            }
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            showNotification('Gagal memproses data dari server', 'error');
                        }
                    } else {
                        console.error('HTTP Error:', xhr.status);
                        showNotification('Gagal memuat data dari server (Status: ' + xhr.status + ')', 'error');
                    }
                    hideLoading();
                }
            };
            
            xhr.onerror = function() {
                console.error('Network error');
                showNotification('Gagal menghubungi server', 'error');
                hideLoading();
            };
            
                xhr.send();
            }
        } catch (error) {
            console.error('Error in applyFilter:', error);
            console.error('Error stack:', error.stack);
            hideLoading();
            showNotification('Terjadi kesalahan: ' + error.message, 'error');
        }
    }

    // Initialize date pickers on page load
    initializeDatePickers();
    
    // Auto-filter on date input change - trigger immediately when date changes
    ['fromDate','toDate'].forEach(function(id){
        const el = document.getElementById(id);
        if (!el) return;
        
        // Trigger on change (when user selects date)
        el.addEventListener('change', function() {
            // Validate date format before applying filter
            const value = this.value;
            if (value && value.includes('/')) {
                const parts = value.split('/');
                if (parts.length === 3 && parts[0].length === 2 && parts[1].length === 2 && parts[2].length === 4) {
                    applyFilter();
                }
            } else if (!value) {
                applyFilter();
            }
        });
        
        // Also trigger on input for better responsiveness (for manual typing)
        el.addEventListener('input', function() {
            // Only trigger if the date format is complete (dd/mm/yyyy = 10 chars)
            const value = this.value;
            if (value && value.includes('/')) {
                const parts = value.split('/');
                if (parts.length === 3 && parts[0].length === 2 && parts[1].length === 2 && parts[2].length === 4) {
                    applyFilter();
                }
            }
        });
    });
    
    // Auto-filter on search input change - instant client-side filtering
    let searchTimeout;
    
    function performSearchFilter() {
        console.log('performSearchFilter called');
        
        const searchInput = document.getElementById('searchFilter');
        if (!searchInput) return;
        
        const searchValue = searchInput.value.trim();
        const fromDate = document.getElementById('fromDate').value || '';
        const toDate = document.getElementById('toDate').value || '';
        
        console.log('Search value:', searchValue);
        console.log('Current data:', window.currentBudgetActualData);
        
        // Always try client-side filtering first (instant response)
        const currentData = window.currentBudgetActualData;
        
        if (!currentData) {
            console.log('No data loaded, fetching from server...');
            // If no data loaded, fetch from server
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                applyFilter();
            }, 300);
            return;
        }
        
        // Check data structure and filter
        let dataToFilter = null;
        if (currentData && currentData.data && Array.isArray(currentData.data)) {
            dataToFilter = currentData.data;
        } else if (currentData && Array.isArray(currentData)) {
            dataToFilter = currentData;
        }
        
        if (dataToFilter) {
            console.log('Filtering data client-side:', dataToFilter.length, 'accounts');
            // Client-side filtering - instant, no delay
            updateMainDataTable(dataToFilter);
        } else {
            console.log('Data structure not recognized, fetching from server...');
            // If data structure not recognized, fetch from server
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                applyFilter();
            }, 300);
        }
    }
    
    // Setup search input event listeners after DOM is ready
    setTimeout(function() {
        const searchInput = document.getElementById('searchFilter');
        if (searchInput) {
            console.log('Setting up search input event listeners');
            
            // Filter instantly on input (client-side is fast)
            searchInput.addEventListener('input', function(e) {
                console.log('Search input event triggered:', e.target.value);
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performSearchFilter, 100); // Reduced debounce for faster response
            });
            
            //filter on paste for instant feedback
            searchInput.addEventListener('paste', function(e) {
                setTimeout(function() {
                    console.log('Search paste event triggered');
                    performSearchFilter();
                }, 10);
            });
            
            // Handle Enter key - trigger search button click
            searchInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    console.log('Enter key pressed in search input');
                    if (searchBtn) {
                        searchBtn.click();
                    } else {
                        applyFilter();
                    }
                    return;
                }
                
                // Skip Tab key
                if (e.key === 'Tab') return;
                
                console.log('Search keyup event triggered:', e.key);
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performSearchFilter, 50); // Even faster on keyup
            });
        } else {
            console.error('Search input not found!');
        }
    }, 200); // Wait a bit longer to ensure DOM is ready


    // ==================== EXCEL EXPORT FUNCTION ====================
    const submitExportForm = document.getElementById('submitExportForm');
    if (submitExportForm) {
        submitExportForm.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            try {
                const form = document.getElementById('exportForm');
                if (!form) {
                    showNotification('Form export tidak ditemukan', 'error');
                    return;
                }
                
                // Get values from main inputs (as primary source)
                const fromDateEl = document.getElementById('fromDate');
                const toDateEl = document.getElementById('toDate');
                const searchFilterEl = document.getElementById('searchFilter');
                
                let fromDate = fromDateEl?.value.trim() || '';
                let toDate = toDateEl?.value.trim() || '';
                let searchFilter = searchFilterEl?.value.trim() || '';
                
                // Get form data
                const formData = new FormData(form);
                const exportSearch = document.getElementById('exportSearch')?.value.trim() || '';
                
                // Use export search if provided, otherwise use main search
                if (exportSearch) {
                    searchFilter = exportSearch;
                }
                
                // Get dates from form if provided, otherwise use main inputs
                let formFromDate = formData.get('from_date') || '';
                let formToDate = formData.get('to_date') || '';
                
                if (formFromDate) fromDate = formFromDate;
                if (formToDate) toDate = formToDate;
                
                // Validate dates
                if (fromDate && fromDate.includes('/') && !validateDate(fromDate)) {
                    showNotification('Format tanggal "Dari Tanggal" tidak valid', 'error');
                    return;
                }
                if (toDate && toDate.includes('/') && !validateDate(toDate)) {
                    showNotification('Format tanggal "Sampai Tanggal" tidak valid', 'error');
                    return;
                }
                
                // Convert to ISO format
                if (fromDate && fromDate.includes('/')) fromDate = convertToISO(fromDate);
                if (toDate && toDate.includes('/')) toDate = convertToISO(toDate);
                
                // Validate date range
                if (fromDate && toDate) {
                    const from = new Date(fromDate);
                    const to = new Date(toDate);
                    if (from > to) {
                        showNotification('Tanggal dari tidak boleh lebih besar dari tanggal sampai', 'error');
                        return;
                    }
                }
                
                // Build URL parameters
                const params = new URLSearchParams();
                
                // Add dates
                if (fromDate) params.append('from_date', fromDate);
                if (toDate) params.append('to_date', toDate);
                
                // Add search
                if (searchFilter) params.append('search', searchFilter);
                
                // Add other form fields (project_id, akun_kode)
                for (const [key, value] of formData.entries()) {
                    if (key !== 'from_date' && key !== 'to_date' && key !== 'search' && value && value.trim() !== '') {
                        params.append(key, value);
                    }
                }
                
                // Build URL
                const baseUrl = form.action;
                const url = baseUrl + (params.toString() ? '?' + params.toString() : '');
                
                // Show notification
                showNotification('Export Excel sedang diproses...', 'info');
                
                // Close modal
                const exportModal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
                if (exportModal) {
                    exportModal.hide();
                }
                
                // Download via iframe
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.src = url;
                document.body.appendChild(iframe);
                
                // Cleanup after download
                setTimeout(function() {
                    if (iframe.parentNode) {
                        document.body.removeChild(iframe);
                    }
                    showNotification('Export Excel selesai', 'success');
                }, 5000);
            } catch (error) {
                console.error('Error in Export Excel:', error);
                showNotification('Terjadi kesalahan: ' + error.message, 'error');
            }
        });
    }
    
    // ==================== SYNC EXPORT MODAL ====================
    const exportModal = document.getElementById('exportModal');
    if (exportModal) {
        exportModal.addEventListener('show.bs.modal', function() {
            // Sync search value
            const mainSearch = document.getElementById('searchFilter')?.value || '';
            const exportSearch = document.getElementById('exportSearch');
            if (exportSearch) {
                exportSearch.value = mainSearch;
            }
            
            // Sync date values (convert to ISO format for form)
            let mainFromDate = document.getElementById('fromDate')?.value || '';
            let mainToDate = document.getElementById('toDate')?.value || '';
            const exportFromDate = document.querySelector('#exportForm input[name="from_date"]');
            const exportToDate = document.querySelector('#exportForm input[name="to_date"]');
            
            // Convert dates from dd/mm/yyyy to yyyy-mm-dd for export form
            if (mainFromDate && mainFromDate.includes('/')) {
                mainFromDate = convertToISO(mainFromDate);
            }
            if (mainToDate && mainToDate.includes('/')) {
                mainToDate = convertToISO(mainToDate);
            }
            
            if (exportFromDate && !exportFromDate.value && mainFromDate) {
                exportFromDate.value = mainFromDate;
            }
            if (exportToDate && !exportToDate.value && mainToDate) {
                exportToDate.value = mainToDate;
            }
        });
    }
});
