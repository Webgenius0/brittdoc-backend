<?php

namespace App\Http\Controllers\Web\Backend\Subscription;

use App\Http\Controllers\Controller;
use App\Models\Planing;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class PlaningController extends Controller
{

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Planing::latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('image', function ($data) {
                    return '<img src="' . asset($data->image) . '" class="wh-40 rounded-3">';
                })
                ->addColumn('status', function ($data) {
                    $status = '<div class="form-check form-switch">';
                    $status .= '<input onclick="changeStatus(event,' . $data->id . ')" type="checkbox" class="form-check-input" style="border-radius: 25rem;width:40px"' . $data->id . '" name="status"';

                    if ($data->status == "active") {
                        $status .= ' checked';
                    }

                    $status .= '>';
                    $status .= '</div>';

                    return $status;
                })
                ->addColumn('action', function ($data) {
                    return '<div class="action-wrapper">
                       <a type="button" href="' . route('planning.edit', $data->id) . '"
                                class="ps-0 border-0 bg-transparent lh-1 position-relative top-2"
                                 ><i class="material-symbols-outlined fs-16 text-body">edit</i>
                            </a>
                        <button class="ps-0 border-0 bg-transparent lh-1 position-relative top-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Delete" onclick="deleteRecord(event,' . $data->id . ')">
                        <i class="material-symbols-outlined fs-16 text-danger">delete</i>
                        </button>
             
                </div>';
                })
                ->rawColumns(['image', 'status', 'action'])
                ->make(true);
        }
        return view("backend.layouts.subscriptionPlan.index");
    }

    public function create()
    {
        return view("backend.layouts.subscriptionPlan.create");
    }
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
            ]);
// dd($validator);
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
