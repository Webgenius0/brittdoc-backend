<?php

namespace App\Http\Controllers\API\Entertrainer;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{

    //user Entertainer  bookig 
    public function BookingEntertainer(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'booking_date' => 'required|date|after_or_equal:today',
            ]);

            $event = Event::find($id);

            if (!$event) {
                return response()->json(['message' => 'Event Id Not found.'], 404);
            }
            //booking date check vaildate
            $startDate = Carbon::parse($event->start_date)->toDateString();
            $endDate = Carbon::parse($event->ending_date)->toDateString();

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

            $start = Carbon::parse($event->available_start_time);
            $end = Carbon::parse($event->available_end_time);

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
                'event_id' => $event->id,
                'category' => $event->category_id,        //event table ar category_id
                'location' => $event->location,         //event table ar location
                'name' => $user->name,                  //user table ar name
                'image' => $user->image,                 //user table ar image
                'booking_date' => $request->booking_date,
                'booking_start_time' => $event->available_start_time,
                'booking_end_time' => $event->available_end_time,
                'platform_rate' => $platform_rate,
                'fee_percentage' => $fee_percentage,
                'fee_amount' => $fee_amount,
                'net_amount' => $net_amount,
                'status' => 'pending'
            ]);
            // dd($booking);
            return Helper::jsonResponse(true, 'event Booking created successfully.', 200, $booking);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, ' event Booking creation failed.', 500, $e->getMessage());
        }
    }

    public function status(Request $request)
    {
        try {
            $request->validate([
                'booking_id' => 'required|exists:bookings,id',
                'status' => 'required|in:pending,upcoming,in-progress,booked,completed,cancelled',
            ]);
            $booking = Booking::find($request->booking_id);

            // Update the status
            $booking->status = $request->status;
            $booking->save();

            return response()->json([
                'message' => 'Booking status updated successfully.',
                'booking' => $booking,
            ]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }


    //user section booking list
    public function allBookingList(Request $request)
    {
        try {
            $status = $request->query('status', '');
            $userId = Auth::id();

            if (!$userId) {
                return Helper::jsonResponse(false, 'User not authenticated.', 401);
            }

            $query = Booking::where('user_id', $userId)
                ->with(['user:id,name,avatar', 'event', 'event.category:id,name,image', 'venue', 'venue.category:id,name,image']);

            if (!empty($status)) {
                $query->where('status', $status);
            }

            $bookings = $query->get();

            return Helper::jsonResponse(true, 'Booking list retrieved successfully.', 200, $bookings);
        } catch (Exception $e) {
            Log::error('Error retrieving booking list: ' . $e->getMessage());
            return Helper::jsonResponse(false, 'Failed to retrieve booking list.', 500, $e->getMessage());
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



    //user section single booking details
    public function BookingDetials($id)
    {
        try {
            $BookedDetails = Booking::with([
                'event' => function ($q) {
                    $q->select('id', 'category_id', 'name', 'image', 'about', 'start_date', 'ending_date')
                        ->with('category:id,name,image,created_at');
                },
                'venue' => function ($q) {
                    $q->select('id', 'category_id', 'name', 'location', 'price', 'description', 'start_date', 'ending_date', 'image')
                        ->with('category:id,name,image,created_at');
                },
                'user:id,name,avatar',
                'rating'
            ])
                ->where('id', $id)
                ->first();

            if (!$BookedDetails) {
                return Helper::jsonErrorResponse('Event/Venue Booked Details Retrived Failed', 403);
            }

            $startTime = Carbon::parse($BookedDetails->booking_start_time);
            $endTime = Carbon::parse($BookedDetails->booking_end_time);
            $hours = $startTime->diffInHours($endTime);

            $statusCheck = $this->applyTimeStatus($BookedDetails);

            return Helper::jsonResponse(true, 'Event/Venue Booked Details Successful', 200, $BookedDetails);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Event/Venue Booked Details Retrived Failed', 403, [$e->getMessage()]);
        }
    }
}
