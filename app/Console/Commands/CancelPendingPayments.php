<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Carbon\Carbon;
use Exception;

class CancelPendingPayments extends Command
{
    protected $signature = 'payments:cancel-pending';
    protected $description = 'Cancel pending payment intents after 10 minutes';

    public function handle()
    {
        Stripe::setApiKey(config('services.stripe.key'));

        // Find bookings that are unpaid and created more than 10 minutes ago
        $pendingBookings = Booking::whereDoesntHave('payment', function ($query) {
            $query->where('status', 'paid');
        })
        ->where('created_at', '<=', Carbon::now()->subMinutes(10))
        ->whereNotIn('status', ['cancelled', 'completed', 'close'])
        ->get();

        foreach ($pendingBookings as $booking) {
            try {
                // Assume you have stored PaymentIntent ID in a metadata or field (if not, adjust accordingly)
                $payment = $booking->payment;

                if ($payment && $payment->payment_intent_id) {
                    $paymentIntent = PaymentIntent::retrieve($payment->payment_intent_id);

                    if ($paymentIntent->status === 'requires_payment_method' || $paymentIntent->status === 'requires_confirmation') {
                        $paymentIntent->cancel();

                        // Optionally update booking/payment status
                        $booking->update(['status' => 'cancelled']);
                        $payment->update(['status' => 'cancelled']);

                        $this->info('Cancelled payment intent for booking: ' . $booking->id);
                    }
                }
            } catch (Exception $e) {
                Log::error('Failed to cancel payment intent for booking ' . $booking->id . ': ' . $e->getMessage());
            }
        }

        return 0;
    }
}
