<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Helpers\Helper;
use App\Mail\OtpMail;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Str;

class ResetPasswordController extends Controller
{
    /**
     * Forgot password
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);
        try {
            $email = $request->input('email');
            $otp = rand(1000, 9999);
            $user = User::where('email', $email)->first();

            if ($user) {
                Mail::to($email)->send(new OtpMail($otp, $user, 'Reset Your Password'));
                $user->update([
                    'otp' => $otp,
                    'otp_expires_at' => Carbon::now()->addMinutes(60),
                ]);
                return Helper::jsonResponse(true, 'OTP Code Sent Successfully Please Check Your Email.', 200);
            } else {
                return Helper::jsonErrorResponse('Invalid Email Address', 404);
            }
        } catch (Exception $e) {
            Log::error('ResetPasswordController::forgotPassword' . $e->getMessage());
            return Helper::jsonErrorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Verify OTP for password reset
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function VerifyOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:4',
        ]);
        try {
            $email = $request->input('email');
            $otp = $request->input('otp');
            $user = User::where('email', $email)->first();

            if (!$user) {
                return Helper::jsonErrorResponse('User not found', 404);
            }

            if (Carbon::parse($user->otp_expires_at)->isPast()) {
                return Helper::jsonErrorResponse('OTP has expired.', 400);
            }

            if ($user->otp !== $otp) {
                return Helper::jsonErrorResponse('Invalid OTP', 400);
            }
            $token = Str::random(60);
            $user->update([
                'otp' => null,
                'otp_expires_at' => null,
                'reset_password_token' => $token,
                'reset_password_token_expire_at' => Carbon::now()->addHour(),
            ]);
            return response()->json([
                'status' => true,
                'message' => 'OTP verified successfully.',
                'code' => 200,
                'token' => $token,
            ]);
        } catch (Exception $e) {
            Log::error('ResetPasswordController::VerifyOTP' . $e->getMessage());
            return Helper::jsonErrorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Resets the password for the user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ResetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);
        try {
            $email = $request->input('email');
            $newPassword = $request->input('password');

            $user = User::where('email', $email)->first();
            if (!$user) {
                return Helper::jsonErrorResponse('User not found', 404);
            }

            if (!empty($user->reset_password_token) && $user->reset_password_token === $request->token && $user->reset_password_token_expire_at >= Carbon::now()) {
                $user->update([
                    'password' => Hash::make($newPassword),
                    'reset_password_token' => null,
                    'reset_password_token_expire_at' => null,
                ]);

                return Helper::jsonResponse(true, 'Password reset successfully.', 200);
            } else {
                return Helper::jsonErrorResponse('Invalid Token', 419);
            }
        } catch (Exception $e) {
            Log::error('ResetPasswordController::ResetPassword' . $e->getMessage());
            return Helper::jsonErrorResponse($e->getMessage(), 500);
        }
    }

    
    //user password change
    public function PasswordUpdate(Request $request)
    {
        try {
            $request->validate([
                'old_password' => 'required',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = Auth::user();
            if (!Hash::check($request->old_password, $user->password)) {
                return Helper::jsonErrorResponse("Old password does not match", 404);
            }
            $user->password = Hash::make($request->password);
            $user->save();

            return Helper::jsonResponse(true, 'Password successfully updated', 200);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse($e->getMessage(), 500);
        }
    }
}
