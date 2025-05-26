<?php

namespace App\Http\Controllers\Web\Backend\Subscription;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Planing;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class PlaningController extends Controller
{
    //user subscription
    public function userPlaning()
    {
        $data = Planing::where('billing_cycle', 'lifetime')->first();
        return view('backend.layouts.subscription.index', compact('data'));
    }

    public function createLifetimePlan(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:3000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'price' => 'required|numeric|min:1',
        ]);

        $data = Planing::where('billing_cycle', 'lifetime')->first();

        $image = $data ? $data->image : null;

        if ($request->hasFile('image')) {
            if ($data && $data->image && file_exists(public_path($data->image))) {
                Helper::fileDelete(public_path($data->image));
            }

            $image = Helper::fileUpload(
                $request->file('image'),
                'subscription',
                time() . '_' . getFileName($request->file('image'))
            );
        }

        Planing::updateOrCreate(
            ['billing_cycle' => 'lifetime'], // condition
            [
                'title' => $request->title,
                'description' => $request->description,
                'image' => $image,
                'price' => $request->price,
                'billing_cycle' => 'lifetime',
            ]
        );

        return redirect()->back()->with('success', 'Lifetime subscription plan created or updated successfully');
    }


    //monthly subscription
    public function MonthlyPlaning()
    {
        $data = Planing::where('billing_cycle', 'monthly')->first();
        return view('backend.layouts.subscription.Entertainer_VenueHolder', compact('data'));
    }

    public function MonthlyPlaningCreate(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:3000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'price' => 'required|numeric|min:1',
        ]);

        $data = Planing::where('billing_cycle', 'monthly')->first();

        $image = $data ? $data->image : null;

        if ($request->hasFile('image')) {
            if ($data && $data->image && file_exists(public_path($data->image))) {
                Helper::fileDelete(public_path($data->image));
            }

            $image = Helper::fileUpload(
                $request->file('image'),
                'subscription',
                time() . '_' . getFileName($request->file('image'))
            );
        }

        Planing::updateOrCreate(
            ['billing_cycle' => 'monthly'], // condition
            [
                'title' => $request->title,
                'description' => $request->description,
                'image' => $image,
                'price' => $request->price,
                'billing_cycle' => 'monthly',
            ]
        );

        return redirect()->back()->with('success', 'monthly subscription plan created or updated successfully');
    }
}
