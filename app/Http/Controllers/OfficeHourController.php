<?php

namespace App\Http\Controllers;

use App\Models\OfficeHour;
use Illuminate\Http\Request;
use Carbon\Carbon;

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

    public function analytics()
    {
        $officeHours = OfficeHour::all();
        $analytics = [];
        $id = 1;

        // Group by ISO week number using Collections (database-agnostic)
        $grouped = $officeHours->groupBy(function ($item) {
            return Carbon::parse($item->date)->format('W');
        });

        foreach ($grouped as $weekNum => $weekItems) {
            $taGroups = $weekItems->groupBy('ta_name');
            foreach ($taGroups as $taName => $taItems) {
                $analytics[] = [
                    'id' => $id++,
                    'week' => 'Week ' . ltrim($weekNum, '0'),
                    'week_num' => $weekNum, // Kept temporarily for accurate sorting
                    'ta_name' => $taName,
                    'attendance' => $taItems->sum('attendance_count'),
                ];
            }
        }

        // Sort analytics by week descending, then attendance descending
        $analytics = collect($analytics)
            ->sortByDesc('attendance')
            ->sortByDesc('week_num')
            ->values()
            ->map(function ($item) {
                unset($item['week_num']); // Remove the temporary sorting key
                return $item;
            })->all();

        // Calculate real-time active sessions
        $now = Carbon::now();
        $activeSessions = OfficeHour::whereDate('date', $now->toDateString())
            ->whereTime('time', '<=', $now->toTimeString())
            ->whereTime('end_time', '>=', $now->toTimeString())
            ->count();

        return response()->json([
            'analytics' => $analytics,
            'activeSessions' => $activeSessions
        ]);
    }
}
