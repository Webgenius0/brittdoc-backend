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
use App\Http\Controllers\API\Message\MessageController;
use App\Http\Controllers\API\Payment\PaymentController;
use App\Http\Controllers\API\RatingController;
use App\Http\Controllers\API\Subscription\SubscriptionController;
use App\Http\Controllers\API\User\FilterController;
use App\Http\Controllers\API\User\HomeController;
use App\Http\Controllers\API\User\UserBookingController;
use App\Http\Controllers\API\V1\CMS\HomePageController;
use App\Http\Controllers\API\V1\Firebase\FirebaseTokenController;
use App\Http\Controllers\API\V1\User\StripePaymentController;
use App\Http\Controllers\API\V1\User\UserContactSupportController;
use App\Http\Controllers\API\V1\User\UserFaqController;
use App\Http\Controllers\API\Venue\VenueBookingController;
use App\Http\Controllers\API\Venue\VenueController;
use App\Http\Controllers\Web\Backend\PrivacyPolicyController;
use App\Models\Message;
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
    //subscription
    Route::get('/user/subscription-planning', [SubscriptionController::class, 'lifetime']);
    Route::post('/user/subscription-booking', [SubscriptionController::class, 'Subscription']);
    Route::post('/user/subscription-payment/{id}', [SubscriptionController::class, 'SubscriptionPayment']);

    //Category API
    Route::get('/category', [CategoryController::class, 'index']);
    Route::get('/category/show/{id}', [CategoryController::class, 'show']);
    Route::get('/trending-categories', [CategoryController::class, 'trending']);

    //Event
    Route::get('/event/user', [EventController::class, "index"]);
    //homepage Api show 
    Route::get('/homepage/event-show', [HomeController::class, "index"]);
    Route::get('/homePage/entertainer/show', [HomeController::class, "entertainer"]);
    Route::get('/homepage/venue-show', [HomeController::class, "venue"]);
    Route::get('/home-page/search', [HomeController::class, "searchHomepage"]);

    //venue details show 
    Route::get('/venue-list', [VenueController::class, "index"]);
    Route::get('/homePage/venue/details/{id}', [HomeController::class, "venueDetails"]);
    //user section venue details
    Route::get('/user/venue/details/{id}', [VenueController::class, "VenueDetails"]);
    Route::post('/user/venue/booking/{id}', [VenueBookingController::class, "BookingVenue"]);
    Route::get('/venues/inprogress/upcomming', [VenueBookingController::class, "InprogressUpcomming"]);

    //user Rating
    Route::get('/user/rating/list', [RatingController::class, "index"]);
    Route::post('/user/venue/event/rating', [RatingController::class, "CreateRating"]);
    Route::get('/user/indivisual/rating/{id}', [RatingController::class, "indivisualvenue"]);
    //payment 
    Route::post('/payments/{id}', [PaymentController::class, 'store']);         //venue and user both payment     
    Route::get('/user-payments/screen/{id}', [PaymentController::class, 'AfterPayScreen']);
    Route::get('/user-payments/entertainer/{id}', [PaymentController::class, 'AfterPayScreenEntertainer']);

    //event show category wise
    Route::get('/entertainer/show/category-wise', [EventController::class, "entertainer"]);
    Route::get('/entertainer/category/details/{id}', [EventController::class, "entertainerCategoryDetails"]);
    Route::post('/user-booking/entertainer/{id}', [BookingController::class, "BookingEntertainer"]);
    // Route::get('/user/event/inprogress/upcomming', [VenueBookingController::class, "InprogressUpcomming1"]);
    Route::get('/user/all/booking', [BookingController::class, "allBookingList"]);
    Route::get('/user/single/booking/details/{id}', [BookingController::class, "BookingDetials"]);

    // fillter user section/homePage/event/show
    Route::get('/filter/entertainer', [FilterController::class, 'filterEntertainer']);
    Route::get('/filter/venue', [FilterController::class, 'filterVenue']);
    //Nearby Search 
    Route::get('/venue-nearby/search', [FilterController::class, 'NearbySearchVenue']);
    Route::get('/event-nearby/search', [FilterController::class, 'NearbySearchEvent']);
    //location
    Route::get('/Entertainer/filter/location', [FilterController::class, 'locationEntertainer']);
    Route::get('/Venue/filter/location', [FilterController::class, 'locationVenueHolder']);
    Route::get('/entertrainers/category', [EventController::class, 'SubCategory']);
    Route::get('/venue_holders/category', [VenueController::class, 'SubCategory']);
    Route::get("/user/accept-request/new-message/{id}", [BookingController::class, "acceptOrRequest"]);    //-----------------------------------------
});


