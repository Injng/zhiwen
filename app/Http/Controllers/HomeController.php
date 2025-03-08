<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Returns the home page.
     * @return Response
     */
    public function index(): Response
    {
        return Inertia::render("Home", [
            "key" => config("services.openrouter.key"),
        ]);
    }
}
