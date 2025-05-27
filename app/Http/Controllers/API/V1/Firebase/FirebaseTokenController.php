<?php

namespace App\Http\Controllers\API\V1\Firebase;

use App\Http\Controllers\Controller;
use App\Models\FirebaseToken;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FirebaseTokenController extends Controller
{
    /**
     * News Serve For Frontend
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Please login again.',
                'code' => 401,
            ], 401);
        }
        //first delete existing token
        $firebase = FirebaseToken::where('user_id', $user->id)->where('device_id', $request->device_id);
        if ($firebase) {
            $firebase->delete();
        }

        try {
            $data = new FirebaseToken();
            $data->user_id = auth('api')->user()->id;
            $data->token = $request->token;
            $data->device_id = $request->device_id;
            $data->save();

            return response()->json([
                'status' => true,
                'message' => 'Token saved successfully',
                'data' => $data,
                'code' => 200,
            ], 200);
        } catch (Exception $e) {
            Log::error('FirebaseTokenController::store' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'No records found',
                'code' => 418,
                'data' => [],
            ], 418);
        }
    }

    /**
     * Get Single Record
     * @param $token, $device_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }
        try {
            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized. Please login again.',
                    'code' => 401,
                ], 401);
            }

            $device_id = $request->device_id;
            $data = FirebaseToken::where('user_id', $user->id)->where('device_id', $device_id)->first();
            if (!$data) {
                return response()->json([
                    'status' => false,
                    'message' => 'No records found',
                    'code' => 404,
                    'data' => [],
                ], 404);
            }
            return response()->json([
                'status' => true,
                'message' => 'Token fetched successfully',
                'data' => $data,
                'code' => 200,
            ], 200);
        } catch (Exception $e) {
            Log::error('FirebaseTokenController::getToken' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'No records found',
                'code' => 418,
                'data' => [],
            ], 418);
        }
    }

    /**
     * Delete Token Single Record
     * @param $token, $device_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }
        try {
            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized. Please login again.',
                    'code' => 401,
                ], 401);
            }
            //first delete existing token
            $firebaseToken = FirebaseToken::where('user_id', $user->id)->where('device_id', $request->device_id);
            if ($firebaseToken) {
                $firebaseToken->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'Token deleted successfully',
                    'code' => 200,
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'No records found',
                    'code' => 404,
                ], 404);
            }
        } catch (Exception $e) {
            Log::error('FirebaseTokenController::deleteToken' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'No records found',
                'code' => 418,
            ], 418);
        }
    }
}
