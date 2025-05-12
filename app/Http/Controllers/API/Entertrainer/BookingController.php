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
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $search = $request->query('search', '');
            $per_page = $request->query('per_page', 100);

            $query = Booking::query();

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhere('booking_date', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            }

            $booking = $query->paginate($per_page);
            if (!empty($search) && $booking->isEmpty()) {
                return Helper::jsonResponse(false, 'No Booking found for the given search.', 404);
            }

            return Helper::jsonResponse(true, 'Booking retrieved successfully.', 200, $booking, true);
        } catch (Exception $e) {
            Log::error("EventController::index" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to retrieve Booking', 500);
        }
    }


    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'event_id' => 'required|exists:events,id',
                'booking_date' => 'required|date',
                'booking_start_time' => 'required|date_format:H:i',
                'booking_end_time' => 'required|date_format:H:i|after:booking_start_time',
            ]);

            if ($validator->fails()) {
                return Helper::jsonResponse(false, 'Booking creation failed.', 422, $validator->errors());
            }
            // Convert time and calculate duration in hours
            $start = Carbon::createFromFormat('H:i', $request->booking_start_time);
            $end = Carbon::createFromFormat('H:i', $request->booking_end_time);
            $hours = $start->diffInHours($end);

            //remming time
            $bookingDateTime = Carbon::parse($request->booking_date . ' ' . $request->booking_start_time);
            $remainingTime = now()->diff($bookingDateTime);

            $platform_rate = $hours * 100;    // $100 per hours
            $fee_percentage = 17;
            $fee_amount = ($platform_rate * $fee_percentage) / 100;
            $net_amount = $platform_rate - $fee_amount;


            $event = Event::find($request->event_id);
            if (!$event) {
                return Helper::jsonResponse(false, 'Event not found.', 404);
            }
            //user
            $user = User::find(Auth::user()->id);
            $booking = Booking::create([
                'user_id' => Auth::user()->id,
                'event_id' => $request->event_id,
                'category' => $event->category_id,        //event table ar category_id
                'location' => $event->location,         //event table ar location
                'name' => $user->name,                  //user table ar name
                'image' => $user->image,                 //user table ar image
                'booking_date' => $request->booking_date,
                'booking_start_time' => $request->booking_start_time,
                'booking_end_time' => $request->booking_end_time,
                'remaining_time' => $remainingTime->format('%H:%I:%S'),
                'platform_rate' => $platform_rate,
                'fee_percentage' => $fee_percentage,
                'fee_amount' => $fee_amount,
                'net_amount' => $net_amount,
                'status' => 'upcoming'
            ]);
            return Helper::jsonResponse(true, 'Booking created successfully.', 200, $booking);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, 'Booking creation failed.', 500, $e->getMessage());
        }
    }





    public function BookingVenue(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'venue_id' => 'required|exists:venues,id',
                'booking_date' => 'required|date',
                'booking_start_time' => 'required|date_format:H:i',
                'booking_end_time' => 'required|date_format:H:i|after:booking_start_time',
            ]);

            if ($validator->fails()) {
                return Helper::jsonResponse(false, 'Booking creation failed.', 422, $validator->errors());
            }
            // Convert time and calculate duration in hours
            $start = Carbon::createFromFormat('H:i', $request->booking_start_time);
            $end = Carbon::createFromFormat('H:i', $request->booking_end_time);
            $hours = $start->diffInHours($end);

            //remming time
            $bookingDateTime = Carbon::parse($request->booking_date . ' ' . $request->booking_start_time);
            $remainingTime = now()->diff($bookingDateTime);

            $platform_rate = $hours * 100;    // $100 per hours
            $fee_percentage = 17;
            $fee_amount = ($platform_rate * $fee_percentage) / 100;
            $net_amount = $platform_rate - $fee_amount;


            $event = Venue::find($request->venue_id);
            if (!$event) {
                return Helper::jsonResponse(false, 'Event not found.', 404);
            }
            //user
            $user = User::find(Auth::user()->id);
            $booking = Booking::create([
                'user_id' => Auth::user()->id,
                'venue_id' => $request->venue_id,
                'category' => $event->category_id,        //event table ar category_id
                'location' => $event->location,         //event table ar location
                'name' => $user->name,                  //user table ar name
                'image' => $user->image,                 //user table ar image
                'booking_date' => $request->booking_date,
                'booking_start_time' => $request->booking_start_time,
                'booking_end_time' => $request->booking_end_time,
                'remaining_time' => $remainingTime->format('%H:%I:%S'),
                'platform_rate' => $platform_rate,
                'fee_percentage' => $fee_percentage,
                'fee_amount' => $fee_amount,
                'net_amount' => $net_amount,
                'status' => 'pending'
            ]);
            return Helper::jsonResponse(true, 'Venue Booking created successfully.', 200, $booking);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, ' Venue Booking creation failed.', 500, $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
