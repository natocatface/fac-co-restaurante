<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Inserta las claves DIAN por defecto en la tabla `settings`.
 * Todas con valor vacío para que el usuario las complete en
 * Settings > DIAN Colombia. Usa ambiente Habilitación por defecto.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings')) return;

        $defaults = [
            // Empresa (Emisor)
            'dian_company_nit'              => '',
            'dian_company_dv'               => '',
            'dian_company_razon_social'     => '',
            'dian_company_nombre_comercial' => '',
            'dian_company_tipo_documento'   => '31',  // 31 = NIT
            'dian_company_tipo_persona'     => '1',   // 1 = Jurídica, 2 = Natural
            'dian_company_regimen'          => '49',  // 49 = No responsable de IVA, 48 = Responsable
            'dian_company_responsabilidad'  => 'R-99-PN', // Catálogo Responsabilidades Fiscales DIAN
            'dian_company_address'          => '',
            'dian_company_city_code'        => '11001', // Bogotá DC
            'dian_company_dept_code'        => '11',
            'dian_company_country_code'     => 'CO',
            'dian_company_phone'            => '',
            'dian_company_email'            => '',
            'dian_company_actividad_economica' => '5611', // 5611 Expendio a la mesa de comidas
            'dian_company_municipio_nombre' => 'BOGOTA',

            // Ambiente
            'dian_environment'              => '2',   // 1=Producción, 2=Habilitación
            'dian_test_set_id'              => '',    // Set de pruebas asignado por DIAN
            'dian_software_id'              => '',    // ID del software registrado en DIAN
            'dian_software_pin'             => '',    // PIN del software

            // Certificado digital
            'dian_cert_path'                => '',    // ruta relativa en storage/app
            'dian_cert_password'            => '',

            // IVA / impuestos
            'iva_rate'                      => '19',  // % IVA general en Colombia
            'ico_rate'                      => '0',   // % Impuesto al Consumo (8 si aplica)
        ];

        foreach ($defaults as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('settings')) return;
        DB::table('settings')->where('key', 'like', 'dian_%')->delete();
        DB::table('settings')->whereIn('key', ['iva_rate', 'ico_rate'])->delete();
    }
};
