<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireOpenCashRegister
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si el usuario es admin o cajero, exigimos caja abierta para ciertas rutas
        if ($user && in_array($user->role, ['admin', 'cashier'])) {
            if (!$user->activeCashRegister) {
                return redirect()->route('cash_registers.create')
                    ->with('warning', 'Debes abrir tu turno de caja antes de realizar operaciones de cobro o venta.');
            }
        }

        return $next($request);
    }
}
