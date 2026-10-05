// Budget specific JavaScript
window.exportBudget = function(type) {
    const from = document.getElementById('from').value;
    const to = document.getElementById('to').value;
    if (type === 'pdf') {
        window.open(`{{ route('ledger.export-pdf') }}?from=${from}&to=${to}`, '_blank');
    } else if (type === 'excel') {
        window.open(`{{ route('ledger.export-excel') }}?from=${from}&to=${to}`, '_blank');
    }
};
