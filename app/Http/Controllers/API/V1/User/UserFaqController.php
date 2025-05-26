<?php

namespace App\Http\Controllers\API\V1\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\API\V1\User\Faqs\UserFaqService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
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
    public function list(Request $request)
    {
        try {
            $faq = Faq::all();

            return response()->json([
                'status' => true,
                'message' => 'FAQs fetched successfully',
                'data' => $faq,
            ]);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Failed to fetch faqs', 500);
        }
    }
}
