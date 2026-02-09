<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;

class DriverController extends Controller
{
    


    public function index()
    {
        $drivers = Driver::with('team')
            ->orderBy('name')
            ->get();

        return response()->json($drivers);
    }
}
