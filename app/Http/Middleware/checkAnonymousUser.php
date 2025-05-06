<?php

namespace App\Http\Middleware;

use App\Models\AnonymousUser;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckAnonymousUser
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Retrieve fingerprint from request headers
        $fingerprint = $request->header('Fingerprint');
        $ipAddress = $request->ip();

        if (!$fingerprint) {
            return response()->json(['message' => 'Fingerprint header missing'], 400);
        }

        // Check if the anonymous user exists
        $anonymousUser = AnonymousUser::where('fingerprint', $fingerprint)->first();
       
        if (!$anonymousUser) {
            // Create a new user by calling the store method
            $storeResponse = $this->createAnonymousUser($ipAddress, $fingerprint);
            if ($storeResponse) {
                // Log::info('Anonymous user created successfully');
                return $next($request);
            } else {
                return response()->json(['message' => 'Failed to create anonymous user'], 500);
            }
        }

        return $next($request);
    }

    /**
     * Create an anonymous user by making an internal request
     */
    private function createAnonymousUser($ip, $fingerprint)
    {
        try {
            
            $request = Request::create('api/anonymous-users/store', 'POST', [
                'ip_address' => $ip,
                'fingerprint' => $fingerprint
            ]);
            $response = app()->handle($request);
            return true;
        } catch (Exception $e) {
            Log::error("Failed to create anonymous user: " . $e->getMessage());
            return ['success' => false];
        }
    }
}

