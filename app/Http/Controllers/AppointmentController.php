<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index()
    {
        return Appointment::query()->latest()->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_name' => 'required|string',
            'reason' => 'required|string',
            'help_needed' => 'required|string',
            'class' => 'required|string',
            'ta_selected' => 'required|string',
            'comments' => 'nullable|string',
        ]);

        return Appointment::create($data);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'student_name' => 'required|string',
            'reason' => 'required|string',
            'help_needed' => 'required|string',
            'class' => 'required|string',
            'ta_selected' => 'required|string',
            'comments' => 'nullable|string',
        ]);

        $appointment->update($data);

        return $appointment->refresh();
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return response()->json(['message' => 'Appointment deleted.']);
    }
}
