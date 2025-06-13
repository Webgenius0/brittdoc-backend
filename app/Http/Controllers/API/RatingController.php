<?php

namespace App\Http\Controllers\API;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Rating;
use App\Models\Venue;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{

    // public function index()
    // {
    //     try {
    //         $ratings = Rating::with('user:id,name,avatar')
    //             ->get();
    //         $ratings->makeHidden(['created_at', 'updated_at']);
    //         $Totalcount = $ratings->count();
    //         $Average = round($ratings->avg('rating'), 2);
    //         return Helper::jsonResponse(true, 'All Ratings retrieved successfully.', 200, [
    //             'Average' => $Average,
    //             'Totalcount' => $Totalcount,
    //             'ratings' => $ratings,
    //         ]);
    //     } catch (Exception $e) {
    //         return Helper::jsonErrorResponse('something went wrong', 500, [$e->getMessage()]);
    //     }
    // }

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return Helper::jsonErrorResponse('Unauthorized', 401);
            }

            $request->validate([
                'event_id' => 'required|exists:events,id',
            ]);

            // Get all ratings related to the 
            $ratings = Rating::with('user:id,name,avatar')
                ->where('event_id', $request->event_id)
                ->get();

            $ratings->makeHidden(['created_at', 'updated_at', 'reciver_id']);

            $Totalcount = $ratings->count();
            $Average = round($ratings->avg('rating'), 2);

            return Helper::jsonResponse(true, 'Ratings retrieved successfully.', 200, [
                'Average' => $Average,
                'Totalcount' => $Totalcount,
                'ratings' => $ratings,
            ]);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Something went wrong', 500, [$e->getMessage()]);
        }
    }

    //---------------------------------------

    //venue and event ar Rating create 
    public function CreateRating(Request $request)
    {
        try {
            $request->validate([
                'venue_id' => 'nullable|exists:venues,id',
                'event_id' => 'nullable|exists:events,id',
                'booking_id' => 'required|exists:bookings,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:500',
            ]);

            //only venue id 
            if ($request->filled('venue_id') && $request->filled('event_id')) {
                return response()->json([
                    'message' => 'You can only rate either a venue or an event at a time.',
                ], 422);
            }

            //venue id and event_id validate 
            if (!$request->filled('venue_id') && !$request->filled('event_id')) {
                return response()->json([
                    'message' => 'Need  venue_id or event_id is required.',
                ], 422);
            }

            // check auth and 
            $existing = Rating::where('user_id', Auth::id())
                ->where(function ($query) use ($request) {
                    if ($request->filled('venue_id')) {
                        $query->where('venue_id', $request->venue_id);
                    }
                    if ($request->filled('event_id')) {
                        $query->where('event_id', $request->event_id);
                    }
                    if ($request->filled('booking_id')) {
                        $query->orWhere('booking_id', $request->booking_id);
                    }
                })->exists();

            if ($existing) {
                return response()->json([
                    'message' => 'You have already Rating this booking',
                ], 422);
            }

            $rating = Rating::create([
                'user_id' => Auth::id(),
                'venue_id' => $request->venue_id,
                'event_id' => $request->event_id,
                'booking_id' => $request->booking_id,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);
            return Helper::jsonResponse(true, 'Rating created successfully.', 201, $rating);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('something went wrong', 403, [$e->getMessage()]);
        }
    }




    //rating indivisual  venue id 
    public function indivisualvenue(Request $request, $id)
    {
        try {
            $rating = Rating::where('event', $id)->get();
            return response()->json([
                'message' => 'Venue Rating get successfully.',
                'rating' => $rating
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                $e->getMessage(),
            ]);
        }
    }
    //..........

    // //olny show list rating for event owner database query
    public function RatingListEntertaner(Request $request,)
    {
        try {
            $user = Auth::user()->id;
            $event = Event::where('user_id', $user)->first();
            if (!$event) {
                return Helper::jsonErrorResponse(false, 'You are not authorized to view this event ratings !');
            }
            // Get all ratings for the event owner
            $ratings = Rating::where('event_id', $event->id)
                ->whereNull('reciver_id')
                ->with('user:id,name,avatar')
                ->get();
            $ratings->makeHidden(['created_at', 'updated_at', 'reciver_id']);
            $Totalcount = $ratings->count();
            $Average = round($ratings->avg('rating'), 2);
            return Helper::jsonResponse(true, 'Event ratings retrieved successfully.', 200, [
                'Average' => $Average,
                'Totalcount' => $Totalcount,
                'ratings' => $ratings,
            ]);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Something went wrong.', 500, [$e->getMessage()]);
        }
    }


    //Entertainers create for event owner  to rate booking users
    public function CreateRatingE(Request $request)
    {
        try {
            $request->validate([
                'event_id' => 'required|exists:events,id',
                'booking_id' => 'required|exists:bookings,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:500',
            ]);

            $event = Event::findOrFail($request->event_id);
            $booking = Booking::findOrFail($request->booking_id);


            if ($booking->event_id != $request->event_id) {
                return Helper::jsonErrorResponse('Invalid booking for this event.');
            }
            // Current logged in user  event owner check
            if (Auth::id() !== $event->user_id) {
                return Helper::jsonErrorResponse(false, 'You are not authorized to rate this booking !');
            }
            if ($booking->status !== 'completed') {
                return Helper::jsonErrorResponse('You can only review completed bookings.', 400);
            }


            // Check if the user has already rated this booking
            $existingRating = Rating::where('user_id', Auth::id())
                ->where('reciver_id', $booking->user_id)
                ->where('booking_id', $request->booking_id)
                ->exists();

            if ($existingRating) {
                return Helper::jsonResponse(true, 'You have already reviewed this user for this booking.', 200);
            }

            //store rating
            $rating = Rating::create([
                'user_id' => Auth::id(),             // (Entertainers)
                'reciver_id' => $booking->user_id,  //(booking user)
                'event_id' => $request->event_id,
                'booking_id' => $request->booking_id,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);
            return Helper::jsonResponse(true, 'Review submitted successfully.', 201, $rating);
        } catch (\Exception $e) {
            return Helper::jsonErrorResponse('Something went wrong.', 500, [$e->getMessage()]);
        }
    }


    // //olny show list rating for event owner database query
    public function RatingListVenueHolder(Request $request,)
    {
        try {
            $user = Auth::user()->id;
            $venue = Venue::where('user_id', $user)->first();
            if (!$venue) {
                return Helper::jsonErrorResponse(false, 'You are not authorized to view this Venue ratings !');
            }
            // Get all ratings for the event owner
            $ratings = Rating::where('venue_id', $venue->id)
                ->whereNull('reciver_id')
                ->with('user:id,name,avatar')
                ->get();
            $ratings->makeHidden(['created_at', 'updated_at', 'reciver_id']);
            $Totalcount = $ratings->count();
            $Average = round($ratings->avg('rating'), 2);
            return Helper::jsonResponse(true, 'Venue ratings retrieved successfully.', 200, [
                'Average' => $Average,
                'Totalcount' => $Totalcount,
                'ratings' => $ratings,
            ]);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Something went wrong.', 500, [$e->getMessage()]);
        }
    }



    // venues create for venue owner to rate booking users
    public function CreateRatingV(Request $request)
    {
        try {
            $request->validate([
                'venue_id' => 'required|exists:venues,id',
                'booking_id' => 'required|exists:bookings,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:500',
            ]);

            $venue = Venue::findOrFail($request->venue_id);
            $booking = Booking::findOrFail($request->booking_id);

            // Booking & venue_id check 
            if ($booking->venue_id != $request->venue_id) {
                return Helper::jsonErrorResponse(false, 'Invalid booking for this venue.');
            }

            // Current logged in user  venue owner check
            if (Auth::id() !== $venue->user_id) {
                return Helper::jsonErrorResponse(false, 'You are not authorized to rate this booking !');
            }

            // Check if the user has already rated this booking
            $existingRating = Rating::where('user_id', Auth::id())
                ->where('reciver_id', $booking->user_id)
                ->where('booking_id', $request->booking_id)
                ->exists();

            if ($existingRating) {
                return Helper::jsonResponse(true, 'You have already reviewed this user for this booking.', 200);
            }

            //store rating
            $rating = Rating::create([
                'user_id' => Auth::id(),             // (venueholder)
                'reciver_id' => $booking->user_id,  //(booking user)
                'venue_id' => $request->venue_id,
                'booking_id' => $request->booking_id,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);
            return Helper::jsonResponse(true, 'Review submitted successfully.', 201, $rating);
        } catch (\Exception $e) {
            return Helper::jsonErrorResponse('Something went wrong.', 500, [$e->getMessage()]);
        }
    }
}
