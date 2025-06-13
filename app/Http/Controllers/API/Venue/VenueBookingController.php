<?php

namespace App\Http\Controllers\API\Venue;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Rating;
use App\Models\User;
use App\Models\Venue;
use App\Models\Weekday;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VenueBookingController extends Controller
{

    // Venue Booking
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

            // Check if venue is already booked on the requested date
            $existingBooking = Booking::where('venue_id', $id)
                ->whereDate('booking_date', Carbon::parse($request->booking_date)->toDateString())
                ->first();

            if ($existingBooking) {
                return Helper::jsonResponse(false, 'This venue is already booked on this date.', 422);
            }

            // Validate booking date range
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

            // Time & pricing calculations
            $start = Carbon::parse($venue->available_start_time);
            $end = Carbon::parse($venue->available_end_time);

            if ($end->lt($start)) {
                [$start, $end] = [$end, $start];
            }

            $diffInMinutes = $start->diffInMinutes($end);
            $hours = (int) ceil($diffInMinutes / 60);

            $platform_rate = $hours * 100; // $100 per hour
            $fee_percentage = 17;
            $fee_amount = ($platform_rate * $fee_percentage) / 100;
            $net_amount = $platform_rate - $fee_amount;

            $user = Auth::user();
            $booking = Booking::create([
                'user_id' => $user->id,
                'venue_id' => $venue->id,
                'category' => $venue->category_id,
                'location' => $venue->location,
                'name' => $user->name,
                'image' => $user->image,
                'booking_date' => $request->booking_date,
                'booking_start_time' => $venue->available_start_time,
                'booking_end_time' => $venue->available_end_time,
                'platform_rate' => $platform_rate,
                'fee_percentage' => $fee_percentage,
                'fee_amount' => $fee_amount,
                'net_amount' => $net_amount,
                'status' => 'pending'
            ]);

            return Helper::jsonResponse(true, 'Venue booking created successfully.', 200, $booking);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, 'Venue booking creation failed.', 500, $e->getMessage());
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

    // booking list
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
                        $q->select('id', 'name', 'description', 'location', 'image', 'price');
                    }
                ])
                ->get();
            $allBookingCompleted->makeHidden("event_id");

            return Helper::jsonResponse(true, 'Venue booked data fetched successfully', 200, $allBookingCompleted);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Venue booked data retrieval failed', 403, [$e->getMessage()]);
        }
    }

    //single booking details
    public function VenueBookingDetials($id)
    {
        try {
            $userId = Auth::id();
            $BookedDetails = Booking::with([
                'venue' => function ($q) {
                    $q->select('id', 'category_id', 'image', 'description', 'name', 'start_date', 'ending_date')
                        ->with('category:id,name');
                },
                'user:id,name,avatar',
                'rating'
            ])->where('id', $id)
                ->whereHas('venue', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })->first();

            // Check if booking exists
            if (!$BookedDetails) {
                return Helper::jsonErrorResponse('Venue Booked Details Retrived Failed', 403);
            }

            // Hide unrelated id
            if (!empty($BookedDetails->venue_id)) {
                $BookedDetails->makeHidden('event_id');
            } elseif (!empty($BookedDetails->event_id)) {
                $BookedDetails->makeHidden('venue_id');
            }

            // Optional: Time status calculation if needed
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
                $q->select('id', 'category_id', 'name', 'description', 'image', 'start_date', 'ending_date')->with(['category:id,name']);
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
            $q->select('id', 'category_id', 'image', 'about', 'name', 'start_date', 'ending_date')
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

    //====================client uupdate ============================
    public function venueBooking(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'booking_date' => 'required|date|after_or_equal:today',
            ]);

            $venue = Venue::find($id);
            if (!$venue) {
                return response()->json(['message' => 'venue Id Not found.'], 404);
            }

            // Check for existing booking on the same date
            $bookingDate = Carbon::parse($request->booking_date)->toDateString();
            $existingBooking = Booking::where('venue_id', $id)
                ->whereDate('booking_date', $bookingDate)
                ->first();

            if ($existingBooking) {
                return Helper::jsonResponse(false, 'This venue is already booked on this date.', 404);
            }

            // Validate booking date within venue range
            $startDate = Carbon::parse($venue->start_date)->toDateString();
            $endDate = Carbon::parse($venue->ending_date)->toDateString();

            $validator->after(function ($validator) use ($bookingDate, $startDate, $endDate) {
                if (!($bookingDate >= $startDate && $bookingDate <= $endDate)) {
                    $validator->errors()->add('booking_date', "Booking date must be between $startDate and $endDate.");
                }
            });

            if ($validator->fails()) {
                return Helper::jsonResponse(false, 'Booking Date not Available.', 422, $validator->errors());
            }

            //time insert 
            $dayName = strtolower(Carbon::parse($bookingDate)->format('l'));
            $weekday = Weekday::where('venue_id', $id)->where('weekday', $dayName)->first();

            if (!$weekday) {
                return Helper::jsonResponse(false, 'This venue is not available on this day.', 422);
            }

            $start = Carbon::parse($weekday->available_start_time);
            $end = Carbon::parse($weekday->available_end_time);

            if ($end->lt($start)) {
                [$start, $end] = [$end, $start];
            }

            $diffInMinutes = $start->diffInMinutes($end);
            $hours = (int) ceil($diffInMinutes / 60);

            // dd($hours);
            $platform_rate = $hours * 100;
            $fee_percentage = 17;
            $fee_amount = ($platform_rate * $fee_percentage) / 100;
            $net_amount = $platform_rate - $fee_amount;

            $user = Auth::user();

            $booking = Booking::create([
                'user_id'             => $user->id,
                'venue_id'            => $venue->id,
                'category'            => $venue->category_id,
                'location'            => $venue->location,
                'name'                => $user->name,
                'image'               => $user->image,
                'booking_date'        => $bookingDate,
                'booking_start_time'  => $weekday->available_start_time,
                'booking_end_time'    => $weekday->available_end_time,
                'platform_rate'       => $platform_rate,
                'fee_percentage'      => $fee_percentage,
                'fee_amount'          => $fee_amount,
                'net_amount'          => $net_amount,
                'status'              => 'pending'
            ]);

            return Helper::jsonResponse(true, 'venue booking created successfully.', 200, $booking);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, 'venue booking creation failed.', 500, $e->getMessage());
        }
    }
}
