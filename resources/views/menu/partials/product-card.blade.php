<div class="product-card">
    @if(isset($badge) && $badge)
        <span class="badge-custom badge-{{ $badge }}">{{ $badgeText }}</span>
    @elseif($product->promotional_price > 0)
        <span class="badge-custom badge-promo">OFERTA</span>
    @elseif($product->is_new)
        <span class="badge-custom badge-new">NUEVO</span>
    @endif

    @if($product->image)
        <img src="{{ asset('storage/'.$product->image) }}" class="product-img" alt="{{ $product->name }}">
    @else
        <div class="product-img d-flex align-items-center justify-content-center text-muted fs-3">
            <i class="bi bi-image"></i>
        </div>
    @endif

    <div class="product-info">
        <div class="product-name">{{ $product->name }}</div>
        @if($product->description)
            <div class="product-desc">{{ $product->description }}</div>
        @endif
        
        <div class="product-price-row">
            @if($product->promotional_price > 0)
                <span class="price promo">{{ $currency }} {{ number_format($product->promotional_price, 2) }}</span>
                <span class="price-old">{{ $currency }} {{ number_format($product->price, 2) }}</span>
            @else
                <span class="price">{{ $currency }} {{ number_format($product->price, 2) }}</span>
            @endif
        </div>
    </div>
</div>
