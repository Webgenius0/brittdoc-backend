<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\City;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'User',
            'email' => 'user@user.com',
            'password' => Hash::make('12345678'),
            'role' => 'user',
        ]);
        User::factory()->create([
            'name' => 'Entertainer',
            'email' => 'entertainer@entertainer.com',
            'password' => Hash::make('12345678'),
            'role' => 'entertainer',
        ]);
        User::factory()->create([
            'name' => 'Venue Holder',
            'email' => 'venueholder@venueholder.com',
            'password' => Hash::make('12345678'),
            'role' => 'venue_holder',
        ]);

        $this->call(SystemSettingSeeder::class);
        $this->call(FaqSeeder::class);
        $this->call(RestrictedWordsTableSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(EventSeeder::class);
        $this->call(VenueSeeder::class);
        $this->call(BookingSeeder::class);
        $this->call([PlaningSeeder::class]);
    }
}
