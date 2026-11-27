{{--
    Admin menu (Dashboard team, Must 4). Add it to any admin page with:
    @include('dashboard::partials.admin-menu')

    Other teams: add your page to the list below (route name => label).
    A link only shows when its route exists, so planned pages appear once they are merged.
--}}
@php
    $adminLinks = [
        'admin.dashboard' => 'Dashboard',
        'admin.reports.sales' => 'Sales report',
        'admin.inventory.index' => 'Inventory',
        'admin.products.index' => 'Products',
        'admin.categories.index' => 'Categories',
        'admin.orders.index' => 'Orders',
        'admin.shipments.index' => 'Shipments',
        'admin.shipping-rates.index' => 'Shipping rates',
        'admin.vouchers.index' => 'Vouchers',
        'admin.reviews.index' => 'Reviews',
        'admin.notifications.index' => 'Notifications',
    ];
@endphp

<nav aria-label="Admin menu" class="mb-6 flex flex-wrap gap-2 text-sm">
    @foreach ($adminLinks as $routeName => $label)
        @if (Route::has($routeName))
            {{-- the page you are on is shown in dark --}}
            @if (request()->routeIs($routeName))
                <a href="{{ route($routeName) }}" class="rounded px-3 py-1 border bg-gray-900 text-white" aria-current="page">{{ $label }}</a>
            @else
                <a href="{{ route($routeName) }}" class="rounded px-3 py-1 border bg-white hover:bg-gray-100">{{ $label }}</a>
            @endif
        @endif
    @endforeach
</nav>
