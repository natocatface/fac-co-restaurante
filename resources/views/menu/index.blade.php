<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Carta Digital — {{ $companyName }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff8c00;
            --primary-light: #ffebcc;
            --dark: #1e1e2d;
            --gray-bg: #f8f9fa;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--gray-bg);
            color: #333;
            -webkit-font-smoothing: antialiased;
            padding-bottom: 80px;
        }

        /* HEADER / HERO */
        .hero {
            background: linear-gradient(135deg, var(--dark) 0%, #3a3a5c 100%);
            color: white;
            padding: 40px 20px 60px;
            text-align: center;
            border-bottom-left-radius: 30px;
            border-bottom-right-radius: 30px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .hero-logo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            margin-bottom: 15px;
            background: white;
            padding: 5px;
        }
        .hero h1 {
            font-weight: 800;
            font-size: 1.8rem;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }
        .hero p {
            font-weight: 300;
            opacity: 0.8;
            font-size: 0.9rem;
        }

        /* HORIZONTAL NAV */
        .category-nav {
            display: flex;
            overflow-x: auto;
            gap: 12px;
            padding: 0 20px;
            margin-top: -25px;
            scrollbar-width: none; /* Firefox */
        }
        .category-nav::-webkit-scrollbar { display: none; } /* Chrome */
        .category-pill {
            background: white;
            color: var(--dark);
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            white-space: nowrap;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .category-pill.active, .category-pill:active {
            background: var(--primary);
            color: white;
            box-shadow: 0 6px 20px rgba(255, 140, 0, 0.4);
        }

        /* SECTION TITLES */
        .section-title {
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--dark);
            margin: 35px 20px 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* PRODUCT CARD */
        .product-card {
            background: white;
            border-radius: 20px;
            margin: 0 20px 15px;
            padding: 15px;
            display: flex;
            gap: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            transition: transform 0.2s;
            position: relative;
            overflow: hidden;
        }
        .product-card:active {
            transform: scale(0.98);
        }
        .product-img {
            width: 90px;
            height: 90px;
            border-radius: 15px;
            object-fit: cover;
            background: #eee;
            flex-shrink: 0;
        }
        .product-info {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .product-name {
            font-weight: 700;
            font-size: 1.05rem;
            margin-bottom: 4px;
            color: var(--dark);
            line-height: 1.2;
        }
        .product-desc {
            font-size: 0.8rem;
            color: #777;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-price-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: auto;
        }
        .price {
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--dark);
        }
        .price.promo {
            color: #e53935;
        }
        .price-old {
            font-size: 0.85rem;
            color: #aaa;
            text-decoration: line-through;
            font-weight: 500;
        }

        /* BADGES */
        .badge-custom {
            position: absolute;
            top: 0;
            right: 0;
            padding: 4px 12px;
            font-size: 0.7rem;
            font-weight: 700;
            border-bottom-left-radius: 15px;
            border-top-right-radius: 20px;
            z-index: 10;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-promo { background: #e53935; color: white; }
        .badge-chef { background: var(--dark); color: #ffd700; }
        .badge-new { background: #2e7d32; color: white; }
        .badge-popular { background: var(--primary); color: white; }

        /* SCROLL SPY OFFSET */
        section {
            scroll-margin-top: 80px;
        }
    </style>
</head>
<body>

    <!-- HERO -->
    <div class="hero">
        @if($companyLogo)
            <img src="{{ asset('storage/'.$companyLogo) }}" class="hero-logo" alt="Logo">
        @else
            <div class="hero-logo d-flex align-items-center justify-content-center mx-auto" style="background:#fff; color:var(--primary); font-size: 30px;">
                <i class="bi bi-shop"></i>
            </div>
        @endif
        <h1>{{ $companyName }}</h1>
        <p>Explora nuestra deliciosa carta digital</p>
    </div>

    <!-- CATEGORY NAVIGATION -->
    <div class="category-nav sticky-top" style="top: 15px; z-index: 100;">
        @if($promotions->count() > 0)
            <a href="#promo" class="category-pill active"><i class="bi bi-tag-fill text-danger me-1"></i> Ofertas</a>
        @endif
        @if($chefRecommendations->count() > 0)
            <a href="#chef" class="category-pill"><i class="bi bi-star-fill text-warning me-1"></i> Sugerencias</a>
        @endif
        @if($mostPopular->count() > 0)
            <a href="#popular" class="category-pill"><i class="bi bi-fire text-danger me-1"></i> Favoritos</a>
        @endif
        @foreach($categories as $category)
            @if($category->products->count() > 0)
                <a href="#cat-{{ $category->id }}" class="category-pill">{{ $category->name }}</a>
            @endif
        @endforeach
    </div>

    <!-- 1. PROMOCIONES -->
    @if($promotions->count() > 0)
        <section id="promo">
            <div class="section-title">
                <i class="bi bi-tags-fill text-danger"></i> Ofertas Especiales
            </div>
            @foreach($promotions as $product)
                @include('menu.partials.product-card', ['product' => $product, 'badge' => 'promo', 'badgeText' => 'OFERTA'])
            @endforeach
        </section>
    @endif

    <!-- 2. RECOMENDACIÓN DEL CHEF -->
    @if($chefRecommendations->count() > 0)
        <section id="chef">
            <div class="section-title">
                <i class="bi bi-star-fill text-warning"></i> Sugerencias del Chef
            </div>
            @foreach($chefRecommendations as $product)
                @include('menu.partials.product-card', ['product' => $product, 'badge' => 'chef', 'badgeText' => 'Sugerencia'])
            @endforeach
        </section>
    @endif

    <!-- 3. LO MÁS PEDIDO -->
    @if($mostPopular->count() > 0)
        <section id="popular">
            <div class="section-title">
                <i class="bi bi-fire text-danger"></i> Los Favoritos
            </div>
            @foreach($mostPopular as $product)
                @include('menu.partials.product-card', ['product' => $product, 'badge' => 'popular', 'badgeText' => 'Top Ventas'])
            @endforeach
        </section>
    @endif

    <!-- 4. NUEVOS PLATOS -->
    @if($newProducts->count() > 0)
        <section id="nuevos">
            <div class="section-title">
                <i class="bi bi-stars text-success"></i> Novedades
            </div>
            @foreach($newProducts as $product)
                @include('menu.partials.product-card', ['product' => $product, 'badge' => 'new', 'badgeText' => 'NUEVO'])
            @endforeach
        </section>
    @endif

    <!-- 5. CARTA COMPLETA POR CATEGORÍAS -->
    @foreach($categories as $category)
        @if($category->products->count() > 0)
            <section id="cat-{{ $category->id }}">
                <div class="section-title">
                    {{ $category->name }}
                </div>
                @foreach($category->products as $product)
                    @include('menu.partials.product-card', ['product' => $product, 'badge' => null])
                @endforeach
            </section>
        @endif
    @endforeach

    <div class="text-center mt-5 mb-4 opacity-50" style="font-size: 0.8rem;">
        <i class="bi bi-qr-code-scan mb-2 d-block fs-3"></i>
        Carta Digital &copy; {{ date('Y') }} {{ $companyName }}
    </div>

    <!-- Script for smooth scroll & active pill state -->
    <script>
        document.querySelectorAll('.category-pill').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if(targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>
