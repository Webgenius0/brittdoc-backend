<?php



use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\LogoutController;
use App\Http\Controllers\API\Auth\RegisterController;
use App\Http\Controllers\API\Auth\ResetPasswordController;
use App\Http\Controllers\API\Auth\UserController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\Entertrainer\BookingController;
use App\Http\Controllers\API\Entertrainer\BookingDetailsController;
use App\Http\Controllers\API\Entertrainer\EventController;
use App\Http\Controllers\API\Payment\PaymentController;
use App\Http\Controllers\API\RatingController;
use App\Http\Controllers\API\User\HomeController;
use App\Http\Controllers\API\User\UserBookingController;
use App\Http\Controllers\API\V1\CMS\HomePageController;
use App\Http\Controllers\API\V1\User\StripePaymentController;
use App\Http\Controllers\API\V1\User\UserContactSupportController;
use App\Http\Controllers\API\V1\User\UserFaqController;
use App\Http\Controllers\API\Venue\VenueBookingController;
use App\Http\Controllers\API\Venue\VenueController;
use App\Http\Controllers\Web\Backend\PrivacyPolicyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
Route::group(['middleware' => ['auth:api', 'check_is_user']], function ($router) {
    //Category API
    Route::get('/category', [CategoryController::class, 'index']);
    Route::get('/category/show/{id}', [CategoryController::class, 'show']);
    Route::get('/trending-categories', [CategoryController::class, 'trending']);

    //Event
    Route::get('/event/user', [EventController::class, "index"]);
    Route::get('/event/booking/list/user', [BookingController::class, "index"]);

    //homepage Api show 
    Route::get('homePage/event/show', [HomeController::class, "index"]);
    Route::get('homePage/entertainer/show', [HomeController::class, "entertainer"]);
    Route::get('homePage/venue/show', [HomeController::class, "venue"]);
    //venue details show 
    Route::get('/homePage/venue/details/{id}', [HomeController::class, "venueDetails"]);
    //user section venue details
    Route::get('/user/venue/details/{id}', [VenueController::class, "VenueDetails"]);   //----------------------
    Route::post('/user/venue/booking/{id}', [VenueBookingController::class, "BookingVenue"]);
    Route::get('/venues/inprogress/upcomming', [VenueBookingController::class, "InprogressUpcomming"]);

    //user Rating
    Route::get('/user/rating/list', [RatingController::class, "index"]);
    Route::post('/user/venue/event/rating', [RatingController::class, "CreateRating"]);
    Route::get('/user/indivisual/rating/{id}', [RatingController::class, "indivisualvenue"]);
    //after pay screen 

    //payment 
    Route::post('/payments/{id}', [PaymentController::class, 'store']);         //venue and user both payment     
    Route::get('/payments/{id}', [PaymentController::class, 'AfterPayScreen']);
    Route::get('/Enertainer/payments/{id}', [PaymentController::class, 'AfterPayScreenEntertainer']);

    //event show category wise
    Route::get('/entertainer/show/category-wise', [EventController::class, "entertainer"]);
    Route::get('/entertainer/category/details/{id}', [EventController::class, "entertainerCategoryDetails"]);
    Route::post('/user/Enterianer/booking/{id}', [BookingController::class, "BookingEntertainer"]);
    Route::get('/user/event/inprogress/upcomming', [VenueBookingController::class, "InprogressUpcomming1"]);
});

//only for entertrainer
Route::group(['middleware' => ['auth:api', 'check_is_entertainer']], function ($router) {
    //Category API
    Route::get('/Entertrainer/category', [EventController::class, 'SubCategory']);
    Route::post('/Entertrainer/category/create', [EventController::class, 'SubCategoryCreate']);
    // //Entertrainer API Resources
    Route::get('/event', [EventController::class, "index"]);
    Route::post('/event/create', [EventController::class, "create"]);
    Route::get('/event/show/{id}', [EventController::class, "show"]);
    Route::get('/event/edit/{id}', [EventController::class, "edit"]);
    Route::post('/event/update/{id}', [EventController::class, "update"]);
    Route::delete('/event/delete/{id}', [EventController::class, "destroy"]);
    //------------------
    Route::get('/event/homePage', [BookingDetailsController::class, "CountTotal"]);
    Route::get('/Event/all/booking/complated', [BookingDetailsController::class, "bookingList"]);
    Route::get('/booking/Event/details/{id}', [BookingDetailsController::class, "EventBookingDetials"]);
    Route::get('/completed/Event/details/{id}', [BookingDetailsController::class, "EventCompletedDetails"]);
    Route::get('/event/inprogress/upcomming', [BookingDetailsController::class, "InprogressUpcommings"]);
});

//only for venue holder
Route::group(['middleware' => ['auth:api', 'check_is_venue_holder']], function ($router) {
    //Category API
    Route::get('/venue_holder/category', [VenueController::class, 'SubCategory']);
    Route::post('/venue_holder/category/create', [VenueController::class, 'SubCategoryCreate']);
    //venue API
    Route::get('/venue', [VenueController::class, "index"]);
    Route::post('/venue/create', [VenueController::class, "create"]);
    Route::get('/venue/show/{id}', [VenueController::class, "show"]);
    Route::get('/venue/edit/{id}', [VenueController::class, "edit"]);
    Route::post('/venue/update/{id}', [VenueController::class, "update"]);
    Route::delete('/venue/delete/{id}', [VenueController::class, "destroy"]);
    //venue booking Details
    Route::get('/venue/homePage', [VenueBookingController::class, "CountTotal"]);
    Route::get('/all/booking/complated', [VenueBookingController::class, "bookingList"]);
    //booking details
    Route::get('/booking/venue/details/{id}', [VenueBookingController::class, "VenueBookingDetials"]);
    Route::get('/completed/venue/details/{id}', [VenueBookingController::class, "venueCompletedDetails"]);
    Route::get('/venue/inprogress/upcomming', [UserBookingController::class, "InprogressUpcomming"]);
});


//payments webhook
Route::post('/payments-create/stripe', [StripePaymentController::class, 'handleWebhook']);

// only for user and host
Route::group(['middleware' => ['auth:api', 'check_is_user_or_entertainer_or_venue_holder']], function ($router) {
    //notification
    Route::get('/notification-settings', [UserController::class, 'getNotificationSettings']);
    Route::post('/notification-settings', [UserController::class, 'notificationSettings']);
    //contact support and faqs
    Route::get('/faqs', [UserFaqController::class, 'list']);
    Route::post('/contact-support-message/sent', [UserContactSupportController::class, 'store']);
    // --------- cms part --------------
    Route::get('/cms/social-link', [HomePageController::class, 'getSocialLinks']); //---ok
    Route::get('/cms/system-info', [HomePageController::class, 'getSystemInfo']);

    // dynamic page
    Route::get("dynamic-pages", [HomePageController::class, "getDynamicPages"]);
    Route::get("dynamic-pages/single/{slug}", [HomePageController::class, "showDaynamicPage"]);

    //Category API
    Route::get('/category', [CategoryController::class, 'index']);
    Route::get('/category/show/{id}', [CategoryController::class, 'show']);

    Route::get('/event/user', [EventController::class, "index"]);
    Route::get('/event/booking/list/user', [BookingController::class, "index"]);

    //common api endpoint 
    Route::post('/change-password', [ResetPasswordController::class, 'PasswordUpdate']);
    Route::get('/privacy-policy', [HomePageController::class, 'privacyList']);

    Route::get('/booking/status-update', [BookingController::class, 'status']);

});
