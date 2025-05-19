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
                'status' => 'required|in:booked,completed',
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
}
