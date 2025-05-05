<?php

namespace App\Http\Controllers\API\V1\User;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\UserPromoCode;
use App\Services\API\V1\User\StripePayment\StripePaymentService;
use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Str;
use Stripe\PaymentIntent;
use App\Helpers\Helper;
use App\Models\Booking;
use Stripe\Exception\ApiErrorException;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;
use UnexpectedValueException;
use Stripe\Exception\SignatureVerificationException;
use function GuzzleHttp\json_encode;
use Stripe\Refund;

class StripePaymentController extends Controller
{
    protected $user;
    protected $stripePaymentService;

    public function __construct(StripePaymentService $stripePaymentService)
    {
        $this->user = Auth::user();
        $this->stripePaymentService = $stripePaymentService;
    }

    public function createPaymentIntent(Request $request)
    {
        $validateData = $request->validate([
            'booking_unique_id' => 'required|exists:bookings,unique_id',
            'promo_code' => 'nullable|exists:promo_codes,code',
            // 'amount'     => 'required|numeric',
        ]);
        try {
            DB::beginTransaction();
            // user id
            $user_id = Auth::id();
            $promo_code_value = 0;

            if (!empty($validateData['promo_code'])) {
                $promo_code_value = $this->checkPromoCodeBalance($validateData['promo_code']);
            }
            // dd($promo_code_value);
            // Check if the order is found
            $booking = Booking::where('user_id', $this->user->id)
                ->where('unique_id', $validateData['booking_unique_id'])
                ->whereNotIn('status', ['cancelled', 'completed', 'close'])
                // ->whereDoesntHave('payment', function ($query) {
                //     $query->whereIn('status', ['success', 'cancelled', 'closed', 'refunded']);
                // })
                ->first();
            // dd($booking->toArray());
            if (!$booking) {
                return Helper::jsonErrorResponse('Bookings not found', 404);
            }

            // Check if the order already has a completed payment
            if ($booking->payment && $booking->payment->isPaid()) {
                return Helper::jsonErrorResponse('This bokking has already been paid.', 400);
            }


            // dd(config('services.stripe.key'));
            if (!config('services.stripe.secret')) {
                return Helper::jsonErrorResponse('Payment facilities are not configured. Please contact support.', 404);
            }
            // check this hotel set payment configarations
            $stripe_secret = Stripe::setApiKey(config('services.stripe.secret'));
            //calculation
            $amount = ($booking->total_price - $promo_code_value ?? 0) * 100; // total amount in cents
            $transactionId = substr('TXT-' . (string) Str::uuid(), 0, 15);

            // Create a payment intent with the calculated amount and metadata
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'usd',
                'metadata' => [
                    'user_id' => $booking->user_id,
                    'unique_id' => $validateData['booking_unique_id'],
                    'booking_id' => $booking->id,
                    'transaction_id' => $transactionId,
                    'payment_method' => 'online',
                ],
            ]);

