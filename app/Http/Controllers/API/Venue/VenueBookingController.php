<?php

namespace App\Http\Controllers\API\Venue;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Rating;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VenueBookingController extends Controller
{
    //user venue bookig 
    public function BookingVenue(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'booking_date' => 'required|date|after_or_equal:today',
            ]);

            $venue = Venue::find($id);

            if (!$venue) {
                return response()->json(['message' => 'Venue Id Not found.'], 404);
            }
            //booking date check vaildate
            $startDate = Carbon::parse($venue->start_date)->toDateString();
            $endDate = Carbon::parse($venue->ending_date)->toDateString();

            $validator->after(function ($validator) use ($request, $startDate, $endDate) {
                $bookingDate = Carbon::parse($request->booking_date)->toDateString();

                if (!($bookingDate >= $startDate && $bookingDate <= $endDate)) {
                    $validator->errors()->add(
                        'booking_date',
                        "Booking date must be between $startDate and $endDate."
                    );
                }
            });

            if ($validator->fails()) {
                return Helper::jsonResponse(false, 'Booking Date not Available.', 422, $validator->errors());
            }

            $start = Carbon::parse($venue->available_start_time);
            $end = Carbon::parse($venue->available_end_time);

            if ($end->lt($start)) {
                [$start, $end] = [$end, $start];
            }
            $diffInMinutes = $start->diffInMinutes($end);
            $hours = (int) ceil($diffInMinutes / 60);
            // dd($hours);

            $platform_rate = $hours * 100;    // $100 per hours
            $fee_percentage = 17;
            $fee_amount = ($platform_rate * $fee_percentage) / 100;
            $net_amount = $platform_rate - $fee_amount;
            // dd($net_amount);

            //user
            $user = User::find(Auth::user()->id);
            $booking = Booking::create([
                'user_id' => Auth::user()->id,
                'venue_id' => $venue->id,
                'category' => $venue->category_id,        //venue table ar category_id
                'location' => $venue->location,         //event table ar location
                'name' => $user->name,                  //user table ar name
                'image' => $user->image,                 //user table ar image
                'booking_date' => $request->booking_date,
                'booking_start_time' => $venue->available_start_time,
                'booking_end_time' => $venue->available_end_time,
                'platform_rate' => $platform_rate,
                'fee_percentage' => $fee_percentage,
                'fee_amount' => $fee_amount,
                'net_amount' => $net_amount,
                'status' => 'pending'
            ]);
            // dd($booking);
            return Helper::jsonResponse(true, 'Venue Booking created successfully.', 200, $booking);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, ' Venue Booking creation failed.', 500, $e->getMessage());
        }
    }

    //venue home page all count
    public function CountTotal(Request $request)
    {
        $totalVenue = Venue::count();
        $totalBooked = Booking::whereIn('venue_id', Venue::pluck('id'))
            ->where('status', 'booked')
            ->count();
        $totalCompleted = Booking::whereIn('venue_id', Venue::pluck('id'))
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'totalVenue' => $totalVenue,
            'totalBooked' => $totalBooked,
            'totalCompleted' => $totalCompleted,
        ]);
    }


    // //booked and completed  homePage
    // public function bookingList(Request $request)
    // {
    //     try {
    //         $status = $request->status ?? '';
    //         $vanueHolderVenueIds = Venue::where('user_id', Auth::user()->id)->get()->pluck('id');
    //         $allBooking_completed = Booking::whereIn('venue_id',  $vanueHolderVenueIds)
    //             ->when($status, function ($q, $status) {
    //                 $q->where('status', $status);
    //             })
    //             ->with('rating')
    //             ->get();

    //         return Helper::jsonResponse(true, 'Venue Booked data fatched Successful', 200, $allBooking_completed);
    //     } catch (Exception $e) {
    //         return Helper::jsonErrorResponse('Venue Booked data Retrived Failed', 403, [$e->getMessage()]);
    //     }
    // }

    public function bookingList(Request $request)
    {
        try {
            $status = $request->status ?? '';
            $venueHolderVenueIds = Venue::where('user_id', Auth::user()->id)->pluck('id');
            
            $allBookingCompleted = Booking::whereIn('venue_id', $venueHolderVenueIds)
                ->when($status, function ($q, $status) {
                    $q->where('status', $status);
                })
                ->with([
                    'rating',
                    'venue' => function ($q) {
                        $q->select('id', 'name','description', 'location', 'image', 'price');
                    }
                ])
                                ->get();

            return Helper::jsonResponse(true, 'Venue booked data fetched successfully', 200, $allBookingCompleted);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Venue booked data retrieval failed', 403, [$e->getMessage()]);
        }
    }


    // venue booking details 
    public function VenueBookingDetials($id)
    {
        try {
            $BookedDetails = Booking::with(['venue' => function ($q) {
                $q->select('id', 'category_id', 'image','description', 'name', 'start_date', 'ending_date')->with(['category:id,name']);
            }, 'user:id,name,avatar'])
                ->where('id', $id)
                ->with('rating')
                ->first();
            // dd($BookedDetails->toArray());
            if (!$BookedDetails) {
                return Helper::jsonErrorResponse('Venue Booked Details Retrived Failed', 403);
            }

            //time culculation 
            $Time = Carbon::now();
            $createdAt = Carbon::parse($BookedDetails->created_at);
            // dd($createdAt);
            $startTime = Carbon::parse($BookedDetails->booking_start_time);
            $endTime = Carbon::parse($BookedDetails->booking_end_time);
            $hours = $startTime->diffInHours($endTime);
            $statusCheck = $this->applyTimeStatus($BookedDetails);

            return Helper::jsonResponse(true, 'Venue Booked Details Successful', 200, $BookedDetails);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Venue Booked Details Retrived Failed', 403, [$e->getMessage()]);
        }
    }

    //Venue Completed Detials get
    public function venueCompletedDetails($id)
    {
        try {
            $completed = Booking::with(['venue' => function ($q) {
                $q->select('id', 'category_id', 'name', 'description','image', 'start_date', 'ending_date')->with(['category:id,name']);
            }, 'user:id,name,avatar'])
                ->where('status', 'completed')
                ->where('id', $id)
                ->with('rating')
                ->first();

            if (!$completed) {
                return Helper::jsonErrorResponse('Venue Completed ID Retrived Failed', 403);
            }

            return Helper::jsonResponse(true, 'Completed Booking Details Successfull', 200, [
                'completed' => $completed,
            ]);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Venue Completed Details Retrived Failed', 403, [$e->getMessage()]);
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

    // Venue  inprogress and upcomming  
    public function InprogressUpcomming(Request $request)
    {
        $status = $request->query('status');
        $now = Carbon::now();

        $bookings = Booking::with(['venue' => function ($q) {
            $q->select('id', 'category_id', 'description', 'image', 'name', 'start_date', 'ending_date')
                ->with('category:id,name');
        }, 'user:id,name,avatar', 'rating'])
            ->where('status', 'booked')
            ->where('user_id', Auth::user()->id)
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

            if (!$booking->venue)                 return false;

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


    //Event all booked show  & search for upcoming and in-process  
    public function InprogressUpcomming1(Request $request)
    {
        $status = $request->query('status');
        $now = Carbon::now();

        $bookings = Booking::with(['event' => function ($q) {
            $q->select('id', 'category_id', 'image', 'about','name', 'start_date', 'ending_date')
                ->with('category:id,name');
        }, 'user:id,name,avatar', 'rating'])
            ->where('status', 'booked')
            ->where('user_id', Auth::user()->id)
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

            if (!$booking->event)                 return false;

            $eventStart = Carbon::parse($booking->event->start_date);
            $eventEnd = Carbon::parse($booking->event->ending_date);
            $createdAt = Carbon::parse($booking->created_at);

            if ($status === 'upcoming') {
                return $createdAt->lessThan($eventStart);
            } elseif ($status === 'inprogress') {
                return $now->between($eventStart, $eventEnd);
            }

            return false;
        })->values();

        return Helper::jsonResponse(true, 'Filtered Data Successful', 200, $filtered);
    }
}
