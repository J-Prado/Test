<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * Serve the Vue single-page app for any route not handled by the API.
 * Route::fallback() runs only after every other route (including /api/*) has
 * been checked, so defined API endpoints always take precedence.
 */
Route::fallback(function (Request $request) {
    if ($request->is('api/*')) {
        abort(404);
    }

    return view('app');
});
