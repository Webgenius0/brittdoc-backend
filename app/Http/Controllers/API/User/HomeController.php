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
