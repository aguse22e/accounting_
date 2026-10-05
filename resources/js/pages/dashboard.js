let transaksiChart;

// Update waktu
function updateTime() {
    const now = new Date();
    document.getElementById('current-time').textContent = now.toLocaleTimeString('id-ID');
}

// Update data
function updateDashboardData() {
    fetch('/api/dashboard-data')
        .then(response => response.json())
        .then(data => {
            // Update statistik dengan animasi
            animateNumber('saldo-akhir-bank', data.saldo_akhir_bank);
            animateNumber('total-transaksi', data.total_transaksi);
            animateNumber('transaksi-hari-ini', data.transaksi_hari_ini);
            animateNumber('net-loss', data.net_loss);
            
            // Update grafik transaksi
            updateTransaksiChart(data.chart_data);
            
            // Update status indicator
            document.getElementById('status-indicator').innerHTML = '<i class="bi bi-circle-fill me-1"></i>Live';
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('status-indicator').innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Error';
        });
}

// Animasi angka
function animateNumber(elementId, targetValue) {
    const element = document.getElementById(elementId);
    const currentText = element.textContent;
    
    // Check if it's a currency format (contains 'Rp')
    if (currentText.includes('Rp')) {
        const currentValue = parseFloat(currentText.replace(/[^\d.-]/g, '')) || 0;
        const increment = (targetValue - currentValue) / 20;
        let current = currentValue;
        
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= targetValue) || (increment < 0 && current <= targetValue)) {
                current = targetValue;
                clearInterval(timer);
            }
            element.textContent = 'Rp ' + current.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }, 50);
    } else {
        // Regular number animation
        const currentValue = parseInt(currentText) || 0;
        const increment = (targetValue - currentValue) / 20;
        let current = currentValue;
        
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= targetValue) || (increment < 0 && current <= targetValue)) {
                current = targetValue;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current);
        }, 50);
    }
}

// Update transaksi terbaru
function refreshTransactions() {
    fetch('/api/recent-transactions')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('transactions-table');
            tbody.innerHTML = '';
            
            data.forEach(transaksi => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${new Date(transaksi.tanggal).toLocaleDateString('id-ID')}</td>
                    <td>${transaksi.akun?.nama_akun || 'N/A'}</td>
                    <td>${transaksi.deskripsi || '-'}</td>
                    <td>
                        <span class="badge ${transaksi.transaksi_type === 'Debit' ? 'bg-success' : 'bg-danger'}">
                            ${transaksi.transaksi_type}
                        </span>
                    </td>
                    <td class="text-end">Rp ${parseInt(transaksi.nominal).toLocaleString('id-ID')}</td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => console.error('Error:', error));
}

// Update aktivitas user terbaru
function refreshActivities() {
    fetch('/api/recent-activities')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('recent-activities-table');
            if (!tbody) return;
            tbody.innerHTML = '';
            data.forEach(item => {
                const tr = document.createElement('tr');
                const createdAt = item.created_at ? new Date(item.created_at) : null;
                const waktu = createdAt ? `${createdAt.toLocaleTimeString('id-ID')} ${createdAt.toLocaleDateString('id-ID')}` : '-';
                const actionColors = { 'CREATE':'success','READ':'info','UPDATE':'warning','DELETE':'danger' };
                const color = actionColors[item.action] || 'secondary';
                tr.innerHTML = `
                    <td>${waktu}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="${item.user?.photo_url || '/storage/default-avatar.png'}" class="rounded-circle me-2" width="24" height="24" style="object-fit:cover;">
                            <div>${item.user?.name || 'Unknown'}</div>
                        </div>
                    </td>
                    <td><span class="badge bg-${color}">${item.action}</span></td>
                    <td>${item.model_name || '-'}</td>
                    <td>${item.model_id || '-'}</td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(error => console.error('Error:', error));
}

// Update grafik transaksi
function updateTransaksiChart(data) {
    if (transaksiChart) {
        transaksiChart.data.labels = data.labels;
        transaksiChart.data.datasets[0].data = data.values;
        transaksiChart.update('active');
    }
}



// Inisialisasi grafik transaksi
function initTransaksiChart() {
    const ctx = document.getElementById('transaksiChart').getContext('2d');
    
    if (transaksiChart) {
        transaksiChart.destroy();
    }
    
    const config = {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Jumlah Transaksi',
                data: [],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                borderWidth: 3,
                pointBackgroundColor: 'rgb(75, 192, 192)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Tanggal'
                    },
                    grid: {
                        display: true,
                        color: 'rgba(0,0,0,0.1)'
                    }
                },
                y: {
                    beginAtZero: true,
                    display: true,
                    title: {
                        display: true,
                        text: 'Jumlah Transaksi'
                    },
                    grid: {
                        display: true,
                        color: 'rgba(0,0,0,0.1)'
                    },
                    ticks: {
                        precision: 0,
                        callback: function(value) {
                            return Number.isInteger(value) ? value : null;
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 1,
                    cornerRadius: 6,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + ' transaksi';
                        }
                    }
                }
            }
        }
    };
    
    transaksiChart = new Chart(ctx, config);
}


// Inisialisasi
document.addEventListener('DOMContentLoaded', function() {
    // Update waktu setiap detik
    setInterval(updateTime, 1000);
    
    // Update data setiap 30 detik
    setInterval(updateDashboardData, 30000);
    
    // Inisialisasi grafik
    initTransaksiChart();
    
    // Load data awal
    updateDashboardData();
    refreshActivities();
});

