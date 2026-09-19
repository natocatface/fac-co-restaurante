<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ticket {{ $order->full_number }}</title>
<style>
    @page { size: 80mm auto; margin: 4mm; }
    body { font-family: 'Courier New', monospace; font-size: 11px; width: 72mm; }
    .c { text-align:center; } .r { text-align:right; }
    hr { border:0; border-top:1px dashed #000; margin:4px 0; }
    table { width:100%; border-collapse:collapse; }
    td { padding:1px 0; }
</style>
</head>
<body>
<div class="c">
    <strong>{{ $emisor['razon_social'] }}</strong><br>
    NIT {{ $emisor['nit'] }}-{{ $emisor['dv'] }}<br>
    {{ $emisor['address'] }}<br>
    {{ $emisor['municipio_nombre'] }}<br>
    Tel: {{ $emisor['phone'] }}
</div>
<hr>
<div class="c"><strong>FACTURA ELECTRÓNICA DE VENTA</strong><br>
    {{ $order->full_number }}<br>
    {{ $order->created_at->format('Y-m-d H:i') }}
</div>
<hr>
Cliente: {{ $order->client_name }}<br>
Doc.: {{ $order->client_document }}
<hr>
<table>
@foreach($order->details as $d)
    <tr><td colspan="2">{{ $d->product->name ?? '—' }}</td></tr>
    <tr><td>{{ $d->quantity }} x {{ number_format($d->price, 2) }}</td>
        <td class="r">{{ number_format($d->price*$d->quantity, 2) }}</td></tr>
@endforeach
</table>
<hr>
<table>
    <tr><td>Subtotal</td><td class="r">{{ number_format($order->subtotal, 2) }}</td></tr>
    <tr><td>IVA</td><td class="r">{{ number_format($order->iva, 2) }}</td></tr>
    <tr><td><strong>TOTAL</strong></td><td class="r"><strong>{{ number_format($order->total, 2) }}</strong></td></tr>
</table>
<hr>
<div class="c">
    @if($qr)<img src="{{ $qr }}" style="width:140px;height:140px;">@endif
</div>
<div style="font-size:8px;word-break:break-all;text-align:center;">
    CUFE: {{ $order->cufe }}
</div>
<hr>
<div class="c">¡Gracias por su compra!</div>
<script>window.print && window.print();</script>
</body>
</html>
