import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // CSS Pages
                'resources/css/pages/activity-user.css',
                'resources/css/pages/budget-actual.css',
                'resources/css/pages/budget.css',
                'resources/css/pages/dashboard.css',
                'resources/css/pages/ledger-activity.css',
                'resources/css/pages/ledger-budget.css',
                'resources/css/pages/ledger-cash.css',
                'resources/css/pages/ledger-fixed.css',
                'resources/css/pages/ledger-gl.css',
                'resources/css/pages/ledger-import.css',
                'resources/css/pages/ledger-posisi.css',
                'resources/css/pages/ledger-trial.css',
                'resources/css/pages/ledger.css',
                'resources/css/pages/management.css',
                'resources/css/pages/profile.css',
                'resources/css/pages/settings.css',
                'resources/css/pages/statement-of-activity.css',
                'resources/css/pages/transaksi-bank.css',
                'resources/css/pages/transaksi-jurnal.css',
                'resources/css/pages/transaksi.css',
                // JS Pages
                'resources/js/pages/activity-user.js',
                'resources/js/pages/budget-actual.js',
                'resources/js/pages/budget.js',
                'resources/js/pages/dashboard.js',
                'resources/js/pages/ledger-activity.js',
                'resources/js/pages/ledger-budget.js',
                'resources/js/pages/ledger-cash.js',
                'resources/js/pages/ledger-fixed.js',
                'resources/js/pages/ledger-gl.js',
                'resources/js/pages/ledger-import.js',
                'resources/js/pages/ledger-posisi.js',
                'resources/js/pages/ledger-trial.js',
                'resources/js/pages/ledger.js',
                'resources/js/pages/management.js',
                'resources/js/pages/profile.js',
                'resources/js/pages/settings.js',
                'resources/js/pages/statement-of-activity.js',
                'resources/js/pages/transaksi-bank.js',
                'resources/js/pages/transaksi-jurnal.js',
                'resources/js/pages/transaksi.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
