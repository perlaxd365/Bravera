<?php

namespace Tests\Feature\Legal;

use App\Livewire\Pages\Legal\Claims;
use App\Mail\ClaimConfirmationMail;
use App\Models\Claim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_pages_render_for_guests(): void
    {
        $this->get(route('terms'))->assertOk();
        $this->get(route('returns'))->assertOk();
        $this->get(route('privacy'))->assertOk();
        $this->get(route('claims'))->assertOk();
    }

    public function test_claim_can_be_submitted(): void
    {
        Mail::fake();

        Livewire::test(Claims::class)
            ->set('name', 'Juan Pérez')
            ->set('document_type', 'DNI')
            ->set('document_number', '12345678')
            ->set('email', 'juan@example.com')
            ->set('phone', '999999999')
            ->set('address', 'Av. Siempre Viva 123')
            ->set('claim_type', 'reclamo')
            ->set('claimed_good', 'Laptop')
            ->set('description', 'El producto llegó dañado.')
            ->set('request', 'Solicito el cambio del producto.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submittedCode', fn ($code) => is_string($code) && str_starts_with($code, 'REC-'));

        $this->assertDatabaseCount('claims', 1);
        $this->assertSame(Claim::STATUS_PENDING, Claim::first()->status);
        Mail::assertSent(ClaimConfirmationMail::class, function (ClaimConfirmationMail $mail) {
            $body = $mail->render();

            return $mail->hasTo('juan@example.com')
                && $mail->claim->code === Claim::first()->code
                && $mail->claim->description === 'El producto llegó dañado.'
                && $mail->claim->request === 'Solicito el cambio del producto.'
                && str_contains($body, $mail->claim->code)
                && str_contains($body, 'El producto llegó dañado.')
                && str_contains($body, 'Solicito el cambio del producto.');
        });
    }

    public function test_claim_requires_fields(): void
    {
        Livewire::test(Claims::class)
            ->call('submit')
            ->assertHasErrors([
                'name', 'document_number', 'email', 'phone', 'address',
                'claimed_good', 'description', 'request',
            ]);
    }
}
