<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // Datos básicos
            'company_name' => 'Mi Restaurante VIP',
            'company_address' => 'Av. Gastronómica 123, Lima',
            'company_phone' => '(01) 555-9999',
            'ticket_footer' => '¡Gracias por su preferencia! Vuelva pronto.',
            'currency_symbol' => 'S/',

            // ── SUNAT (valores por defecto para BETA) ───────────────────
            'sunat_ruc' => '20000000001',
            'sunat_razon_social' => 'EMPRESA DEMO SAC',
            'sunat_nombre_comercial' => 'DEMO',
            'sunat_direccion_fiscal' => 'AV. PRINCIPAL 123',
            'sunat_ubigeo' => '150101',          // Lima/Lima/Lima
            'sunat_departamento' => 'LIMA',
            'sunat_provincia' => 'LIMA',
            'sunat_distrito' => 'LIMA',
            'sunat_urbanizacion' => '-',
            'sunat_codigo_pais' => 'PE',
            'sunat_igv_rate' => '18',            // %
            'sunat_environment' => 'beta',       // beta | produccion
            'sunat_sol_user' => 'MODDATOS',      // Usuario DEMO de SUNAT en BETA
            'sunat_sol_pass' => 'MODDATOS',
            'sunat_cert_path' => '',             // Storage relative path
            'sunat_cert_password' => '',
        ];

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}