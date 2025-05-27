<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        // Sample events data
        $events = [
            [
                'user_id' => 3,
                'name' => 'Summer Music Festival',
                'location' => 'Central Park',
                'category_id' => 1, // assuming Music category from your categories
                'price' => 200.00,
                'about' => 'An open-air music festival featuring top bands.',
                'start_date' => '2025-05-20',
                'ending_date' => '2025-05-31',
                'available_start_time' => '14:00:00',
                'available_end_time' => '23:00:00',
                //image null
                'latitude' => 40.785091,
                'longitude' => -73.968285,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 3,
                'name' => 'Business Conference 2025',
                'location' => 'Grand Convention Center',
                'category_id' => 2, // Conference Room category
                'price' => 100.00,
                'about' => 'Annual business conference with keynote speakers.',
                'start_date' => '2025-05-27',
                'ending_date' => '2025-05-31',
                'available_start_time' => '09:00:00',
                'available_end_time' => '18:00:00',
                //image null
                'latitude' => 34.052235,
                'longitude' => -118.243683,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 3,
                'name' => 'Comedy Night',
                'location' => 'Downtown Theater',
                'category_id' => 3, // Comedy category
                'price' => 100.00,
                'about' => 'A night filled with laughs and top comedians.',
                'start_date' => '2025-05-21',
                'ending_date' => '2025-05-30',
                'available_start_time' => '20:00:00',
                'available_end_time' => '23:30:00',
                //image null
                'latitude' => 41.878113,
                'longitude' => -87.629799,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 3,
                'name' => 'Photography Workshop',
                'location' => 'Art Studio',
                'category_id' => 4, // Photography category
                'price' => 150.00,
                'about' => 'Hands-on photography workshop for beginners.',
                'start_date' => '2025-05-20',
                'ending_date' => '2025-06-30',
                'available_start_time' => '10:00:00',
                'available_end_time' => '16:00:00',
                //image null
                'latitude' => 37.774929,
                'longitude' => -122.419416,
                'status' => 'inactive',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 3,
                'name' => 'Dance Gala',
                'location' => 'City Hall Auditorium',
                'category_id' => 2, // Dance category
                'price' => 120.00,
                'about' => 'An evening showcasing diverse dance performances.',
                'start_date' => '2025-05-27',
                'ending_date' => '2025-05-30',
                'available_start_time' => '19:00:00',
                'available_end_time' => '22:00:00',
                //image null
                'latitude' => 51.507351,
                'longitude' => -0.127758,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('events')->insert($events);
    }
}
