<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemSetting::create([
            'title' => 'Brittdoc',
            'system_name' => 'Brittdoc',
            'email' => 'example@email.com',
            'contact_number' => '+88084936789',
            'company_open_hour' => '24 HOUR',
            'copyright_text' => '© Copyright 2023, All right reserved',
            'logo' => 'uploads/logos/logo.png',
            'favicon' => 'uploads/favicons/favicon.png',
            'address' => 'TEST',
            'description' => 'TEST',
        ]);
    }
}
