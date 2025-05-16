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

    //testing  booking create 
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


    //Venue User   Booking Create  
    // public function BookingVenue(Request $request, $id)
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'booking_date' => 'required|date|after_or_equal:today',
    //         ]);

    //         $venue = Venue::find($id);

    //         if (!$venue) {
    //             return response()->json(['message' => 'Venue Id Not found.'], 404);
    //         }
    //         //booking date check vaildate
    //         $startDate = Carbon::parse($venue->start_date)->toDateString();
    //         $endDate = Carbon::parse($venue->ending_date)->toDateString();

    //         $validator->after(function ($validator) use ($request, $startDate, $endDate) {
    //             $bookingDate = Carbon::parse($request->booking_date)->toDateString();

    //             if (!($bookingDate >= $startDate && $bookingDate <= $endDate)) {
    //                 $validator->errors()->add(
    //                     'booking_date',
    //                     "Booking date must be between $startDate and $endDate."
    //                 );
    //             }
    //         });

    //         if ($validator->fails()) {
    //             return Helper::jsonResponse(false, 'Booking Date not Available.', 422, $validator->errors());
    //         }

    //         $start = Carbon::parse($venue->available_start_time);
    //         $end = Carbon::parse($venue->available_end_time);

    //         if ($end->lt($start)) {
    //             [$start, $end] = [$end, $start];
    //         }
    //         $diffInMinutes = $start->diffInMinutes($end);
    //         $hours = (int) ceil($diffInMinutes / 60);
    //         // dd($hours);

    //         $platform_rate = $hours * 100;    // $100 per hours
    //         $fee_percentage = 17;
    //         $fee_amount = ($platform_rate * $fee_percentage) / 100;
    //         $net_amount = $platform_rate - $fee_amount;
    //         // dd($net_amount);

    //         //user
    //         $user = User::find(Auth::user()->id);
    //         $booking = Booking::create([
    //             'user_id' => Auth::user()->id,
    //             'venue_id' => $venue->id,
    //             'category' => $venue->category_id,        //venue table ar category_id
    //             'location' => $venue->location,         //event table ar location
    //             'name' => $user->name,                  //user table ar name
    //             'image' => $user->image,                 //user table ar image
    //             'booking_date' => $request->booking_date,
    //             'booking_start_time' => $venue->available_start_time,
    //             'booking_end_time' => $venue->available_end_time,
    //             'platform_rate' => $platform_rate,
    //             'fee_percentage' => $fee_percentage,
    //             'fee_amount' => $fee_amount,
    //             'net_amount' => $net_amount,
    //             'status' => 'pending'
    //         ]);
    //         // dd($booking);
    //         return Helper::jsonResponse(true, 'Venue Booking created successfully.', 200, $booking);
    //     } catch (Exception $e) {
    //         return Helper::jsonResponse(false, ' Venue Booking creation failed.', 500, $e->getMessage());
    //     }
    // }



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
}
