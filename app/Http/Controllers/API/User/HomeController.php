<?php

namespace App\Http\Controllers\API\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{

    public function index(Request $request)
    {
        try {
            $events = Event::with('category:id,name,image')->select('id', 'name', 'image', 'location', 'price', 'category_id')->with('rating')->get();

            return response()->json([
                'success' => true,
                'message' => 'Event show successfully',
                'events' => $events,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
                'error' => $e->getMessage(),
            ]);
        }
    }


    public function searchHomepage(Request $request)
    {
        try {
            $searchName  = $request->search;
            $categoryIds = $request->category_id;

            if ($categoryIds) {
                $categoryIds = is_array($categoryIds) ? $categoryIds : explode(',', $categoryIds);
            }

            // Search Events
            $eventQuery = Event::query()->with(['user:id,name', 'category:id,name']);
            if ($searchName) {
                $eventQuery->where(function ($q) use ($searchName) {
                    $q->where('name', 'like', "%{$searchName}%")
                        ->orWhereHas('category', function ($q2) use ($searchName) {
                            $q2->where('name', 'like', "%{$searchName}%");
                        });
                });
            }

            if ($categoryIds) {
                $eventQuery->whereIn('category_id', $categoryIds);
            }
            $events = $eventQuery->get();
            // Search Venues
            $venueQuery = Venue::query()->with(['user:id,name', 'category:id,name']);

            if ($searchName) {
                $venueQuery->where(function ($q) use ($searchName) {
                    $q->where('name', 'like', "%{$searchName}%");
                });
            }

            if ($categoryIds) {
                $venueQuery->whereIn('category_id', $categoryIds);
            }

            $venues = $venueQuery->get();

            return response()->json([
                'success' => true,
                'events'  => $events,
                'venues'  => $venues,
            ]);
        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching.',
            ], 500);
        }
    }



    public function entertainer(Request $request)
    {
        try {
            $Entertainer = Event::with(['user:id,name,avatar', 'category:id,name'])->select('id', 'price', 'category_id', 'user_id')->get();

            return response()->json([
                'success' => true,
                'message' => 'entertainer show successfully',
                'Entertainer' => $Entertainer,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
                'error' => $e->getMessage(),
            ]);
        }
    }


    //venue information
    public function venue(Request $request)
    {
        try {
            $events = Venue::select('id', 'name', 'location', 'price', 'category_id', 'image')->with('rating')->get();

            return response()->json([
                'success' => true,
                'message' => 'Event show successfully',
                'events' => $events,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
                'error' => $e->getMessage(),
            ]);
        }
    }


    //venue details api 
    public function venueDetails(Request $request, $id)
    {
        $venue = Venue::find($id);
        return  $venue;
    }
}
