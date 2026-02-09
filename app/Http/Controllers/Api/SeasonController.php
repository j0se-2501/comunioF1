<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Season;

class SeasonController extends Controller
{
    


    public function index()
    {
        return response()->json(
            Season::orderByDesc('year')->get()
        );
    }
}
