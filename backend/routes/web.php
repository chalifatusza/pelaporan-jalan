<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/uploads/{filename}', function ($filename) {
    // Prevent directory traversal attacks
    $filename = basename($filename);
    
    $paths = [
        base_path('../uploads/' . $filename),
        '/tmp/' . $filename,
        public_path('uploads/' . $filename)
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            return response()->file($path);
        }
    }

    abort(404);
});

