<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Resolución de numeración DIAN.
 *
 * Centraliza la asignación de consecutivos para evitar duplicados aún
 * bajo concurrencia (uso de SELECT ... FOR UPDATE dentro de transacción).
 */
class DianResolution extends Model
{
    protected $table = 'dian_resolutions';

    protected $fillable = [
        'environment',
        'numero_resolucion',
        'fecha_resolucion',
        'prefijo',
        'rango_desde',
        'rango_hasta',
        'vigencia_desde',
        'vigencia_hasta',
        'clave_tecnica',
        'consecutivo_actual',
        'is_active',
    ];

    protected $casts = [
        'environment'        => 'integer',
        'rango_desde'        => 'integer',
        'rango_hasta'        => 'integer',
        'consecutivo_actual' => 'integer',
        'fecha_resolucion'   => 'date',
        'vigencia_desde'     => 'date',
        'vigencia_hasta'     => 'date',
        'is_active'          => 'boolean',
    ];

    /**
     * Obtiene la resolución activa según el ambiente configurado
     * (settings.dian_environment).
     */
    public static function active(): ?self
    {
        $env = (int) (Setting::where('key', 'dian_environment')->value('value') ?? 2);
        return self::where('is_active', true)
            ->where('environment', $env)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Reserva atómicamente el siguiente número consecutivo dentro del
     * rango autorizado por la DIAN. Lanza excepción si:
     *  - no hay resolución activa
     *  - se agotó el rango
     *  - la resolución venció
     *
     * @return array{prefijo: string, numero: int, resolution_id: int}
     */
    public static function nextNumber(): array
    {
        return DB::transaction(function () {
            $env = (int) (Setting::where('key', 'dian_environment')->value('value') ?? 2);

            /** @var self|null $res */
            $res = self::where('is_active', true)
                ->where('environment', $env)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if (!$res) {
                throw new \RuntimeException(
                    'No hay una resolución DIAN activa para el ambiente ' . ($env === 1 ? 'Producción' : 'Habilitación')
                    . '. Configúrala en Settings > DIAN.'
                );
            }

            $today = now()->toDateString();
            if ($res->vigencia_hasta && $res->vigencia_hasta->toDateString() < $today) {
                throw new \RuntimeException("La resolución DIAN {$res->numero_resolucion} venció el {$res->vigencia_hasta->toDateString()}.");
            }

            $current = max((int) $res->consecutivo_actual, (int) $res->rango_desde - 1);
            $next    = $current + 1;
            if ($next > (int) $res->rango_hasta) {
                throw new \RuntimeException("Rango de numeración DIAN agotado en la resolución {$res->numero_resolucion}.");
            }

            $res->consecutivo_actual = $next;
            $res->save();

            return [
                'prefijo'       => $res->prefijo,
                'numero'        => $next,
                'resolution_id' => $res->id,
            ];
        });
    }
}
