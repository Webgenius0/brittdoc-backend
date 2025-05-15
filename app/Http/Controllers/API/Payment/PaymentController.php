<?php

namespace App\Http\Controllers\API\Payment;

use App\Http\Controllers\Controller;
use App\Models\Booking;
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


    // public function AfterPayScreen
    public function AfterPayScreen($id)
    {
        try {
            $booking = Booking::with('rating')->find($id);
            $venue = Venue::where('status', 'active')->find($id);
            if (!$venue) {
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
                "message" => " successfully",
                "platform_rate" => $platform_rate,
                "name" => $venue->name,
                "image" => $venue->image,
                "location" => $venue->location,
                "booking_start_time" => $booking->booking_start_time,
                "booking_end_time" => $booking->booking_end_time,
                "Rating_id" => $booking->rating->id,
                "Rating" => $booking->rating ? $booking->rating->rating : null,
            ]);
        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Error retrieving details",
                "error" => $e->getMessage()
            ], 500);
        }
    }
}
