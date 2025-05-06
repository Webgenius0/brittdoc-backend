<?php

use App\Http\Controllers\Web\Backend\CategoryController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('admin.dashboard');
    }
    return view('auth.login');
})->name('home');

require __DIR__ . '/auth.php';

Route::resource('/category', CategoryController::class);
Route::post('/category/status/{id}', [CategoryController::class,'status' ])->name('category.status');
