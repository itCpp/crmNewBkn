<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
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

Route::middleware('user.token')
    ->any('/broadcasting/auth', function (Request $request) {
        return Broadcast::auth($request);
    });

Route::get('/event/{id}', 'Requests\Events@eventView');
