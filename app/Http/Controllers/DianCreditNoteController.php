<?php

namespace App\Http\Controllers;

use App\Models\DianCreditNote;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Notas Crédito Electrónicas DIAN.
 *
 * En esta primera versión la creación queda en estado PENDING (el flujo de
 * firma/envío se construirá análogamente a DianService cuando se habilite la
 * fase 2). Esto permite registrar la nota internamente y emitirla luego.
 */
class DianCreditNoteController extends Controller
{
    public function index()
    {
        $notes = DianCreditNote::with('order')
            ->orderByDesc('created_at')
            ->paginate(25);
        return view('dian.credit_notes.index', compact('notes'));
    }

    public function create(Order $order)
    {
        abort_unless($order->isElectronic(), 404);
        return view('dian.credit_notes.create', compact('order'));
    }

    public function store(Request $request, Order $order)
    {
        $validated = $request->validate([
            'reason_code'        => 'required|integer|between:1,5',
            'reason_description' => 'required|string|max:255',
        ]);

        $note = DB::transaction(function () use ($order, $validated) {
            $last = DianCreditNote::where('prefijo', 'NC')->max('numero') ?? 0;

            return DianCreditNote::create([
                'order_id'           => $order->id,
                'prefijo'            => 'NC',
                'numero'             => $last + 1,
                'reason_code'        => $validated['reason_code'],
                'reason_description' => $validated['reason_description'],
                'subtotal'           => $order->subtotal,
                'iva'                => $order->iva,
                'total'              => $order->total,
                'dian_status'        => 'PENDING',
                'user_id'            => auth()->id(),
            ]);
        });

        return redirect()->route('credit_notes.show', $note)
            ->with('success', 'Nota crédito ' . $note->full_number . ' creada (pendiente de envío a DIAN).');
    }

    public function show(DianCreditNote $creditNote)
    {
        $creditNote->load('order.details.product', 'events');
        return view('dian.credit_notes.show', compact('creditNote'));
    }

    public function retry(DianCreditNote $creditNote)
    {
        // Placeholder: la fase 2 implementará CreditNoteXmlBuilder + envío.
        return redirect()->back()->with('error', 'El reenvío de notas crédito se habilitará en una próxima fase.');
    }

    public function downloadXml(DianCreditNote $creditNote)
    {
        abort_unless($creditNote->xml_path && Storage::disk('local')->exists($creditNote->xml_path), 404);
        return Storage::disk('local')->download($creditNote->xml_path);
    }

    public function downloadApplicationResponse(DianCreditNote $creditNote)
    {
        abort_unless($creditNote->ar_path && Storage::disk('local')->exists($creditNote->ar_path), 404);
        return Storage::disk('local')->download($creditNote->ar_path);
    }

    public function pdf(DianCreditNote $creditNote)
    {
        $creditNote->load('order.details.product');
        return view('dian.credit_notes.pdf', compact('creditNote'));
    }
}
