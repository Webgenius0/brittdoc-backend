<?php

namespace App\Services\API\V1\User\ParkingSpace;

use App\Models\Booking;
use App\Models\HourlyPricing;
use App\Models\PlatformSetting;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Request;

class UserParkingSpaceService
{
    protected $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }
    public function getHourlyPricing($request)
    {
        $perPage = $request->per_page ?? 25;
        $startTime = $request->start_time ?? now()->format('H:i');
        $isStartTime = $request->start_time ?? null;
        $endTime = $isStartTime ? ($request->end_time ?? (new Carbon($startTime))->addHour()->format('H:i')) : (new Carbon($startTime))->addHour()->format('H:i');
        $startDate = $request->start_date ?? now()->format('Y-m-d');
        $endDate = $request->end_date;
        $latitude = $request->latitude ?? 12.9716770;
        $longitude = $request->longitude ?? 77.5946770;
        $radius = $request->radius ?? 500;
        // dd($endTime);
        $dayNames = [];
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

        $haversine = "(6371 * acos(cos(radians(?)) 
                    * cos(radians(parking_spaces.latitude)) 
                    * cos(radians(parking_spaces.longitude) - radians(?)) 
                    + sin(radians(?)) 
                    * sin(radians(parking_spaces.latitude))))";

        $hourlyPricing = HourlyPricing::with([
            'parkingSpace.driverInstructions',
            'parkingSpace.reviews' => function ($query) {
                $query->where('status', 'approved');
            },
            'parkingSpace.spotDetails',
            'days'
        ])
            ->join('parking_spaces', 'hourly_pricings.parking_space_id', '=', 'parking_spaces.id')
            ->where('hourly_pricings.status', 'active')
            ->where('parking_spaces.status', 'available')
            ->where('parking_spaces.is_verified', true)
            ->whereNull('parking_spaces.deleted_at')
            ->whereRaw("$haversine <= ?", [$latitude, $longitude, $latitude, $radius])
            ->select('hourly_pricings.*')
            ->selectRaw("$haversine AS distance", [$latitude, $longitude, $latitude])
            ->whereHas('days', function ($q) use ($dayNames) {
                if (!empty($dayNames)) {
                    $q->whereIn('day', $dayNames);
                }
                $q->where('status', 'available');
            })
            ->when($startTime, fn($q) => $q->whereTime('start_time', '<=', $startTime))
            ->when($endTime, fn($q) => $q->whereTime('end_time', '>=', $endTime))
            ->orderBy('distance', 'asc');

        return $hourlyPricing->paginate($perPage);
    }

    public function transformPricingData($result, $startTime, $endTime, $startDate, $endDate)
    {
        // dd($startTime. $endTime. $startDate. $endDate);
        Log::info('hourly pricing', [$startTime, $endTime, $startDate, $endDate]);
        return $result->getCollection()->map(function ($pricing) use ($startTime, $endTime, $startDate, $endDate) {
            $bookingsQuery = Booking::where('parking_space_id', $pricing->parking_space_id)
                ->whereNotIn('status', ['cancelled', 'close', 'completed']);

            if ($startDate) {
                $bookingsQuery->whereDate('start_time', '>=', $startDate);
            }
            if ($endDate) {
                $bookingsQuery->whereDate('end_time', '<=', $endDate);
            }
            if ($startTime) {
                $bookingsQuery->whereTime('booking_time_start', '<=', $startTime);
            }
            if ($endTime) {
                $bookingsQuery->whereTime('booking_time_end', '>=', $endTime);
            }

            $bookingCount = $bookingsQuery->get()->sum(fn($b) => $b->number_of_slot ?? 1);
            $pricing->booking_count = $bookingCount;
            $pricing->available_slots = max(0, $pricing->parkingSpace->total_slots - $bookingCount);

            if ($startTime && $endTime) {
                $dailyHours = Carbon::parse($startTime)->floatDiffInHours(Carbon::parse($endTime));
                if ($endDate) {
                    $totalHours = Carbon::parse("$startDate $startTime")->floatDiffInHours(Carbon::parse("$endDate $endTime"));
                } else {
                    $totalHours = $dailyHours;
                }

                $pricing->estimated_hours = round($totalHours, 2);
                $pricing->estimated_price = round($totalHours * $pricing->rate, 2);
            }

            $reviews = $pricing->parkingSpace->reviews;
            $pricing->review_count = $reviews->count();
            $pricing->average_rating = $reviews->count() > 0 ? round($reviews->avg('rating'), 2) : 0;

            return $pricing;
        });
    }
    public function getHourlyPricingDetails($id, $request)
    {
        try {
            $startTime = $request->start_time ?? now()->format('H:i');
            $endTime = $request->start_time ? ($request->end_time ?? (new Carbon($startTime))->addHour()->format('H:i')) : (new Carbon($startTime))->addHour()->format('H:i');
            $startDate = $request->start_date ?? now()->format('Y-m-d');
            $endDate = $request->end_date;
            // get day names
            $dayNames = [];
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
            // Log::info($dayNames);
            $pricing = HourlyPricing::with([
                'parkingSpace.driverInstructions',
                'parkingSpace.reviews' => function ($query) {
                    $query->where('status', 'approved');
                },
                'parkingSpace.spotDetails',
                'days'
            ])
                ->whereHas('days', function ($q) {
                    $q->where('status', 'available');
                })
                ->when($startTime, fn($q) => $q->whereTime('start_time', '<=', $startTime))
                ->when($endTime, fn($q) => $q->whereTime('end_time', '>=', $endTime))
                ->findOrFail($id);

            $bookingsQuery = Booking::where('parking_space_id', $pricing->parking_space_id)
                ->whereNotIn('status', ['cancelled', 'close', 'completed']);

            if ($startDate) {
                $bookingsQuery->whereDate('start_time', '>=', $startDate);
            }
            if ($endDate) {
                $bookingsQuery->whereDate('end_time', '<=', $endDate);
            }
            if ($startTime) {
                $bookingsQuery->whereTime('booking_time_start', '<=', $startTime);
            }
            if ($endTime) {
                $bookingsQuery->whereTime('booking_time_end', '>=', $endTime);
            }

            $bookingCount = $bookingsQuery->get()->sum(fn($b) => $b->number_of_slot ?? 1);
            $pricing->booking_count = $bookingCount;
            $pricing->available_slots = max(0, $pricing->parkingSpace->total_slots - $bookingCount);

            if ($startTime && $endTime) {
                $dailyHours = Carbon::parse($startTime)->floatDiffInHours(Carbon::parse($endTime));
                if ($endDate) {
                    $totalHours = Carbon::parse("$startDate $startTime")->floatDiffInHours(Carbon::parse("$endDate $endTime"));
                } else {
                    $totalHours = $dailyHours;
                }

                $pricing->estimated_hours = round($totalHours, 2);
                $pricing->estimated_price = round($totalHours * $pricing->rate, 2);
            }

            $reviews = $pricing->parkingSpace->reviews;
            $pricing->review_count = $reviews->count();
            $pricing->average_rating = $reviews->count() > 0 ? round($reviews->avg('rating'), 2) : 0;
            $pricing->platform_fee = $this->platformFee() ?? [];
            return $pricing;
        } catch (Exception $e) {
            Log::error("UserParkingSpaceService::getHourlyPricingDetails - " . $e->getMessage());
            throw $e;
        }
    }

    private function platformFee()
    {
        $platformFee = PlatformSetting::where('status', 'active')->select('id', 'key', 'value')->get();
        return $platformFee;
    }
}