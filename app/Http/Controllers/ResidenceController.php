<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ResidenceController extends Controller
{
    public function index(): View
    {
        return view('site.residences');
    }

    public function show(): View
    {
        return view('site.residence-details');
    }
}