//only for entertrainer
Route::group(['middleware' => ['auth:api', 'check_is_entertainer']], function ($router) {
    //subscription
    Route::get('/subscription-planning', [SubscriptionController::class, 'monthly']);
    Route::post('/entertainer/subscription-booking', [SubscriptionController::class, 'Subscription']);
    Route::post('/entertainer/subscription-payment/{id}', [SubscriptionController::class, 'SubscriptionPayment']);

    //Category API
    Route::get('/entertrainer/category', [EventController::class, 'SubCategory']);
    Route::post('/entertrainer/category-create', [EventController::class, 'SubCategoryCreate']);
    // //Entertrainer API Resources
    Route::get('/event', [EventController::class, "index"]);
    Route::post('/event/create', [EventController::class, "create"]);
    Route::get('/event/show/{id}', [EventController::class, "show"]);
    Route::get('/event/edit/{id}', [EventController::class, "edit"]);
    Route::post('/event/update/{id}', [EventController::class, "update"]);
    Route::delete('/event/delete/{id}', [EventController::class, "destroy"]);
    //------------------
    Route::get('/event/home-page', [BookingDetailsController::class, "CountTotal"]);
    Route::get('/entertainer/all-event', [BookingDetailsController::class, "bookingList"]);
    Route::get('/single/event-details/{id}', [BookingDetailsController::class, "EventBookingDetials"]);
    Route::get('/completed/Event/details/{id}', [BookingDetailsController::class, "EventCompletedDetails"]);
    Route::get('/event/inprogress/upcomming', [BookingDetailsController::class, "InprogressUpcommings"]);
    Route::get("/booking/accept-cancelled/{id}", [BookingController::class, "acceptOrCancel"]);     //------------------------------------
    Route::get("/custom-booking/withdraw-message/{id}", [BookingController::class, "withdrawOfferE"]);  //--------------------------------------

});

//only for venue holder
Route::group(['middleware' => ['auth:api', 'check_is_venue_holder']], function ($router) {
    //subscription
    Route::get('/venue-holder/subscription-planning', [SubscriptionController::class, 'monthly']);
    Route::post('/venue-holder/subscription-booking', [SubscriptionController::class, 'Subscription']);
    Route::post('/venue-holder/subscription-payment/{id}', [SubscriptionController::class, 'SubscriptionPayment']);

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
    Route::get('/venue-count/home-page', [VenueBookingController::class, "CountTotal"]);
    Route::get('/all/booking/complated', [VenueBookingController::class, "bookingList"]);
    //booking details
    Route::get('/booking/venue/details/{id}', [VenueBookingController::class, "VenueBookingDetials"]);
    Route::get('/completed/venue/details/{id}', [VenueBookingController::class, "venueCompletedDetails"]);
    Route::get('/venue/inprogress/upcomming', [UserBookingController::class, "InprogressUpcomming"]);
    //custom offer 
    Route::post('/venue/customer/offer', [VenueController::class, "CustomerOffer"]);
    Route::get('/venue/customer/booked/{id}', [VenueController::class, "StatusCustom"]);
    Route::get("/bookings/accept-cancelled/{id}", [BookingController::class, "acceptOrCancelV"]); //------------------------------------
    Route::get("/custom-bookings/withdraw-message/{id}", [BookingController::class, "withdrawOfferV"]); //--------------------------------------

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
    Route::get('/cms/social-link', [HomePageController::class, 'getSocialLinks']);
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

    //message 
    Route::post('/messages/send', [MessageController::class, 'sendMessage']);
    Route::get('/get/messages', [MessageController::class, 'getMessage']);
    // Route::get('/messages/group', [MessageController::class, 'GroupMessage']);      //convension 2-3-1 sender and reciver only
    Route::get('/messages/all-chats', [MessageController::class, 'chatList']); //user id all message show
    Route::get('/chats/restricted-words', [MessageController::class, 'RestrictedWords']);

    //custom offer  
    Route::post('/event/customer/offer', [EventController::class, "CustomerOffer"]);
    Route::get('/event/customer/booked/{id}', [EventController::class, "StatusCustom"]);
});

// Firebase Token Module
Route::post("firebase/token/add", [FirebaseTokenController::class, "store"]);
Route::post("firebase/token/get", [FirebaseTokenController::class, "getToken"]);
Route::post("firebase/token/delete", [FirebaseTokenController::class, "deleteToken"]);
