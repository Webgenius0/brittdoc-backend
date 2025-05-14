<?php

namespace App\Http\Controllers\API\Venue;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
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


    //booked and completed  homePage
    public function bookingList(Request $request)
    {
        try {
            $status = $request->status ?? '';
            $vanueHolderVenueIds = Venue::where('user_id', Auth::user()->id)->get()->pluck('id');
            $allBooking_completed = Booking::whereIn('venue_id',  $vanueHolderVenueIds)
                ->when($status, function ($q, $status) {
                    $q->where('status', $status);
                })
                ->with('rating')
                ->get();

            return Helper::jsonResponse(true, 'Venue Booked data fatched Successful', 200, $allBooking_completed);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Venue Booked data Retrived Failed', 403, [$e->getMessage()]);
        }
    }

    // venue booking details 
    public function VenueBookingDetials($id)
    {
        try {
            $BookedDetails = Booking::with(['venue' => function ($q) {
                $q->select('id', 'category_id', 'name', 'start_date', 'ending_date')->with(['category:id,name']);
            }, 'user:id,name,avatar'])
                ->where('status', 'booked')
                ->where('id', $id)
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
                $q->select('id', 'category_id', 'name', 'start_date', 'ending_date')->with(['category:id,name']);
            }, 'user:id,name,avatar'])
                ->where('status', 'completed')
                ->where('id', $id)
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

    private function applyTimeStatus($booking)
    {
        $now = Carbon::now();
        $start = Carbon::parse($booking->booking_date . $booking->booking_start_time);
        $end = Carbon::parse($booking->booking_date . $booking->booking_end_time);
        // dd($start .' '. $end);
        $booking->is_critical = false;
        $booking->is_expired = false;
        $booking->is_running = false;

        if ($now->lt($start)) {
            // Before start time
            $diffInMinutes = $now->diffInMinutes($start);
            $diffInDays = floor($diffInMinutes / (60 * 24));
            $hours = floor(($diffInMinutes % (60 * 24)) / 60);
            $minutes = $diffInMinutes % 60;

            if ($diffInMinutes <= 10) {
                $booking->is_critical = true;
            }

            $status = '';
            if ($diffInDays > 0) {
                $status .= "{$diffInDays} Day(s) ";
            }
            if ($hours > 0) {
                $status .= "{$hours} Hour(s) ";
            }
            $status .= "{$minutes} Minute(s) Left To Start";

            $booking->booking_status = $status;
        } elseif ($now->between($start, $end)) {
            // Running
            $diffInMinutes = $now->diffInMinutes($end);
            $diffInDays = floor($diffInMinutes / (60 * 24));
            $hours = floor(($diffInMinutes % (60 * 24)) / 60);
            $minutes = $diffInMinutes % 60;

            $booking->is_running = true;

            if ($diffInMinutes <= 10) {
                $booking->is_critical = true;
            }

            $status = '';
            if ($diffInDays > 0) {
                $status .= "{$diffInDays} Day(s) ";
            }
            if ($hours > 0) {
                $status .= "{$hours} Hour(s) ";
            }
            $status .= "{$minutes} Minute(s) Left To End";

            $booking->booking_status = $status;
        } else {
            // After end time
            $booking->is_expired = true;
            $booking->booking_status = "Time Ended and Complted";
        }

        return $booking;
    }
    
   
}


















