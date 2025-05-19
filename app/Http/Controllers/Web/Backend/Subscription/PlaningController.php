<?php

namespace App\Http\Controllers\Web\Backend\Subscription;

use App\Http\Controllers\Controller;
use App\Models\Planing;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlaningController extends Controller
{
    //planing create
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'image' => 'nullable|string',
                'description' => 'nullable|string',
                'price' => 'required|numeric',
                'billing_cycle' => 'required|in:lifetime,monthly',
                'status' => 'in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $planing = Planing::create($request->all());
            return response()->json($planing, 201);
        } catch (Exception $e) {
            return response()->json([
                $e->getMessage()
            ]);
        }
    }
}
