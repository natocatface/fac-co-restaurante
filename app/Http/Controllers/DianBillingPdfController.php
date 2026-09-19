<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use App\Services\Dian\DianConfig;
use App\Services\Dian\QrGenerator;

/**
 * Representación gráfica (PDF / HTML imprimible) de la Factura Electrónica
 * DIAN. Por simplicidad, devolvemos una vista HTML con tamaño A4 / Ticket
 * y la imprimimos desde el navegador.
 */
class DianBillingPdfController extends Controller
{
    public function show(Order $order)
    {
        abort_unless($order->isElectronic(), 404);
        $order->load('details.product', 'client');
        $settings = Setting::pluck('value', 'key')->toArray();
        $qr = $order->qr_url ? (new QrGenerator())->dataUri($order->qr_url) : null;
        $config = new DianConfig();
        $emisor = $config->emisor();
        return view('dian.billing.pdf.a4', compact('order', 'settings', 'qr', 'emisor'));
    }

    public function ticket(Order $order)
    {
        abort_unless($order->isElectronic(), 404);
        $order->load('details.product', 'client');
        $settings = Setting::pluck('value', 'key')->toArray();
        $qr = $order->qr_url ? (new QrGenerator())->dataUri($order->qr_url, 140) : null;
        $config = new DianConfig();
        $emisor = $config->emisor();
        return view('dian.billing.pdf.ticket', compact('order', 'settings', 'qr', 'emisor'));
    }
}
