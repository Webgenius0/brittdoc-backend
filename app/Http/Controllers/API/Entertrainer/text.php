<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bookings = Booking::with(['user', 'event'])
            ->orderBy('booking_date', 'desc')
            ->get();

        return view('bookings.index', compact('bookings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::all();
        $events = Event::all();
        return view('bookings.create', compact('users', 'events'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'event_id' => 'required|exists:events,id',
            'category' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'booking_date' => 'required|date',
            'booking_start_time' => 'required|date_format:H:i',
            'booking_end_time' => 'required|date_format:H:i|after:booking_start_time',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Calculate financial details
        $start = Carbon::parse($request->booking_start_time);
        $end = Carbon::parse($request->booking_end_time);
        $hours = $end->diffInHours($start);

        $platformRate = $hours * 100; // $100 per hour
        $feePercentage = 17;
        $platformFeeAmount = ($platformRate * $feePercentage) / 100;
        $netAmount = $platformRate - $platformFeeAmount;

        // Calculate remaining time until event starts
        $bookingDateTime = Carbon::parse($request->booking_date . ' ' . $request->booking_start_time);
        $remainingTime = now()->diff($bookingDateTime);

        $booking = Booking::create([
            'user_id' => $request->user_id,
            'event_id' => $request->event_id,
            'category' => $request->category,
            'location' => $request->location,
            'booking_date' => $request->booking_date,
            'booking_start_time' => $request->booking_start_time,
            'booking_end_time' => $request->booking_end_time,
            'remaining_time' => $remainingTime->format('%H:%I:%S'),
            'platform_rate' => $platformRate,
            'fee_percentage' => $feePercentage,
            'platform_fee_amount' => $platformFeeAmount,
            'net_amount' => $netAmount,
            'status' => 'upcoming'
        ]);

        return redirect()->route('bookings.show', $booking->id)
            ->with('success', 'Booking created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking)
    {
        return view('bookings.show', compact('booking'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Booking $booking)
    {
        $users = User::all();
        $events = Event::all();
        return view('bookings.edit', compact('booking', 'users', 'events'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Booking $booking)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'event_id' => 'required|exists:events,id',
            'category' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'booking_date' => 'required|date',
            'booking_start_time' => 'required|date_format:H:i',
            'booking_end_time' => 'required|date_format:H:i|after:booking_start_time',
            'status' => 'required|in:upcoming,in-progress,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Recalculate financial details if times changed
        if (
            $request->booking_start_time != $booking->booking_start_time ||
            $request->booking_end_time != $booking->booking_end_time
        ) {

            $start = Carbon::parse($request->booking_start_time);
            $end = Carbon::parse($request->booking_end_time);
            $hours = $end->diffInHours($start);

            $platformRate = $hours * 100;
            $feePercentage = 17;
            $platformFeeAmount = ($platformRate * $feePercentage) / 100;
            $netAmount = $platformRate - $platformFeeAmount;
        } else {
            // Keep existing values
            $platformRate = $booking->platform_rate;
            $platformFeeAmount = $booking->platform_fee_amount;
            $netAmount = $booking->net_amount;
        }

        // Update remaining time
        $bookingDateTime = Carbon::parse($request->booking_date . ' ' . $request->booking_start_time);
        $remainingTime = now()->diff($bookingDateTime);

        $booking->update([
            'user_id' => $request->user_id,
            'event_id' => $request->event_id,
            'category' => $request->category,
            'location' => $request->location,
            'booking_date' => $request->booking_date,
            'booking_start_time' => $request->booking_start_time,
            'booking_end_time' => $request->booking_end_time,
            'remaining_time' => $remainingTime->format('%H:%I:%S'),
            'platform_rate' => $platformRate,
            'fee_percentage' => $feePercentage,
            'platform_fee_amount' => $platformFeeAmount,
            'net_amount' => $netAmount,
            'status' => $request->status
        ]);

        return redirect()->route('bookings.show', $booking->id)
            ->with('success', 'Booking updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking)
    {
        $booking->delete();

        return redirect()->route('bookings.index')
            ->with('success', 'Booking deleted successfully.');
    }

    /**
     * Display bookings for a specific user
     */
    public function userBookings($userId)
    {
        $bookings = Booking::where('user_id', $userId)
            ->with('event')
            ->orderBy('booking_date', 'desc')
            ->get();

        return view('bookings.user', compact('bookings'));
    }

    /**
     * Update booking status
     */
    public function updateStatus(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'status' => 'required|in:upcoming,in-progress,completed,cancelled'
        ]);

        $booking->update(['status' => $validated['status']]);

        return back()->with('success', 'Booking status updated.');
    }

    /**
     * Calculate and update remaining time for all upcoming bookings
     */
    public function updateRemainingTimes()
    {
        $bookings = Booking::where('status', 'upcoming')->get();

        foreach ($bookings as $booking) {
            $bookingDateTime = Carbon::parse($booking->booking_date . ' ' . $booking->booking_start_time);
            $remainingTime = now()->diff($bookingDateTime);

            $booking->update([
                'remaining_time' => $remainingTime->format('%H:%I:%S')
            ]);
        }

        return response()->json(['message' => 'Remaining times updated for all upcoming bookings.']);
    }
}
