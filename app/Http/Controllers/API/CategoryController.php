<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use Exception;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Get 'per_page' from the request or default to 1000
            $per_page = $request->has('per_page') ? $request->per_page : 1000;

            $categories = Category::where('status', 'active')->select('id', 'title', 'thumbnail', 'description')->paginate($per_page);
            return Helper::jsonResponse(true, 'Categories retrieved successfully.', 200, $categories, true);
        } catch (\Exception $e) {
            return Helper::jsonErrorResponse('Failed to retrieve categories', 403);
        }
    }
    public function show(Request $request, string $id)
    {
        try {
            $category = Category::select('id', 'title', 'thumbnail', 'description')
                ->with(['subCategories:id,category_id,title,thumbnail'])
                ->find($id);
            if (!$category) {
                return Helper::jsonErrorResponse('Category not found', 404);
            }

            return Helper::jsonResponse(true, 'Category retrieved successfully.', 200, $category);
        } catch (\Exception $e) {
            return Helper::jsonErrorResponse('Failed to retrieve category', 500);
        }
    }
    public function getSubCategories(Request $request)
    {
        try {
            // Get 'per_page' from the request or default to 1000
            $per_page = $request->has('per_page') ? $request->per_page : 1000;
            $sub_categories = SubCategory::select('id', 'title', 'thumbnail', 'description')
                ->paginate($per_page);

            return Helper::jsonResponse(true, 'Category retrieved successfully.', 200, $sub_categories);
        } catch (\Exception $e) {
            return Helper::jsonErrorResponse('Failed to retrieve category', 500);
        }
    }
    public function getSubCategory(Request $request, string $id)
    {
        try {
            $category = SubCategory::where('status', 'active')->select('id', 'title', 'thumbnail', 'description')
                ->find($id);
            if (!$category) {
                return Helper::jsonErrorResponse('Category not found', 404);
            }

            return Helper::jsonResponse(true, 'Category retrieved successfully.', 200, $category);
        } catch (\Exception $e) {
            return Helper::jsonErrorResponse('Failed to retrieve category', 500);
        }
    }
    public function searchingCategory(Request $request)
{
    try {
        // Get the search keyword from the request
        $search = $request->input('search', '');

        // Query for categories
        $categoryQuery = Category::where('status', 'active')
            ->select('id', 'title', 'thumbnail', 'description')
            ->where(function ($query) use ($search) {
                // If a search term is provided, search in category title or description
                if ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                }
            });

        // Query for subcategories
        $subCategoryQuery = SubCategory::where('status', 'active')
            ->select('id', 'title', 'thumbnail', 'description')
            ->where(function ($query) use ($search) {
                // If a search term is provided, search in subcategory title or description
                if ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                }
            });

        // Fetch the results
        $categories = $categoryQuery->get();
        $subCategories = $subCategoryQuery->get();

        // Check if no data is found
        if ($categories->isEmpty() && $subCategories->isEmpty()) {
            return Helper::jsonResponse(false, 'No categories or subcategories found for the given search.', 404);
        }

        // Return the combined results
        return Helper::jsonResponse(true, 'Search data fetched successfully.', 200, [
            'categories' => $categories,
            'subCategories' => $subCategories
        ]);
    } catch (Exception $e) {
        // Return error response if an exception occurs
        return Helper::jsonErrorResponse('Something went wrong.', 403);
    }
}

}
