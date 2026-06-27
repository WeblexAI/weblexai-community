<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use Illuminate\Http\RedirectResponse;

class OverviewController extends Controller
{
    public function index(): RedirectResponse
    {
        return to_route('projects.index');
    }
}

