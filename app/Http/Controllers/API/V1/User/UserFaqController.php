<?php

namespace App\Http\Controllers\API\V1\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Services\API\V1\User\Faqs\UserFaqService;
use Exception;
use Illuminate\Http\Request;
use Log;
use Pest\Console\Help;

class UserFaqController extends Controller
{

    protected $userFaqService;
    public function __construct(UserFaqService $userFaqService)
    {
        $this->userFaqService = $userFaqService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $faqs = $this->userFaqService->index($request);
            return Helper::jsonResponse(true, 'Faqs fetched successfully', 200, $faqs, true);
        } catch (Exception $e) {
            Log::error("UserFaqController::index" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to fetch faqs', 500);
        }
    }
}
