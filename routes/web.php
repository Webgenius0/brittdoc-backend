<?php

use App\Http\Controllers\Web\Backend\CategoryController;
use App\Http\Controllers\Web\Backend\PrivacyPolicyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('admin.dashboard');
    }
    return view('auth.login');
})->name('home');

require __DIR__ . '/auth.php';

Route::resource('/category', CategoryController::class);
Route::post('/category/status/{id}', [CategoryController::class, 'status'])->name('category.status');

//privacy policy
Route::resource('/privacy', PrivacyPolicyController::class);


Route::get('/run-command', function () {
    return view('components.backend.command_runner');
})->name('run.command.form');

Route::post('/run-command', function (Request $request) {
    // Validate the command input
    $request->validate([
        'command' => 'required|string',
    ]);

    // Get the command from the input
    $command = $request->input('command');

    // Strip "php artisan" from the command if present
    $cleanedCommand = trim(str_replace(['php artisan', 'artisan'], '', $command));

    try {
        // Run the cleaned Artisan command
        Artisan::call($cleanedCommand);
        $output = Artisan::output();
    } catch (\Exception $e) {
        // Handle any errors from the Artisan command
        $output = "Error running command: " . $e->getMessage();
    }

    // Return the output to the view
    return redirect()->route('run.command.form')->with('output', $output);
})->name('run.command');