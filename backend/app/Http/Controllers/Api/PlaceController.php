<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Place;
use Illuminate\Http\Request;
use MatanYadaev\EloquentSpatial\Objects\Point;

class PlaceController extends Controller
{
    public function index()
    {
        return Place::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $place = Place::create([
            'name' => $request->name,
            'location' => new Point($request->lat, $request->lng),
        ]);

        return response()->json($place, 201);
    }

    public function show(Place $place)
    {
        return $place;
    }

    public function update(Request $request, Place $place)
    {
        $request->validate([
            'name' => 'sometimes|string',
            'lat' => 'sometimes|numeric',
            'lng' => 'sometimes|numeric',
        ]);

        if ($request->has('lat') && $request->has('lng')) {
            $place->location = new Point($request->lat, $request->lng);
        }

        if ($request->has('name')) {
            $place->name = $request->name;
        }

        $place->save();

        return response()->json($place);
    }

    public function destroy(Place $place)
    {
        $place->delete();
        return response()->noContent();
    }
}

