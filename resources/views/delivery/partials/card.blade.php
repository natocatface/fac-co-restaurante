<div class="card mb-2 shadow-sm border-0" style="border-radius: 10px; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'" onclick="window.location='{{ route('delivery.show', $delivery->id) }}'">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="badge bg-light text-dark border">#{{ $delivery->id }}</span>
            <span class="small text-muted"><i class="bi bi-clock me-1"></i>{{ $delivery->created_at->format('H:i') }}</span>
        </div>
        
        <h6 class="fw-bold mb-1 text-truncate">{{ $delivery->client_name }}</h6>
        <p class="small text-muted mb-2 text-truncate"><i class="bi bi-geo-alt-fill text-danger me-1"></i>{{ $delivery->address }}</p>
        
        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
            <div class="small fw-bold text-primary">
                {{ $currency ?? 'S/' }}{{ number_format($delivery->total_with_fee, 2) }}
            </div>
            @if($delivery->status === 'on_way' || $delivery->status === 'delivered')
                <div class="small text-muted text-truncate" style="max-width: 100px;">
                    <i class="bi bi-person-circle me-1"></i>{{ $delivery->driver->name ?? 'Repartidor' }}
                </div>
            @elseif($delivery->status === 'pending')
                <div class="small text-warning fw-bold"><i class="bi bi-hourglass-split me-1"></i>Recién</div>
            @endif
        </div>
    </div>
</div>
