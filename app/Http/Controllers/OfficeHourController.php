<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
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
            'end_time' => 'required|after:time',
            'location' => 'required|string',
        ]);

        $data['attendance_count'] = 0;

        $officeHour = OfficeHour::create($data);

        $date = Carbon::parse($officeHour->date)->startOfDay();
        if ($date->greaterThanOrEqualTo(Carbon::today()) && $date->lessThanOrEqualTo(Carbon::today()->addDays(7))) {
            Announcement::create([
                'title' => "New Office Hour: {$officeHour->ta_name}",
                'body' => "A new office hour session has been scheduled.\n\nDetails:\nDate: {$officeHour->date}\nTime: {$officeHour->time} - {$officeHour->end_time}\nLocation: {$officeHour->location}",
                'author_name' => 'System Auto-Notice',
                'office_hour_id' => $officeHour->id,
            ]);
        }

        return $officeHour;
    }

    public function update(Request $request, OfficeHour $officeHour)
    {
        $data = $request->validate([
            'ta_name' => 'required|string',
            'date' => 'required|date',
            'time' => 'required',
            'end_time' => 'required|after:time',
            'location' => 'required|string',
        ]);

        $officeHour->update($data);

        if ($officeHour->wasChanged()) {
            Announcement::create([
                'title' => "Office Hour Updated: {$officeHour->ta_name}",
                'body' => "An office hour session has been updated.\n\nNew Details:\nDate: {$officeHour->date}\nTime: {$officeHour->time} - {$officeHour->end_time}\nLocation: {$officeHour->location}",
                'author_name' => 'System Auto-Notice',
                'office_hour_id' => $officeHour->id,
            ]);
        }

        return $officeHour->refresh();
    }

    public function destroy(OfficeHour $officeHour)
    {
        $taName = $officeHour->ta_name;
        $date = $officeHour->date;
        $time = $officeHour->time;
        $endTime = $officeHour->end_time;
        $location = $officeHour->location;

        $officeHour->delete();

        Announcement::create([
            'title' => "Office Hour Canceled: {$taName}",
            'body' => "An office hour session has been canceled.\n\nCanceled Session Details:\nDate: {$date}\nTime: {$time} - {$endTime}\nLocation: {$location}",
            'author_name' => 'System Auto-Notice',
        ]);

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
