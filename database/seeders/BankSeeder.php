<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Bank::updateOrCreate(['name' => 'Banco de Venezuela']);
        Bank::updateOrCreate(['name' => 'Banco Nacional de Crédito (BNC)']);
    }
}