            $payment = Payment::create([
                'booking_id' => $booking->id,
                'user_id' => $this->user->id,
                'transaction_id' => null,
                'promo_code_id' => null,
                'transaction_number' => $transactionId,
                'payment_method' => 'online',
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'amount' => $amount / 100,
                'promo_code' => $validateData['promo_code'] ?? null,
                'status' => 'pending',
            ]);
            DB::commit();
            return Helper::jsonResponse(true, 'Payment Intent created successfully.', 200, [
                'client_secret' => $paymentIntent->client_secret,
            ]);
        } catch (ApiErrorException $e) {
            DB::rollBack();
            return Helper::jsonResponse(false, 'Stripe API error: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
            DB::rollBack();
            return Helper::jsonResponse(false, 'General error: ' . $e->getMessage(), 500);
        }
    }

    public function handleWebhook(Request $request): JsonResponse
    {
        Log::info('StripePaymentController::handleWebhook:- ' . json_encode($request->all()));

        $stripe_webhook_secret = Stripe::setApiKey(config('services.stripe.webhook_secret'));

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');
        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            Log::info('Stripe webhook event: ' . json_encode($event));
        } catch (UnexpectedValueException $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage());
            return Helper::jsonResponse(false, $e->getMessage(), 400, []);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature error: ' . $e->getMessage());
            return Helper::jsonResponse(false, $e->getMessage(), 400, []);
        }

        //? Handle the event based on its type
        try {

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    Log::info('payment_intent.succeeded');
                    $this->handlePaymentSuccess($event->data->object);
                    return Helper::jsonResponse(true, 'Payment successful', 200, []);

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailure($event->data->object);
                    return Helper::jsonResponse(true, 'Payment failed', 200, []);

                default:
                    return Helper::jsonResponse(true, 'Unhandled event type', 200, []);
            }
        } catch (Exception $e) {
            return Helper::jsonResponse(false, $e->getMessage(), 500, []);
        }
    }

    protected function handlePaymentSuccess($paymentIntent): void
    {
        Log::info('StripePaymentController::handlePaymentSuccess:- ' . json_encode($paymentIntent));
        $payment = Payment::where('booking_id', $paymentIntent->metadata->booking_id)->first();
        if (!$payment) {
            Log::info('StripePaymentController::handlePaymentSuccess:- Payment not found');
            return;
        }
        Log::info('payment data: ' . json_encode($payment));
        $payment->status = 'success';
        $payment->save();

        //? Assign promo code to user
        if ($payment->promo_code && $payment->user_id) {
            $this->assignPromoCodeToUser($payment->user_id, $payment->promo_code);
        }

        Log::info("StripePaymentController::handlePaymentSuccess:- Payment success: " . $payment);
        // send notification
        // $user = User::find($paymentIntent->metadata->user_id);
    }

    protected function handlePaymentFailure($paymentIntent): void
    {
        Log::info("StripePaymentController::handlePaymentFailure:- Payment failed: " . json_encode($paymentIntent));
        //* Record the failure payment in the database
        $payment = Payment::where('booking_id', $paymentIntent->metadata->booking_id)
            ->where('transaction_id', $paymentIntent->metadata->transaction_id)->first();

        $payment->status = 'failed';
        $payment->save();
        Log::info("StripePaymentController::handlePaymentFailure:- Payment failed: " . $payment);

        // $guest = User::find($paymentIntent->metadata->user_id);
        // $notificationData = [
        //     'message' => 'Payment is unsuccessful',
        //     // 'url' => $paymentIntent->metadata->order_id ?? '',
        //     'url' => '',
        //     'type' => NotificationType::PAYMENT,
        //     'thumbnail' => ''
        // ];
        // // notify guest 
        // $guest->notify(new GuestRequestNotification($notificationData));
        // $firebaseTokens = FirebaseTokens::where('user_id', $guest->id)->get();

        // // Now you have a collection, you can check if the collection is not empty and then get the tokens
        // if (!empty($firebaseTokens)) {

        //     $notifyData = [
        //         'title' => 'Payment is unsuccessful',
        //         'body' => $paymentIntent->metadata->order_id
        //     ];
        //     foreach ($firebaseTokens as $tokens) {
        //         if (!empty($tokens->token)) {
        //             $token = $tokens->token; // Pluck tokens into an array
        //             // Send notifications using the token array
        //             Helper::sendNotifyMobile($token, $notifyData);
        //         } else {
        //             Log::warning('Token is missing for user: ' . $guest->id);
        //         }
        //     }
        // } else {
        //     Log::warning('No Firebase tokens found for this user.');
        // }
    }
    private function checkPromoCodeBalance($code)
    {
        $promo_code = PromoCode::withCount('userPromoCodes')
            ->where('code', $code)
            ->where('uses_limit', '>', function ($query) {
                $query->selectRaw('count(*)')
                    ->from('user_promo_codes')
                    ->whereColumn('user_promo_codes.promo_code_id', 'promo_codes.id');
            })
            ->whereDoesntHave('userPromoCodes', function ($query) {
                $query->where('user_id', $this->user->id);
            })
            ->first();
        // dd($promo_code->toArray());
        if (!$promo_code) {
            Log::error('StripePaymentController::checkPromoCodeBalance:- Promo code not found or max use limit reached. for user ' . $this->user->id);
            throw new Exception('Promo code not found or max use limit reached.');
        }

        return $promo_code->value;
    }

    private function assignPromoCodeToUser($userId, $code)
    {
        try {
            $promo_code = PromoCode::where('code', $code)->first();
            // dd($promo_code);
            if (!$promo_code) {
                Log::error('StripePaymentController::assignPromoCodeToUser:- Promo code not found. code: ' . $code . ' for user ' . $userId);
                // return Helper::jsonErrorResponse('Promo code not found.', 404);
                return true;
            }
            $userPromoCode = UserPromoCode::create([
                'user_id' => $userId,
                'promo_code_id' => $promo_code->id,
                'start_time' => now(),
                'end_time' => now(),
            ]);
            Log::info('StripePaymentController::assignPromoCodeToUser:- Promo code assigned to user. code: ' . $code . ' for user ' . $userId);
            return $userPromoCode;
        } catch (Exception $e) {
            Log::error('StripePaymentController::assignPromoCodeToUser:- Promo code not found. code: ' . $code);
            return Helper::jsonErrorResponse('Promo code not found.', 404);
        }
    }

    public function refundPayment($payment_intent_id)
    {
        try {
            $this->stripePaymentService->refundPayment($payment_intent_id);
            return Helper::jsonResponse(true, 'Payment refunded successfully.', 200);
        } catch (Exception $e) {
            Log::error("StripePaymentController::refundPayment" . $e->getMessage());
            return Helper::jsonErrorResponse($e->getMessage(), 400);
        }
    }
    
    // public function refundPayment($payment_intent_id)
    // {
    //     try {
    //         DB::beginTransaction();
    //         Stripe::setApiKey(config('services.stripe.secret'));

    //         // Fetch the payment from your DB
    //         $payment = Payment::where('payment_intent_id', $payment_intent_id)->first();

    //         if (!$payment || $payment->status !== 'success') {
    //             return Helper::jsonErrorResponse('Payment not found or already refunded/failed.', 404);
    //         }

    //         // Refund the payment
    //         $refund = Refund::create([
    //             'payment_intent' => $payment->payment_intent_id,
    //         ]);
    //         Log::info('StripePaymentController::refundPayment:- Refund created: ' . json_encode($refund));

    //         // Update the payment record
    //         $payment->status = 'refunded';
    //         $payment->save();
    //         Log::info('StripePaymentController::refundPayment:- Payment updated: ' . json_encode($payment));
    //         DB::commit();
    //         return Helper::jsonResponse(true, 'Payment refunded successfully.', 200, [
    //             'refund_id' => $refund->id,
    //             'status' => $refund->status,
    //         ]);
    //     } catch (ApiErrorException $e) {
    //         DB::rollBack();
    //         Log::error('StripePaymentController::refundPayment:- ' . $e->getMessage());
    //         return Helper::jsonErrorResponse('Stripe API Error: ' . $e->getMessage(), 500);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('StripePaymentController::refundPayment:- ' . $e->getMessage());
    //         return Helper::jsonErrorResponse('Error: ' . $e->getMessage(), 500);
    //     }
    // }

}

