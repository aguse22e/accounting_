function exportCash(type){
    const form = document.getElementById('formCash');
    const params = new URLSearchParams(new FormData(form));
    const url = type==='pdf' ? (window.ledgerRoutes?.exportPdf || '/ledger/export-pdf') : (window.ledgerRoutes?.exportExcel || '/ledger/export-excel');
    params.append('menu', 'Arus Kas');
    window.location = url + '?' + params.toString();
}

