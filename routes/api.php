<?php



use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\LogoutController;
use App\Http\Controllers\API\Auth\RegisterController;
use App\Http\Controllers\API\Auth\ResetPasswordController;
use App\Http\Controllers\API\Auth\UserController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\V1\CMS\HomePageController;
use App\Http\Controllers\API\V1\User\StripePaymentController;
use App\Http\Controllers\API\V1\User\UserContactSupportController;
use App\Http\Controllers\API\V1\User\UserFaqController;

use Illuminate\Support\Facades\Route;



Route::group(['middleware' => 'guest:api'], function ($router) {
    //register
    Route::post('register', [RegisterController::class, 'register']);
    Route::post('/verify-email', [RegisterController::class, 'VerifyEmail']);
    Route::post('/resend-otp', [RegisterController::class, 'ResendOtp']);
    //login
    Route::post('login', [LoginController::class, 'login']);
    //forgot password
    Route::post('/forget-password', [ResetPasswordController::class, 'forgotPassword']);
    Route::post('/verify-otp', [ResetPasswordController::class, 'VerifyOTP']);
    Route::post('/reset-password', [ResetPasswordController::class, 'ResetPassword']);;
});

Route::group(['middleware' => 'auth:api'], function ($router) {
    Route::get('/refresh-token', [LoginController::class, 'refreshToken']);
    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/update-profile', [UserController::class, 'updateProfile']);
    Route::post('/update-password', [UserController::class, 'changePassword']);
    Route::delete('/delete-profile', [UserController::class, 'deleteProfile']);
});


// only for user
Route::group(['middleware' => ['auth:api', 'check_is_user']], function ($router) {});

//only for entertrainer
Route::group(['middleware' => ['auth:api', 'check_is_entertrainer']], function ($router) {});
//only for venue holder
Route::group(['middleware' => ['auth:api', 'check_is_venue_holder']], function ($router) {});


//payments webhook
Route::post('/payments-create/stripe', [StripePaymentController::class, 'handleWebhook']);

// only for user and host
Route::group(['middleware' => ['auth:api', 'check_is_user_or_entertainer_or_venue_holder']], function ($router) {
    //notification
    Route::get('/notification-settings', [UserController::class, 'getNotificationSettings']);
    Route::post('/notification-settings', [UserController::class, 'notificationSettings']);
    //contact support and faqs
    Route::get('/faqs', [UserFaqController::class, 'index']);
    Route::post('/contact-support-message/sent', [UserContactSupportController::class, 'store']);
    // --------- cms part --------------
    Route::get('/cms/social-link', [HomePageController::class, 'getSocialLinks']);
    Route::get('/cms/system-info', [HomePageController::class, 'getSystemInfo']);

    // dynamic page
    Route::get("dynamic-pages", [HomePageController::class, "getDynamicPages"]);
    Route::get("dynamic-pages/single/{slug}", [HomePageController::class, "showDaynamicPage"]);
});


//Category API
Route::get('/category', [CategoryController::class, 'index']);
Route::post('/category/create', [CategoryController::class, 'create']);
Route::get('/category/show/{id}', [CategoryController::class, 'show']);
