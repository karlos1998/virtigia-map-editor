<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    $mapModel = (new \App\Models\Map())->setConnectionName('retro');
    $maps = $mapModel->newQuery()->get();
    dd($maps);
});

