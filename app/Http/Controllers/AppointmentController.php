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
            'preferred_date' => 'required|date_format:Y-m-d',
            'preferred_time' => 'required|date_format:H:i',
            'comments' => 'nullable|string',
        ]);

        $data['status'] = 'pending';
        $data['ta_selected'] = 'Unassigned';

        return Appointment::create($data);
    }

    public function update(Request $request, Appointment $appointment)
    {
        // TA quick status update (accept / decline) without resubmitting full form
        if ($request->has('status') && ! $request->filled('student_name')) {
            $data = $request->validate([
                'status' => 'required|in:pending,accepted,declined',
                'assigned_to_name' => 'nullable|string',
                'assigned_to_email' => 'nullable|email',
                'assigned_to_role' => 'nullable|in:ta,professor',
            ]);

            if ($data['status'] === 'accepted') {
                $appointment->assigned_to_name = $data['assigned_to_name'] ?? $appointment->assigned_to_name;
                $appointment->assigned_to_email = $data['assigned_to_email'] ?? $appointment->assigned_to_email;
                $appointment->assigned_to_role = $data['assigned_to_role'] ?? $appointment->assigned_to_role;
                if (! empty($appointment->assigned_to_name)) {
                    $appointment->ta_selected = $appointment->assigned_to_name;
                }
            } else {
                $appointment->assigned_to_name = null;
                $appointment->assigned_to_email = null;
                $appointment->assigned_to_role = null;
                $appointment->ta_selected = 'Unassigned';
            }
            $appointment->update($data);

            return $appointment->refresh();
        }

        $data = $request->validate([
            'student_name' => 'required|string',
            'reason' => 'required|string',
            'help_needed' => 'required|string',
            'class' => 'required|string',
            'preferred_date' => 'required|date_format:Y-m-d',
            'preferred_time' => 'required|date_format:H:i',
            'comments' => 'nullable|string',
        ]);
        $data['ta_selected'] = $appointment->status === 'accepted'
            ? ($appointment->assigned_to_name ?? 'Unassigned')
            : 'Unassigned';

        $appointment->update($data);

        return $appointment->refresh();
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return response()->json(['message' => 'Appointment deleted.']);
    }
}
