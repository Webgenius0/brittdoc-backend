<?php

namespace App\Http\Controllers\API\Entertrainer;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\Message;
use App\Models\Venue;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{

    //all entertainer event list 
    public function index(Request $request)
    {
        try {
            $search = $request->query('search', '');
            $per_page = $request->query('per_page', 100);

            $query = Event::with('category:id,name,image')->select('id', 'category_id', 'user_id', 'name', 'price', 'location', 'image', 'created_at');

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

            return Helper::jsonResponse(true, 'All List Event retrieved successfully.', 200, $event, true);
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
                'image' => 'nullable|image|max:20240',
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
     * Entertainer Event Details 
     */
    public function show(string $id)
    {
        try {
            $event = Event::where('id', $id)->with('category:id,name', 'user:id,name,avatar')->first();
            if (!$event) {
                return Helper::jsonResponse(false, 'Event ID  not found', 404);
            }
            return Helper::jsonResponse(true, "Event Details retrieved successfully", 200, $event);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Event not found', 500, [$e->getMessage()]);
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

            return Helper::jsonResponse(true, "Event retrieved successfull", 200, $event);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Event not found', 500, [$e->getMessage()]);
        }
    }

    //update
    public function update(Request $request, string $id)
    {
        try {
            $event = Event::where('id', $id)
                ->where('user_id', Auth::user()->id)
                ->first();

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found',
                ], 404);
            }

            $request->validate([
                'name' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:255',
                'category_id' => 'nullable|exists:categories,id',
                'price' => 'nullable|numeric|min:0',
                'about' => 'nullable|string|max:1200',
                'start_date' => 'nullable|date',
                'ending_date' => 'nullable|date|after_or_equal:start_date',
                'available_start_time' => 'nullable|date_format:H:i',
                'available_end_time' => 'nullable|date_format:H:i|after:available_start_time',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20240',
                'latitude' => 'nullable',
                'longitude' => 'nullable',
            ]);

            $category = Category::where('id', $request->category_id)
                ->where('type', 'entertainer')
                ->first();

            if (!$category && $request->category_id) {
                return Helper::jsonResponse(false, 'Selected category is not valid for entertainer', 422);
            }

            // handle image
            $image = $event->image;
            if ($request->hasFile('image')) {
                if ($event->image) {
                    $parsedUrl = parse_url($event->image, PHP_URL_PATH);
                    $oldImagePath = ltrim($parsedUrl, '/');
                    Helper::fileDelete($oldImagePath);
                }
                $image = Helper::fileUpload($request->file('image'), 'Event', time() . '_' . $request->file('image')->getClientOriginalName());
            }

            // final update - only once
            $event->update([
                'name' => $request->input('name', $event->name),
                'location' => $request->input('location', $event->location),
                'category_id' => $category ? $category->id : $event->category_id,
                'price' => $request->input('price', $event->price),
                'about' => $request->input('about', $event->about),
                'start_date' => $request->input('start_date', $event->start_date),
                'ending_date' => $request->input('ending_date', $event->ending_date),
                'available_start_time' => $request->input('available_start_time', $event->available_start_time),
                'available_end_time' => $request->input('available_end_time', $event->available_end_time),
                'latitude' => $request->input('latitude', $event->latitude),
                'longitude' => $request->input('longitude', $event->longitude),
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
                'message' => 'Event not updated',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $event = Event::where('id', $id,)->where('user_id', Auth::user()->id)->with('bookings')->first();
            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found',
                ], 404);
            }

            //check booking
            $hasBooked = $event->bookings->contains(function ($booking) {
                return $booking->status === 'booked';
            });

            if ($hasBooked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event cannot be deleted because it has active bookings.',
                    'warning' => true,
                ], 403);
            }


            if ($event->image) {
                $parsedUrl = parse_url($event->image, PHP_URL_PATH);
                $oldImagePath = ltrim($parsedUrl, '/');
                Helper::fileDelete($oldImagePath);
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
                "message" => "Sub-category List successfully",
                "category" => $category
            ]);
        } catch (Exception $e) {
            return response()->json([
                $e->getMessage()
            ], 500);
        }
    }

    public function SubCategoryCreate(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240'
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
            ]);
        } catch (Exception $e) {
            return response()->json([
                "failed" => false,
                $e->getMessage()
            ], 500);
        }
    }


    //show entertainer category(2 items) wish and id pass show all category  
    public function entertainer(Request $request)
    {
        try {
            $searchName  = $request->search;
            $categoryIds = $request->category_id;

            if ($categoryIds) {
                $categoryIds = is_array($categoryIds) ? $categoryIds : explode(',', $categoryIds);
            }

            $query = Event::query()->with(['user:id,name', 'category:id,name']);

            if ($searchName) {
                $query->where(function ($q) use ($searchName) {
                    $q->where('name', 'like', "%{$searchName}%")
                        ->orWhereHas('category', function ($q2) use ($searchName) {
                            $q2->where('name', 'like', "%{$searchName}%");
                        });
                });
            }


            if (!empty($categoryIds)) {
                $query->whereIn('category_id', $categoryIds);
            }

            $events = $query->get()->makeHidden(['created_at', 'updated_at', 'status']);

            if ($events->isEmpty()) {
                return Helper::jsonResponse(true, 'No events found.', 200);
            }
            $groupedEvents = $events->groupBy(function ($item) {
                return $item->category->name ?? ' Category';
            });

            return Helper::jsonResponse(true, 'Event data grouped by category.', 200, $groupedEvents);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Failed to retrieve event data.', 403, [$e->getMessage()]);
        }
    }


    //entertainer Category Details show
    public function entertainerCategoryDetails($id)
    {
        try {
            $Completed = Booking::where('status', 'completed')
                ->where('event_id', $id)
                ->count();

            $event = Event::where('status', 'active')
                ->with('rating')
                ->find($id);

            if (!$event) {
                return response()->json([
                    "success" => false,
                    "message" => "event not found or inactive"
                ], 404);
            }

            $start = Carbon::parse($event->available_start_time);
            $end = Carbon::parse($event->available_end_time);
            $hours = (int)ceil(abs($start->floatDiffInHours($end)));

            $platform_rate = $hours * $event->price;

            $dateRange = [];
            if ($event->start_date && $event->ending_date) {
                $startDate = Carbon::parse($event->start_date);
                $endDate = Carbon::parse($event->ending_date);

                $bookedDates = Booking::where('event_id', $event->id)
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
                "message" => "event details retrieved successfully",
                "Completed" => $Completed,
                "platform_rate" => $platform_rate,
                "event" => $event,
                "Date_range" => $dateRange
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Error retrieving event details",
                "error" => $e->getMessage()
            ], 500);
        }
    }

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

            $booking = Booking::with('event')->findOrFail($request->booking_id);
            if ($booking->event->user_id !== Auth::user()->id) {
                return Helper::jsonResponse(false, 'You are not authorized to update this booking.', 403);
            }

            $booking->update([
                'booking_date'       => $request->booking_date,
                'booking_start_time' => $request->booking_start_time,
                'booking_end_time'   => $request->booking_end_time,
                'platform_rate'      => $request->platform_rate,
                'location'          => $request->location,
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
            $booking = Booking::with('user')->select('id', 'user_id', 'platform_rate', 'name', 'status', 'location', 'booking_date', 'booking_start_time', 'booking_end_time', 'platform_rate', 'created_at',)->findOrFail($id);

            $booking->status = 'booked';
            $booking->save();
            return Helper::jsonResponse(true, 'Booking updated successfully', 200, $booking);
        } catch (\Exception $e) {
            return Helper::jsonErrorResponse('Message fetching failed', 403, [$e->getMessage()]);
        }
    }
}
