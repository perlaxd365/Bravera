<?php

namespace App\Livewire\Admin\Points;

use App\Mail\PointsRedemptionStatusMail;
use App\Models\PointRedemption;
use App\Services\PointsService;
use App\Services\ReliableMailer;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('admin.layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $status = PointRedemption::STATUS_PENDING;

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function approve(int $id): void
    {
        $redemption = PointRedemption::query()->with('user')->findOrFail($id);
        $redemption = app(PointsService::class)->approve($redemption, auth()->user());

        $this->sendStatus($redemption);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Canje aprobado.']);
    }

    public function markPaid(int $id, ?string $reference = null): void
    {
        $reference = trim((string) $reference);

        if ($reference === '') {
            $this->addError('payoutReference', 'Ingresa la referencia del depósito.');

            return;
        }

        $redemption = PointRedemption::query()->with('user')->findOrFail($id);
        $redemption = app(PointsService::class)->markPaid(
            $redemption,
            auth()->user(),
            $reference,
        );

        $this->sendStatus($redemption);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Depósito registrado.']);
    }

    public function reject(int $id, ?string $reason = null): void
    {
        $redemption = PointRedemption::query()->with('user')->findOrFail($id);
        $redemption = app(PointsService::class)->reject(
            $redemption,
            auth()->user(),
            $reason ?: 'Rechazado por administración.',
        );

        $this->sendStatus($redemption);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Canje rechazado y puntos devueltos.']);
    }

    private function sendStatus(PointRedemption $redemption): void
    {
        $redemption->loadMissing('user');

        if (! $redemption->user?->email) {
            return;
        }

        ReliableMailer::send(
            [$redemption->user->email],
            new PointsRedemptionStatusMail($redemption->user, $redemption),
            description: 'correo de estado de canje de puntos',
            context: ['redemption_id' => $redemption->id],
        );
    }

    public function render()
    {
        $redemptions = PointRedemption::query()
            ->with(['user', 'reviewer'])
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->paginate(15);

        $stats = [
            'pending' => PointRedemption::query()->where('status', PointRedemption::STATUS_PENDING)->count(),
            'approved' => PointRedemption::query()->where('status', PointRedemption::STATUS_APPROVED)->count(),
            'paid' => PointRedemption::query()->where('status', PointRedemption::STATUS_PAID)->count(),
            'pending_amount' => (float) PointRedemption::query()->where('status', PointRedemption::STATUS_PENDING)->sum('amount'),
            'paid_amount' => (float) PointRedemption::query()->where('status', PointRedemption::STATUS_PAID)->sum('amount'),
        ];

        return view('livewire.admin.points.index', compact('redemptions', 'stats'))
            ->title('Brevare Puntos | Brevare');
    }
}
