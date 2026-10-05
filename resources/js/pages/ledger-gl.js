window.exportGL = function(type){
    const form = document.getElementById('formGL');
    const params = new URLSearchParams(new FormData(form));
    const url = type==='pdf' ? (window.ledgerRoutes?.exportPdf || '/ledger/export-pdf') : (window.ledgerRoutes?.exportExcel || '/ledger/export-excel');
    params.append('menu', 'General Ledger Detail');
    window.location = url + '?' + params.toString();
}

