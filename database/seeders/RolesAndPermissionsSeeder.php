<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'Super Admin']);
        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Operador']);
        Role::firstOrCreate(['name' => 'Marketing']);
        Role::firstOrCreate(['name' => 'Atención al Cliente']);
        Role::firstOrCreate(['name' => 'Cliente']);
    }
}
