@extends('layouts.app')

@section('content')
<h1>Crear Nota Crédito — Factura {{ $order->full_number }}</h1>

<form method="POST" action="{{ route('credit_notes.store', $order) }}">
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Motivo (Catálogo DIAN)</label>
                <select name="reason_code" class="form-select" required>
                    <option value="1">1 — Devolución parcial</option>
                    <option value="2">2 — Anulación de factura</option>
                    <option value="3">3 — Rebaja / descuento</option>
                    <option value="4">4 — Ajuste de precio</option>
                    <option value="5">5 — Otros</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <input type="text" name="reason_description" class="form-control" required maxlength="255">
            </div>
        </div>
        <div class="card-footer text-end">
            <a href="{{ route('billing.show', $order) }}" class="btn btn-secondary">Cancelar</a>
            <button class="btn btn-warning">Crear Nota Crédito</button>
        </div>
    </div>
</form>
@endsection
