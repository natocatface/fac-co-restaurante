@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0 text-gray-800"><i class="bi bi-person-vcard me-2 text-primary"></i>Repartidores</h3>
            <p class="text-muted small mb-0 mt-1">Gestión del personal de delivery.</p>
        </div>
        <div>
            <a href="{{ route('delivery.index') }}" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Volver a Delivery
            </a>
            <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#driverModal">
                <i class="bi bi-plus-lg me-1"></i> Nuevo Repartidor
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th class="ps-4">NOMBRE</th>
                            <th>TELÉFONO</th>
                            <th>ESTADO</th>
                            <th>PEDIDOS ASIGNADOS</th>
                            <th class="text-end pe-4">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($drivers as $driver)
                            <tr class="border-bottom">
                                <td class="ps-4 fw-bold">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            {{ strtoupper(substr($driver->name, 0, 1)) }}
                                        </div>
                                        {{ $driver->name }}
                                    </div>
                                </td>
                                <td>{{ $driver->phone ?? '-' }}</td>
                                <td>
                                    @if($driver->is_active)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1"><i class="bi bi-circle-fill small me-1" style="font-size: 0.5rem;"></i>Activo</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1"><i class="bi bi-circle-fill small me-1" style="font-size: 0.5rem;"></i>Inactivo</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-secondary">{{ $driver->deliveries_count }}</span></td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editDriverModal{{ $driver->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('delivery.drivers.destroy', $driver) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar repartidor?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            {{-- Modal Edit --}}
                            <div class="modal fade" id="editDriverModal{{ $driver->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Editar Repartidor</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="{{ route('delivery.drivers.update', $driver) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold small">Nombre *</label>
                                                    <input type="text" name="name" class="form-control" value="{{ $driver->name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold small">Teléfono</label>
                                                    <input type="text" name="phone" class="form-control" value="{{ $driver->phone }}">
                                                </div>
                                                <div class="mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active{{ $driver->id }}" {{ $driver->is_active ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="active{{ $driver->id }}">Repartidor Activo</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-person-x display-4 d-block mb-3 opacity-50"></i>
                                    No hay repartidores registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal Nuevo --}}
<div class="modal fade" id="driverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Nuevo Repartidor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('delivery.drivers.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nombre *</label>
                        <input type="text" name="name" class="form-control" required placeholder="Ej. Juan Pérez">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Teléfono</label>
                        <input type="text" name="phone" class="form-control" placeholder="Ej. 987654321">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Repartidor</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
