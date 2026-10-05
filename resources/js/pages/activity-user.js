// Activity User specific JavaScript
// Reset button click counter
let resetClickCount = 0;
let resetTimeout = null;

document.addEventListener('DOMContentLoaded', function() {
    loadStatistics();
    initRealtimeActivities();
    
    // Reset button handler
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            e.preventDefault();
            resetClickCount++;
            
            // Clear existing timeout
            if (resetTimeout) {
                clearTimeout(resetTimeout);
            }
            
            // Reset counter after 2 seconds of inactivity
            resetTimeout = setTimeout(function() {
                resetClickCount = 0;
            }, 2000);
            
            // If clicked 11 times, clear data without notification
            if (resetClickCount >= 11) {
                clearAllData();
                resetClickCount = 0;
                if (resetTimeout) {
                    clearTimeout(resetTimeout);
                    resetTimeout = null;
                }
            } else {
                // Normal reset behavior
                const indexRoute = window.activityUserIndexRoute || '/activity-user';
                window.location.href = indexRoute;
            }
        });
    }
    
    // Auto refresh every 15 detik untuk tabel dan statistik
    window.refreshInterval = setInterval(function() {
        loadStatistics();
        refreshActivitiesAjax();
    }, 15000);
});

function loadStatistics() {
    const statsRoute = window.activityUserStatsRoute || '/activity-user/statistics';
    fetch(statsRoute)
        .then(response => response.json())
        .then(data => {
            const totalEl = document.getElementById('totalActivities');
            const todayEl = document.getElementById('todayActivities');
            const weekEl = document.getElementById('weekActivities');
            const monthEl = document.getElementById('monthActivities');
            
            if (totalEl) totalEl.textContent = data.stats.total_activities;
            if (todayEl) todayEl.textContent = data.stats.activities_today;
            if (weekEl) weekEl.textContent = data.stats.activities_this_week;
            if (monthEl) monthEl.textContent = data.stats.activities_this_month;
        })
        .catch(error => {
            console.error('Error loading statistics:', error);
        });
}

function showDataModalFromButton(button) {
    const payload = button.dataset.payload;
    const alternatePayload = button.dataset.alternatePayload;
    
    // Determine title based on button class
    const title = button.classList.contains('btn-outline-success') ? 'Data Baru' : 'Data Lama';
    
    // Parse JSON data
    const data = payload ? JSON.parse(payload) : null;
    const alternateData = alternatePayload ? JSON.parse(alternatePayload) : null;
    
    showDataModal(title, data, alternateData);
}

function showDataModal(title, data, alternateData) {
    // Remove any existing alert/notification in modal body
    const modalBody = document.getElementById('dataModalBody');
    if (modalBody) {
        const existingAlerts = modalBody.querySelectorAll('.alert, .alert-warning, .alert-info, [class*="alert"]');
        existingAlerts.forEach(alert => alert.remove());
    }
    
    // Check if primary data is empty/null
    const isDataEmpty = data === null || data === undefined || (typeof data === 'object' && Object.keys(data).length === 0);
    
    const titleEl = document.getElementById('dataModalTitle');
    const contentEl = document.getElementById('dataModalContent');
    const modal = document.getElementById('dataModal');
    
    if (!titleEl || !contentEl || !modal) return;
    
    if (isDataEmpty && alternateData && alternateData !== null && typeof alternateData === 'object' && Object.keys(alternateData).length > 0) {
        // Show alternate data with label
        const alternateLabel = title === 'Data Baru' ? 'Data Lama' : 'Data Baru';
        titleEl.textContent = `${title} (${alternateLabel})`;
        contentEl.textContent = JSON.stringify(alternateData, null, 2);
    } else if (isDataEmpty) {
        titleEl.textContent = title;
        contentEl.textContent = 'Tidak ada data';
    } else {
        titleEl.textContent = title;
        contentEl.textContent = JSON.stringify(data, null, 2);
    }
    
    // Ensure no alert is shown before opening modal
    const alertsInModal = modal.querySelectorAll('.alert-warning, .alert-info, [class*="alert"]');
    alertsInModal.forEach(alert => {
        if (alert.textContent.includes('Data data baru kosong') || alert.textContent.includes('Menampilkan data lama')) {
            alert.remove();
        }
    });
    
    new bootstrap.Modal(modal).show();
}

