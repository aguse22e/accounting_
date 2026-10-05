<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManagerAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    /**
     * Handle an incoming request.
     * Membatasi akses manager hanya ke halaman ledger dan cetak
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // Jika user adalah manager, batasi akses hanya ke ledger
        if ($user && $user->role === 'manager') {
            $allowedRoutes = [
                'ledger.index',
                'ledger.export-pdf',
                'ledger.export-excel',
                'ledger.posisi',
                'ledger.posisi.export-pdf',
                'ledger.posisi.export-excel',
                'ledger.trial',
                'ledger.gl',
                'ledger.activity',
                'ledger.cash',
                'ledger.fixed',
                'ledger.budget',
                'ledger.statement-of-activity',
                'ledger.statement-of-activity.export-pdf',
                'ledger.statement-of-activity.export-excel',
                'logout',
                'settings.update',
            ];
            
            $currentRoute = $request->route() ? $request->route()->getName() : null;
            
            // Jika route tidak diizinkan, redirect ke ledger
            if ($currentRoute !== null && !in_array($currentRoute, $allowedRoutes)) {
                return redirect()->route('ledger.index')->with('error', 'Akses ditolak. Anda hanya dapat mengakses halaman Ledger.');
            }
        }
        
        return $next($request);
    }
}
