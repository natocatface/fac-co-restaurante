@extends('layouts.app')

@section('content')
{{-- POS ocupa 100% del viewport independiente del layout --}}
<div id="pos-wrap" style="
    position: fixed;
    inset: 0;
    left: 220px;  /* ancho del sidebar del sistema */
    display: flex;
    flex-direction: column;
    background: #f0eef8;
    z-index: 900;
">
    {{-- TOPBAR POS --}}
    <div style="
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        padding: 10px 20px;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(45,27,94,0.06);
    ">
        <div style="display:flex;align-items:center;gap:12px;">
            <a href="{{ route('pos.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            <div>
                <div class="fw-bold text-primary" style="font-size:.95rem;line-height:1.2;">Mesa: {{ $table->name }}</div>
                <div class="text-muted" style="font-size:.75rem;">Zona: {{ $table->area->name }}</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            @if($order)
                <button type="button" class="btn btn-outline-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#moveTableModal">
                    <i class="bi bi-arrow-left-right me-1"></i> Mover Mesa
                </button>
            @endif
            <span class="badge bg-light text-dark border px-2 py-1" style="font-size:.78rem;">
                <i class="bi bi-person-fill me-1"></i> {{ auth()->user()->name }}
            </span>
        </div>
    </div>

    {{-- CUERPO: tres columnas --}}
    <div style="display:flex; flex:1; min-height:0; overflow:hidden;">

        {{-- Categorías --}}
        <div style="width:155px; flex-shrink:0; background:#f8f7ff; border-right:1px solid #e5e7eb; overflow-y:auto;">
            <div class="list-group list-group-flush">
                <button onclick="filterProducts('all')" class="list-group-item list-group-item-action active text-center py-3 category-btn" id="cat-btn-all">
                    <i class="bi bi-grid-fill d-block fs-4 mb-1"></i> Todo
                </button>
                @foreach($categories as $category)
                    <button onclick="filterProducts('cat-{{ $category->id }}')" class="list-group-item list-group-item-action text-center py-3 category-btn" id="cat-btn-{{ $category->id }}">
                        @if($category->image)
                            <img src="{{ asset('storage/'.$category->image) }}" class="rounded mb-1" width="40" height="40" style="object-fit:cover;">
                        @else
                            <i class="bi bi-tag d-block fs-4 mb-1"></i>
                        @endif
                        <span class="d-block small fw-bold lh-sm">{{ $category->name }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Productos --}}
        <div style="flex:1; min-width:0; background:#fff; overflow-y:auto; padding:12px 14px 20px;" id="products-container">
            {{-- Barra búsqueda --}}
            <div style="position:sticky;top:0;background:#fff;padding-bottom:10px;z-index:10;">
                <div class="input-group input-group-lg shadow-sm">
                    <span class="input-group-text bg-white border-end-0 text-primary"><i class="bi bi-upc-scan"></i></span>
                    <input type="text" id="barcodeInput" class="form-control border-start-0" placeholder="Escanear código..." autofocus autocomplete="off">
                </div>
            </div>

            @php
                // Paleta de colores por categoría (ciclo automático)
                $catPalette = [
                    ['bg'=>'#fff4e6','icon'=>'#ff8c00','border'=>'#ffd08a','iconClass'=>'bi-egg-fried'],
                    ['bg'=>'#f3eeff','icon'=>'#9b5de5','border'=>'#c9a8f5','iconClass'=>'bi-award-fill'],
                    ['bg'=>'#ecfeff','icon'=>'#06b6d4','border'=>'#67e8f9','iconClass'=>'bi-cup-straw'],
                    ['bg'=>'#f0fdf4','icon'=>'#22c55e','border'=>'#86efac','iconClass'=>'bi-cup-hot-fill'],
                    ['bg'=>'#fef2f2','icon'=>'#ef4444','border'=>'#fca5a5','iconClass'=>'bi-fire'],
                    ['bg'=>'#eff6ff','icon'=>'#3b82f6','border'=>'#93c5fd','iconClass'=>'bi-droplet-fill'],
                    ['bg'=>'#fdf4ff','icon'=>'#d946ef','border'=>'#f0abfc','iconClass'=>'bi-flower1'],
                    ['bg'=>'#fff7ed','icon'=>'#f97316','border'=>'#fdba74','iconClass'=>'bi-basket2-fill'],
                ];
                $catColorMap = [];
                $ci = 0;
                foreach($categories as $cat) {
                    $catColorMap[$cat->id] = $catPalette[$ci % count($catPalette)];
                    $ci++;
                }
            @endphp
            <div class="row row-cols-2 row-cols-lg-3 row-cols-xl-4 g-3 pb-5">
                @foreach($categories as $category)
                    @php $pal = $catColorMap[$category->id]; @endphp
                    @foreach($category->products as $product)
                        <div class="col product-item cat-{{ $category->id }}">
                            <div class="pos-product-card" onclick="addToOrder({{ $product->id }})" style="--pal-bg:{{ $pal['bg'] }};--pal-icon:{{ $pal['icon'] }};--pal-border:{{ $pal['border'] }};">

                                {{-- Imagen o bloque de color con ícono --}}
                                <div class="pos-product-img-wrap">
                                    @if($product->image)
                                        <img src="{{ asset('storage/'.$product->image) }}" class="pos-product-img" alt="{{ $product->name }}">
                                    @else
                                        <div class="pos-product-icon-block">
                                            <i class="bi {{ $pal['iconClass'] }} pos-product-icon"></i>
                                        </div>
                                    @endif

                                    {{-- Badge Precio --}}
                                    <div class="pos-price-badge">
                                        {{ $currency ?? 'S/' }}{{ number_format($product->price, 2) }}
                                    </div>

                                    {{-- Badge Stock --}}
                                    @if(!is_null($product->stock))
                                        <div class="pos-stock-badge {{ $product->stock <= 5 ? 'pos-stock-low' : 'pos-stock-ok' }}">
                                            <i class="bi bi-box-seam me-1"></i>{{ $product->stock }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Nombre --}}
                                <div class="pos-product-footer">
                                    <div class="pos-product-name">{{ $product->name }}</div>
                                    <div class="pos-product-cat">{{ $category->name }}</div>
                                </div>

                                {{-- Overlay al hover --}}
                                <div class="pos-add-overlay">
                                    <i class="bi bi-plus-circle-fill"></i>
                                    <span>Agregar</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        {{-- CARRITO: columna derecha con ancho fijo --}}
        <div style="
            width: 280px;
            flex-shrink: 0;
            background: #fff;
            border-left: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            height: 100%;
            box-shadow: -4px 0 20px rgba(45,27,94,0.07);
        ">
            <div style="padding: 14px 16px; background: #f8f7ff; border-bottom: 1px solid #e5e7eb; flex-shrink: 0;">
                <h6 class="fw-bold mb-0"><i class="bi bi-cart"></i> Cuenta Actual</h6>
            </div>
            <div id="cart-container" style="flex: 1; display: flex; flex-direction: column; min-height: 0; overflow-y: auto;">
                @include('pos.partials.cart', ['order' => $order])
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="noteModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-warning">
                <h6 class="modal-title fw-bold text-dark">Nota Cocina</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="noteDetailId">
                <textarea id="noteText" class="form-control" rows="3"></textarea>
            </div>
            <div class="modal-footer p-1">
                <button type="button" class="btn btn-warning w-100 btn-sm text-dark fw-bold" onclick="saveNote()">Guardar Nota</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="moveTableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-info text-white">
                <h6 class="modal-title fw-bold">Mover Mesa</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            @if($order)
                <form action="{{ route('pos.move', $order->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <label class="form-label small text-muted">Destino:</label>
                        <select name="target_table_id" class="form-select" required>
                            <option value="" selected disabled>-- Elegir Mesa --</option>
                            @foreach($freeTables as $ft)
                                <option value="{{ $ft->id }}">{{ $ft->name }} ({{ $ft->area->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer p-1">
                        <button type="submit" class="btn btn-info w-100 btn-sm text-white fw-bold">Confirmar</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="optionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-2">
                <h6 class="modal-title fw-bold">Ajustes</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Descuento Global</label>
                    <input type="number" step="0.01" id="inputDiscount" class="form-control" value="{{ $order ? $order->discount : 0 }}" onclick="this.select()">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Propina</label>
                    <input type="number" step="0.01" id="inputTip" class="form-control" value="{{ $order ? $order->tip : 0 }}" onclick="this.select()">
                </div>
            </div>
            <div class="modal-footer p-1">
                <button type="button" class="btn btn-primary w-100 btn-sm fw-bold" onclick="applyOptions()">Aplicar Cambios</button>
            </div>
        </div>
    </div>
</div>

@if($order)
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('pos.checkout', $order->id) }}" method="POST" class="modal-content border-0 shadow-lg">
            @csrf
            <div class="modal-header bg-success text-white py-2">
                <h6 class="modal-title fw-bold">Cobrar Venta</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">CLIENTE</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" list="clientsList" id="clientSearchInput" placeholder="Buscar..." oninput="searchClient(this)" autocomplete="off">
                        <button class="btn btn-light border" type="button" onclick="document.getElementById('clientSearchInput').value=''; searchClient({value:''})"><i class="bi bi-x"></i></button>
                    </div>
                    <datalist id="clientsList">
                        @foreach($clients as $client)
                            <option value="{{ $client->name }}" data-id="{{ $client->id }}" data-document="{{ $client->document_number }}"></option>
                        @endforeach
                    </datalist>
                    <input type="hidden" name="client_id" id="clientId">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-8"><input type="text" name="client_document" id="clientDoc" class="form-control bg-light" placeholder="NIT / CC" readonly></div>
                    <div class="col-4"><select name="document_type" class="form-select fw-bold"><option value="Ticket">Ticket</option><option value="Factura">Factura DIAN</option></select></div>
                </div>
                <div class="mb-3 text-center">
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="payment_method" id="payCash" value="cash" checked onclick="toggleCashInput(true)">
                        <label class="btn btn-outline-success fw-bold" for="payCash">Efectivo</label>
                        <input type="radio" class="btn-check" name="payment_method" id="payCard" value="card" onclick="toggleCashInput(false)">
                        <label class="btn btn-outline-primary fw-bold" for="payCard">Tarjeta</label>
                    </div>
                </div>
                <div id="cashInputGroup">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Recibido</label>
                        <input type="number" step="0.01" name="received_amount" id="receivedAmount" class="form-control text-center fw-bold fs-4 text-success" 
                               value="{{ number_format($order->total + ($order->tip ?? 0) - ($order->discount ?? 0), 2, '.', '') }}" 
                               oninput="calculateChange()" onclick="this.select()">
                    </div>
                    <div class="d-flex justify-content-between">
                        <small>Cambio:</small>
                        <h4 class="fw-bold mb-0 text-secondary" id="changeAmount">0.00</h4>
                    </div>
                </div>
                <input type="hidden" id="hiddenTotal" value="{{ number_format($order->total + ($order->tip ?? 0) - ($order->discount ?? 0), 2, '.', '') }}">
            </div>
            <div class="modal-footer p-2 bg-light">
                <button type="submit" class="btn btn-success w-100 btn-lg fw-bold">CONFIRMAR PAGO</button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
    const tableId = {{ $table->id }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Escáner
    const barcodeInput = document.getElementById('barcodeInput');
    if(barcodeInput) {
        barcodeInput.focus();
        barcodeInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault(); 
                let code = barcodeInput.value.trim();
                if(code.length > 0) addByBarcode(code); 
            }
        });
    }

    // AJAX
    window.addByBarcode = function(code) {
        fetch(`{{ url('/pos/order') }}/${tableId}/barcode`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ barcode: code })
        }).then(r => r.text()).then(html => {
            document.getElementById('cart-container').innerHTML = html;
            barcodeInput.value = ''; barcodeInput.focus();
            updateCheckoutTotal();
        });
    };

    window.addToOrder = function(productId) {
        fetch(`{{ url('/pos/order') }}/${tableId}/add`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ product_id: productId })
        }).then(r => r.text()).then(html => {
            document.getElementById('cart-container').innerHTML = html;
            updateCheckoutTotal();
        });
    };

    window.updateQty = function(id, qty) {
        if(qty < 1 && !confirm('¿Eliminar producto?')) return;
        fetch(`{{ url('/pos/detail') }}/${id}/update`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ quantity: qty })
        }).then(r => r.text()).then(html => {
            document.getElementById('cart-container').innerHTML = html;
            updateCheckoutTotal();
        });
    };

    window.removeItem = function(id) {
        fetch(`{{ url('/pos/detail') }}/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }).then(r => r.text()).then(html => {
            document.getElementById('cart-container').innerHTML = html;
            updateCheckoutTotal();
        });
    };

    window.applyOptions = function() {
        var discount = document.getElementById('inputDiscount').value;
        var tip = document.getElementById('inputTip').value;
        var modal = bootstrap.Modal.getInstance(document.getElementById('optionsModal'));
        modal.hide();

        fetch(`{{ url('/pos/order') }}/{{ $order ? $order->id : 0 }}/discount`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ discount: discount, tip: tip })
        }).then(r => r.text()).then(html => {
            document.getElementById('cart-container').innerHTML = html;
            updateCheckoutTotal();
        });
    };

    // Utils
    window.filterProducts = function(cat) {
        document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById(cat === 'all' ? 'cat-btn-all' : 'cat-btn-' + cat.replace('cat-', '')).classList.add('active');
        document.querySelectorAll('.product-item').forEach(item => {
            item.style.display = (cat === 'all' || item.classList.contains(cat)) ? 'block' : 'none';
        });
    };

    window.updateCheckoutTotal = function() {
        setTimeout(() => {
            var newTotal = document.getElementById('cartTotalValue') ? document.getElementById('cartTotalValue').value : 0;
            var hiddenInput = document.getElementById('hiddenTotal');
            var receivedInput = document.getElementById('receivedAmount');
            if(hiddenInput) hiddenInput.value = newTotal;
            if(receivedInput) receivedInput.value = newTotal;
        }, 500);
    };

    // Modal Notas
    var noteModalEl = document.getElementById('noteModal');
    if(noteModalEl){
        noteModalEl.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('noteDetailId').value = button.getAttribute('data-detail-id');
            document.getElementById('noteText').value = button.getAttribute('data-note-content') || '';
            setTimeout(() => document.getElementById('noteText').focus(), 500);
        });
    }
    window.saveNote = function() {
        var detailId = document.getElementById('noteDetailId').value;
        var note = document.getElementById('noteText').value;
        var modal = bootstrap.Modal.getInstance(document.getElementById('noteModal'));
        modal.hide();
        fetch(`{{ url('/pos/detail') }}/${detailId}/note`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ note: note })
        }).then(r => r.text()).then(html => document.getElementById('cart-container').innerHTML = html);
    };

    // Cobro
    window.toggleCashInput = function(show) { document.getElementById('cashInputGroup').style.display = show ? 'block' : 'none'; }
    window.calculateChange = function() {
        var total = parseFloat(document.getElementById('hiddenTotal').value) || 0;
        var received = parseFloat(document.getElementById('receivedAmount').value) || 0;
        var el = document.getElementById('changeAmount');
        if(el) el.innerText = (received - total).toFixed(2);
    }
    window.searchClient = function(input) {
        var list = document.getElementById('clientsList');
        if(!list || input.value === '') { document.getElementById('clientId').value=''; document.getElementById('clientDoc').value=''; return; }
        for(var i=0; i<list.options.length; i++) {
            if(list.options[i].value === input.value) {
                document.getElementById('clientId').value = list.options[i].getAttribute('data-id');
                document.getElementById('clientDoc').value = list.options[i].getAttribute('data-document');
                break;
            }
        }
    }
</script>

<style>
    /* ── POS Product Cards ─────────────────────────────────────── */
    .pos-product-card {
        position: relative;
        border-radius: 16px;
        border: 2px solid var(--pal-border);
        background: #fff;
        cursor: pointer;
        overflow: hidden;
        transition: transform .18s, box-shadow .18s, border-color .18s;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        height: 100%;
        display: flex;
        flex-direction: column;
        user-select: none;
    }
    .pos-product-card:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 10px 28px rgba(0,0,0,0.14);
        border-color: var(--pal-icon);
    }
    .pos-product-card:active {
        transform: scale(0.97);
        box-shadow: 0 2px 8px rgba(0,0,0,0.10);
    }

    /* Image / icon block */
    .pos-product-img-wrap {
        position: relative;
        width: 100%;
        height: 115px;
        flex-shrink: 0;
    }
    .pos-product-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .pos-product-icon-block {
        width: 100%;
        height: 100%;
        background: var(--pal-bg);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .pos-product-icon {
        font-size: 2.6rem;
        color: var(--pal-icon);
        opacity: .85;
    }

    /* Price badge */
    .pos-price-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        background: var(--pal-icon);
        color: #fff;
        font-size: .72rem;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.18);
        letter-spacing: .02em;
    }

    /* Stock badge */
    .pos-stock-badge {
        position: absolute;
        bottom: 8px;
        left: 8px;
        font-size: .67rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 20px;
        border: 2px solid #fff;
        box-shadow: 0 1px 6px rgba(0,0,0,0.15);
    }
    .pos-stock-ok  { background: #22c55e; color: #fff; }
    .pos-stock-low { background: #ef4444; color: #fff; animation: pulse-red 1.2s infinite; }
    @keyframes pulse-red {
        0%,100% { opacity: 1; } 50% { opacity: .65; }
    }

    /* Footer */
    .pos-product-footer {
        padding: 8px 10px 10px;
        text-align: center;
        border-top: 1.5px solid var(--pal-border);
        background: var(--pal-bg);
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .pos-product-name {
        font-size: .82rem;
        font-weight: 700;
        color: #1e1b3a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.2;
    }
    .pos-product-cat {
        font-size: .65rem;
        color: var(--pal-icon);
        font-weight: 600;
        margin-top: 2px;
        opacity: .8;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Hover overlay */
    .pos-add-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.38);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        opacity: 0;
        transition: opacity .18s;
        color: #fff;
        pointer-events: none;
        border-radius: 14px;
    }
    .pos-add-overlay i { font-size: 2rem; }
    .pos-add-overlay span { font-size: .8rem; font-weight: 700; letter-spacing: .06em; }
    .pos-product-card:hover .pos-add-overlay { opacity: 1; }
</style>
@endsection