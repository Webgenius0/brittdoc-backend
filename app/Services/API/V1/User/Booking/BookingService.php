<?php

namespace App\Services\API\V1\User\Booking;

use App\Models\Booking;
use App\Models\BookingPlatformFee;
use App\Models\DailyPricing;
use App\Models\HourlyPricing;
use App\Models\MonthlyPricing;
use App\Models\ParkingSpace;
use App\Models\Payment;
use App\Models\PlatformSetting;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Str;

class BookingService
{
    protected $user;
    public function __construct()
    {
        $this->user = Auth::user();
    }
    /**
     * Fetch all resources.
     *
     * @return mixed
     */

    public function index($request)
    {
        try {
            $status = $request->status ?? 'active';
            $bookings = Booking::with(['parkingSpace:id,slug,title,gallery_images,address,latitude,longitude', 'payment'])
                ->where('user_id', $this->user->id)
                ->select('id', 'unique_id', 'parking_space_id', 'number_of_slot', 'start_time', 'end_time', 'status', 'created_at')
                ->where('status', $status)
                ->latest()
                ->paginate($request->per_page ?? 25);

            $bookings->getCollection()->transform(function ($booking) {
                $now = Carbon::now();
                $start = Carbon::parse($booking->start_time);
                $end = Carbon::parse($booking->end_time);

                $booking->is_critical = false;
                $booking->is_expired = false;
                $booking->is_running = false;

                if ($now->lt($start)) {
                    // Before start time
                    $diffInMinutes = $now->diffInMinutes($start);
                    $diffInDays = floor($diffInMinutes / (60 * 24));
                    $hours = floor(($diffInMinutes % (60 * 24)) / 60);
                    $minutes = $diffInMinutes % 60;

                    if ($diffInMinutes <= 10) {
                        $booking->is_critical = true;
                    }

                    $status = '';
                    if ($diffInDays > 0) {
                        $status .= "{$diffInDays} Day(s) ";
                    }
                    if ($hours > 0) {
                        $status .= "{$hours} Hour(s) ";
                    }
                    $status .= "{$minutes} Minute(s) Left To Start Parking";

                    $booking->parking_status = $status;

                } elseif ($now->between($start, $end)) {
                    // Parking Running
                    $diffInMinutes = $now->diffInMinutes($end);
                    $diffInDays = floor($diffInMinutes / (60 * 24));
                    $hours = floor(($diffInMinutes % (60 * 24)) / 60);
                    $minutes = $diffInMinutes % 60;

                    $booking->is_running = true;

                    if ($diffInMinutes <= 10) {
                        $booking->is_critical = true;
                    }

                    $status = '';
                    if ($diffInDays > 0) {
                        $status .= "{$diffInDays} Day(s) ";
                    }
                    if ($hours > 0) {
                        $status .= "{$hours} Hour(s) ";
                    }
                    $status .= "{$minutes} Minute(s) Left To End Parking";

                    $booking->parking_status = $status;

                } else {
                    // After end time
                    $booking->is_expired = true;
                    $booking->parking_status = "Parking Time Ended";
                }

                return $booking;
            });


            return $bookings;
        } catch (Exception $e) {
            Log::error("BookingService::index " . $e->getMessage());
            throw $e;
        }
    }


    /**
     * Store a new resource.
     *
     * @param array $validatedData
     * @return mixed
     */
    public function store(array $validatedData)
    {
        try {
            DB::beginTransaction();
            // Combine date + time to generate full timestamps
            $startDateTime = Carbon::parse($validatedData['booking_date_start'] . ' ' . $validatedData['booking_time_start'])->format('Y-m-d H:i:s');
            $endDateTime = Carbon::parse($validatedData['booking_date_end'] . ' ' . $validatedData['booking_time_end'])->format('Y-m-d H:i:s');
            $validatedData['start_time'] = $startDateTime;
            $validatedData['end_time'] = $endDateTime;
            $validatedData['user_id'] = $this->user->id;
            $validatedData['unique_id'] = (string) Str::uuid();
            $checkPricingType = $this->checkPricingType($validatedData);
            $validatedData['estimated_hours'] = $checkPricingType->estimated_hours;
            $validatedData['estimated_price'] = $checkPricingType->estimated_price;
            $validatedData['platform_fee'] = $this->platformFee($validatedData['estimated_price']);
            $validatedData['total_price'] = ($checkPricingType->estimated_price + $validatedData['platform_fee']) * $validatedData['number_of_slot'];
            $singlePrice = $checkPricingType->rate;
            $validatedData['per_hour_price'] = $singlePrice;
            $this->checkParkingSlotAvailbelity($validatedData);
            // Create the booking
            $booking = Booking::create($validatedData);
            $this->platformFeeAssign($booking->id);
            DB::commit();
            return $booking;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("BookingService::store" . $e->getMessage());
            throw $e;
        }
    }


