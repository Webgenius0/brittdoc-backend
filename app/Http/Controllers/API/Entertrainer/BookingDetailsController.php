<?php

namespace App\Http\Controllers\API\Entertrainer;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingDetailsController extends Controller
{
    //Entertrainer home page all count
    public function CountTotal(Request $request)
    {
        $totalEvent = Event::count();
        $totalBooked = Booking::whereIn('event_id', Event::pluck('id'))
            ->where('status', 'booked')
            ->count();
        $totalCompleted = Booking::whereIn('event_id', Event::pluck('id'))
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'totalEvent' => $totalEvent,
            'totalBooked' => $totalBooked,
            'totalCompleted' => $totalCompleted,
        ]);
    }

    // all Entertainser Event
    public function bookingList(Request $request)
    {
        try {
            $status = $request->status ?? '';

            $EntertainerEvnetIds = Event::where('user_id', Auth::user()->id)->pluck('id');

            $allBooking_completed = Booking::whereIn('event_id',  $EntertainerEvnetIds)
                ->when($status, function ($q, $status) {
                    $q->where('status', $status);
                })
                ->with([
                    'rating',
                    'event' => function ($q) {
                        $q->select('id', 'name', 'category_id', 'about', 'image', 'price', 'location')->with('category:id,name,image,created_at');
                    }
                ])
                ->get();

            return Helper::jsonResponse(true, 'Event Booked data fetched successfully', 200, $allBooking_completed);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Event Booked data retrieval failed', 403, [$e->getMessage()]);
        }
    }


    // Event booking details 
    public function EventBookingDetials($id)
    {
        try {
            $BookedDetails = Booking::with(['event' => function ($q) {
                $q->select('id', 'category_id', 'name', 'image', 'about', 'start_date', 'ending_date')->with('category:id,name,image,created_at');
            }, 'user:id,name,avatar'])
                ->where('id', $id)
                ->with('rating')
                ->first();
            // dd($BookedDetails->toArray());
            if (!$BookedDetails) {
                return Helper::jsonErrorResponse('Event Booked Details Retrived Failed', 403);
            }

            //time culculation 
            $Time = Carbon::now();
            $createdAt = Carbon::parse($BookedDetails->created_at);
            // dd($createdAt);
            $startTime = Carbon::parse($BookedDetails->booking_start_time);
            $endTime = Carbon::parse($BookedDetails->booking_end_time);
            $hours = $startTime->diffInHours($endTime);
            $statusCheck = $this->applyTimeStatus($BookedDetails);

            return Helper::jsonResponse(true, 'Event Booked Details Successful', 200, $BookedDetails);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Event Booked Details Retrived Failed', 403, [$e->getMessage()]);
        }
    }

    //Entertainer Completed Detials get
    public function EventCompletedDetails($id)
    {
        try {
            $completed = Booking::with(['event' => function ($q) {
                $q->select('id', 'category_id', 'name', 'about', 'start_date', 'ending_date')->with(['category:id,name']);
            }, 'user:id,name,avatar'])
                ->where('status', 'completed')
                ->where('id', $id)
                ->with('rating')
                ->first();

            if (!$completed) {
                return Helper::jsonErrorResponse('Event Completed ID Retrived Failed', 403);
            }

            return Helper::jsonResponse(true, 'Completed Booking Details Successfull', 200, [
                'completed' => $completed,
            ]);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Event Completed Details Retrived Failed', 403, [$e->getMessage()]);
        }
    }


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

    //Entertainer section event  inprogress and upcomming
    public function InprogressUpcommings(Request $request)
    {
        $status = $request->query('status');
        $now = Carbon::now();

        $bookings = Booking::with(['event' => function ($q) {
            $q->select('id', 'category_id', 'about', 'name', 'start_date', 'ending_date')
                ->with('category:id,name');
        }, 'user:id,name,avatar', 'rating'])
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

            if (!$booking->event) return false;

            $EventStart = Carbon::parse($booking->event->start_date);
            $eventEnd = Carbon::parse($booking->event->ending_date);
            $createdAt = Carbon::parse($booking->created_at);

            if ($status === 'upcoming') {
                return $createdAt->lessThan($EventStart);
            } elseif ($status === 'inprogress') {
                return $now->between($EventStart, $eventEnd);
            }

            return false;
        })->values();

        return Helper::jsonResponse(true, 'Filtered Data Successful', 200, $filtered);
    }
}
