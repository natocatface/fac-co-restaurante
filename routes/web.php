<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\DianBillingController;
use App\Http\Controllers\DianBillingPdfController;
use App\Http\Controllers\DianCreditNoteController;

/*
|--------------------------------------------------------------------------
| Web Routes (SISTEMA PROFESIONAL v5.0 - PRODUCCIÓN)
|--------------------------------------------------------------------------
*/

// --- 1. AUTENTICACIÓN Y PÚBLICO ---
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.perform');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Menú Digital Público (Escaneo QR)
Route::get('/menu', [App\Http\Controllers\MenuController::class, 'index'])->name('menu.index');

// --- 2. SISTEMA INTERNO ---
Route::middleware(['auth'])->group(function () {

    // =========================================================
    // ZONA OPERATIVA (Accesible para Mozo, Cajero, Admin)
    // =========================================================
    
    // Rutas de Caja (Apertura y Cierre) - Todos pueden intentar entrar, pero el controller valida
    Route::get('/cash-registers/open', [App\Http\Controllers\CashRegisterController::class, 'create'])->name('cash_registers.create');
    Route::post('/cash-registers/open', [App\Http\Controllers\CashRegisterController::class, 'store'])->name('cash_registers.store');
    Route::get('/cash-registers/close', [App\Http\Controllers\CashRegisterController::class, 'close'])->name('cash_registers.close');
    Route::post('/cash-registers/close', [App\Http\Controllers\CashRegisterController::class, 'processClose'])->name('cash_registers.processClose');

    // POS (Punto de Venta) - Protegido para que cajeros abran caja primero
    Route::middleware(['cash_register'])->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::get('/pos/table/{table}', [PosController::class, 'order'])->name('pos.order');
    });
    
    Route::post('/pos/order/{table}/add', [PosController::class, 'addToOrder'])->name('pos.add');
    
    // --- NUEVA RUTA: CÓDIGO DE BARRAS ---
    Route::post('/pos/order/{table}/barcode', [PosController::class, 'addByBarcode'])->name('pos.barcode');
    
    // Herramientas de Orden
    Route::get('/pos/order/{order}/precheck', [PosController::class, 'precheck'])->name('pos.precheck');
    Route::get('/pos/order/{order}/kitchen-ticket', [PosController::class, 'kitchenTicket'])->name('pos.kitchen');
    Route::post('/pos/order/{order}/discount', [PosController::class, 'applyDiscount'])->name('pos.discount');
    Route::post('/pos/order/{order}/move', [PosController::class, 'moveTable'])->name('pos.move');
    
    // División de Cuenta
    Route::get('/pos/order/{order}/split-content', [PosController::class, 'getSplitContent'])->name('pos.split.content');
    Route::post('/pos/order/{order}/split', [PosController::class, 'processSplit'])->name('pos.split');
    
    // Gestión de Items
    Route::post('/pos/detail/{detail}/update', [PosController::class, 'updateQuantity'])->name('pos.update');
    Route::post('/pos/detail/{detail}/note', [PosController::class, 'updateNote'])->name('pos.note');
    Route::delete('/pos/detail/{detail}', [PosController::class, 'removeItem'])->name('pos.remove');
    
    // Monitor de Cocina
    Route::get('/kitchen', [KitchenController::class, 'index'])->name('kitchen.index');
    Route::post('/kitchen/{detail}/status', [KitchenController::class, 'updateStatus'])->name('kitchen.update');

    // RESERVAS Y AGENDA
    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::put('/reservations/{reservation}/status', [ReservationController::class, 'updateStatus'])->name('reservations.status');
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])->name('reservations.destroy');


    // =========================================================
    // ZONA FINANCIERA (Cajeros y Admins)
    // =========================================================
    Route::middleware(['role:admin,cashier'])->group(function () {
        // Cobro Final
        Route::post('/pos/order/{order}/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
        
        // Ventas, Caja y Gastos
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/daily-report', [SaleController::class, 'dailyReport'])->name('sales.daily.report');
        Route::get('/sales/{order}/ticket', [SaleController::class, 'ticket'])->name('sales.ticket');
        
        Route::resource('expenses', ExpenseController::class)->only(['store', 'destroy']);

        // ── DELIVERY ──────────────────────────────────────────
        Route::get('/delivery',                          [DeliveryController::class, 'index'])->name('delivery.index');
        Route::get('/delivery/create',                   [DeliveryController::class, 'create'])->name('delivery.create');
        Route::post('/delivery',                         [DeliveryController::class, 'store'])->name('delivery.store');
        Route::get('/delivery/{delivery}',               [DeliveryController::class, 'show'])->name('delivery.show');
        Route::post('/delivery/{delivery}/status',       [DeliveryController::class, 'updateStatus'])->name('delivery.status');
        Route::post('/delivery/{delivery}/driver',       [DeliveryController::class, 'assignDriver'])->name('delivery.driver');
        Route::post('/delivery/{delivery}/checkout',     [DeliveryController::class, 'checkout'])->name('delivery.checkout');
        Route::post('/delivery/{delivery}/cancel',       [DeliveryController::class, 'cancel'])->name('delivery.cancel');
        // Repartidores
        Route::get('/delivery-drivers',                  [DeliveryController::class, 'driversIndex'])->name('delivery.drivers');
        Route::post('/delivery-drivers',                 [DeliveryController::class, 'driversStore'])->name('delivery.drivers.store');
        Route::put('/delivery-drivers/{driver}',         [DeliveryController::class, 'driversUpdate'])->name('delivery.drivers.update');
        Route::delete('/delivery-drivers/{driver}',      [DeliveryController::class, 'driversDestroy'])->name('delivery.drivers.destroy');
    });


    // =========================================================
    // ZONA ADMINISTRATIVA (Solo Admin)
    // =========================================================
    Route::middleware(['role:admin'])->group(function () {

        // ── FACTURACIÓN ELECTRÓNICA (DIAN COLOMBIA) ──────────
        // Factura electrónica de venta
        Route::get('/billing',                    [DianBillingController::class, 'index'])->name('billing.index');
        Route::get('/billing/{order}',            [DianBillingController::class, 'show'])->name('billing.show');
        Route::post('/billing/{order}/send',      [DianBillingController::class, 'send'])->name('billing.send');
        Route::post('/billing/{order}/retry',     [DianBillingController::class, 'retry'])->name('billing.retry');
        Route::get('/billing/{order}/xml',        [DianBillingController::class, 'downloadXml'])->name('billing.xml');
        Route::get('/billing/{order}/ar',         [DianBillingController::class, 'downloadApplicationResponse'])->name('billing.ar');
        Route::get('/billing/{order}/pdf',        [DianBillingPdfController::class, 'show'])->name('billing.pdf');
        Route::get('/billing/{order}/ticket',     [DianBillingPdfController::class, 'ticket'])->name('billing.pdf.ticket');

        // Notas de Crédito (DIAN)
        Route::get('/credit-notes',                       [DianCreditNoteController::class, 'index'])->name('credit_notes.index');
        Route::get('/credit-notes/create/{order}',        [DianCreditNoteController::class, 'create'])->name('credit_notes.create');
        Route::post('/credit-notes/{order}',              [DianCreditNoteController::class, 'store'])->name('credit_notes.store');
        Route::get('/credit-notes/{creditNote}',          [DianCreditNoteController::class, 'show'])->name('credit_notes.show');
        Route::post('/credit-notes/{creditNote}/retry',   [DianCreditNoteController::class, 'retry'])->name('credit_notes.retry');
        Route::get('/credit-notes/{creditNote}/xml',      [DianCreditNoteController::class, 'downloadXml'])->name('credit_notes.xml');
        Route::get('/credit-notes/{creditNote}/ar',       [DianCreditNoteController::class, 'downloadApplicationResponse'])->name('credit_notes.ar');
        Route::get('/credit-notes/{creditNote}/pdf',      [DianCreditNoteController::class, 'pdf'])->name('credit_notes.pdf');

        // Dashboard y BI
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        
        // Gestión
        Route::get('/cash-registers', [App\Http\Controllers\CashRegisterController::class, 'index'])->name('cash_registers.index');
        Route::resource('clients', ClientController::class);
        Route::resource('categories', CategoryController::class);
        
        // Productos e Inventario
        Route::post('/products/{product}/adjust', [ProductController::class, 'adjustStock'])->name('products.adjust');
        Route::post('/products/{product}/toggle', [ProductController::class, 'toggleStatus'])->name('products.toggle');
        Route::resource('products', ProductController::class);
        Route::get('/inventory/logs', function() {
            $logs = \App\Models\InventoryLog::with('product', 'user')->orderBy('created_at', 'desc')->paginate(50);
            return view('products.kardex', compact('logs'));
        })->name('inventory.logs');
        
        // Configuración y Usuarios
        Route::resource('users', UserController::class);
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

        // MANTENIMIENTO DEL SISTEMA (RESET Y BACKUPS)
        Route::get('/system', [SystemController::class, 'index'])->name('system.index');
        Route::post('/system/reset', [SystemController::class, 'resetData'])->name('system.reset');
        Route::post('/system/backup', [SystemController::class, 'backup'])->name('system.backup');
        Route::post('/system/restore', [SystemController::class, 'restore'])->name('system.restore');

        // Mapa de Mesas
        Route::get('/tables', [TableController::class, 'index'])->name('tables.index');
        Route::post('/tables/area', [TableController::class, 'storeArea'])->name('tables.storeArea');
        Route::delete('/tables/area/{area}', [TableController::class, 'destroyArea'])->name('tables.destroyArea');
        Route::post('/tables/table', [TableController::class, 'storeTable'])->name('tables.storeTable');
        Route::delete('/tables/table/{table}', [TableController::class, 'destroyTable'])->name('tables.destroyTable');
        Route::post('/tables/update-positions', [TableController::class, 'updatePositions'])->name('tables.updatePositions');
    });

});