    private function checkParkingSlotAvailbelity($validatedData)
    {

        $parkingSpace = ParkingSpace::where('status', 'available')->where('id', $validatedData['parking_space_id'])->first();
        if (!$parkingSpace) {
            throw new Exception('Parking Space not available', 404);
        }
        $bookingsQuery = Booking::where('parking_space_id', $validatedData['parking_space_id'])
            ->whereNotIn('status', ['cancelled', 'completed', 'close']);

        // Optional: Filter by date/time range if provided
        if ($validatedData['booking_date_start']) {
            $bookingsQuery->whereDate('start_time', '>=', $validatedData['booking_date_start']);
        }
        if ($validatedData['booking_date_end']) {
            $bookingsQuery->whereDate('end_time', '<=', $validatedData['booking_date_end']);
        }
        if ($validatedData['start_time']) {
            $bookingsQuery->whereTime('booking_time_start', '<=', $validatedData['start_time']);
        }
        if ($validatedData['end_time']) {
            $bookingsQuery->whereTime('booking_time_end', '>=', $validatedData['end_time']);
        }

        // Sum up number_of_slot (default to 1 if null)
        $bookingCount = $bookingsQuery->get()->sum(function ($booking) {
            return $booking->number_of_slot ?? 1;
        });

        $booking_count = $bookingCount;
        // dd($booking_count);
        $available_slots = max(0, $parkingSpace->total_slots - $bookingCount);
        Log::info('parking_space_id: ' . $validatedData['parking_space_id'] . ' ,Available slots: ' . $available_slots . ', Booking count: ' . $booking_count);
        if ($available_slots < $validatedData['number_of_slot']) {
            throw new Exception(' Not enough available slots', 400);
        }

        // dd($available_slots);
    }

    private function checkPricingType($validatedData)
    {

        // Validate pricing_id based on pricing_type
        if ($validatedData['pricing_type'] == 'hourly') {
            $dayNames = [];

            $startDate = $validatedData['booking_date_start'];
            $endDate = $validatedData['booking_date_end'];
            if ($startDate && !$endDate) {
                $dayNames[] = Carbon::parse($startDate)->format('l');
            } elseif ($startDate && $endDate) {
                $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
                foreach ($period as $date) {
                    $day = $date->format('l');
                    if (!in_array($day, $dayNames)) {
                        $dayNames[] = $day;
                    }
                }
            }
            $hourlyPricing = HourlyPricing::where('id', $validatedData['pricing_id'])
                ->whereHas('days', function ($q) use ($dayNames) {
                    if (!empty($dayNames)) {
                        $q->whereIn('day', $dayNames);
                    }
                    $q->where('status', 'available'); // Filter days by status
                })
                ->Where('status', 'active')
                ->When($validatedData['booking_time_start'], function ($query) use ($validatedData) {
                    $query->whereTime('start_time', '<=', $validatedData['booking_time_start'])
                        ->whereTime('end_time', '>=', $validatedData['booking_time_end']);
                })
                ->first();
            // Log::info($dayNames);
            if (!$hourlyPricing) {
                throw new Exception(' Hourly pricing not found', 404);
            }
            $startTime = $validatedData['booking_time_start'] ?? now()->format('H:i');
            $endTime = $validatedData['booking_time_end'] ?? (new Carbon($startTime))->addHour()->format('H:i');
            $startDate = $validatedData['booking_date_start'] ?? now()->format('Y-m-d');
            $endDate = $validatedData['booking_date_end'];

            if ($startTime && $endTime) {
                $dailyHours = Carbon::parse($startTime)->floatDiffInHours(Carbon::parse($endTime));
                if ($endDate) {
                    $totalHours = Carbon::parse("$startDate $startTime")->floatDiffInHours(Carbon::parse("$endDate $endTime"));
                    Log::info('totalHours' . $totalHours);
                } else {
                    $totalHours = $dailyHours;
                }

                $hourlyPricing->estimated_hours = round($totalHours, 2);
                $hourlyPricing->estimated_price = round($totalHours * $hourlyPricing->rate, 2);
            }
            Log::info($hourlyPricing);
            return $hourlyPricing;




        } elseif ($validatedData['pricing_type'] == 'daily') {
            $dailyPricing = DailyPricing::where('id', $validatedData['pricing_id'])->Where('status', 'active')->first();
            if (!$dailyPricing) {
                throw new Exception('Daily pricing not found', 404);
            }
            Log::info($dailyPricing);
            return $dailyPricing;
        } elseif ($validatedData['pricing_type'] == 'monthly') {
            $monthlyPricing = MonthlyPricing::where('id', $validatedData['pricing_id'])->Where('status', 'active')->first();
            if (!$monthlyPricing) {
                throw new Exception('Monthly pricing not found', 404);
            }
            Log::info($monthlyPricing);
            return $monthlyPricing;
        }
        ;
    }

