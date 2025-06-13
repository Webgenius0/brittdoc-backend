<?php

namespace App\Http\Controllers\API\Entertrainer;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\OffDay;
use App\Models\Weekday;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
                ->with([
                    'rating.user:id,name,avatar',
                    'user:id,name,avatar'
                ])
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

    //============================================================================Client Side Update==================================================================================

    public function eventCreate(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:events,name',
                'location' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id,type,entertainer',
                'price' => 'required|numeric|min:1',
                'about' => 'required|string|max:1000',
                'start_date' => 'required|date|after_or_equal:today',
                'ending_date' => 'required|date|after_or_equal:start_date',
                'weekdays' => 'required|array|min:1',
                'weekdays.*.day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'weekdays.*.available_start_time' => 'nullable|date_format:H:i',
                'weekdays.*.available_end_time' => 'nullable|date_format:H:i|after:weekdays.*.available_start_time',
                'weekdays.*.is_active' => 'sometimes|boolean',
                'unavailable_date' => 'nullable|array',
                'unavailable_date.*' => 'date',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20480',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',

            ]);

            if ($request->file('image')) {
                $image = Helper::fileUpload($request->file('image'), 'Event', time() . '_' . getFileName($request->file('image')));
            } else {
                $image = null;
            }

            // Create event
            $event = Event::create([
                'user_id' => Auth::id(),
                'name' => $validated['name'],
                'location' => $validated['location'],
                'category_id' => $validated['category_id'],
                'price' => $validated['price'],
                'about' => $validated['about'],
                'start_date' => $validated['start_date'],
                'ending_date' => $validated['ending_date'],
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'image' => $image,
            ]);

            //customize weekdays handling
            $allWeekdays = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            $defaultSchedule = [
                'available_start_time' => '09:00',
                'available_end_time' => '12:00',
                'is_active' => true,
            ];
            $weekdaysData = [];
            foreach ($allWeekdays as $day) {
                $weekdaysData[$day] = $defaultSchedule;
            }

            // Merge with provided data if exists
            if (!empty($validated['weekdays'])) {
                foreach ($validated['weekdays'] as $providedDay) {
                    $dayName = strtolower($providedDay['day']);
                    if (in_array($dayName, $allWeekdays)) {
                        $weekdaysData[$dayName] = [
                            'available_start_time' => $providedDay['available_start_time'] ?? $defaultSchedule['available_start_time'],
                            'available_end_time' => $providedDay['available_end_time'] ?? $defaultSchedule['available_end_time'],
                            'is_active' => $providedDay['is_active'] ?? $defaultSchedule['is_active']
                        ];
                    }
                }
            }

            // Create records for all 7 days
            foreach ($weekdaysData as $dayName => $schedule) {
                Weekday::create([
                    'event_id' => $event->id,
                    'weekday' => $dayName,
                    'available_start_time' => $schedule['available_start_time'],
                    'available_end_time' => $schedule['available_end_time'],
                    'is_active' => $schedule['is_active']
                ]);
            }
            // off days
            if (!empty($validated['unavailable_date'])) {
                OffDay::create([
                    'event_id' => $event->id,
                    'unavailable_date' => $validated['unavailable_date'],
                ]);
            }

            // Generate availability preview
            $availability = $this->generateAvailabilityPreview1(
                $validated['start_date'],
                $validated['ending_date'],
                $weekdaysData,
                $validated['unavailable_date'] ?? []
            );
            DB::commit();

            return Helper::jsonResponse(true, 'Event created successfully', 201, [
                'event' => $event->load('weekdays', 'offDays'),
                'availability_preview' => $availability
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'failed' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'failed' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }
    protected function generateAvailabilityPreview1($startDate, $endDate, $weekdaysData, $unavailable_date)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $activeDays = collect($weekdaysData)
            ->filter(fn($day) => $day['is_active'])
            ->keys()
            ->toArray();

        $unactiveDays = collect($weekdaysData)
            ->filter(fn($day) => !$day['is_active'])
            ->keys()
            ->toArray();

        $unavailable_date = collect($unavailable_date)
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->toArray();

        $availableDates = [];
        $unavailableDates = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $formattedDate = $date->format('Y-m-d');
            $dayName = strtolower($date->format('l'));

            if (in_array($formattedDate, $unavailable_date)) {
                $unavailableDates[] = $formattedDate;
                continue;
            }

            if (in_array($dayName, $activeDays)) {
                $availableDates[] = $formattedDate;
            } else {
                $unavailableDates[] = $formattedDate;
            }
        }

        return [
            'active_weekdays' => $activeDays,
            'unactive_weekdays' => $unactiveDays,
            'off_days' => $unavailable_date,
            'available_dates' => $availableDates,
            'unavailable_dates' => $unavailableDates,
        ];
    }


    // Generate availability preview details and update
    protected function generateAvailabilityPreview($startDate, $endDate, $weekdays, $unavailable_date)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $activeDays = collect($weekdays)->where('is_active', true)->pluck('day')->map(fn($day) => strtolower($day))->toArray();
        $UnactiveDays = collect($weekdays)->where('is_active', false)->pluck('day')->map(fn($day) => strtolower($day))->toArray();

        $unavailable_date = collect($unavailable_date)
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->toArray();

        $availableDates = [];
        $unavailableDates = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $formattedDate = $date->format('Y-m-d');
            $dayName = strtolower($date->format('l'));

            if (in_array($formattedDate, $unavailable_date)) {
                $unavailableDates[] = $formattedDate;
                continue;
            }

            if (in_array($dayName, $activeDays)) {
                $availableDates[] = $formattedDate;
            } else {
                $unavailableDates[] = $formattedDate;
            }
           
        }

        return [
            'active_weekdays' => $activeDays,
            'Unactive_weekdays' => $UnactiveDays,
            'off_days' => $unavailable_date,
            'available_dates' => $availableDates,
            'unavailable_dates' => $unavailableDates,
        ];
    }

    //event details show
    public function EventDetails($id)
    {
        try {
            $event = Event::where('user_id', Auth::user()->id)->with(['weekdays', 'offDays'])->find($id);
            if (!$event) {
                return Helper::jsonResponse(false, 'Event not found', 404);
            }
            // Prepare data for availability preview
            $weekdays = $event->weekdays->map(function ($day) {
                return [
                    'day' => $day->weekday,
                    'start_time' => $day->start_time,
                    'end_time' => $day->end_time,
                    'is_active' => (bool) $day->is_active,
                ];
            })->toArray();

            $offDays = $event->offDays->pluck('off_date')->toArray();

            // Generate availability preview
            $availability = $this->generateAvailabilityPreview(
                $event->start_date,
                $event->ending_date,
                $weekdays,
                $offDays
            );

            return Helper::jsonResponse(
                true,
                'Event details fetched successfully',
                200,
                [
                    'event' => $event,
                    'availability_preview' => $availability,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return Helper::jsonResponse(false, 'Event not found', 404);
        } catch (Exception $e) {
            return Helper::jsonResponse(
                false,
                'Server error:' . $e->getMessage(),
                500
            );
        }
    }

    //entertainer event update
    public function updateEvent(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:255',
                'price' => 'nullable|numeric|min:0',
                'about' => 'nullable|string|max:1000',
                'start_date' => 'nullable|date|after_or_equal:today',
                'ending_date' => 'nullable|date|after_or_equal:start_date',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20480',
                'weekdays' => 'nullable|array',
                'weekdays.*.day' => 'required_with:weekdays|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'weekdays.*.available_start_time' => 'nullable|date_format:H:i',
                'weekdays.*.available_end_time' => 'nullable|date_format:H:i',
                'weekdays.*.is_active' => 'sometimes|boolean',
                'unavailable_date' => 'nullable|array',
                'unavailable_date.*' => 'date|after_or_equal:start_date|before_or_equal:ending_date',
            ]);

            // $event = Event::find($id);
            // // if (!$event) {
            // //     return response()->json([
            // //         'message' => 'Event ID not found.',
            // //     ], 404);
            // // } 
            // if ($event->user_id !== Auth::user()->id) {
            //     return response()->json([
            //         'message' => 'You are not authorized to update this event.',
            //     ], 403);
            // }

            $event = Event::where('id', $id)
                ->where('user_id', Auth::user()->id)
                ->first();

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found',
                ], 404);
            }


            $image = $event->image;
            if ($request->hasFile('image')) {
                if ($event->image) {
                    $parsedUrl = parse_url($event->image, PHP_URL_PATH);
                    $oldImagePath = ltrim($parsedUrl, '/');
                    Helper::fileDelete($oldImagePath);
                }
                $image = Helper::fileUpload($request->file('image'), 'Event', time() . '_' . $request->file('image')->getClientOriginalName());
            }

            // Update event fields
            $event->update([
                'name' => $validated['name'] ?? $event->name,
                'location' => $validated['location'] ?? $event->location,
                'category_id' => $validated['category_id'] ?? $event->category_id,
                'price' => $validated['price'] ?? $event->price,
                'about' => $validated['about'] ?? $event->about,
                'image' => $image,
                'start_date' => $validated['start_date'] ?? $event->start_date,
                'ending_date' => $validated['ending_date'] ?? $event->ending_date,
                'latitude' => $validated['latitude'] ?? $event->latitude,
                'longitude' => $validated['longitude'] ?? $event->longitude,
            ]);


            if (isset($validated['weekdays'])) {
                foreach ($validated['weekdays'] as $weekdayData) {
                    $weekday = $event->weekdays()->where('weekday', $weekdayData['day'])->first();
                    if ($weekday) {
                        // Optional: check time validity
                        if (!empty($weekdayData['available_start_time']) && !empty($weekdayData['available_end_time'])) {
                            if (strtotime($weekdayData['available_start_time']) >= strtotime($weekdayData['available_end_time'])) {
                                throw new \Exception("available_end_time must be after available_start_time for {$weekdayData['day']}.");
                            }
                        }
                        $weekday->update([
                            'available_start_time' => $weekdayData['available_start_time'] ?? $weekday->available_start_time,
                            'available_end_time' => $weekdayData['available_end_time'] ?? $weekday->available_end_time,
                            'is_active' => $weekdayData['is_active'] ?? $weekday->is_active,
                        ]);
                    } else {
                        $event->weekdays()->create([
                            'weekday' => $weekdayData['day'],
                            'available_start_time' => $weekdayData['available_start_time'] ?? null,
                            'available_end_time' => $weekdayData['available_end_time'] ?? null,
                            'is_active' => $weekdayData['is_active'] ?? true,
                        ]);
                    }
                }
            }

            // Save off days
            if (!empty($validated['unavailable_date'])) {
                OffDay::updateOrCreate(
                    ['event_id' => $event->id],
                    ['unavailable_date' => $validated['unavailable_date']]
                );
            }

            DB::commit();
            $availability = $this->generateAvailabilityPreview(
                $validated['start_date'],
                $validated['ending_date'],
                $validated['weekdays'],
                $validated['unavailable_date'] ?? []
            );


            return response()->json([
                'message' => 'Event updated successfully.',
                'event' => $event->fresh(['weekdays', 'offdays']),
                'availabilty' => $availability
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Event update failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //delete entertainer event
    public function eventDelete($id)
    {
        DB::beginTransaction();
        try {
            $event = Event::where('id', $id,)->where('user_id', Auth::user()->id)->with('bookings')->first();
            if (!$event) {
                return response()->json([
                    'failed' => false,
                    'message' => 'Event ID not found',
                ], 404);
            }

            //check booking
            $hasBooked = $event->bookings->contains(function ($booking) {
                return $booking->status === 'booked';
            });

            if ($hasBooked) {
                return response()->json([
                    'failed' => false,
                    'message' => 'Event cannot be deleted because it has active bookings.',
                    'warning' => true,
                ], 403);
            }
            if ($event->image) {
                $parsedUrl = parse_url($event->image, PHP_URL_PATH);
                $oldImagePath = ltrim($parsedUrl, '/');
                Helper::fileDelete($oldImagePath);
            }

            $event->weekdays()->delete();
            $event->offDays()->delete();
            $event->delete();

            DB::commit();
            return Helper::jsonResponse(true, 'Event deleted successfully', 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
                $e->getMessage()
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }
  
}
