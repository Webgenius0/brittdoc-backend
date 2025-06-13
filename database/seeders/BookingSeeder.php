<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use App\Models\Weekday;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::find(2);

        //event booking
        $eventIds = range(1, 15);
        foreach ($eventIds as $eventId) {
            $event = Event::find($eventId);
            if (!$event) {
                continue;
            }

            $startDate = Carbon::parse($event->start_date);
            $endDate = Carbon::parse($event->ending_date);

            $totalDays = $startDate->diffInDays($endDate);
            $bookingDate = $startDate->copy()->addDays(rand(0, $totalDays))->toDateString();

            if (Booking::where('event_id', $eventId)->where('booking_date', $bookingDate)->exists()) {
                continue;
            }

            // Get weekday info
            $dayName = strtolower(Carbon::parse($bookingDate)->format('l'));
            $weekday = $event->weekdays()->where('weekday', $dayName)->first();

            if (!$weekday || !$weekday->is_active) {
                continue;
            }

            $start = Carbon::parse($weekday->available_start_time);
            $end = Carbon::parse($weekday->available_end_time);
            $hours = ceil($start->diffInMinutes($end) / 60);

            $platformRate = $hours * 100;
            $feePercentage = 17;
            $feeAmount = ($platformRate * $feePercentage) / 100;
            $netAmount = $platformRate - $feeAmount;
            $statuses = ['pending', 'booked', 'upcoming', 'in-progress', 'completed'];

            Booking::create([
                'user_id'            => $user->id,
                'event_id'           => $eventId,
                'location'           => $event->location,
                'name'               => $user->name,
                'booking_date'       => $bookingDate,
                'booking_start_time' => $weekday->available_start_time,
                'booking_end_time'   => $weekday->available_end_time,
                'platform_rate'      => $platformRate,
                'fee_percentage'     => $feePercentage,
                'fee_amount'         => $feeAmount,
                'net_amount'         => $netAmount,
                'status'             => $statuses[array_rand($statuses)],
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }

        //venue Booking
        $venueIds = range(1, 10);
        foreach ($venueIds as $venueId) {
            $venue = Venue::find($venueId);
            if (!$venue) {
                continue;
            }

            $startDate = Carbon::parse($venue->start_date);
            $endDate = Carbon::parse($venue->ending_date);

            $totalDays = $startDate->diffInDays($endDate);
            $bookingDate = $startDate->copy()->addDays(rand(0, $totalDays))->toDateString();

            if (Booking::where('venue_id', $venueId)->where('booking_date', $bookingDate)->exists()) {
                continue;
            }

            // Get weekday info
            $dayName = strtolower(Carbon::parse($bookingDate)->format('l'));
            $weekday = $venue->weekdays()->where('weekday', $dayName)->first();

            if (!$weekday || !$weekday->is_active) {
                continue;
            }

            $start = Carbon::parse($weekday->available_start_time);
            $end = Carbon::parse($weekday->available_end_time);
            $hours = ceil($start->diffInMinutes($end) / 60);

            $platformRate = $hours * 100;
            $feePercentage = 17;
            $feeAmount = ($platformRate * $feePercentage) / 100;
            $netAmount = $platformRate - $feeAmount;
            $statuses = ['pending', 'booked', 'upcoming', 'in-progress', 'completed'];

            Booking::create([
                'user_id'            => $user->id,
                'venue_id'           => $venueId,
                'location'           => $venue->location,
                'name'               => $user->name,
                'booking_date'       => $bookingDate,
                'booking_start_time' => $weekday->available_start_time,
                'booking_end_time'   => $weekday->available_end_time,
                'platform_rate'      => $platformRate,
                'fee_percentage'     => $feePercentage,
                'fee_amount'         => $feeAmount,
                'net_amount'         => $netAmount,
                'status'             => $statuses[array_rand($statuses)],
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }
}