    private function platformFeeAssign($booking_id)
    {
        $platform_fee = PlatformSetting::where('status', 'active')->get();
        // Log::info("Platform fee: " . $platform_fee);
        if (!$platform_fee) {
            return true;
        } else {
            foreach ($platform_fee as $fee) {
                $platform_fee = BookingPlatformFee::create([
                    'booking_id' => $booking_id,
                    'platform_setting_id' => $fee->id,
                    'key' => $fee->key,
                    'value' => $fee->value,
                ]);
            }
            Log::info("Platform fee assigned booking_id: " . $booking_id);
        }
    }

    private function platformFee($price)
    {
        $platform_fee = PlatformSetting::where('status', 'active')->get();
        // Log::info("Platform fee: " . $platform_fee);
        if (!$platform_fee) {
            return 0;
        }

        $total = 0;
        foreach ($platform_fee as $fee) {
            $total += $price / 100 * $fee->value;
        }

        Log::info("Platform fee total: " . $total);
        return $total;
    }



    /**
     * Display a specific resource.
     *
     * @param int $id
     * @return mixed
     */

    public function show($unique_id)
    {
        try {
            $booking = Booking::with(['parkingSpace:id,slug,title,gallery_images,address,latitude,longitude', 'payment'])
                ->where('user_id', $this->user->id)
                ->select('id', 'unique_id', 'parking_space_id', 'number_of_slot', 'start_time', 'end_time', 'status', 'created_at')
                ->where('unique_id', $unique_id)
                ->firstOrFail();

            $now = Carbon::now();
            $start = Carbon::parse($booking->start_time);
            $end = Carbon::parse($booking->end_time);

            $booking->is_critical = false;
            $booking->is_expired = false;
            $booking->is_running = false;

            if ($now->lt($start)) {
                // Before parking starts
                $diffInMinutes = $now->diffInMinutes($start);

                if ($diffInMinutes <= 10) {
                    $booking->is_critical = true;
                }

                $hours = floor($diffInMinutes / 60);
                $minutes = $diffInMinutes % 60;

                if ($hours > 0) {
                    $booking->parking_status = "{$hours} Hour(s) {$minutes} Minute(s) Left To Start Parking";
                } else {
                    $booking->parking_status = "{$minutes} Minute(s) Left To Start Parking";
                }

            } elseif ($now->between($start, $end)) {
                // Parking is running
                $diffInMinutes = $now->diffInMinutes($end);
                $booking->is_running = true;

                if ($diffInMinutes <= 10) {
                    $booking->is_critical = true;
                }

                $hours = floor($diffInMinutes / 60);
                $minutes = $diffInMinutes % 60;

                if ($hours > 0) {
                    $booking->parking_status = "{$hours} Hour(s) {$minutes} Minute(s) Left To End Parking";
                } else {
                    $booking->parking_status = "{$minutes} Minute(s) Left To End Parking";
                }

            } else {
                // Parking ended
                $booking->is_expired = true;
                $booking->parking_status = "Parking Time Ended";
            }

            return $booking;

        } catch (Exception $e) {
            Log::error("BookingService::show " . $e->getMessage());
            throw $e;
        }
    }



    /**
     * Update a specific resource.
     *
     * @param int $id
     * @param array $validatedData
     * @return mixed
     */
    public function update(int $id, array $validatedData)
    {
        try {

        } catch (Exception $e) {
            Log::error("BookingService::update" . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a specific resource.
     *
     * @param int $id
     * @return mixed
     */
    public function destroy(int $id)
    {
        try {
            $booking = Booking::where('id', $id)->where('user_id', $this->user->id)->first();
            if (!$booking) {
                throw new Exception('Booking not found or you do not own this booking', 404);
            }
            $booking->delete();
            return true;
        } catch (Exception $e) {
            Log::error("BookingService::destroy" . $e->getMessage());
            throw $e;
        }
    }


    public function userDashboardData()
    {
        try {
            $bookingCounts = Booking::where('user_id', $this->user->id)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status');
            $data = [
                'booking_pending' => $bookingCounts['pending'] ?? 0,
                'booking_active' => $bookingCounts['active'] ?? 0,
                'booking_confirmed' => $bookingCounts['confirmed'] ?? 0,
                'booking_cancelled' => $bookingCounts['cancelled'] ?? 0,
                'booking_close' => $bookingCounts['close'] ?? 0,
                'booking_completed' => $bookingCounts['completed'] ?? 0,
            ];

            return $data;
        } catch (Exception $e) {
            Log::error("BookingService::userDashboardData" . $e->getMessage());
            throw $e;
        }
    }
    public function userDashboardTransactions($request)
    {
        try {
            $per_page = $request->per_page ?? 25;
            $payments = Payment::where('user_id', $this->user->id)
                ->select('id', 'transaction_number', 'booking_id', 'amount', 'status', 'created_at')
                ->with(['booking.parkingSpace:id,address', 'booking:id,parking_space_id'])
                ->paginate($per_page);
            return $payments;
        } catch (Exception $e) {
            Log::error("BookingService::userDashboardData" . $e->getMessage());
            throw $e;
        }
    }


}