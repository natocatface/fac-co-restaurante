@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            
            <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
                <!-- Header -->
                <div class="bg-primary text-white p-4 text-center" style="background: linear-gradient(135deg, #2d1b5e 0%, #ff8c00 100%) !important;">
                    <div class="mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center bg-white text-primary rounded-circle" style="width: 60px; height: 60px; font-size: 24px;">
                            <i class="bi bi-box-arrow-in-right" style="color: #ff8c00;"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1">Apertura de Caja</h4>
                    <p class="mb-0 opacity-75 small">Registra tu fondo de cambio inicial para comenzar el turno.</p>
                </div>

                <!-- Body -->
                <div class="card-body p-4 p-md-5">
                    
                    @if(session('warning'))
                        <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-warning px-4 py-3" style="border-radius: 12px;">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('warning') }}
                        </div>
                    @endif

                    <form action="{{ route('cash_registers.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="form-label text-muted fw-bold small text-uppercase">Monto Inicial (Fondo de Caja)</label>
                            <div class="input-group input-group-lg shadow-sm" style="border-radius: 12px; overflow: hidden;">
                                <span class="input-group-text bg-white border-end-0 text-muted">
                                    {{ \App\Models\Setting::where('key','currency_symbol')->value('value') ?? 'S/' }}
                                </span>
                                <input type="number" step="0.01" min="0" class="form-control border-start-0 ps-0 form-control-lg" name="opening_amount" value="0.00" required autofocus>
                            </div>
                            @error('opening_amount')
                                <small class="text-danger mt-2 d-block">{{ $message }}</small>
                            @enderror
                            <div class="form-text mt-2">Ingresa el dinero físico con el que inicias operaciones hoy (monedas y billetes para dar vuelto).</div>
                        </div>

                        <div class="d-grid mt-5">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold border-0 shadow-sm" style="background: #ff8c00; border-radius: 12px; padding: 14px;">
                                Abrir Turno de Caja
                            </button>
                            <a href="{{ route('dashboard') }}" class="btn btn-light btn-lg mt-3 text-muted fw-bold shadow-sm" style="border-radius: 12px; padding: 14px;">
                                Cancelar y volver
                            </a>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
