@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0 text-dark">Historial de Turnos de Caja</h2>
            <p class="text-muted mb-0">Auditoría de todos los turnos abiertos y cerrados por los usuarios.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">ID</th>
                            <th class="px-4 py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Usuario</th>
                            <th class="px-4 py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Apertura</th>
                            <th class="px-4 py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Cierre</th>
                            <th class="px-4 py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Monto Esperado</th>
                            <th class="px-4 py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Monto Real</th>
                            <th class="px-4 py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Diferencia</th>
                            <th class="px-4 py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($registers as $reg)
                            <tr>
                                <td class="px-4 py-3"><span class="text-sm fw-bold">#{{ $reg->id }}</span></td>
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; font-weight: bold; font-size: 14px;">
                                            {{ substr($reg->user->name, 0, 1) }}
                                        </div>
                                        <h6 class="mb-0 text-sm">{{ $reg->user->name }}</h6>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm d-block">{{ $reg->opening_time->format('d/m/Y') }}</span>
                                    <span class="text-xs text-muted">{{ $reg->opening_time->format('h:i A') }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($reg->closing_time)
                                        <span class="text-sm d-block">{{ $reg->closing_time->format('d/m/Y') }}</span>
                                        <span class="text-xs text-muted">{{ $reg->closing_time->format('h:i A') }}</span>
                                    @else
                                        <span class="text-sm text-muted">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm fw-bold">
                                    {{ \App\Models\Setting::where('key','currency_symbol')->value('value') ?? 'S/' }}
                                    {{ number_format($reg->expected_amount ?? $reg->opening_amount, 2) }}
                                </td>
                                <td class="px-4 py-3 text-sm fw-bold">
                                    @if($reg->status == 'closed')
                                        {{ \App\Models\Setting::where('key','currency_symbol')->value('value') ?? 'S/' }}
                                        {{ number_format($reg->closing_amount, 2) }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($reg->status == 'closed')
                                        @if($reg->difference == 0)
                                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">S/ 0.00 (Cuadrado)</span>
                                        @elseif($reg->difference > 0)
                                            <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill">+ S/ {{ number_format($reg->difference, 2) }} (Sobrante)</span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill">S/ {{ number_format($reg->difference, 2) }} (Faltante)</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($reg->status == 'open')
                                        <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill"><i class="bi bi-door-open me-1"></i> Abierta</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill"><i class="bi bi-door-closed me-1"></i> Cerrada</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                        No hay registros de caja disponibles.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($registers->hasPages())
                <div class="p-4 border-top">
                    {{ $registers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
