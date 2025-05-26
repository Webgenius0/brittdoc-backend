<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use Exception;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->query('search', '');
            $per_page = $request->query('per_page', 100);

            // Build the base query
            $query = Category::where('status', 'active')
                ->select('id', 'name', 'image', 'type');

            // Add search conditions if search term exists
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%");
                });
            }

            // Execute pagination on the complete query
            $categories = $query->paginate($per_page);
            if (!empty($search) && $categories->isEmpty()) {
                return Helper::jsonResponse(false, 'No categories found for the given search.', 404);
            }

            return Helper::jsonResponse(true, 'Categories retrieved successfully.', 200, $categories, true);
        } catch (Exception $e) {
            Log::error("CategoryController::index" . $e->getMessage());
            return Helper::jsonErrorResponse('Failed to retrieve categories', 500);
        }
    }



    public function create(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name',
                'type' => 'required|in:venue_holder,entertainer',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240'
            ]);

            if ($request->hasFile('image')) {
                $validatedData['image'] = Helper::fileUpload($request->file('image'), 'category', time() . '_' . getFileName($request->file('image')));
            }
            $categories = Category::Create($validatedData);
            return response()->json([
                "success" => true,
                "message" => "Category created successfully",
                "categories" => $categories
            ]);
        } catch (Exception $e) {
            Log::error("CategoryController::store" . $e->getMessage());
            return response()->json([
                "success" => false,
                // "message" => "Category not create"
                "message" => $e->getMessage()
            ]);
        }
    }


    public function show(Request $request, string $id)
    {
        try {
            $category = Category::select('id', 'name', 'image', 'type')->find($id);

            if (!$category) {
                return Helper::jsonErrorResponse('Category not found', 404);
            }
            return Helper::jsonResponse(true, 'Category retrieved successfully.', 200, $category);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse('Failed to retrieve category', 500);
        }
    }

    public function trending()
    {
        $trendingCategories = Category::whereHas('events', function ($query) {
            $query->whereHas('bookings'); 
        })
            ->with([
                'events' => function ($query) {
                    $query->select('id', 'category_id', 'name', 'price', 'location', 'image')
                        ->whereHas('bookings'); 
                }
            ])
            ->withCount(['events as total_bookings' => function ($query) {
                $query->join('bookings', 'events.id', '=', 'bookings.event_id');
            }])
            ->orderByDesc('total_bookings')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Trending categories fetched successfully',
            'data' => $trendingCategories
        ]);
    }
}
