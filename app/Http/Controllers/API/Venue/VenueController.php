<?php

namespace App\Http\Controllers\API\Venue;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Venue;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VenueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $search = $request->query('search', '');
            $per_page = $request->query('per_page', 100);
            $query = Venue::query();

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('category_id', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            }

            $venue = $query->paginate($per_page);
            if (!empty($search) && $venue->isEmpty()) {
                return Helper::jsonResponse(false, 'No Venue found for the given search.', 404);
            }

            return Helper::jsonResponse(true, 'Venue retrieved successfully.', 200, $venue, true);
        } catch (Exception $e) {
            Log::error("VenueController::index" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to retrieve Venue', 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // dd($request->all());
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:venues,name',
                'category_id' => 'required|exists:categories,id',
                'description' => 'required|string|max:1000',
                'location' => 'required|string|max:255',
                'capacity' => 'required|integer|min:1',
                'price' => 'required|numeric|min:0',
                'available_date' => 'required|date',
                'available_start_time' => 'required|date_format:H:i',
                'available_end_time' => 'required|date_format:H:i',
                'image.*' => 'image|mimes:jpeg,png,jpg,gif|max:4048'
            ]);

            // check if the category is valid for venue holders
            $category = Category::where('id', $request->category_id)
                ->where('type', 'venue_holder')
                ->first();

            if (!$category) {
                return helper::jsonResponse(false, 'Selected category is not valid for venue holders', 422);
            }

            // multiple images upload
            $uploadedImages = [];
            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $image) {
                    $uploadedImages[] = Helper::fileUpload($image, 'Venue', time() . '_' . $image->getClientOriginalName());
                }
            }

            //venue create
            $data = Venue::create([
                'user_id' => Auth::user()->id,
                'name' => $request->input('name'),
                'location' => $request->input('location'),
                'category_id' => $category->id,
                'capacity' => $request->input('capacity'),
                'price' => $request->input('price'),
                'description' => $request->input('description'),
                'available_date' => $request->input('available_date'),
                'available_start_time' => $request->input('available_start_time'),
                'available_end_time' => $request->input('available_end_time'),
                'image' => $uploadedImages, // JSON format for multiple images
            ]);

            return Helper::jsonResponse(true, 'Venue created successfully.', 201, $data);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Failed to create Venue', 500);
            Log::error("VenueController::create" . $e->getMessage());
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
        try {
            $venue = Venue::where('id', $id)->with('user')->first();
            if (!$venue) {
                return Helper::jsonResponse(false, 'Venue ID  not found.', 404);
            }
            return Helper::jsonResponse(true, 'Venue retrieved successfully.', 200, $venue);
        } catch (Exception $e) {
            Log::error("VenueController::show" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to retrieve Venue', 500);
        }
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $venue = Venue::where('id', $id,)->where('user_id', Auth::user()->id)->first();
            if (!$venue) {
                return Helper::jsonResponse(false, 'Venue not found', 404);
            }

            return Helper::jsonResponse(true, 'Venue retrieved successfully.', 200, $venue);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Failed to retrieve Venue', 500);
            Log::error("VenueController::edit" . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // dd($request->all());
        try {
            $venue = Venue::where('id', $id,)->where('user_id', Auth::user()->id)->first();
            if (!$venue) {
                return Helper::jsonResponse(false, 'Venue not found', 404);
            }

            $request->validate([
                'name' => 'required|string|max:255|unique:venues,name,' . $id,
                'category_id' => 'required|exists:categories,id',
                'description' => 'required|string|max:1000',
                'location' => 'required|string|max:255',
                'capacity' => 'required|integer|min:1',
                'price' => 'required|numeric|min:0',
                'available_date' => 'required|date',
                'available_start_time' => 'required|date_format:H:i',
                'available_end_time' => 'required|date_format:H:i',
                'image.*' => 'image|mimes:jpeg,png,jpg,gif|max:4048'
            ]);

            // check if the category is valid for venue holders
            $category = Category::where('id', $request->category_id)
                ->where('type', 'venue_holder')
                ->first();

            if (!$category) {
                return Helper::jsonResponse(false, 'Selected category is not valid for venue holders', 422);
            }

            $uploadedImages = [];
            if ($request->hasFile('image')) {
                // Delete old images once
                $oldImages = is_array($venue->image) ? $venue->image : json_decode($venue->image ?? '', true);
                if (!empty($oldImages)) {
                    foreach ($oldImages as $oldImage) {
                        Helper::fileDelete($oldImage);
                    }
                }
                // Upload new images
                foreach ($request->file('image') as $image) {
                    $uploadedImages[] = Helper::fileUpload(
                        $image,
                        'Venue',
                        time() . '_' . $image->getClientOriginalName()
                    );
                }
            }

            $venue->update([
                'user_id' => Auth::user()->id,
                'name' => $request->input('name'),
                'location' => $request->input('location'),
                'category_id' => $category->id,
                'capacity' => $request->input('capacity'),
                'price' => $request->input('price'),
                'description' => $request->input('description'),
                'available_date' => $request->input('available_date'),
                'available_start_time' => $request->input('available_start_time'),
                'available_end_time' => $request->input('available_end_time'),
                'image' => $uploadedImages, // JSON format for multiple images
            ]);
            return Helper::jsonResponse(true, 'Venue updated successfully.', 200, $venue);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Failed to update Venue', 500);
            Log::error("VenueController::update" . $e->getMessage());
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $venue = Venue::where('id', $id,)->where('user_id', Auth::user()->id)->first();
            if (!$venue) {
                return Helper::jsonResponse(false, 'Venue not found', 404);
            }
            // Delete old images
            $oldImages = is_array($venue->image) ? $venue->image : json_decode($venue->image ?? '', true);
            if (!empty($oldImages)) {
                foreach ($oldImages as $oldImage) {
                    Helper::fileDelete($oldImage);
                }
            }

            $venue->delete();
            return Helper::jsonResponse(true, 'Venue deleted successfully.', 200);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Failed to delete Venue', 500);
            Log::error("VenueController::destroy" . $e->getMessage());
        }
    }
}
