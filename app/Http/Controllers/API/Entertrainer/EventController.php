<?php

namespace App\Http\Controllers\API\Entertrainer;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $search = $request->query('search', '');
            $per_page = $request->query('per_page', 100);

            $query = Event::query();

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('category_id', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            }

            $event = $query->paginate($per_page);
            if (!empty($search) && $event->isEmpty()) {
                return Helper::jsonResponse(false, 'No event found for the given search.', 404);
            }

            return Helper::jsonResponse(true, 'Event retrieved successfully.', 200, $event, true);
        } catch (Exception $e) {
            Log::error("EventController::index" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to retrieve Event', 500);
        }
    }


    /**
     * Show the form for creating a new resource.
     */

    public function create(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:events,name',
                'location' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'price' => 'required|numeric|min:0',
                'about' => 'required|string|max:1200',
                'start_date' => 'required|date',
                'ending_date' => 'required|date|after_or_equal:start_date',
                'available_start_time' => 'required|date_format:H:i',
                'available_end_time' => 'required|date_format:H:i|after:available_start_time',
                'image' => 'nullable|image|max:2048',
                'latitude' => 'nullable',
                'longitude' => 'nullable',
            ]);

            // Create a new event input
            if ($request->file('image')) {
                $image = Helper::fileUpload($request->file('image'), 'Event', time() . '_' . getFileName($request->file('image')));
            }

            // check if the category is valid for venue holders
            $category = Category::where('id', $request->category_id)
                ->where('type', 'entertainer')
                ->first();

            if (!$category) {
                return Helper::jsonResponse(false, 'Selected category is not valid for entertainer', 422);
            }

            $data = Event::create([
                'user_id' => Auth::user()->id,
                'name' => $request->input('name'),
                'location' => $request->input('location'),
                'category_id' => $category->id,
                'price' => $request->input('price'),
                'about' => $request->input('about'),
                'start_date' => $request->input('start_date'),
                'ending_date' => $request->input('ending_date'),
                'available_start_time' => $request->input('available_start_time'),
                'available_end_time' => $request->input('available_end_time'),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'image' => $image,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Event created successfully',
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event not created',
                'message' => $e->getMessage(),
            ]);
        }
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //store
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $event = Event::where('id', $id)->with('user')->first();
            if (!$event) {
                return Helper::jsonResponse(false, 'Event ID  not found.', 404);
            }
            return response()->json([
                'success' => true,
                'message' => 'Event retrieved Details successfully',
                'data' => $event,
            ]);
        } catch (Exception $e) {
            // Log::error("EventController::show" . $e->getMessage());
            // return Helper::jsonErrorResponse('Failed to retrieve Event', 500);
            return response()->json([
                'error' => false,
                'message' => 'Event not found',
                $e->getMessage()
            ]);
        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        try {
            $event = Event::where('id', $id,)->where('user_id', Auth::user()->id)->first();
            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found',
                ], 404);
            }
            return response()->json([
                'success' => true,
                'message' => 'Event retrieved successfully',
                'data' => $event,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve event',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // dd($request->all());
        try {
            $event = Event::where('id', $id,)->where('user_id', Auth::user()->id)->first();
            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found',
                ], 404);
            }

            $request->validate([
                'name' => 'required|string|max:255|unique:events,name,' . $id,
                'location' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id',
                'price' => 'required|numeric|min:0',
                'about' => 'required|string|max:1200',
                'start_date' => 'required|date',
                'ending_date' => 'required|date|after_or_equal:start_date',
                'available_start_time' => 'required|date_format:H:i',
                'available_end_time' => 'required|date_format:H:i|after:available_start_time',
                'image' => 'nullable|image|max:2048',
                'latitude' => 'nullable',
                'longitude' => 'nullable',
            ]);

            // check if the category is valid for venue holders
            $category = Category::where('id', $request->category_id)
                ->where('type', 'entertainer')
                ->first();

            if (!$category) {
                return Helper::jsonResponse(false, 'Selected category is not valid for entertainer', 422);
            }

            if ($request->hasFile('image')) {
                // Delete old image
                if ($event->image) {
                    Helper::fileDelete($event->image);
                }
                // Upload new image
                $image = Helper::fileUpload($request->file('image'), 'Event', time() . '_' . getFileName($request->file('image')));
            }

            $event->update([
                'name' => $request->input('name'),
                'location' => $request->input('location'),
                'category_id' => $category->id,
                'price' => $request->input('price'),
                'about' => $request->input('about'),
                'start_date' => $request->input('start_date'),
                'ending_date' => $request->input('ending_date'),
                'available_start_time' => $request->input('available_start_time'),
                'available_end_time' => $request->input('available_end_time'),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'image' => $image,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Event updated successfully',
                'data' => $event,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                "message" => "Event not updated",
                "message" => $e->getMessage()
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $event = Event::where('id', $id,)->where('user_id', Auth::user()->id)->first();
            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found',
                ], 404);
            }

            if ($event->image) {
                Helper::fileDelete($event->image);
            }

            $event->delete();
            return response()->json([
                'success' => true,
                'message' => 'Event deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete event',
                'error' => $e->getMessage(),
            ]);
        }
    }


    public function SubCategory(Request $request)
    {
        try {
            $category = Category::where('type', 'entertainer')->get();
            return response()->json([
                "success" => true,
                "message" => "Sub-category created successfully",
                "category" => $category
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                "failed" => false,
                $e->getMessage()
            ], 500);
        }
    }

    public function SubCategoryCreate(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
            ]);

            $validatedData['type'] = 'entertainer';

            if ($request->hasFile('image')) {
                $validatedData['image'] = Helper::fileUpload($request->file('image'), 'category', time() . '_' . getFileName($request->file('image')));
            }
            $category = Category::create($validatedData);

            return response()->json([
                "success" => true,
                "message" => "Sub-category created successfully",
                "category" => $category
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                "failed" => false,
                $e->getMessage()
            ], 500);
        }
    }
}
