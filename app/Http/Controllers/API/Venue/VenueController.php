<?php

namespace App\Http\Controllers\API\Venue;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Venue;
use Carbon\Carbon;
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
                'price' => 'required|numeric|min:1',
                'start_date' => 'required|date',
                'ending_date' => 'required|date|after_or_equal:start_date',
                'available_start_time' => 'required|date_format:H:i',
                'available_end_time' => 'required|date_format:H:i|after:available_start_time',
                'image.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
                'latitude' => 'nullable',
                'longitude' => 'nullable',

            ]);

            // dd($data);
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
                'start_date' => $request->input('start_date'),
                'ending_date' => $request->input('ending_date'),
                'available_start_time' => $request->input('available_start_time'),
                'available_end_time' => $request->input('available_end_time'),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'image' => $uploadedImages, // JSON format for multiple images
            ]);

            return Helper::jsonResponse(true, 'Venue created successfully.', 201, $data);
        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Category not create",
                "message" => $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $venue = Venue::where('id', $id)->with(['user:id,name,avatar', 'category:id,name,image'])->first();
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

    // //venue update function 
    public function update(Request $request, string $id)
    {
        try {
            $venue = Venue::where('id', $id)
                ->where('user_id', Auth::user()->id)
                ->first();

            if (!$venue) {
                return Helper::jsonResponse(false, 'Venue not found', 404);
            }

            $request->validate([
                'name' => 'nullable|string|max:255|unique:venues,name,' . $id,
                'category_id' => 'nullable|exists:categories,id',
                'description' => 'nullable|string|max:1000',
                'location' => 'nullable|string|max:255',
                'capacity' => 'nullable|integer|min:1',
                'price' => 'nullable|numeric|min:0',
                'available_date' => 'nullable|date',
                'available_start_time' => 'nullable|date_format:H:i',
                'available_end_time' => 'nullable|date_format:H:i|after:available_start_time',
                'image.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
                'latitude' => 'nullable',
                'longitude' => 'nullable',
            ]);

            // Validate category
            $categoryId = $request->category_id ?? $venue->category_id;
            $category = Category::where('id', $categoryId)
                ->where('type', 'venue_holder')
                ->first();

            if (!$category) {
                return Helper::jsonResponse(false, 'Selected category is not valid for venue holders', 422);
            }

            $dataToUpdate = [
                'user_id' => Auth::user()->id,
                'name' => $request->input('name', $venue->name),
                'location' => $request->input('location', $venue->location),
                'category_id' => $category->id,
                'capacity' => $request->input('capacity', $venue->capacity),
                'price' => $request->input('price', $venue->price),
                'description' => $request->input('description', $venue->description),
                'available_date' => $request->input('available_date', $venue->available_date),
                'available_start_time' => $request->input('available_start_time', $venue->available_start_time),
                'available_end_time' => $request->input('available_end_time', $venue->available_end_time),
                'latitude' => $request->input('latitude', $venue->latitude),
                'longitude' => $request->input('longitude', $venue->longitude),
            ];

            if ($request->hasFile('image')) {
                $oldImages = is_array($venue->image) ? $venue->image : json_decode($venue->image ?? '', true);

                if (!empty($oldImages)) {
                    foreach ($oldImages as $oldImage) {
                        $parsedUrl = parse_url($oldImage, PHP_URL_PATH);
                        $oldImagePath = ltrim($parsedUrl, '/');
                        Helper::fileDelete($oldImagePath);
                    }
                }

                $uploadedImages = [];
                foreach ($request->file('image') as $image) {
                    $uploadedImages[] = Helper::fileUpload(
                        $image,
                        'Venue',
                        time() . '_' . $image->getClientOriginalName()
                    );
                }
                $dataToUpdate['image'] = $uploadedImages;
            }

            $venue->update($dataToUpdate);
            return Helper::jsonResponse(true, 'Venue updated successfully.', 200, $venue);
        } catch (Exception $e) {
            Log::error("VenueController::update - " . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to update Venue: ' . $e->getMessage(), 500);
        }
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $venue = Venue::where('id', $id,)->where('user_id', Auth::user()->id)
                ->with('bookings')
                ->first();
            if (!$venue) {
                return Helper::jsonResponse(false, 'Venue not found', 404);
            }

            // Check booked
            $hasBooked = $venue->bookings->contains(function ($booking) {
                return $booking->status === 'booked';
            });

            if ($hasBooked) {
                return Helper::jsonResponse(false, 'Venue cannot be deleted because it has active bookings.', 403);
            }


            // Delete old images
            $oldImages = is_array($venue->image) ? $venue->image : json_decode($venue->image ?? '', true);
            if (!empty($oldImages)) {
                foreach ($oldImages as $oldImage) {
                    $parsedUrl = parse_url($oldImage, PHP_URL_PATH);
                    $oldImagePath = ltrim($parsedUrl, '/');
                    Helper::fileDelete($oldImagePath);
                }
            }

            $venue->delete();
            return Helper::jsonResponse(true, 'Venue deleted successfully.', 200);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Failed to delete Venue', 500);
            Log::error("VenueController::destroy" . $e->getMessage());
        }
    }

    //sub category venue
    public function SubCategory(Request $request)
    {
        try {
            $category = Category::where('type', 'venue_holder')->get();
            if ($category->isEmpty()) {
                return response()->json([
                    "success" => false,
                    "message" => "Sub-category list not found",
                    "category" => []
                ]);
            }

            return Helper::jsonResponse(true, 'Sub-category list retrieved successfully.', 200, [
                'category' => $category,
            ]);
        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => $e->getMessage()
            ], 500);
        }
    }

    // sub category create venue
    public function SubCategoryCreate(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
            ]);

            $validatedData['type'] = 'venue_holder';

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




    //Venue Details user Section 
    public function VenueDetails($id)
    {
        try {
            $Completed = Booking::where('status', 'completed')
                ->where('venue_id', $id)
                ->count();

            $venue = Venue::where('status', 'active')
                ->with('rating')
                ->find($id);

            if (!$venue) {
                return response()->json([
                    "success" => false,
                    "message" => "venue not found or inactive"
                ], 404);
            }

            $start = Carbon::parse($venue->available_start_time);
            $end = Carbon::parse($venue->available_end_time);
            $hours = (int)ceil(abs($start->floatDiffInHours($end)));

            $platform_rate = $hours * $venue->price;

            $dateRange = [];
            if ($venue->start_date && $venue->ending_date) {
                $startDate = Carbon::parse($venue->start_date);
                $endDate = Carbon::parse($venue->ending_date);

                $bookedDates = Booking::where('venue_id', $venue->id)
                    ->pluck('booking_date')
                    ->map(fn($date) => Carbon::parse($date)->toDateString())
                    ->toArray();

                while ($startDate->lte($endDate)) {
                    $currentDate = $startDate->toDateString();
                    if ($currentDate >= Carbon::today()->toDateString() && !in_array($currentDate, $bookedDates)) {
                        $dateRange[] = $currentDate;
                    }

                    $startDate->addDay();
                }
            }
            $dateRange = !empty($dateRange) ? $dateRange : ['No Booking'];

            return response()->json([
                "success" => true,
                "message" => "venue details retrieved successfully",
                "Completed" => $Completed,
                "platform_rate" => $platform_rate,
                "venue" => $venue,
                "Date_range" => $dateRange
            ]);
        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Error retrieving venue details",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    // Venue customer offer update
    public function CustomerOffer(Request $request)
    {
        try {
            $request->validate([
                'booking_id'          => 'required|exists:bookings,id',
                'booking_date'        => 'required|date',
                'booking_start_time'  => 'required|date_format:H:i',
                'booking_end_time'    => 'required|date_format:H:i|after:booking_start_time',
                'platform_rate'       => 'required|numeric',
                'location'            => 'required|string|max:255',
            ]);

            $booking = Booking::with('venue')->findOrFail($request->booking_id);
            if ($booking->venue->user_id !== Auth::user()->id) {
                return Helper::jsonResponse(false, 'You are not authorized to update this booking.', 403);
            }
            $booking->update([
                'booking_date'       => $request->booking_date,
                'booking_start_time' => $request->booking_start_time,
                'booking_end_time'   => $request->booking_end_time,
                'platform_rate'      => $request->platform_rate,
                'capacity'      => $request->capacity,
                'location'           => $request->location,

                // 'custom_Booking'     => 'YES', 
                'custom_Booking'     => true,
            ]);

            return Helper::jsonResponse(true, 'Booking updated successfully', 200, $booking);
        } catch (\Exception $e) {
            return Helper::jsonResponse(false, 'Error: ' . $e->getMessage(), 500);
        }
    }


    public function StatusCustom(Request $request, $id)
    {
        try {
            $booking = Booking::with('user')->select('id', 'platform_rate', 'user_id', 'name', 'status', 'location', 'booking_date', 'booking_start_time', 'booking_end_time', 'platform_rate', 'created_at',)->findOrFail($id);

            $booking->status = 'booked';
            $booking->save();
            return Helper::jsonResponse(true, 'Booking updated successfully', 200, $booking);
        } catch (\Exception $e) {
            return Helper::jsonErrorResponse('Message fetching failed', 403, [$e->getMessage()]);
        }
    }
}
