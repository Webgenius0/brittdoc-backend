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

    // Entertainer Booking 
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

            // Check if booking already exists for the same event and date
            $existingBooking = Booking::where('event_id', $id)
                ->whereDate('booking_date', Carbon::parse($request->booking_date)->toDateString())
                ->first();

            if ($existingBooking) {
                return Helper::jsonResponse(false, 'This event is already booked on this date.', 422);
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

            $platform_rate = $hours * 100; // $100 per hour
            $fee_percentage = 17;
            $fee_amount = ($platform_rate * $fee_percentage) / 100;
            $net_amount = $platform_rate - $fee_amount;

            $user = Auth::user();

            $booking = Booking::create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'category' => $event->category_id,
                'location' => $event->location,
                'name' => $user->name,
                'image' => $user->image,
                'booking_date' => $request->booking_date,
                'booking_start_time' => $event->available_start_time,
                'booking_end_time' => $event->available_end_time,
                'platform_rate' => $platform_rate,
                'fee_percentage' => $fee_percentage,
                'fee_amount' => $fee_amount,
                'net_amount' => $net_amount,
                'status' => 'pending'
            ]);

            return Helper::jsonResponse(true, 'Event booking created successfully.', 200, $booking);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, 'Event booking creation failed.', 500, $e->getMessage());
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


    public function allBookingList(Request $request)
    {
        try {
            $status = $request->query('status', '');
            $userId = Auth::id();

            if (!$userId) {
                return Helper::jsonResponse(false, 'User not authenticated.', 401);
            }

            $query = Booking::where('user_id', $userId)
                ->with([
                    'user:id,name,avatar',
                    'event:id,name,category_id',
                    'event.category:id,name,image',
                    'venue:id,name,category_id',
                    'venue.category:id,name,image',
                    'rating'
                ]);

            // filter by status if provided
            if (!empty($status)) {
                $query->where('status', $status);
            }
            // query execute  bookings 
            $bookings = $query->get();

            //applyTimeStatus
            $bookings = $bookings->map(function ($booking) {
                return $this->applyTimeStatus($booking);
            });
            return Helper::jsonResponse(true, 'Booking retrieved successfully.', 200, $bookings);
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

    // user Site customer booking status change
    public function acceptOrRequest(Request $request, $id)
    {
        try {
            $userId = Auth::user()->id;
            if (!$userId) {
                return Helper::jsonErrorResponse('User not authenticated');
            }

            // Check if the user is the owner of the booking
            $booking = Booking::where('user_id', $userId)->findOrFail($id);
            if (!$request->has('status')) {
                return Helper::jsonResponse(true, 'Booking info fetched successfully', 200, $booking);
            }
            if ($booking->status === 'booked') {
                return Helper::jsonResponse(false, 'Booking already booked', 200, $booking);
            }
            if (in_array($request->status, ['accept', 'request'])) {
                $booking->status = $request->status;
                $booking->save();
                return Helper::jsonResponse(true, 'Booking  successfully', 200, $booking);
            } else {
                return Helper::jsonErrorResponse('Invalid status', 400);
            }
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Failed to  booking status', 500, [$e->getMessage()]);
        }
    }

    // entertainer Booking Accept or Cancel
    public function acceptOrCancel(Request $request, $id)
    {
        try {

            $userId = Auth::user()->id;
            $booking = Booking::with('event:id,user_id')->findOrFail($id);

            if ($booking->event->user_id !== $userId) {
                return Helper::jsonErrorResponse('You are not authorized to access this booking', 403);
            }

            if (!$request->has('status')) {
                return Helper::jsonResponse(true, 'Booking info fetched successfully', 200, $booking);
            }

            if (in_array($request->status, ['accept', 'cancelled'])) {
                $booking->status = $request->status;
                $booking->save();
                return Helper::jsonResponse(true, 'Booking status updated successfully', 200, $booking);
            } else {
                return Helper::jsonErrorResponse('Invalid status', 400);
            }
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Failed to update booking status', 500, [$e->getMessage()]);
        }
    }

    // user Site customer booking status change
    public function withdrawOfferE(Request $request, $id)
    {
        try {
            $userId = Auth::user()->id;
            $booking = Booking::with('event:id,user_id')->findOrFail($id);

            if ($booking->event->user_id !== $userId) {
                return Helper::jsonErrorResponse('You are not authorized to access this booking', 403);
            }
            // Check if the user is the owner of the booking
            if (!$request->has('status')) {
                return Helper::jsonResponse(true, 'Booking info fetched successfully', 200, $booking);
            }
            if ($booking->status === 'booked') {
                return Helper::jsonResponse(false, 'Booking already booked', 200, $booking);
            }
            if (in_array($request->status, ['withdraw'])) {
                $booking->status = $request->status;
                $booking->save();
                return Helper::jsonResponse(true, 'Booking  successfully', 200, $booking);
            } else {
                return Helper::jsonErrorResponse('Invalid status', 400);
            }
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Failed to  booking status', 500, [$e->getMessage()]);
        }
    }

    // Venue Booking Accept or Cancel
    public function acceptOrCancelV(Request $request, $id)
    {
        try {

            $userId = Auth::user()->id;
            $booking = Booking::with('venue:id,user_id')->findOrFail($id);

            if ($booking->venue->user_id !== $userId) {
                return Helper::jsonErrorResponse('You are not authorized to access this booking', 403);
            }

            if (!$request->has('status')) {
                return Helper::jsonResponse(true, 'Booking info fetched successfully', 200, $booking);
            }

            if (in_array($request->status, ['accept', 'cancelled'])) {
                $booking->status = $request->status;
                $booking->save();
                return Helper::jsonResponse(true, 'Booking status updated successfully', 200, $booking);
            } else {
                return Helper::jsonErrorResponse('Invalid status', 400);
            }
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Failed to update booking status', 500, [$e->getMessage()]);
        }
    }

    // venue Site  status change
    public function withdrawOfferV(Request $request, $id)
    {
        try {
            $userId = Auth::user()->id;
            $booking = Booking::with('venue:id,user_id')->findOrFail($id);

            if ($booking->venue->user_id !== $userId) {
                return Helper::jsonErrorResponse('You are not authorized to access this booking', 403);
            }
            // Check if the user is the owner of the booking
            if (!$request->has('status')) {
                return Helper::jsonResponse(true, 'Booking info fetched successfully', 200, $booking);
            }
            if ($booking->status === 'booked') {
                return Helper::jsonResponse(false, 'Booking already booked', 200, $booking);
            }
            if (in_array($request->status, ['withdraw'])) {
                $booking->status = $request->status;
                $booking->save();
                return Helper::jsonResponse(true, 'Booking  successfully', 200, $booking);
            } else {
                return Helper::jsonErrorResponse('Invalid status', 400);
            }
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Failed to  booking status', 500, [$e->getMessage()]);
        }
    }
}
