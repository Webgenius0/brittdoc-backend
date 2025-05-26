<?php

namespace App\Http\Controllers\API\Auth;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\License;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Notifications\UserRegistrationNotification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Fetch the authenticated user's details.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return Helper::jsonResponse(true, 'User details fetched successfully', 200, auth('api')->user());
    }

    /**
     * Update the authenticated user's profile information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:100',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'phone' => 'nullable|unique:users,phone,' . Auth::user()->id . '|numeric|max_digits:20',
            'password' => 'nullable|string|min:6|confirmed',
        ]);
        try {
            if (!empty($validatedData['password'])) {
                $validatedData['password'] = bcrypt($validatedData['password']);
            } else if (array_key_exists('password', $validatedData)) {
                unset($validatedData['password']);
            }

            $user = auth('api')->user();
            //upload avatar photo
            if ($request->hasFile('avatar')) {
                if (!empty($user->avatar)) {
                    Helper::fileDelete(public_path($user->getRawOriginal('avatar')));
                }
                $validatedData['avatar'] = Helper::fileUpload($request->file('avatar'), 'user/avatar', getFileName($request->file('avatar')));
            } else {
                $validatedData['avatar'] = $user->avatar;
            }
            //upload cover photo
            if ($request->hasFile('cover_photo')) {
                if (!empty($user->cover_photo)) {
                    Helper::fileDelete(public_path($user->getRawOriginal('cover_photo')));
                }
                $validatedData['cover_photo'] = Helper::fileUpload($request->file('cover_photo'), 'user/cover_photo', getFileName($request->file('cover_photo')));
            } else {
                $validatedData['cover_photo'] = $user->cover_photo;
            }

            $user->update($validatedData);
            return Helper::jsonResponse(true, 'Profile updated successfully', 200, $user);
        } catch (Exception $e) {
            Log::error('UserController::updateProfile' . $e->getMessage());
            return Helper::jsonErrorResponse('something went wrong', 403);
        }
    }

    /**
     * Update the authenticated user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $validatedData = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);
        try {
            $user = auth('api')->user();

            // Check if the current password matches the stored password
            if (!\Hash::check($validatedData['current_password'], $user->password)) {
                return Helper::jsonErrorResponse('Current password is incorrect', 422);
            }

            // Update the password
            $user->update([
                'password' => bcrypt($validatedData['new_password']),
            ]);
            return Helper::jsonResponse(true, 'Password updated successfully', 200);
        } catch (Exception $e) {
            Log::error('UserController::changePassword' . $e->getMessage());
            return Helper::jsonErrorResponse('something went wrong', 403);
        }
    }

    public function deleteProfile()
    {
        try {
            // Get the authenticated user
            $user = User::findOrFail(auth('api')->id());

            // If the user has an avatar, attempt to delete it
            if (!empty($user->avatar)) {
                // Ensure that the file exists before attempting to delete
                $avatarPath = public_path($user->avatar);
                if (file_exists($avatarPath)) {
                    Helper::fileDelete($avatarPath);
                }
            }

            // Soft delete the user (if using SoftDeletes)
            $user->delete();

            // Return success response
            return Helper::jsonResponse(true, 'Profile deleted successfully', 200);
        } catch (Exception $e) {
            Log::error('UserController::deleteProfile' . $e->getMessage());
            // Return error response
            return Helper::jsonErrorResponse('Something went wrong. Please try again later.', 403);
        }
    }


    // notification setting
    public function notificationSettings(Request $request)
    {
        // Validate the input data
        $validatedData = $request->validate([
            'general_notification' => 'nullable|boolean',
            'sound' => 'nullable|boolean',
            'vibration' => 'nullable|boolean',
            'special_offer' => 'nullable|boolean',
            'payment' => 'nullable|boolean',
            'app_update' => 'nullable|boolean',
            'other' => 'nullable|boolean'
        ]);

        try {
            // Get the authenticated user
            $user = auth('api')->user();

            // Fetch the current notification settings for the user
            $settings = NotificationSetting::firstOrNew(['user_id' => $user->id]);

            // Update only the fields that were provided in the request
            foreach ($validatedData as $key => $value) {
                if (!is_null($value)) {
                    $settings->{$key} = $value;
                }
            }

            // Save the updated settings
            $settings->save();

            // Return a success response
            return Helper::jsonResponse(true, 'Notification settings updated successfully', 200, $settings);
        } catch (Exception $e) {
            // Handle any errors and return a failure response
            return Helper::jsonErrorResponse('Something went wrong', 403);
        }
    }


    public function getNotificationSettings()
    {
        try {
            $user = Auth::user();
            $settings = NotificationSetting::where('user_id', $user->id)->first();

            if (!$settings) {
                return Helper::jsonErrorResponse('Notification settings not found', 404);
            }
            return Helper::jsonResponse(true, 'Notification settings fatch successfully', 200, $settings);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('something went wrong', 403);
        }
    }
}
