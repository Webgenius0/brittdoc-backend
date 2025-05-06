<?php

namespace App\Services\API\V1\User\StripePayment;

use App\Models\Payment;
use DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Refund;
use Stripe\Exception\ApiErrorException;

class StripePaymentService
{
    protected $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }


    public function refundPayment($payment_intent_id)
    {
        try {
            DB::beginTransaction();
            Stripe::setApiKey(config('services.stripe.secret'));
            
            // Fetch the payment from your DB
            $payment = Payment::where('payment_intent_id', $payment_intent_id)->first();

            if (!$payment || $payment->status !== 'success') {
                throw new Exception('Payment not found or already refunded/failed.');
            }

            // Refund the payment
            $refund = Refund::create([
                'payment_intent' => $payment->payment_intent_id,
            ]);
            Log::info('StripePaymentService::refundPayment :- Refund created: ' . json_encode($refund));

            // Update the payment record
            $payment->status = 'refunded';
            $payment->save();
            Log::info('StripePaymentService::refundPayment :- Payment updated: ' . json_encode($payment));
            DB::commit();
            return $refund;
        } catch (ApiErrorException $e) {
            DB::rollBack();
            Log::error('StripePaymentService::refundPayment:- ' . $e->getMessage());
            throw new Exception('Stripe API Error: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
            Log::error("StripePaymentService::refundPayment " . $e->getMessage());
            throw $e;
        }
    }

}