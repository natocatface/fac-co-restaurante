<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use App\Services\Dian\DianService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Listado y operaciones sobre Facturas Electrónicas DIAN.
 */
class DianBillingController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status'); // PENDING, ACCEPTED, REJECTED, ERROR

        $orders = Order::query()
            ->where('document_type', 'Factura')
            ->when($status, fn($q) => $q->where('dian_status', $status))
            ->orderByDesc('created_at')
            ->paginate(25);

        $currency = Setting::where('key', 'currency_symbol')->value('value') ?? '$';

        return view('dian.billing.index', compact('orders', 'status', 'currency'));
    }

    public function show(Order $order)
    {
        abort_unless($order->isElectronic(), 404);
        $order->load(['details.product', 'client', 'events']);
        $currency = Setting::where('key', 'currency_symbol')->value('value') ?? '$';
        return view('dian.billing.show', compact('order', 'currency'));
    }

    public function send(Order $order)
    {
        abort_unless($order->isElectronic(), 404);
        $order = (new DianService())->sendInvoice($order->load('details.product'));
        return redirect()->route('billing.show', $order)
            ->with($order->dian_status === 'ACCEPTED' ? 'success' : 'error',
                $order->dian_description ?? $order->dian_status);
    }

    public function retry(Order $order)
    {
        abort_unless($order->isElectronic(), 404);
        $order = (new DianService())->retry($order->load('details.product'));
        return redirect()->route('billing.show', $order)
            ->with($order->dian_status === 'ACCEPTED' ? 'success' : 'error',
                $order->dian_description ?? $order->dian_status);
    }

    public function downloadXml(Order $order)
    {
        abort_unless($order->xml_path && Storage::disk('local')->exists($order->xml_path), 404);
        return Storage::disk('local')->download($order->xml_path);
    }

    public function downloadApplicationResponse(Order $order)
    {
        abort_unless($order->ar_path && Storage::disk('local')->exists($order->ar_path), 404);
        return Storage::disk('local')->download($order->ar_path);
    }
}
