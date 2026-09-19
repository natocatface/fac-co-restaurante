@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-arrow-counterclockwise"></i> Notas Crédito DIAN</h1>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<table class="table table-striped">
    <thead><tr><th>Nota</th><th>Factura</th><th>Cliente</th><th class="text-end">Total</th><th>Estado</th><th>Fecha</th><th></th></tr></thead>
    <tbody>
    @forelse($notes as $n)
        <tr>
            <td><strong>{{ $n->full_number }}</strong></td>
            <td>{{ $n->order->full_number ?? '—' }}</td>
            <td>{{ $n->order->client_name ?? '—' }}</td>
            <td class="text-end">$ {{ number_format($n->total, 2) }}</td>
            <td><span class="badge bg-secondary">{{ $n->dian_status }}</span></td>
            <td>{{ $n->created_at->format('Y-m-d H:i') }}</td>
            <td><a href="{{ route('credit_notes.show', $n) }}" class="btn btn-sm btn-info">Ver</a></td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted">Sin notas crédito</td></tr>
    @endforelse
    </tbody>
</table>
{{ $notes->links() }}
@endsection
