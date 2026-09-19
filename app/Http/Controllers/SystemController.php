<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\InventoryLog;

class SystemController extends Controller
{
    public function index()
    {
        // Contamos qué vamos a borrar para informar al usuario
        $counts = [
            'orders' => Order::count(),
            'reservations' => Reservation::count(),
            'logs' => InventoryLog::count(),
        ];
        return view('system.index', compact('counts'));
    }

    public function resetData(Request $request)
    {
        $request->validate(['password' => 'required']);

        // Verificación de seguridad simple: La contraseña debe ser la del usuario actual
        if (!password_verify($request->password, auth()->user()->password)) {
            return back()->with('error', 'Contraseña incorrecta. No se realizaron cambios.');
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // 1. Borrar Ventas y Detalles
            DB::table('order_details')->truncate();
            DB::table('orders')->truncate();

            // 2. Borrar Reservas y Finanzas
            DB::table('reservations')->truncate();
            DB::table('expenses')->truncate();
            DB::table('cash_registers')->truncate();

            // 3. Borrar Kardex
            DB::table('inventory_logs')->truncate();
            DB::table('products')->update(['stock' => 0]); 

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            return back()->with('success', '¡Sistema reiniciado! Ventas, Reservas y Stock han vuelto a cero.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error crítico: ' . $e->getMessage());
        }
    }

    public function backup()
    {
        try {
            $dbName = env('DB_DATABASE');
            $dbUser = env('DB_USERNAME');
            $dbPass = env('DB_PASSWORD');
            $dbHost = env('DB_HOST', '127.0.0.1');

            $fileName = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filePath = storage_path('app/' . $fileName);

            $passwordParam = empty($dbPass) ? '' : '--password="' . $dbPass . '"';
            $command = "mysqldump --user=\"{$dbUser}\" {$passwordParam} --host=\"{$dbHost}\" {$dbName} > \"{$filePath}\"";

            $output = [];
            $returnVar = null;
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                return back()->with('error', 'No se pudo generar el backup. Verifica que mysqldump esté en el PATH.');
            }

            return response()->download($filePath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar la copia de seguridad: ' . $e->getMessage());
        }
    }

    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file',
            'password' => 'required'
        ]);

        if (!password_verify($request->password, auth()->user()->password)) {
            return back()->with('error', 'Contraseña incorrecta. No se restauró el sistema.');
        }

        try {
            $file = $request->file('backup_file');
            
            // Verificación simple de que es un archivo SQL
            if ($file->getClientOriginalExtension() !== 'sql') {
                return back()->with('error', 'El archivo debe ser un .sql válido.');
            }

            $dbName = env('DB_DATABASE');
            $dbUser = env('DB_USERNAME');
            $dbPass = env('DB_PASSWORD');
            $dbHost = env('DB_HOST', '127.0.0.1');

            $filePath = $file->getRealPath();
            
            $passwordParam = empty($dbPass) ? '' : '--password="' . $dbPass . '"';
            // Usa < para inyectar el archivo SQL en la BD
            $command = "mysql --user=\"{$dbUser}\" {$passwordParam} --host=\"{$dbHost}\" {$dbName} < \"{$filePath}\"";

            $output = [];
            $returnVar = null;
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                return back()->with('error', 'Ocurrió un error al ejecutar la restauración en la base de datos.');
            }

            return back()->with('success', '¡El sistema ha sido restaurado exitosamente desde la copia de seguridad!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al restaurar el sistema: ' . $e->getMessage());
        }
    }
}