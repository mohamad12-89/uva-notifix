<?php

namespace App\Http\Controllers;

use App\Models\OfficeHour;
use Illuminate\Http\Request;

class OfficeHourController extends Controller
{
    public function index()
    {
        return OfficeHour::query()->orderBy('date')->orderBy('time')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ta_name' => 'required|string',
            'date' => 'required|date',
            'time' => 'required',
            'end_time' => 'required',
            'location' => 'required|string',
        ]);

        $data['attendance_count'] = 0;

        return OfficeHour::create($data);
    }

    public function update(Request $request, OfficeHour $officeHour)
    {
        $data = $request->validate([
            'ta_name' => 'required|string',
            'date' => 'required|date',
            'time' => 'required',
            'end_time' => 'required',
            'location' => 'required|string',
        ]);

        $officeHour->update($data);

        return $officeHour->refresh();
    }

    public function destroy(OfficeHour $officeHour)
    {
        $officeHour->delete();

        return response()->json(['message' => 'Office hour deleted.']);
    }

    public function join(OfficeHour $officeHour)
    {
        $officeHour->increment('attendance_count');

        return $officeHour->refresh();
    }

    public function unjoin(OfficeHour $officeHour)
    {
        // Prevent negative counts if someone clicks "Unjoin" repeatedly
        if ($officeHour->attendance_count > 0) {
            $officeHour->decrement('attendance_count');
        }

        return $officeHour->refresh();
    }
}
