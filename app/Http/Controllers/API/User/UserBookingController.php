<?php

namespace App\Http\Controllers\API\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserBookingController extends Controller
{
    //apply time helper function 
    private function applyTimeStatus($booking)
    {
        $now = Carbon::now();
        $start = Carbon::parse($booking->booking_date . ' ' . date('H:i:s', strtotime($booking->booking_start_time)));
        $end = Carbon::parse($booking->booking_date . ' ' . date('H:i:s', strtotime($booking->booking_end_time)));

        $booking->is_critical = false;
        $booking->is_expired = false;
        $booking->is_running = false;

        if ($now->lt($start)) {
            $diffInMinutes = $now->diffInMinutes($start);
            $formattedTime = Carbon::createFromTime(0, 0, 0)->addMinutes($diffInMinutes)->format('D H:i:s');

            if ($diffInMinutes <= 10) {
                $booking->is_critical = true;
            }

            $booking->booking_status = "{$formattedTime} Left To Start";
        } elseif ($now->between($start, $end)) {
            $diffInMinutes = $now->diffInMinutes($end);

            $formattedTime = Carbon::createFromTime(0, 0, 0)->addMinutes($diffInMinutes)->format('D H:i:s');

            $booking->is_running = true;

            if ($diffInMinutes <= 10) {
                $booking->is_critical = true;
            }
            $booking->booking_status = "{$formattedTime} Left To End";
        } else {
            $booking->is_expired = true;
            $booking->booking_status = "Time Ended and Completed";
        }

        return $booking;
    }



    //all booked venue show  & search for upcoming and in-process  
    public function InprogressUpcomming(Request $request)
    {
        $status = $request->query('status');
        $now = Carbon::now();
        $venueHolder = Venue::where('user_id', Auth::user()->id)->get()->pluck('id');
        $bookings = Booking::with([
            'venue' => function ($q) {
                $q->select('id', 'user_id', 'category_id', 'image', 'name', 'start_date', 'ending_date')
                    ->where('user_id', Auth::user()->id)
                    ->with('category:id,name');
            },
            'user:id,name,avatar',
            'rating'
        ])
            ->whereIn('venue_id', $venueHolder)
            ->where('status', 'booked')
            ->get();

        //  status query 
        if (!$status) {
            $bookings->each(function ($booking) {
                $this->applyTimeStatus($booking);
            });

            return Helper::jsonResponse(true, 'All Booked Data Returned', 200, $bookings);
        }
        //status 
        $filtered = $bookings->filter(function ($booking) use ($status, $now) {
            $this->applyTimeStatus($booking);

            if (!$booking->venue)
                return false;

            $venueStart = Carbon::parse($booking->venue->start_date);
            $venueEnd = Carbon::parse($booking->venue->ending_date);
            $createdAt = Carbon::parse($booking->created_at);

            if ($status === 'upcoming') {
                return $createdAt->lessThan($venueStart);
            } elseif ($status === 'inprogress') {
                return $now->between($venueStart, $venueEnd);
            }

            return false;
        })->values();

        return Helper::jsonResponse(true, 'Filtered Data Successful', 200, $filtered);
    }
}
