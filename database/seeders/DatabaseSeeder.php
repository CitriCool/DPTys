<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(ReglasSeeder::class);

        // Datos de prueba ficticios: desactivar con DPTYS_SEED_DEMO=false
        if (filter_var(env('DPTYS_SEED_DEMO', true), FILTER_VALIDATE_BOOLEAN)) {
            $this->call(DemoSeeder::class);
        }
    }
}
