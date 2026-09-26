<?php

namespace App\Models;

use App\Notifications\ResetPasswordLinkNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone', 'avatar', 'provider', 'provider_id', 'last_login_at', 'email_verified_at'])]
#[Hidden(['password', 'remember_token', 'email_verification_code'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Roles que tienen acceso al panel administrativo y a la
     * información interna (p. ej. envíos a proveedores).
     */
    public const STAFF_ROLES = [
        'Super Admin',
        'Administrador',
        'Operador',
        'Marketing',
        'Atención al Cliente',
    ];

    /**
     * Indica si el usuario pertenece al staff de la tienda.
     */
    public function isStaff(): bool
    {
        return $this->hasAnyRole(self::STAFF_ROLES);
    }

    /**
     * Los clientes que creen su cuenta en la tienda
     * reciben el rol Cliente automáticamente.
     */
    public function assignCustomerRole(): void
    {
        $roleClass = config('permission.models.role');

        $customer = ($roleClass)::findOrCreate('Cliente');

        $this->assignRole($customer);
    }

    /**
     * Indica si la cuenta proviene de un proveedor OAuth (Google, etc.).
     */
    public function hasProvider(): bool
    {
        return ! is_null($this->provider);
    }

    /**
     * El usuario necesita crear una contraseña cuando su cuenta OAuth
     * aún no tiene una (el proveedor nunca entrega la contraseña).
     */
    public function needsPasswordSetup(): bool
    {
        return is_null($this->password);
    }

    /**
     * Marca la fecha del último inicio de sesión.
     */
    public function registerLogin(): void
    {
        $this->forceFill(['last_login_at' => now()])->saveQuietly();
    }

    /**
     * Envía el correo de restablecimiento de contraseña por la cola,
     * para que el envío no falle el request si el SMTP está lento.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordLinkNotification($token));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_code_expires_at' => 'datetime',
            'email_verification_code_sent_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function customerAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
