<?php

namespace App\Enums;

/**
 * Motivos de reembolso aceptados por Culqi.
 *
 * Verificado contra la API real (modo test): POST /v2/refunds exige `reason` y
 * solo admite estos tres valores literales. Cualquier otro valor devuelve
 * 400 parameter_error.
 */
enum RefundReason: string
{
    case Duplicado = 'duplicado';
    case Fraudulento = 'fraudulento';
    case SolicitudComprador = 'solicitud_comprador';

    public function label(): string
    {
        return match ($this) {
            self::Duplicado => 'Pago duplicado',
            self::Fraudulento => 'Pago fraudulento',
            self::SolicitudComprador => 'Solicitud del comprador',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
