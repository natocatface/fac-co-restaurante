<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Factura {{ $order->full_number }}</title>
<style>
    body { font-family: Arial, sans-serif; font-size:12px; color:#222; }
    h1 { font-size:18px; margin:0; }
    table { width:100%; border-collapse: collapse; margin-top:8px; }
    th, td { padding:4px 6px; border:1px solid #ddd; }
    th { background:#f1f1f1; text-align:left; }
    .header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #333; padding-bottom:8px; }
    .right { text-align:right; }
    .totals { width:40%; margin-left:auto; }
    .qr { text-align:center; margin-top:12px; }
</style>
</head>
<body>
<div class="header">
    <div>
        <h1>{{ $emisor['razon_social'] ?? '—' }}</h1>
        <div>NIT {{ $emisor['nit'] }}-{{ $emisor['dv'] }}</div>
        <div>{{ $emisor['address'] }}</div>
        <div>{{ $emisor['municipio_nombre'] }} — {{ $emisor['country_code'] }}</div>
        <div>Tel: {{ $emisor['phone'] }} — {{ $emisor['email'] }}</div>
    </div>
    <div class="right">
        <h1>Factura Electrónica de Venta</h1>
        <div><strong>{{ $order->full_number }}</strong></div>
        <div>Fecha: {{ $order->created_at->format('Y-m-d H:i') }}</div>
        <div>Ambiente: {{ ($settings['dian_environment'] ?? '2')==='1' ? 'Producción' : 'Habilitación' }}</div>
    </div>
</div>

<h3>Adquiriente</h3>
<table>
    <tr><th>Nombre / Razón social</th><td>{{ $order->client_name }}</td>
        <th>Documento</th><td>{{ $order->client_document }}</td></tr>
    <tr><th>Email</th><td>{{ $order->client_email ?? '—' }}</td>
        <th>Tel.</th><td>{{ $order->client_phone ?? '—' }}</td></tr>
</table>

<h3>Detalle</h3>
<table>
    <thead>
        <tr><th>#</th><th>Descripción</th><th>Cant.</th><th class="right">P. Unit</th><th class="right">Total</th></tr>
    </thead>
    <tbody>
    @foreach($order->details as $i=>$d)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $d->product->name ?? '—' }}</td>
            <td>{{ $d->quantity }}</td>
            <td class="right">{{ number_format($d->price, 2) }}</td>
            <td class="right">{{ number_format($d->price*$d->quantity, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totals">
    <tr><th>Subtotal</th><td class="right">$ {{ number_format($order->subtotal, 2) }}</td></tr>
    <tr><th>IVA</th><td class="right">$ {{ number_format($order->iva, 2) }}</td></tr>
    <tr><th>Total</th><td class="right"><strong>$ {{ number_format($order->total, 2) }}</strong></td></tr>
</table>

<div class="qr">
    @if($qr)<img src="{{ $qr }}" alt="QR DIAN" style="width:200px;height:200px;">@endif
    <div style="font-size:10px;word-break:break-all;margin-top:6px;">CUFE: {{ $order->cufe }}</div>
</div>

<p style="text-align:center;font-size:10px;margin-top:18px;">Factura Electrónica de Venta generada bajo Resolución DIAN — Estado: {{ $order->dian_status }}</p>

<script>window.print && window.print();</script>
</body>
</html>
