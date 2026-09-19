@extends('layouts.app')

@section('content')
<h1>Nota Crédito {{ $creditNote->full_number }}</h1>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="card">
    <div class="card-body">
        <p><strong>Factura afectada:</strong>
            <a href="{{ route('billing.show', $creditNote->order) }}">{{ $creditNote->order->full_number ?? '—' }}</a>
        </p>
        <p><strong>Motivo:</strong> {{ $creditNote->reason_code }} — {{ $creditNote->reason_description }}</p>
        <p><strong>Subtotal:</strong> $ {{ number_format($creditNote->subtotal, 2) }}</p>
        <p><strong>IVA:</strong> $ {{ number_format($creditNote->iva, 2) }}</p>
        <p><strong>Total:</strong> $ {{ number_format($creditNote->total, 2) }}</p>
        <p><strong>Estado DIAN:</strong> {{ $creditNote->dian_status }}</p>
        <p><strong>CUDE:</strong> <code style="word-break:break-all">{{ $creditNote->cude ?? '—' }}</code></p>
        @if($creditNote->dian_description)<p><strong>Descripción:</strong> {{ $creditNote->dian_description }}</p>@endif
    </div>
</div>

<div class="alert alert-info mt-3">
    El envío automático de Notas Crédito a la DIAN se habilita en la siguiente fase del proyecto.
    La nota queda registrada y disponible para emisión cuando el módulo esté activo.
</div>
@endsection
