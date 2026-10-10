@props(['compact' => false])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-4']) }}>
    <div class="flex items-center gap-2">
        <flux:icon name="lock-closed" class="size-4 shrink-0 text-emerald-600" />
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-700">Pago 100% seguro</p>
            <p class="mt-0.5 text-xs text-gray-500">Elige el medio que prefieras al pagar</p>
        </div>
    </div>

    <ul class="mt-4 flex flex-wrap items-start justify-center gap-x-3 gap-y-4 sm:gap-x-5" aria-label="Medios de pago aceptados">
        <li class="w-16 text-center sm:w-[72px]" aria-label="Visa">
            <span @class(['mx-auto flex items-center justify-center overflow-hidden rounded-full border-[3px] border-gray-300 bg-white p-1 shadow-[0_4px_0_#d1d5db]', 'size-14' => $compact, 'size-16 sm:size-[72px]' => ! $compact])>
                <span class="relative flex size-full items-center justify-center overflow-hidden rounded-full bg-white">
                    <span class="absolute inset-x-0 top-[24%] h-[40%] bg-[#173783]"></span>
                    <span class="absolute inset-x-0 bottom-0 h-[24%] bg-[#f5a623]"></span>
                    <span class="relative z-10 text-sm font-black italic tracking-tight text-white sm:text-base">VISA</span>
                </span>
            </span>
            <span class="mt-2 block text-[10px] font-medium text-gray-600">Tarjeta</span>
        </li>
        <li class="w-16 text-center sm:w-[72px]" aria-label="Mastercard">
            <span @class(['mx-auto flex items-center justify-center overflow-hidden rounded-full border-[3px] border-gray-300 bg-white p-1 shadow-[0_4px_0_#d1d5db]', 'size-14' => $compact, 'size-16 sm:size-[72px]' => ! $compact])>
                <span class="relative flex size-full items-center justify-center overflow-hidden rounded-full bg-[#1676a8]">
                    <span class="absolute left-[18%] size-7 rounded-full bg-[#eb3328] sm:size-8"></span>
                    <span class="absolute right-[18%] size-7 rounded-full bg-[#f6a623]/95 sm:size-8"></span>
                    <span class="relative z-10 rounded-full bg-[#1676a8]/90 px-1 text-[8px] font-extrabold tracking-tight text-white sm:text-[9px]">Mastercard</span>
                </span>
            </span>
            <span class="mt-2 block text-[10px] font-medium text-gray-600">Mastercard</span>
        </li>
        <li class="w-16 text-center sm:w-[72px]" aria-label="Yape">
            <span @class(['mx-auto flex items-center justify-center overflow-hidden rounded-full border-[3px] border-gray-300 bg-white p-1 shadow-[0_4px_0_#d1d5db]', 'size-14' => $compact, 'size-16 sm:size-[72px]' => ! $compact])>
                <span class="flex size-full flex-col items-center justify-center rounded-full bg-[#710080] text-white">
                    <span class="text-[8px] font-semibold leading-none text-[#8df4db]">S/</span>
                    <span class="mt-0.5 text-base font-black italic leading-none sm:text-lg">yape</span>
                </span>
            </span>
            <span class="mt-2 block text-[10px] font-medium text-gray-600">Yape</span>
        </li>
        <li class="w-16 text-center sm:w-[72px]" aria-label="Plin">
            <span @class(['mx-auto flex items-center justify-center overflow-hidden rounded-full border-[3px] border-gray-300 bg-white p-1 shadow-[0_4px_0_#d1d5db]', 'size-14' => $compact, 'size-16 sm:size-[72px]' => ! $compact])>
                <span class="flex size-full flex-col items-center justify-center rounded-full bg-[#ec168c] text-white">
                    <span class="text-xl font-black leading-none">P</span>
                    <span class="text-[9px] font-bold leading-none">plin</span>
                </span>
            </span>
            <span class="mt-2 block text-[10px] font-medium text-gray-600">Plin</span>
        </li>
        <li class="w-16 text-center sm:w-[72px]" aria-label="Pago en efectivo">
            <span @class(['mx-auto flex items-center justify-center overflow-hidden rounded-full border-[3px] border-gray-300 bg-white p-1 shadow-[0_4px_0_#d1d5db]', 'size-14' => $compact, 'size-16 sm:size-[72px]' => ! $compact])>
                <span class="flex size-full flex-col items-center justify-center rounded-full bg-emerald-600 text-white">
                    <flux:icon name="banknotes" class="size-6" />
                    <span class="mt-0.5 text-[8px] font-bold leading-none">EFECTIVO</span>
                </span>
            </span>
            <span class="mt-2 block text-[10px] font-medium text-gray-600">Efectivo</span>
        </li>
    </ul>
</div>
