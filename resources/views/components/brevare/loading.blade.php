@props([
    'lines' => 3,
    'class' => null,
])

<div {{ $attributes->merge(['class' => 'animate-pulse space-y-3']) }}>
    <div class="h-4 w-2/3 rounded-md bg-gray-200"></div>
    @foreach (range(1, (int) $lines) as $i)
        <div class="h-3 rounded-md bg-gray-100" style="width: {{ rand(80, 100) }}%"></div>
    @endforeach
</div>