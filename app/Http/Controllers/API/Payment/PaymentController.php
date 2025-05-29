<?php

namespace App\Http\Controllers\API\Payment;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Rating;
use App\Models\Venue;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    //booking payment 
    public function store(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,completed,cancelled',
            ]);

            $booking = Booking::find($id);
            if (!$booking) {
                return response()->json([
                    'message' => 'Booking not found.',
                ], 404);
            }

            $payment = Payment::create([
                'user_id' => Auth::id(),
                'booking_id' => $booking->id,
                'status' => $request->input('status'),
            ]);

            if ($payment->status === 'pending') {
                $booking->status = 'booked';
                $booking->save();
            }

            return response()->json([
                'message' => 'Payment stored successfully.',
                'payment' => $payment,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    //uesr section After Pay Screen
    public function AfterPayScreen($id)
    {
        try {
            $booking = Booking::with('rating', 'venue')->find($id);

            if (!$booking) {
                return response()->json([
                    "success" => false,
                    "message" => "Booking not found"
                ]);
            }

            $venue = $booking->venue;
            if (!$venue || $venue->status !== 'active') {
                return response()->json([
                    "success" => false,
                    "message" => "Venue not found or inactive"
                ]);
            }

            // Calculate platform rate
            $start = Carbon::parse($venue->available_start_time);
            $end = Carbon::parse($venue->available_end_time);
            $hours = (int) ceil($start->floatDiffInHours($end));
            $platform_rate = $hours * $venue->price;

            return response()->json([
                "success" => true,
                "message" => "After payment screen data retrieved successfully",
                "platform_rate" => $platform_rate,
                "name" => $venue->name,
                "image" => $venue->image,
                "location" => $venue->location,
                "booking_start_time" => $booking->booking_start_time,
                "booking_end_time" => $booking->booking_end_time,
                "rating_id" => $booking->rating->id ?? null,
                "rating" => $booking->rating->rating ?? null,
            ]);
        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Error retrieving after payment screen data",
                "error" => $e->getMessage()
            ], 500);
        }
    }



    //uesr section After Pay Screen Entertainer
    public function AfterPayScreenEntertainer($id)
    {
        try {
            $booking = Booking::with(['rating', 'user:id,name,avatar', 'event:id,category_id', 'event.category:id,name',])
                ->select('id', 'name', 'booking_date', 'booking_start_time', 'booking_end_time', 'event_id', 'user_id')
                ->find($id);

            $event = Event::where('status', 'active')->find($id);
            if (!$event) {
                return response()->json([
                    "success" => false,
                    "message" => "event not found or inactive"
                ]);
            }
            // Calculate platform rate
            $start = Carbon::parse($event->available_start_time);
            $end = Carbon::parse($event->available_end_time);
            $hours = (int) ceil($start->floatDiffInHours($end));
            $platform_rate = $hours * $event->price;


            return Helper::jsonResponse(true, 'Payment successfully.', 200, [
                'platform_rate' => $platform_rate,
                'booking' => $booking,
            ]);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('something went wrong', 500, [$e->getMessage()]);
        }
    }
}
