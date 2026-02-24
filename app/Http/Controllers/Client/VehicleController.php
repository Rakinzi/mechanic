<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('client/MyVehicles', [
            'vehicles' => Vehicle::query()
                ->whereHas('client', fn ($query) => $query->where('user_id', request()->user()->id))
                ->with('jobCards')
                ->orderBy('registration_number')
                ->get(),
        ]);
    }
}