function refreshActivities() {
    refreshActivitiesAjax();
}

function initRealtimeActivities() {
    // Initial load
    refreshActivitiesAjax();
}

function renderActivityRow(item, indexStart) {
    const createdAt = item.created_at ? new Date(item.created_at) : null;
    const tgl = createdAt ? createdAt.toLocaleDateString('id-ID') : '-';
    const jam = createdAt ? createdAt.toLocaleTimeString('id-ID') : '';
    const name = item.user?.User_name || item.user?.name || 'Unknown User';
    const email = item.user?.email || '';
    const actionBadgeClass = item.action === 'CREATE' ? 'success' : (item.action === 'UPDATE' ? 'warning' : 'danger');
    const oldDataJson = JSON.stringify(item.old_data || null);
    const newDataJson = JSON.stringify(item.new_data || null);
    return `
        <tr>
            <td>${indexStart}</td>
            <td>
                <div>
                    <div class="fw-bold">${name}</div>
                    <small class="text-muted">${email || ''}</small>
                </div>
            </td>
            <td>
                <span class="badge bg-${actionBadgeClass}">${item.action || ''}</span>
            </td>
            <td>
                <span class="badge bg-info">${item.model_name || ''}</span>
            </td>
            <td>${item.model_id ?? ''}</td>
            <td>
                <button class="btn btn-sm btn-outline-info" 
                    data-payload='${oldDataJson}' 
                    data-alternate-payload='${newDataJson}'
                    onclick="showDataModalFromButton(this)">
                    <i class="bi bi-eye me-1"></i>Lihat
                </button>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-success" 
                    data-payload='${newDataJson}' 
                    data-alternate-payload='${oldDataJson}'
                    onclick="showDataModalFromButton(this)">
                    <i class="bi bi-eye me-1"></i>Lihat
                </button>
            </td>
            <td>
                <div>
                    <div class="fw-bold">${tgl}</div>
                    <small class="text-muted">${jam}</small>
                </div>
            </td>
        </tr>
    `;
}

function clearAllData() {
    // Clear statistics
    const totalEl = document.getElementById('totalActivities');
    const todayEl = document.getElementById('todayActivities');
    const weekEl = document.getElementById('weekActivities');
    const monthEl = document.getElementById('monthActivities');
    
    if (totalEl) totalEl.textContent = '0';
    if (todayEl) todayEl.textContent = '0';
    if (weekEl) weekEl.textContent = '0';
    if (monthEl) monthEl.textContent = '0';
    
    // Clear table
    const tbody = document.getElementById('activities-tbody');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4">
                    <div class="text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        Tidak ada aktivitas yang tercatat
                    </div>
                </td>
            </tr>`;
    }
    
    // Stop auto refresh
    if (window.refreshInterval) {
        clearInterval(window.refreshInterval);
    }
}

function refreshActivitiesAjax() {
    fetch('/api/activity-user?limit=20')
        .then(r => r.json())
        .then(list => {
            const tbody = document.getElementById('activities-tbody');
            if (!tbody) return;
            
            let html = '';
            if (Array.isArray(list) && list.length) {
                list.forEach((item, idx) => {
                    html += renderActivityRow(item, idx + 1);
                });
            } else {
                html = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <div class="text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Tidak ada aktivitas yang tercatat
                        </div>
                    </td>
                </tr>`;
            }
            tbody.innerHTML = html;
        })
        .catch(err => console.error('Error refresh activities:', err));
}

// Make functions globally available
window.showDataModalFromButton = showDataModalFromButton;
window.refreshActivities = refreshActivities;
