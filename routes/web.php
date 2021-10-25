<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'ip' => $request->ip(),
        'date' => now(),
        'laravel' => Illuminate\Foundation\Application::VERSION,
        'php' => PHP_VERSION,
    ]);
});

Route::get('/event/{id}', 'Requests\Events@eventView');
