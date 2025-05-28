<?php

namespace App\Http\Controllers\API\Subscription;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Planing;
use App\Models\Subscription;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{

    //user planing get
    public function lifetime(Request $request)
    {
        try {
            $data = Planing::where('billing_cycle', 'lifetime')->get();
            return Helper::jsonResponse(true, 'lifetime subsription get successfully ', 200, $data);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    //monthly Entertainer & VenueHolder planing get
    public function monthly(Request $request)
    {
        try {
            $data = Planing::where('billing_cycle', 'monthly')->first();
            return Helper::jsonResponse(true, 'subsription get successfully ', 200, $data);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    //booking subscription planing get 
    public function Subscription(Request $request)
    {
        $request->validate([
            'planing_id' => 'required|exists:planings,id',
        ]);

        $existingSubscription = Subscription::where('user_id', Auth::user()->id)
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhere('status', 'pending');
            })->first();

        if ($existingSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an subscription Booked.',
            ]);
        }

        $plan = Planing::findOrFail($request->planing_id);

        $startDate = Carbon::now();
        $endDate = Carbon::now()->addMonth();

        $subscription = Subscription::create([
            'planing_id' => $request->planing_id,
            'user_id' => Auth::user()->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'price' => $plan->price,
            'status' => 'pending',
        ]);
        return response()->json([
            'message' => 'Subscription booking successfully.',
            'subscription' => $subscription,
        ]);
    }



    public function SubscriptionPayment($id)
    {
        try {
            $subscription = Subscription::with(['user:id,name,avatar', 'planing:id,billing_cycle,image'])->select('id', 'price', 'user_id', 'planing_id', 'price')->find($id);
            if (!$subscription) {
                return response()->json([
                    'message' => 'Subscription ID not found.',
                ], 404);
            }
            $payment = Payment::create([
                'user_id' => Auth::id(),
                'subscription_id' => $subscription->id,
            ]);

            if ($payment) {
                Subscription::where('id', $subscription->id)
                    ->update(['status' => 'active']);
            }

            return response()->json([
                'message' => 'Payment successfully.',
                'payment' => $payment,
                'subscription' => $subscription,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
