<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BookingStatusUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:booking-status-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $currentDate = $now->toDateString();
        $currentTime = $now->toTimeString();

        // Booked to Upcoming
        Booking::where('status', 'booked')
            ->whereDate('booking_date', '=', $currentDate)
            ->whereTime('booking_start_time', '<=', $now->copy()->addDay()->toTimeString())
            ->whereTime('booking_start_time', '>', $currentTime)
            ->update(['status' => 'upcoming']);

        // Upcoming to In-progress
        Booking::whereIn('status', ['booked', 'upcoming'])
            ->whereDate('booking_date', '=', $currentDate)
            ->whereTime('booking_start_time', '<=', $currentTime)
            ->whereTime('booking_end_time', '>=', $currentTime)
            ->update(['status' => 'in-progress']);

        // In-progress to Completed
        Booking::where('status', 'in-progress')
            ->where(function ($query) use ($currentDate, $currentTime) {
                $query->whereDate('booking_date', '<', $currentDate)
                    ->orWhere(function ($q) use ($currentDate, $currentTime) {
                        $q->whereDate('booking_date', '=', $currentDate)
                            ->whereTime('booking_end_time', '<', $currentTime);
                    });
            })
            ->update(['status' => 'completed']);

        $this->info('Booking statuses updated successfully.');
        Log::info("testing");
    }
}
