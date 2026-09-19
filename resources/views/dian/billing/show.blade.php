@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-receipt"></i> Factura {{ $order->full_number ?? 'Sin numerar' }}</h1>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">Detalle</div>
            <div class="card-body">
                <p><strong>Cliente:</strong> {{ $order->client_name }}</p>
                <p><strong>Documento:</strong> {{ $order->client_document }}</p>
                <p><strong>Fecha:</strong> {{ $order->created_at->format('Y-m-d H:i') }}</p>
                <table class="table">
                    <thead><tr><th>Producto</th><th>Cant.</th><th>P. Unit</th><th>Total</th></tr></thead>
                    <tbody>
                    @foreach($order->details as $d)
                        <tr>
                            <td>{{ $d->product->name ?? '—' }}</td>
                            <td>{{ $d->quantity }}</td>
                            <td>{{ $currency }} {{ number_format($d->price, 2) }}</td>
                            <td>{{ $currency }} {{ number_format($d->price*$d->quantity, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <p class="text-end"><strong>Subtotal:</strong> {{ $currency }} {{ number_format($order->subtotal, 2) }}</p>
                <p class="text-end"><strong>IVA:</strong> {{ $currency }} {{ number_format($order->iva, 2) }}</p>
                <p class="text-end"><strong>Total:</strong> {{ $currency }} {{ number_format($order->total, 2) }}</p>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card">
            <div class="card-header">Estado DIAN</div>
            <div class="card-body">
                <p><strong>Estado:</strong> {{ $order->dian_status }}</p>
                <p><strong>CUFE:</strong><br><code style="word-break:break-all">{{ $order->cufe ?? '—' }}</code></p>
                <p><strong>Código:</strong> {{ $order->dian_response_code ?? '—' }}</p>
                <p><strong>Descripción:</strong> {{ $order->dian_description ?? '—' }}</p>
                <p><strong>Enviada:</strong> {{ $order->sent_at?->format('Y-m-d H:i') ?? '—' }}</p>
                <p><strong>Aceptada:</strong> {{ $order->accepted_at?->format('Y-m-d H:i') ?? '—' }}</p>

                <div class="mt-3">
                    @if($order->dian_status !== 'ACCEPTED')
                        <form method="POST" action="{{ route('billing.send', $order) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-success">Enviar a DIAN</button>
                        </form>
                        <form method="POST" action="{{ route('billing.retry', $order) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-warning">Reintentar</button>
                        </form>
                    @endif
                    @if($order->xml_path)
                        <a href="{{ route('billing.xml', $order) }}" class="btn btn-secondary">Descargar XML</a>
                    @endif
                    @if($order->ar_path)
                        <a href="{{ route('billing.ar', $order) }}" class="btn btn-secondary">ApplicationResponse</a>
                    @endif
                    <a href="{{ route('billing.pdf', $order) }}" target="_blank" class="btn btn-primary">PDF</a>
                </div>
            </div>
        </div>

        @if($order->events->count())
        <div class="card mt-3">
            <div class="card-header">Bitácora DIAN</div>
            <div class="card-body" style="max-height:300px;overflow:auto;">
                @foreach($order->events as $e)
                    <small>{{ $e->created_at->format('Y-m-d H:i:s') }} — <strong>{{ $e->event_type }}</strong> {{ $e->response_code }} {{ $e->description }}</small><hr style="margin:4px 0;">
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
