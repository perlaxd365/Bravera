@props(['status'])

@php
    $steps = [
        ['status' => 'confirmed', 'label' => 'Confirmado', 'icon' => '✓'],
        ['status' => 'processing', 'label' => 'En preparación', 'icon' => '◆'],
        ['status' => 'shipped', 'label' => 'Enviado', 'icon' => '→'],
        ['status' => 'delivered', 'label' => 'Entregado', 'icon' => '✓'],
    ];
    $statusValue = is_object($status) && isset($status->value) ? $status->value : (string) $status;
    $statusLabel = is_object($status) && method_exists($status, 'label') ? $status->label() : ucfirst($statusValue);
    $currentIndex = array_search($statusValue, array_column($steps, 'status'), true);
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:20px 0 0;border:1px solid #e5e7eb;border-radius:14px;border-collapse:separate;">
    <tr>
        <td style="padding:16px 14px 8px;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#6b7280;">
            Seguimiento de tu pedido
        </td>
    </tr>
    @if ($currentIndex === false)
        <tr>
            <td style="padding:0 14px 16px;font-size:13px;line-height:1.5;color:#374151;">
                <strong style="color:#111827;">Estado:</strong> {{ $statusLabel }}
            </td>
        </tr>
    @else
        <tr>
            <td style="padding:0 6px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;table-layout:fixed;">
                    <tr>
                        @foreach ($steps as $index => $step)
                            @php
                                $complete = $index < $currentIndex;
                                $active = $complete || $index === $currentIndex;
                                $circleBg = $active ? '#047857' : '#f3f4f6';
                                $circleColor = $active ? '#ffffff' : '#9ca3af';
                                $lineBefore = $index > 0 && $index <= $currentIndex;
                                $lineAfter = $index < count($steps) - 1 && $index < $currentIndex;
                            @endphp
                            <td width="25%" align="center" valign="top" style="width:25%;padding:0 2px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td valign="middle" style="height:32px;padding:0;font-size:1px;line-height:1px;">
                                            @if ($index > 0)
                                                <table role="presentation" width="100%" height="2" cellpadding="0" cellspacing="0" border="0"><tr><td height="2" bgcolor="{{ $lineBefore ? '#047857' : '#e5e7eb' }}" style="height:2px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
                                            @endif
                                        </td>
                                        <td width="32" align="center" valign="middle" style="width:32px;height:32px;padding:0;">
                                            <span style="display:inline-block;width:32px;height:32px;line-height:32px;border-radius:50%;background-color:{{ $circleBg }};color:{{ $circleColor }};font-size:16px;font-weight:700;text-align:center;">
                                                {{ $complete ? '✓' : $step['icon'] }}
                                            </span>
                                        </td>
                                        <td valign="middle" style="height:32px;padding:0;font-size:1px;line-height:1px;">
                                            @if ($index < count($steps) - 1)
                                                <table role="presentation" width="100%" height="2" cellpadding="0" cellspacing="0" border="0"><tr><td height="2" bgcolor="{{ $lineAfter ? '#047857' : '#e5e7eb' }}" style="height:2px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($steps as $index => $step)
                            @php $emphasis = $index <= $currentIndex; @endphp
                            <td width="25%" align="center" valign="top" style="width:25%;padding:8px 2px 16px;font-size:10px;line-height:1.35;font-weight:{{ $index === $currentIndex ? '700' : '600' }};color:{{ $emphasis ? '#1f2937' : '#9ca3af' }};">
                                {{ $step['label'] }}
                                @if ($index === $currentIndex)
                                    <br><span style="font-size:9px;font-weight:500;color:#047857;">Estado actual</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </table>
            </td>
        </tr>
    @endif
</table>
