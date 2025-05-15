<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{

    public function index(Request $request)
    {
        try {
            $rating = Rating::all();
            return response()->json([
                'message' => 'All Rating retrieved successfully.',
                'rating' => $rating
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                $e->getMessage(),
            ], 201);
        }
    }

    //rating create venuehloder 
    public function VenueRating(Request $request)
    {
        try {
            $request->validate([
                'venue_id' => 'nullable|exists:venues,id',
                'booking_id' => 'nullable|exists:bookings,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
            ]);
            $existing = Rating::where('user_id', Auth::id())->where(function ($query) use ($request) {
                $query->where('booking_id', $request->booking_id)->orWhere('venue_id', $request->venue_id);
            })->exists();

            if ($existing) {
                return response()->json([
                    'message' => 'You have already rated this booking venue.',
                ], 422);
            }

            $rating = Rating::create([
                'user_id' => Auth::user()->id,
                "venue_id" => $request->input("venue_id"),
                "booking_id" => $request->input("booking_id"),
                "rating" => $request->input("rating"),
                "comment" => $request->input("comment"),

            ]);

            return response()->json([
                'message' => 'Rating created successfully.',
                'data' => $rating

            ], 201);
        } catch (Exception $e) {
            return response()->json([
                $e->getMessage(),
            ], 201);
        }
    }

    //rating indivisual  venue id 
    public function indivisualvenue(Request $request, $id)
    {
        try {
            $rating = Rating::where('venue_id', $id)->get();
            return response()->json([
                'message' => 'Venue Rating get successfully.',
                'rating' => $rating
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                $e->getMessage(),
            ], 201);
        }
    }
}
