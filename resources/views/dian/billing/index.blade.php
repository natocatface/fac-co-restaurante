@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-receipt-cutoff"></i> Facturación Electrónica DIAN</h1>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="filters" style="margin-bottom:12px;">
    <a href="{{ route('billing.index') }}" class="btn btn-sm {{ !$status ? 'btn-primary' : 'btn-light' }}">Todas</a>
    @foreach(['PENDING','SIGNED','SENT','ACCEPTED','REJECTED','ERROR'] as $s)
        <a href="{{ route('billing.index', ['status'=>$s]) }}"
           class="btn btn-sm {{ $status===$s ? 'btn-primary' : 'btn-light' }}">{{ $s }}</a>
    @endforeach
</div>

<table class="table table-striped">
    <thead>
        <tr>
            <th>Factura</th>
            <th>Cliente</th>
            <th>Documento</th>
            <th class="text-end">Total</th>
            <th>Estado DIAN</th>
            <th>Fecha</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    @forelse($orders as $o)
        <tr>
            <td><strong>{{ $o->full_number ?? '—' }}</strong></td>
            <td>{{ $o->client_name }}</td>
            <td>{{ $o->client_document }}</td>
            <td class="text-end">{{ $currency }} {{ number_format($o->total, 2) }}</td>
            <td>
                <span class="badge
                    @if($o->dian_status==='ACCEPTED') bg-success
                    @elseif($o->dian_status==='REJECTED' || $o->dian_status==='ERROR') bg-danger
                    @elseif($o->dian_status==='PENDING') bg-secondary
                    @else bg-warning text-dark @endif">{{ $o->dian_status }}</span>
            </td>
            <td>{{ $o->created_at->format('Y-m-d H:i') }}</td>
            <td>
                <a href="{{ route('billing.show', $o) }}" class="btn btn-sm btn-info">Ver</a>
                <a href="{{ route('billing.pdf', $o) }}" class="btn btn-sm btn-secondary" target="_blank">PDF</a>
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted">Sin facturas</td></tr>
    @endforelse
    </tbody>
</table>

{{ $orders->withQueryString()->links() }}
@endsection
