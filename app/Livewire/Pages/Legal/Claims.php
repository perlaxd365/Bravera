<?php

namespace App\Livewire\Pages\Legal;

use App\Models\Claim;
use App\Mail\ClaimConfirmationMail;
use App\Services\ReliableMailer;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('store.layouts.app')]
class Claims extends Component
{
    public string $name = '';

    public string $document_type = 'DNI';

    public string $document_number = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public bool $is_minor = false;

    public string $guardian_name = '';

    public string $order_number = '';

    public string $claim_type = 'reclamo';

    public string $claimed_good = '';

    public string $description = '';

    public string $request = '';

    public ?string $submittedCode = null;

    public function mount(): void
    {
        if ($user = Auth::user()) {
            $this->name = $user->name ?? '';
            $this->email = $user->email ?? '';
        }
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'document_type' => ['required', 'in:DNI,CE,RUC,Pasaporte'],
            'document_number' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'is_minor' => ['boolean'],
            'guardian_name' => ['nullable', 'required_if:is_minor,true', 'string', 'max:150'],
            'order_number' => ['nullable', 'string', 'max:100'],
            'claim_type' => ['required', 'in:reclamo,queja'],
            'claimed_good' => ['required', 'string', 'max:2000'],
            'description' => ['required', 'string', 'max:5000'],
            'request' => ['required', 'string', 'max:5000'],
        ]);

        $validated['code'] = Claim::nextCode();
        $validated['status'] = Claim::STATUS_PENDING;

        $claim = Claim::create($validated);

        $emailSent = ReliableMailer::send(
            [$claim->email],
            new ClaimConfirmationMail($claim),
            'confirmación de reclamo',
            ['claim_code' => $claim->code],
        );

        $this->submittedCode = $claim->code;

        $this->reset([
            'document_number', 'phone', 'address', 'is_minor', 'guardian_name',
            'order_number', 'claimed_good', 'description', 'request',
        ]);

        $this->claim_type = Claim::TYPE_RECLAMO;

        $message = $emailSent
            ? 'Tu reclamación fue registrada y enviamos una confirmación a tu correo.'
            : 'Tu reclamación fue registrada, pero no pudimos enviar el correo de confirmación. Guarda tu código: '.$claim->code;

        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function render()
    {
        return view('livewire.pages.legal.claims');
    }
}
