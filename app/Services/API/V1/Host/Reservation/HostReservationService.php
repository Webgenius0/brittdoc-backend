<?php

namespace App\Services\API\V1\Host\Reservation;

use App\Enums\NotificationType;
use App\Http\Controllers\API\V1\User\StripePaymentController;
use App\Models\Booking;
use App\Models\ParkingSpace;
use App\Services\API\V1\User\NotificationOrMail\NotificationOrMailService;
use App\Services\API\V1\User\StripePayment\StripePaymentService;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class HostReservationService
{
    protected $user;
    protected $stripePaymentService;
    protected $notificationOrMailService;

    public function __construct(StripePaymentService $stripePaymentService, NotificationOrMailService $notificationOrMailService)
    {
        $this->user = Auth::user();
        $this->stripePaymentService = $stripePaymentService;
        $this->notificationOrMailService = $notificationOrMailService;
    }
    /**
     * Fetch all resources.
     *
     * @return mixed
     */
    public function index($request)
    {
        try {
            $per_page = $request->per_page ?? 25;
            $status = $request->status ?? null; //'pending', 'confirmed', 'active', 'cancelled', 'close', 'completed'
            $userParkingSpaces = ParkingSpace::where('user_id', $this->user->id)->pluck('id')->toArray();
            $reservation = Booking::whereIn('parking_space_id', $userParkingSpaces)
                ->whereHas('payment', function ($query) {
                    $query->whereIn('status', ['success', 'refunded']);
                })
                ->when($status, function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->with(['user:id,name,avatar', 'parkingSpace:id,address,title'])
                ->select('id', 'unique_id', 'parking_space_id', 'user_id', 'start_time', 'end_time', 'total_price', 'estimated_hours', 'estimated_price', 'status', 'created_at')
                ->latest()
                ->paginate($per_page);
            // filter added time for reservation
            $reservation->getCollection()->transform(function ($booking) {
                return $this->applyTimeStatus($booking);
            });

            return $reservation;
        } catch (Exception $e) {
            Log::error("HostReservationService::index" . $e->getMessage());
            throw $e;
        }
    }


    /**
     * Display a specific resource.
     *
     * @param string $unique_id
     * @return mixed
     */
    public function show(string $unique_id)
    {
        try {
            $booking = Booking::where('unique_id', $unique_id)
                ->whereHas('payment', function ($query) {
                    $query->whereIn('status', ['success', 'refunded']);
                })
                ->with(['payment:id,booking_id,status', 'user:id,name,avatar,email,phone', 'parkingSpace:id,title,address,description,latitude,longitude', 'parkingSpace.spotDetails:id,parking_space_id,icon,details', 'vehicleDetail:id,registration_number,type,make,model,license_plate_number_eng,license_plate_number_ara'])
                ->select('id', 'unique_id', 'parking_space_id', 'vehicle_details_id', 'user_id', 'start_time', 'end_time', 'total_price', 'estimated_hours', 'estimated_price', 'status', 'created_at')
                ->firstOrFail();
            // filter added time for reservation
            $booking = $this->applyTimeStatus($booking);

            return $booking;

        } catch (Exception $e) {
            Log::error("HostReservationService::show - " . $e->getMessage());
            throw $e;
        }
    }

    public function acceptReservation(string $unique_id)
    {
        try {
            DB::beginTransaction();
            $reservation = Booking::where('unique_id', $unique_id)
                ->where('status', 'pending')
                ->whereHas('payment', function ($query) {
                    $query->where('status', 'success');
                })
                ->first();
            if (!$reservation) {
                throw new ModelNotFoundException('Reservation not found or already accepted.');
            }
            $reservation->status = 'confirmed';
            $reservation->save();
            // dd($reservation->toArray());
            $qrData = <<<EOT
                        📌 Booking Confirmation

                        🅿️ Parking Space: {$reservation->parkingSpace->title}
                        📍 Address: {$reservation->parkingSpace->address}
                        🌐 Location: https://www.google.com/maps?q={$reservation->parkingSpace->latitude},{$reservation->parkingSpace->longitude}

                        🕒 Time:
                        {$reservation->start_time->format('M d, Y h:i A')}
                        to
                        {$reservation->end_time->format('M d, Y h:i A')}

                        📝 Description:
                        Your booking has been confirmed.
                        EOT;
            // Send notification to user
            $this->notificationOrMailService->sendNotificationAndMail(
                [$reservation->user],
                'Your reservation has been accepted. We will send you the QR code to access the parking space before the booking starts. Please make sure to arrive on time as the space is reserved for you. If you have any questions, please contact us at suppor',
                NotificationType::BookingConfirmationNotification,
                'Reservation Accepted',
                $qrData
            );
            DB::commit();
            return $reservation;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("HostReservationService::acceptReservation failed", [
                'unique_id' => $unique_id,
                'user_id' => optional($this->user)->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    public function cancelReservation(string $unique_id)
    {
        try {
            DB::beginTransaction();
            $reservation = Booking::where('unique_id', $unique_id)
                ->where('status', 'pending')
                ->whereHas('payment', function ($query) {
                    $query->where('status', 'success');
                })
                ->with(['payment:id,booking_id,payment_intent_id,status'])
                ->first();
            if (!$reservation) {
                throw new ModelNotFoundException('Reservation not found or already accepted.');
            }
            $reservation->status = 'cancelled';
            $reservation->save();

            // Refund payment if applicable
            $this->stripePaymentService->refundPayment($reservation->payment->payment_intent_id);

            DB::commit();
            return $reservation;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("HostReservationService::cancelReservation failed", [
                'unique_id' => $unique_id,
                'user_id' => optional($this->user)->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }


    private function applyTimeStatus($booking)
    {
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
    }

    public function hostDashboardData()
    {
        try {

            $userParkingSpaces = ParkingSpace::where('user_id', $this->user->id)->pluck('id')->toArray();

            $totalReservations = Booking::whereIn('parking_space_id', $userParkingSpaces)
                ->whereHas('payment', function ($query) {
                    $query->whereIn('status', ['success', 'refunded']);
                })
                ->groupBy('status')
                ->pluck(DB::raw('count(*) as count'), 'status');

            //total earnings
            $totalEarnings = Booking::whereIn('parking_space_id', $userParkingSpaces)
                ->whereHas('payment', function ($query) {
                    $query->whereIn('status', ['success', 'refunded']);
                })
                ->where('status', 'completed')
                ->sum('total_price');

            $data = [
                'booking_pending' => $totalReservations['pending'] ?? 0,
                'booking_active' => $totalReservations['active'] ?? 0,
                'booking_confirmed' => $totalReservations['confirmed'] ?? 0,
                'booking_cancelled' => $totalReservations['cancelled'] ?? 0,
                'booking_close' => $totalReservations['close'] ?? 0,
                'booking_completed' => $totalReservations['completed'] ?? 0,
                'total_earnings' => $totalEarnings ?? 0,
            ];
            return $data;
        } catch (Exception $e) {
            Log::error("HostReservationService::hostDashboardData" . $e->getMessage());
            throw $e;
        }
    }
    public function hostDashboardTransactions($request)
    {
        try {
            $per_page = $request->per_page ?? 25;
            $transaction_search = $request->transaction_search ?? null;
            $userParkingSpaces = ParkingSpace::where('user_id', $this->user->id)->pluck('id')->toArray();
            $transactions = Booking::whereIn('parking_space_id', $userParkingSpaces)
                ->whereHas('payment', function ($query) {
                    $query->whereIn('status', ['success', 'refunded']);
                })
                ->when($transaction_search, function ($query) use ($transaction_search) {
                    $query->whereHas('payment', function ($query) use ($transaction_search) {
                        $query->where('transaction_number', 'like', '%' . $transaction_search . '%');
                    });
                })
                ->with(['payment:id,booking_id,transaction_number,amount,status', 'user:id,name,avatar', 'parkingSpace:id,address,title'])
                ->select('id', 'unique_id', 'parking_space_id', 'user_id', 'start_time', 'end_time', 'total_price', 'estimated_hours', 'estimated_price', 'status', 'created_at')
                ->latest()
                ->paginate($per_page);

            return $transactions;
        } catch (Exception $e) {
            Log::error("HostReservationService::hostDashboardTransactions" . $e->getMessage());
            throw $e;
        }
    }

}