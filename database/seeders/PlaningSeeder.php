<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlaningSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('planings')->insert([

            [
                'title' => 'lifetime plan',
                'description' => 'Perfect for professionals.',
                'image' => '',
                'price' => 10.00,
                'billing_cycle' => 'lifetime',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Monthly Plan',
                'image' => '',
                'description' => 'This is a great plan for beginners.',
                'price' => 10.00,
                'billing_cycle' => 'monthly',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
