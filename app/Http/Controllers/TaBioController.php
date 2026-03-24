<?php

namespace App\Http\Controllers;

use App\Models\TaBio;
use Illuminate\Http\Request;

class TaBioController extends Controller
{
    public function index()
    {
        return TaBio::query()->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'year' => 'required|string',
            'major' => 'required|string',
            'email' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        return TaBio::create($data);
    }

    public function update(Request $request, TaBio $taBio)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'year' => 'required|string',
            'major' => 'required|string',
            'email' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $taBio->update($data);

        return $taBio->refresh();
    }

    public function destroy(TaBio $taBio)
    {
        $taBio->delete();

        return response()->json(['message' => 'TA bio deleted.']);
    }
}
