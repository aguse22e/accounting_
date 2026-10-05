function exportActivity(type){
    const form = document.getElementById('formActivity');
    const params = new URLSearchParams(new FormData(form));
    const url = type==='pdf' ? (window.ledgerRoutes?.exportPdf || '/ledger/export-pdf') : (window.ledgerRoutes?.exportExcel || '/ledger/export-excel');
    params.append('menu', 'Aktivitas');
    window.location = url + '?' + params.toString();
}

