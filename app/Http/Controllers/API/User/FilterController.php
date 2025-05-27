<?php

namespace App\Http\Controllers\API\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Venue;
use Exception;
use Illuminate\Http\Request;
//-----------------------
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class FilterController extends Controller
{
    //Entertainer Filter 
    public function filterEntertainer(Request $request)
    {
        try {
            $query = Event::query()->with(['category']);
            $query->whereHas('category', function ($q) {
                $q->where('type', 'entertainer');
            });

            $query->where(function ($q) use ($request) {
                if ($request->has('category')) {
                    $q->orWhereHas('category', function ($cat) use ($request) {
                        $cat->whereIn('name', explode(',', $request->category));
                    });
                }

                if ($request->has('location')) {
                    $q->orWhere('location', 'like', '%' . $request->location . '%');
                }

                //price range filter
                if ($request->has('min_price') && $request->has('max_price')) {
                    $q->orWhereBetween('price', [$request->min_price, $request->max_price]);
                }
                //date and time filter
                if ($request->has(['start_date', 'end_date'])) {
                    $q->where(function ($query) use ($request) {
                        $query->whereDate('available_start_time', '>=', $request->start_date)
                            ->whereDate('available_end_time', '<=', $request->end_date);
                    });
                }

                if ($request->has(['available_start_time', 'available_end_time'])) {
                    $q->where(function ($query) use ($request) {
                        $query->whereTime('available_start_time', '>=', $request->available_start_time)
                            ->whereTime('available_end_time', '<=', $request->available_end_time);
                    });
                }
            });

            $events = $query->get();

            if ($events->isEmpty()) {
                return Helper::jsonResponse(false, 'No matching Event found', 200, []);
            }

            return Helper::jsonResponse(true, 'All Booked Data Returned', 200, $events);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    //filter venue search 
    public function filterVenue(Request $request)
    {
        try {
            $query = Venue::query()->with(['category']);

            $query->whereHas('category', function ($q) {
                $q->where('type', 'venue_holder');
            });

            $query->where(function ($q) use ($request) {
                if ($request->has('category')) {
                    $q->whereHas('category', function ($cat) use ($request) {
                        $cat->whereIn('name', explode(',', $request->category));
                    });
                }

                if ($request->has('location')) {
                    $q->where('location', 'like', '%' . $request->location . '%');
                }
                // Capacity filter
                if ($request->has('min_capacity') && $request->has('max_capacity')) {
                    $q->whereBetween('capacity', [$request->min_capacity, $request->max_capacity]);
                }
                //price range filter
                if ($request->has('min_price') && $request->has('max_price')) {
                    $q->orWhereBetween('price', [$request->min_price, $request->max_price]);
                }
                //date and time filter
                if ($request->has(['start_date', 'end_date'])) {
                    $q->where(function ($query) use ($request) {
                        $query->whereDate('available_start_time', '>=', $request->start_date)
                            ->whereDate('available_end_time', '<=', $request->end_date);
                    });
                }

                if ($request->has(['available_start_time', 'available_end_time'])) {
                    $q->where(function ($query) use ($request) {
                        $query->whereTime('available_start_time', '>=', $request->available_start_time)
                            ->whereTime('available_end_time', '<=', $request->available_end_time);
                    });
                }
            });
            $venues = $query->get();
            if ($venues->isEmpty()) {
                return Helper::jsonResponse(false, 'No matching venues found.', 200, []);
            }

            return Helper::jsonResponse(true, 'Venues filtered successfully', 200, $venues);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, 'Server error: ' . $e->getMessage(), 500);
        }
    }

    //venue nearby search
    public function NearbySearchVenue(Request $request)
    {
        try {
            $userLat = $request->input('latitude');
            $userLng = $request->input('longitude');
            $radius = 10;

            $venues = Venue::selectRaw("*, 
                        (6371 * acos(
                            cos(radians(?)) * 
                            cos(radians(latitude)) * 
                            cos(radians(longitude) - radians(?)) + 
                            sin(radians(?)) * 
                            sin(radians(latitude))
                        )) AS distance", [$userLat, $userLng, $userLat])
                ->having("distance", "<=", $radius)
                ->orderBy("distance")
                ->get();
            if ($venues->isEmpty()) {
                return Helper::jsonResponse(false, 'Your Distance Not Found Venue', 200, []);
            }

            $venues->map(function ($venue) {
                $venue->distance = round($venue->distance, 2) . ' km';
                return $venue;
            });
            return Helper::jsonResponse(false, 'Nearby search Successfully', 200, $venues);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, 'Server error: ' . $e->getMessage(), 500);
        }
    }


    //Event nearby search
    public function NearbySearchEvent(Request $request)
    {
        try {
            $userLat = $request->input('latitude');
            $userLng = $request->input('longitude');
            $radius = 10;

            $events = Event::selectRaw("*, 
                        (6371 * acos(
                            cos(radians(?)) * 
                            cos(radians(latitude)) * 
                            cos(radians(longitude) - radians(?)) + 
                            sin(radians(?)) * 
                            sin(radians(latitude))
                        )) AS distance", [$userLat, $userLng, $userLat])
                ->having("distance", "<=", $radius)
                ->orderBy("distance")
                ->get();
            if ($events->isEmpty()) {
                return Helper::jsonResponse(false, 'Your Distance Not Found Event', 200, []);
            }

            $events->map(function ($venue) {
                $venue->distance = round($venue->distance, 2) . ' km';
                return $venue;
            });
            return Helper::jsonResponse(false, 'Nearby search Successfully', 200, $events);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, 'Server error: ' . $e->getMessage(), 500);
        }
    }

    //location get Entertainer
    public function locationEntertainer()
    {
        try {
            $events = Event::pluck('location')->unique()->values();
            if ($events->isEmpty()) {
                return Helper::jsonResponse(false, 'No locations found', 404, []);
            }
            return Helper::jsonResponse(true, 'Locations retrieved successfully', 200, $events);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, 'Server error: ' . $e->getMessage(), 500);
        }
    }

    //location get venueholder
    public function locationVenueHolder()
    {
        try {
            $venues = Venue::pluck('location')->unique()->values();
            if ($venues->isEmpty()) {
                return Helper::jsonResponse(false, 'No locations found', 404, []);
            }
            return Helper::jsonResponse(true, 'Locations retrieved successfully', 200, $venues);
        } catch (Exception $e) {
            return Helper::jsonResponse(false, 'Server error: ' . $e->getMessage(), 500);
        }
    }
}
