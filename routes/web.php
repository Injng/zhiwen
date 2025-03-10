<?php

use App\Http\Controllers\DictController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get("/", [HomeController::class, "index"]);

Route::post("/examples/new", [DictController::class, "addExample"]);
Route::post("/definitions/new", [DictController::class, "addDefinition"]);
Route::post("/entries/new", [DictController::class, "addEntry"]);

Route::get("/entry/{word}", [DictController::class, "getEntry"]);
Route::get("/definitions/{entryId}", [DictController::class, "getDefinitions"]);
Route::get("/examples/{definitionId}", [DictController::class, "getExamples"]);

