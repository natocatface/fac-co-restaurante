<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Nota Crédito {{ $creditNote->full_number }}</title>
<style>body{font-family:Arial;font-size:12px} table{width:100%;border-collapse:collapse}th,td{border:1px solid #ccc;padding:4px}</style></head>
<body>
<h1>Nota Crédito {{ $creditNote->full_number }}</h1>
<p><strong>Factura asociada:</strong> {{ $creditNote->order->full_number }}</p>
<p><strong>Motivo:</strong> {{ $creditNote->reason_code }} — {{ $creditNote->reason_description }}</p>
<p><strong>Subtotal:</strong> $ {{ number_format($creditNote->subtotal, 2) }}</p>
<p><strong>IVA:</strong> $ {{ number_format($creditNote->iva, 2) }}</p>
<p><strong>Total:</strong> $ {{ number_format($creditNote->total, 2) }}</p>
<p><strong>Estado DIAN:</strong> {{ $creditNote->dian_status }}</p>
<p style="font-size:10px">CUDE: {{ $creditNote->cude }}</p>
<script>window.print && window.print();</script>
</body>
</html>
