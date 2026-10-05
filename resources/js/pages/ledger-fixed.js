// Ledger Fixed Assets specific JavaScript
window.exportFixed = function(type) {
    const form = document.getElementById('formFixed');
    if (!form) {
        console.error('Form dengan id formFixed tidak ditemukan');
        alert('Form tidak ditemukan. Silakan refresh halaman.');
        return;
    }
    
    const fromInput = document.getElementById('from');
    const toInput = document.getElementById('to');
    
    if (!fromInput || !toInput) {
        console.error('Input from atau to tidak ditemukan');
        alert('Input tanggal tidak ditemukan. Silakan refresh halaman.');
        return;
    }
    
    const from = fromInput.value || '';
    const to = toInput.value || '';
    
    if (!from || !to) {
        alert('Silakan pilih periode tanggal terlebih dahulu.');
        return;
    }
    
    const params = new URLSearchParams();
    params.append('from', from);
    params.append('to', to);
    params.append('menu', 'Aset Tetap');
    
    const url = type === 'pdf' 
        ? (window.ledgerRoutes?.exportPdf || '/ledger/export-pdf') 
        : (window.ledgerRoutes?.exportExcel || '/ledger/export-excel');
    
    const fullUrl = url + '?' + params.toString();
    console.log('Export URL:', fullUrl);
    
    // Buka URL export di tab baru untuk memicu download
    window.open(fullUrl, '_blank');
};

