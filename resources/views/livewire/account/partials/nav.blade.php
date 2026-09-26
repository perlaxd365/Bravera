@php
    $user = Auth::user();
    $initials = collect(preg_split('/[\s,]+/', trim((string) $user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->join('');

    $links = [
        ['route' => 'account.profile', 'match' => 'account.profile', 'label' => 'Mi perfil', 'icon' => 'user'],
        ['route' => 'account.password', 'match' => 'account.password', 'label' => 'Contraseña', 'icon' => 'lock'],
        ['route' => 'account.orders', 'match' => 'account.orders*', 'label' => 'Mis pedidos', 'icon' => 'cube'],
        ['route' => 'account.addresses', 'match' => 'account.addresses', 'label' => 'Mis direcciones', 'icon' => 'map-pin'],
    ];
@endphp

@if (($orientation ?? 'vertical') === 'vertical')
    <div class="sticky top-24 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 bg-gray-50/60 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex size-11 items-center justify-center rounded-full bg-gray-100 text-sm font-bold text-gray-600 ring-1 ring-inset ring-gray-200">
                    @if ($user->avatar)
                        <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="size-11 rounded-full object-cover" loading="lazy">
                    @else
                        {{ $initials }}
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900">{{ $user->name }}</p>
                    <p class="truncate text-xs text-gray-500">{{ $user->email }}</p>
                </div>
            </div>
        </div>

        <nav class="p-2" aria-label="Menú de cuenta">
            @foreach ($links as $link)
                @php $active = request()->routeIs($link['match']); @endphp
                <a href="{{ route($link['route']) }}" wire:navigate
                    class="mb-0.5 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    <flux:icon :name="$link['icon']" class="size-4.5 {{ $active ? 'text-white' : 'text-gray-400' }}" />
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="border-t border-gray-100 p-2">
            <form method="POST" action="{{ route('logout') }}" class="rounded-xl">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-50">
                    <flux:icon name="arrow-right-start-on-rectangle" class="size-4.5" />
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
@else
    <nav class="mb-6 flex gap-2 overflow-x-auto pb-1 lg:hidden" aria-label="Menú de cuenta">
        @foreach ($links as $link)
            @php $active = request()->routeIs($link['match']); @endphp
            <a href="{{ route($link['route']) }}" wire:navigate
                class="{{ $active ? 'bg-gray-900 text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-600' }} flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition">
                <flux:icon :name="$link['icon']" class="size-4 {{ $active ? '' : 'text-gray-400' }}" />
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
@endif