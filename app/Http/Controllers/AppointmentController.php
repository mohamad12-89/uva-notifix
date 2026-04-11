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
            'preferred_date' => 'required|date_format:Y-m-d',
            'preferred_time' => 'required|date_format:H:i',
            'comments' => 'nullable|string',
        ]);

        $data['status'] = 'pending';

        return Appointment::create($data);
    }

    public function update(Request $request, Appointment $appointment)
    {
        // TA quick status update (accept / decline) without resubmitting full form
        if ($request->has('status') && ! $request->filled('student_name')) {
            $data = $request->validate([
                'status' => 'required|in:pending,accepted,declined',
            ]);
            $appointment->update($data);

            return $appointment->refresh();
        }

        $data = $request->validate([
            'student_name' => 'required|string',
            'reason' => 'required|string',
            'help_needed' => 'required|string',
            'class' => 'required|string',
            'ta_selected' => 'required|string',
            'preferred_date' => 'required|date_format:Y-m-d',
            'preferred_time' => 'required|date_format:H:i',
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
