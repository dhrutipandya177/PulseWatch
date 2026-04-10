<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\Plan;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index()
    {
        $plans = Plan::where('is_active', true)->orderBy('price_cents')->get();

        return view('central.landing.index', compact('plans'));
    }

    public function features()
    {
        return view('central.landing.features');
    }

    public function pricing()
    {
        $plans = Plan::where('is_active', true)->orderBy('price_cents')->get();

        return view('central.landing.pricing', compact('plans'));
    }
}
