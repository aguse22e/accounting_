// Ledger Import specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // File input validation
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const maxSize = 10 * 1024 * 1024; // 10MB
                const allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'];
                
                if (file.size > maxSize) {
                    alert('File terlalu besar. Maksimal 10MB.');
                    e.target.value = '';
                    return;
                }
                
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak didukung. Gunakan file Excel (.xlsx) atau CSV (.csv).');
                    e.target.value = '';
                    return;
                }
            }
        });
    }
    
    console.log('Ledger Import page loaded');
});

