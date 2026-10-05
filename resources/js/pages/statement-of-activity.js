// Statement of Activity specific JavaScript
function exportPdf() {
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = window.exportPdfRoute || '';
    
    const fromDate = document.querySelector('input[name="from_date"]')?.value;
    const toDate = document.querySelector('input[name="to_date"]')?.value;
    
    if (fromDate && toDate) {
        form.innerHTML = `
            <input type="hidden" name="from_date" value="${fromDate}">
            <input type="hidden" name="to_date" value="${toDate}">
        `;
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
}

// Make function globally available
window.exportPdf = exportPdf;

document.addEventListener('DOMContentLoaded', function() {
    console.log('Statement of Activity page loaded');
});
