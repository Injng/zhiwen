<?php

use App\Http\Controllers\DictController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get("/", [HomeController::class, "index"]);

Route::get("/entry/{word}", [DictController::class, "getEntry"]);
Route::get("/definitions/{entryId}", [DictController::class, "getDefinitions"]);
Route::get("/examples/{definitionId}", [DictController::class, "getExamples"]);
