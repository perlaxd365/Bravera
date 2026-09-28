<?php

namespace Database\Seeders;

use App\Models\User as ModelsUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Buzon del Super Admin. Debe coincidir con la casilla real de la tienda:
     * si el seeder y la base de datos usan direcciones distintas, updateOrCreate
     * crea un segundo administrador en vez de actualizar el existente.
     */
    private const ADMIN_EMAIL = 'administracion@brevare.com';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ModelsUser::updateOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'name' => 'Administrador',
                'email' => self::ADMIN_EMAIL,
                // ATENCION: contraseña conocida que vive en el repositorio.
                // Debe cambiarse antes de publicar el panel.
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        )->assignRole('Super Admin');
    }
}
