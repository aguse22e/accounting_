function exportLedger(type){
    const form = document.getElementById('formTrial');
    const params = new URLSearchParams(new FormData(form));
    const url = type==='pdf' ? (window.ledgerRoutes?.exportPdf || '/ledger/export-pdf') : (window.ledgerRoutes?.exportExcel || '/ledger/export-excel');
    params.append('menu', 'Neraca Saldo');
    window.location = url + '?' + params.toString();
}

