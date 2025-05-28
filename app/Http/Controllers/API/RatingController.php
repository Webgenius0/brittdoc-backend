<?php

namespace App\Http\Controllers\API;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Rating;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{

    public function index()
    {
        try {
            $ratings = Rating::with('user:id,name,avatar')
                ->get();
            $ratings->makeHidden(['created_at', 'updated_at']);
            $Totalcount = $ratings->count();
            $Average = round($ratings->avg('rating'), 2);
            return Helper::jsonResponse(true, 'All Ratings retrieved successfully.', 200, [
                'Average' => $Average,
                'Totalcount' => $Totalcount,
                'ratings' => $ratings,
            ]);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('something went wrong', 500, [$e->getMessage()]);
        }
    }


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
}
