<?php

namespace App\Http\Controllers\Web\Backend;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Models\SubCategory;
use Exception;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{
    /**
     * Display a listing of the units with DataTables support.
     */
    public function index(Request $request)
    {
        $userType = $request->userType;
        // dd($userType);

        if ($request->ajax()) {
            $data = User::where('role', $userType)->latest();
            return DataTables::of($data)
                ->addIndexColumn()

                ->addColumn('avatar', function ($data) {
                    return '<img src="' . asset($data->avatar ?? 'backend/admin/assets/images/avatar_defult.png') . '" class="wh-40 rounded-3" alt="no image found">';
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
                        <a type="button" href="javascript:void(0)"
                                class="ps-0 border-0 bg-transparent lh-1 position-relative top-2"
                                data-bs-toggle="modal" data-bs-target="#ShowUser" onclick="viewModel(' . $data->id . ')" ><i class="material-symbols-outlined fs-16 text-primary">visibility</i>
                            </a>
                        <button class="ps-0 border-0 bg-transparent lh-1 position-relative top-2" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Delete" onclick="deleteRecord(event,' . $data->id . ')">
                        <i class="material-symbols-outlined fs-16 text-danger">delete</i>
                        </button>
             
                </div>';
                })
                ->rawColumns(['avatar', 'status', 'action'])
                ->make(true);
        }
        return view("backend.layouts.user.index", compact("userType"));
    }
    /**
     * Show the form for creating a new data.
     */
    public function create()
    {
        // return view("backend.layouts.category.create");
        flash()->warning('not found this page');
        return back();
    }
    /**
     * Store a newly created data in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        // dd($validatedData);
        try {
            if ($request->hasFile('thumbnail')) {
                $validatedData['thumbnail'] = Helper::fileUpload($request->file('thumbnail'), 'category', time() . '_' . getFileName($request->file('thumbnail')));
            }
            SubCategory::Create($validatedData);
            return response()->json([
                "success" => true,
                "message" => "Category created successfully"
            ]);
        } catch (Exception $e) {
            Log::error("CategoryController::store" . $e->getMessage());
            return response()->json([
                "success" => false,
                "message" => "Category not create"
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        flash()->warning('not found this page');
        return back();

    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = User::with('services','bookings')->findOrFail($id);
        return view("backend.layouts.user.show", compact("data"));
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // dd($request->all());
        $validatedData = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        try {
            $data = SubCategory::findOrFail($id);
            if ($request->hasFile('thumbnail')) {
                if ($data && $data->thumbnail && file_exists(public_path($data->thumbnail))) {
                    Helper::fileDelete(public_path($data->thumbnail));
                }
                $validatedData['thumbnail'] = Helper::fileUpload($request->file('thumbnail'), 'SubCategory', time() . '_' . getFileName($request->file('thumbnail')));
            }
            $data = SubCategory::findOrFail($id);
            $data->update($validatedData);

            return response()->json([
                "success" => true,
                "message" => "Category Updated Successfully"
            ]);
        } catch (Exception $e) {
            Log::error("CategoryController::update" . $e->getMessage());
            return response()->json([
                "success" => false,
                "message" => "Category not Update"
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = User::findOrFail($id);
        // check if the category exists
        if (empty($data)) {
            return response()->json([
                "success" => false,
                "message" => "Item not found."
            ], 404);
        }
        // delete the category
        if (!empty($data->image)) {
            Helper::fileDelete(public_path($data->image));
        }
        $data->delete();

        return response()->json([
            "success" => true,
            "message" => "Item deleted successfully."
        ]);
    }

    /**
     * Change the status of the specified resource from storage.
     */
    public function status(Request $request, $id)
    {
        $data = User::find($id);

        // check if the category exists
        if (empty($data)) {
            return response()->json([
                "success" => false,
                "message" => "Item not found."
            ], 404);
        }

        // toggle status of the category
        if ($data->status == 'active') {
            $data->status = 'inactive';
        } else {
            $data->status = 'active';
        }

        // save the changes
        $data->save();
        return response()->json([
            'success' => true,
            'message' => 'Item status changed successfully.'
        ]);
    }
}
