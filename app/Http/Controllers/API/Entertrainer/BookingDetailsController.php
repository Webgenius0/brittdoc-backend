<?php

namespace App\Http\Controllers\API\Entertrainer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use Illuminate\Http\Request;

class BookingDetailsController extends Controller
{
    //Entertrainer home page all count
    public function CountTotal(Request $request)
    {
        $totalEvent = Event::count();
        $totalBooked = Booking::whereIn('event_id', Event::pluck('id'))
            ->where('status', 'booked')
            ->count();
        $totalCompleted = Booking::whereIn('venue_id', Event::pluck('id'))
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'totalEvent' => $totalEvent,
            'totalBooked' => $totalBooked,
            'totalCompleted' => $totalCompleted,
        ]);
    }
}
