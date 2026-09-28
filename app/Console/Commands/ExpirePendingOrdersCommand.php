<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Modules\Ordering\Services\OrderService;
use Illuminate\Console\Command;

class ExpirePendingOrdersCommand extends Command
{
    /**
     * Nombre del comando.
     */
    protected $signature = 'brevare:expire-pending-orders {--hours= : Antigüedad máxima en horas antes de cancelar}';

    /**
     * Descripción.
     */
    protected $description = 'Cancela los pedidos que nadie llegó a pagar y devuelve su stock reservado';

    /**
     * Ejecuta la limpieza.
     *
     * El checkout de Culqi abre su modal después de crear el pedido y reservar
     * el stock, porque los métodos asíncronos no se pagan sin una orden previa.
     * Si el comprador cierra el modal sin pagar, esa reserva quedaría bloqueada
     * para siempre: este comando la devuelve.
     *
     * Solo toca pedidos que siguen en pendiente, así que repetirlo no libera
     * dos veces el mismo stock.
     */
    public function handle(OrderService $orders): int
    {
        $hours = (int) ($this->option('hours') ?: config('payments.pending_order_hours', 24));

        $pending = Order::query()
            ->where('status', OrderStatus::PENDING->value)
            ->where('payment_status', PaymentStatus::PENDING->value)
            ->where('created_at', '<=', now()->subHours(max(1, $hours)))
            ->oldest('id')
            ->get();

        $expired = 0;

        foreach ($pending as $order) {
            if ($orders->abandonPendingOrder($order)) {
                $expired++;
            }
        }

        $this->info("Pedidos cancelados por falta de pago: {$expired}");

        if ($expired > 0) {
            $this->line('Se devolvió el stock reservado y se liberaron los usos de cupón de esos pedidos.');
        }

        return self::SUCCESS;
    }
}
