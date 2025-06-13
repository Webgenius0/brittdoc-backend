<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\Weekday;
use App\Models\OffDay;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'name' => 'event 1',
                'location' => 'Los Angeles',
                'category_id' => 1,
                'price' => 100,
                'about' => 'A luxurious venue for events1',
                'start_date' => '2025-06-12',
                'ending_date' => '2025-07-01',
                'latitude' => 23.0522,
                'longitude' => 90.2437,
            ],
            [
                'name' => 'event 2',
                'location' => 'New York',
                'category_id' => 2,
                'price' => 100,
                'about' => 'Open garden suitable for weddings 2',
                'start_date' => '2025-06-01',
                'ending_date' => '2025-07-20',
                'latitude' => 24.0522,
                'longitude' => 91.2437,
            ],
            [
                'name' => 'event 3',
                'location' => 'Bogura',
                'category_id' => 3,
                'price' => 200,
                'about' => 'Banquet hall in downtown Chicago 3',
                'start_date' => '2025-06-01',
                'ending_date' => '2025-08-30',
                'latitude' => 25.0522,
                'longitude' => 92.2437,
            ],
            [
                'name' => 'event 4',
                'location' => 'Sirajgonj',
                'category_id' => 4,
                'price' => 200,
                'about' => 'Best for corporate conferences 4',
                'start_date' => '2025-06-01',
                'ending_date' => '2025-09-30',
                'latitude' => 26.0522,
                'longitude' => 93.2437,
            ],
            [
                'name' => 'event 5',
                'location' => 'Pabna',
                'category_id' => 1,
                'price' => 200,
                'about' => 'Beachside open event resort 5',
                'start_date' => '2025-06-01',
                'ending_date' => '2025-10-20',
                'latitude' => 27.0522,
                'longitude' => 94.2437,
            ],
            [
                'name' => 'event 6',
                'location' => 'Dhaka',
                'category_id' => 2,
                'price' => 100,
                'about' => 'Event 6',
                'start_date' => '2025-06-15',
                'ending_date' => '2025-10-20',
                'latitude' => 23.0522,
                'longitude' => 90.2437,
            ],
            [
                'name' => 'event 7',
                'location' => 'Dhaka',
                'category_id' => 2,
                'price' => 150,
                'about' => 'Event Green Field Resort 7',
                'start_date' => '2025-06-12',
                'ending_date' => '2025-10-20',
                'latitude' => 23.0522,
                'longitude' => 90.2437,
            ],
            [
                'name' => 'event 8',
                'location' => 'Dhaka',
                'category_id' => 4,
                'price' => 150,
                'about' => 'Event Green Field Resort 8',
                'start_date' => '2025-06-12',
                'ending_date' => '2025-10-20',
                'latitude' => 23.0522,
                'longitude' => 90.2437,
            ],
            [
                'name' => 'event 9',
                'location' => 'kishoregonj',
                'category_id' => 3,
                'price' => 150,
                'about' => 'Event Green Field Resort 9',
                'start_date' => '2025-06-12',
                'ending_date' => '2025-10-20',
                'latitude' => 23.0522,
                'longitude' => 90.2437,
            ],
            [
                'name' => 'event 10',
                'location' => 'new work',
                'category_id' => 1,
                'price' => 150,
                'about' => 'Event Green Field Resort 10',
                'start_date' => '2025-06-12',
                'ending_date' => '2025-10-20',
                'latitude' => 23.0522,
                'longitude' => 90.2437,
            ],
        ];

        $weekdays = [
            'saturday' => ['10:00', '12:00', true],
            'sunday' => ['10:00', '11:00', true],
            'monday' => ['09:00', '12:00', true],
            'tuesday' => ['09:00', '12:00', true],
            'wednesday' => ['09:00', '12:00', true],
            'thursday' => ['09:00', '12:00', true],
            'friday' => ['09:00', '10:00', false],
        ];

        $offDays = ["2025-06-28", "2025-06-29"];
        foreach ($events as $index => $data) {
            DB::beginTransaction();
            try {
                $event = Event::create([
                    'user_id' => 3,
                    'name' => $data['name'],
                    'location' => $data['location'],
                    'category_id' => $data['category_id'],
                    'price' => $data['price'],
                    'about' => $data['about'],
                    'start_date' => $data['start_date'],
                    'ending_date' => $data['ending_date'],
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'image' => null,
                ]);

                foreach ($weekdays as $day => [$start, $end, $active]) {
                    Weekday::create([
                        'event_id' => $event->id,
                        'weekday' => $day,
                        'available_start_time' => $start,
                        'available_end_time' => $end,
                        'is_active' => $active,
                    ]);
                }

                offDay::create([
                    'event_id' => $event->id,
                    'unavailable_date' => $offDays,
                ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
            }
        }
    }
}
