<?php

use App\Http\Controllers\DictController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SrsController;
use Illuminate\Support\Facades\Route;

// get pages
Route::get("/", [HomeController::class, "index"]);

// POST API for dictionary
Route::post("/examples/new", [DictController::class, "addExample"]);
Route::post("/definitions/new", [DictController::class, "addDefinition"]);
Route::post("/entries/new", [DictController::class, "addEntry"]);

// GET API for dictionary
Route::get("/entry/{word}", [DictController::class, "getEntry"]);
Route::get("/definitions/{entryId}", [DictController::class, "getDefinitions"]);
Route::get("/examples/{definitionId}", [DictController::class, "getExamples"]);

// API for spaced repetition system
Route::post("/cards/new", [SrsController::class, "addCard"]);
Route::get("/cards/due", [SrsController::class, "getDueCards"]);
Route::post("/cards/{id}", [SrsController::class, "updateCard"]);
