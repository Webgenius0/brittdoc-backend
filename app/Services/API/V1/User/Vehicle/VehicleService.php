<?php

namespace App\Services\API\V1\User\Vehicle;

use App\Helpers\Helper;
use App\Models\VehicleDetail;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;

class VehicleService
{
    use AuthorizesRequests;
    protected $user;


    public function __construct()
    {
        $this->user = Auth::user();
    }
    /**
     * Fetch all resources.
     *
     * @return mixed
     */
    public function index($per_page)
    {
        try {
            $vehicles = VehicleDetail::where('user_id', $this->user->id)->paginate($per_page);
            return $vehicles;
        } catch (Exception $e) {
            Log::error("VehicleService::index" . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Store a new resource.
     *
     * @param array $validatedData
     * @return mixed
     */
    public function store(array $validatedData)
    {
        try {
            $validatedData['user_id'] = $this->user->id;
            $vehicle = VehicleDetail::create($validatedData);
            return $vehicle;
        } catch (Exception $e) {
            Log::error("VehicleService::store" . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Display a specific resource.
     *
     * @param int $id
     * @return mixed
     */
    public function show(int $id)
    {
        try {
            $vehicle = VehicleDetail::findOrFail($id);
            return $vehicle;
        } catch (Exception $e) {
            Log::error("VehicleService::show" . $e->getMessage());
            throw $e;
        }
    }


    /**
     * Update a specific resource.
     *
     * @param int $id
     * @param array $validatedData
     * @return mixed
     */
    public function update(int $id, array $validatedData)
    {
        try {
            $vehicle = VehicleDetail::where('id', $id)->where('user_id', $this->user->id)->first();
            if (!$vehicle) {
                throw new Exception('Vehicle not found or you do not own this vehicle', 404);
            }
            // update the vehicle
            $vehicle->update($validatedData);
        } catch (Exception $e) {
            Log::error( "VehicleService::update" . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a specific resource.
     *
     * @param int $id
     * @return mixed
     */
    public function destroy(int $id)
    {
        try {
            $vehicle = VehicleDetail::where('id', $id)->where('user_id', $this->user->id)->first();
            if (!$vehicle) {
                throw new Exception('Vehicle not found or you do not own this vehicle', 404);
            }
            // delete the vehicle
            $vehicle->delete();
        } catch (Exception $e) {
            Log::error("VehicleService::destroy" . $e->getMessage());
            throw $e;
        }
    }

}