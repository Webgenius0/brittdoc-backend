<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\Privacy;
use Illuminate\Http\Request;

class PrivacyPolicyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data=Privacy::first();
        return view('backend.layouts.privacy.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //validate request
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:3000',
        ]);
        //create or update privacy policy
        $privacy = Privacy::first();
        if ($privacy) {
            $privacy->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);
        } else {
            Privacy::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);
        }
        return redirect()->back()->with('success', 'Privacy Policy created successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
