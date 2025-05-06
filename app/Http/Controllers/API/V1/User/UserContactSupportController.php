<?php

namespace App\Http\Controllers\API\V1\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Services\API\V1\User\ContactSupport\UserContactSupportService;
use Exception;
use Illuminate\Http\Request;
use Log;

class UserContactSupportController extends Controller
{
    protected $userContactSupportService;
    public function __construct(UserContactSupportService $userContactSupportService)
    {
        $this->userContactSupportService = $userContactSupportService;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string',
        ]);
        try {
            $this->userContactSupportService->store($validatedData);
            return Helper::jsonResponse(true, 'Message sent successfully', 200);
        } catch (Exception $e) {
            Log::error("UserContactSupportController::store" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to send message ' . $e->getMessage(), 500);
        }
    }

}
