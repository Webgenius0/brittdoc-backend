<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlatformSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PlatformSetting::create([
            'key' => 'vat',
            'value' => 15.00,
        ]);
    }
}
