<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\DianResolution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        $timezones = [
            'America/Bogota'                 => '(UTC-05:00) Bogotá',
            'America/Lima'                   => '(UTC-05:00) Lima',
            'America/Caracas'                => '(UTC-04:00) Caracas',
            'America/La_Paz'                 => '(UTC-04:00) La Paz',
            'America/Santiago'               => '(UTC-03:00) Santiago',
            'America/Argentina/Buenos_Aires' => '(UTC-03:00) Buenos Aires',
            'America/Montevideo'             => '(UTC-03:00) Montevideo',
            'America/Mexico_City'            => '(UTC-06:00) Ciudad de México',
            'Europe/Madrid'                  => '(UTC+01:00) Madrid',
            'UTC'                            => '(UTC+00:00) Tiempo Universal Coordinado',
        ];

        return view('settings.index', compact('settings', 'timezones'));
    }

    public function update(Request $request)
    {
        $data = $request->except(['_token', 'company_logo', 'dian_cert_file']);

        // 1. Guardar todas las claves textuales (datos empresa, DIAN, IVA, etc.)
        foreach ($data as $key => $value) {
            if (is_null($value)) continue;
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // 2. Guardar Logo
        if ($request->hasFile('company_logo')) {
            $request->validate(['company_logo' => 'image|max:2048']);
            $oldLogo = Setting::where('key', 'company_logo')->value('value');
            if ($oldLogo) Storage::disk('public')->delete($oldLogo);
            $path = $request->file('company_logo')->store('settings', 'public');
            Setting::updateOrCreate(['key' => 'company_logo'], ['value' => $path]);
        }

        // 3. Guardar certificado DIAN (.p12 / .pfx)
        if ($request->hasFile('dian_cert_file')) {
            $request->validate(['dian_cert_file' => 'file|max:1024']);

            $ext = strtolower($request->file('dian_cert_file')->getClientOriginalExtension());
            if (!in_array($ext, ['p12', 'pfx'])) {
                return redirect()->back()->with('error', 'El certificado DIAN debe ser un archivo .p12 o .pfx');
            }

            $oldCert = Setting::where('key', 'dian_cert_path')->value('value');
            if ($oldCert && Storage::disk('local')->exists($oldCert)) {
                Storage::disk('local')->delete($oldCert);
            }

            $filename = 'dian_cert_' . date('Ymd_His') . '.' . $ext;
            $request->file('dian_cert_file')->storeAs('dian/certs', $filename, 'local');

            Setting::updateOrCreate(
                ['key' => 'dian_cert_path'],
                ['value' => 'dian/certs/' . $filename]
            );
        }

        // 4. Sincronizar la fila DIAN Resolution con lo que el usuario ingresó
        $this->syncDianResolution($request);

        return redirect()->back()->with('success', 'Configuración actualizada correctamente.');
    }

    /**
     * Crea/actualiza la resolución activa DIAN para el ambiente seleccionado.
     */
    private function syncDianResolution(Request $request): void
    {
        $env       = (int) ($request->input('dian_environment') ?? 2);
        $numero    = trim((string) $request->input('dian_resolucion_numero'));
        $prefijo   = trim((string) ($request->input('dian_resolucion_prefijo') ?: 'SETP'));
        $desde     = (int) ($request->input('dian_resolucion_rango_desde') ?: 0);
        $hasta     = (int) ($request->input('dian_resolucion_rango_hasta') ?: 0);
        $fechaIni  = $request->input('dian_resolucion_fecha_desde');
        $fechaFin  = $request->input('dian_resolucion_fecha_hasta');
        $claveTec  = $request->input('dian_clave_tecnica');

        if ($numero === '' || $desde <= 0 || $hasta <= 0 || $hasta < $desde) {
            return; // datos incompletos: no creamos resolución
        }

        DB::transaction(function () use ($env, $numero, $prefijo, $desde, $hasta, $fechaIni, $fechaFin, $claveTec) {
            // Desactivar previas del mismo ambiente
            DianResolution::where('environment', $env)->update(['is_active' => false]);

            DianResolution::updateOrCreate(
                [
                    'environment'        => $env,
                    'numero_resolucion'  => $numero,
                ],
                [
                    'prefijo'        => $prefijo,
                    'rango_desde'    => $desde,
                    'rango_hasta'    => $hasta,
                    'vigencia_desde' => $fechaIni ?: null,
                    'vigencia_hasta' => $fechaFin ?: null,
                    'clave_tecnica'  => $claveTec,
                    'is_active'      => true,
                ]
            );
        });
    }
}
