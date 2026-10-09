<?php

namespace App\Livewire\Admin\Customers;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('admin.layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function whatsappUrl(User $customer): ?string
    {
        $phone = $customer->phone ?: $customer->customerAddresses->first()?->phone;
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (strlen($digits) === 9) {
            $digits = '51'.$digits;
        }

        if (! preg_match('/^51\d{9}$/', $digits)) {
            return null;
        }

        $order = $customer->orders->first();
        $message = $order
            ? sprintf(
                'Hola %s, te escribimos de Brevare para informarte sobre tu pedido %s. Su estado actual es: %s.',
                $customer->name,
                $order->order_number,
                $order->status->label(),
            )
            : sprintf('Hola %s, te escribimos de Brevare. ¿En qué podemos ayudarte?', $customer->name);

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
    }

    public function render()
    {
        $customers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Cliente'))
            ->with([
                'orders' => fn ($query) => $query->latest()->limit(1),
                'customerAddresses' => fn ($query) => $query->latest()->limit(1),
            ])
            ->withCount('orders')
            ->when(trim($this->search) !== '', function ($query): void {
                $term = '%'.addcslashes(trim($this->search), '%_\\').'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhereHas('customerAddresses', fn ($address) => $address->where('phone', 'like', $term))
                        ->orWhereHas('orders', fn ($order) => $order->where('order_number', 'like', $term));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.admin.customers.index', compact('customers'))
            ->title('Clientes | Brevare');
    }
}
