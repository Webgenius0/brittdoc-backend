<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'DJs', 'type' => 'entertainer', 'status' => 'active'],
            ['name' => 'Artist', 'type' => 'entertainer', 'status' => 'active'],
            ['name' => 'Comedy', 'type' => 'entertainer', 'status' => 'active'],
            ['name' => 'Commdian', 'type' => 'entertainer', 'status' => 'inactive'],
            ['name' => 'wedding', 'type' => 'venue_holder', 'status' => 'active'],
            ['name' => 'Banquet Hall', 'type' => 'venue_holder', 'status' => 'active'],
            ['name' => 'Conference Room', 'type' => 'venue_holder', 'status' => 'inactive'],
            ['name' => 'Stage Setup', 'type' => 'venue_holder', 'status' => 'active'],
        ];

        DB::table('categories')->insert($categories);
    }
}
