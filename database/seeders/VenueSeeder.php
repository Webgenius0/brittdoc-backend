<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VenueSeeder extends Seeder
{
    public function run(): void
    {
        $venues = [
            [
                'user_id' => 4,
                'name' => 'Grand Ballroom',
                'category_id' => 5, // Banquet Hall category (venue_holder)
                'description' => 'Spacious ballroom suitable for weddings and large events.',
                'location' => '123 Main St, Cityville',
                'capacity' => 300,
                'price' => 100.00,
                'start_date' => '2025-06-01',
                'ending_date' => '2025-12-31',
                'available_start_time' => '08:00:00',
                'available_end_time' => '23:00:00',

                'latitude' => 40.712776,
                'longitude' => -74.005974,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 4,
                'name' => 'Downtown Conference Center',
                'category_id' => 6, 
                'description' => 'Modern conference center with multiple meeting rooms.',
                'location' => '456 Market St, Cityville',
                'capacity' => 150,
                'price' => 100.00,
                'start_date' => '2025-04-15',
                'ending_date' => '2025-12-31',
                'available_start_time' => '07:00:00',
                'available_end_time' => '21:00:00',

                'latitude' => 40.758896,
                'longitude' => -73.985130,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 4,
                'name' => 'Open Air Garden',
                'category_id' => 7, 
                'description' => 'Beautiful garden venue perfect for outdoor ceremonies.',
                'location' => '789 Garden Ave, Cityville',
                'capacity' => 200,
                'price' =>300.00,
                'start_date' => '2025-05-01',
                'ending_date' => '2025-10-31',
                'available_start_time' => '09:00:00',
                'available_end_time' => '20:00:00',

                'latitude' => 40.730610,
                'longitude' => -73.935242,
                'status' => 'inactive',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 4,
                'name' => 'City Hall Auditorium',
                'category_id' => 5, 
                'description' => 'Large auditorium suitable for performances and conferences.',
                'location' => '101 City Hall Rd, Cityville',
                'capacity' => 500,
                'price' => 100.00,
                'start_date' => '2025-01-01',
                'ending_date' => '2025-12-31',
                'available_start_time' => '10:00:00',
                'available_end_time' => '22:00:00',

                'latitude' => 40.712217,
                'longitude' => -74.016058,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 4,
                'name' => 'Rooftop Terrace',
                'category_id' => 7, 
                'description' => 'Open rooftop venue with stunning city views.',
                'location' => '202 Skyline Blvd, Cityville',
                'capacity' => 100,
                'price' => 200.00,
                'start_date' => '2025-03-01',
                'ending_date' => '2025-12-31',
                'available_start_time' => '16:00:00',
                'available_end_time' => '23:59:59',

                'latitude' => 40.741895,
                'longitude' => -73.989308,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('venues')->insert($venues);
    }
}
